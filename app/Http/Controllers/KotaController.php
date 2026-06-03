<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\KotaRequest;
use App\Models\Kota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;

class KotaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->string('q')->toString();

        $data = Kota::query()
            ->when($q, fn ($query) => $query->where('nama_kota', 'like', "%{$q}%"))
            ->orderBy('nama_kota')
            ->get();

        return response()->json(['data' => $data]);
    }

    public function store(KotaRequest $request): RedirectResponse
    {
        Kota::create($request->validated());

        return back()->with('success', 'Kota berhasil ditambahkan.');
    }

    public function update(KotaRequest $request, Kota $kota): RedirectResponse
    {
        $kota->update($request->validated());

        return back()->with('success', 'Kota berhasil diperbarui.');
    }

    public function destroy(Kota $kota): RedirectResponse
    {
        try {
            $kota->delete();
        } catch (\Exception $e) {
            return back()->with('error', 'Kota tidak bisa dihapus karena masih dipakai pada data lain (tarif/pengiriman).');
        }

        return back()->with('success', 'Kota berhasil dihapus.');
    }

    public function import(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                // ✅ support CSV + Excel
                'mimes:csv,txt,xlsx,xls',
                // naikkan limit biar Excel aman
                'max:10240',
            ],
            'mode' => ['nullable', 'in:preview,import'],
        ]);

        $mode = $validated['mode'] ?? 'import';
        $file = $request->file('file');

        if (! $file) {
            return $this->respondImportError($request, 'File tidak ditemukan.');
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());

        try {
            if (in_array($ext, ['xlsx', 'xls'], true)) {
                [$headerRow, $rows] = $this->readExcelRows($request, $file);
            } else {
                [$headerRow, $rows] = $this->readCsvRows($request, $file);
            }
        } catch (\Throwable $e) {
            return $this->respondImportError($request, $e->getMessage() ?: 'File tidak dapat diproses.');
        }

        if (! is_array($headerRow) || count($headerRow) === 0) {
            return $this->respondImportError($request, 'Header file tidak valid.');
        }

        $header = $this->normalizeHeader($headerRow);

        $requiredHeader = ['nama_kota', 'provinsi', 'kode_pos', 'latitude', 'longitude', 'status'];
        foreach ($requiredHeader as $h) {
            if (! in_array($h, $header, true)) {
                return $this->respondImportError(
                    $request,
                    'Header wajib memiliki kolom: '.implode(', ', $requiredHeader)
                );
            }
        }

        $maxRows = 1000;
        $previewLimit = 200;

        $preview = [];
        $invalid = 0;
        $processed = 0;
        $validRows = [];

        foreach ($rows as $row) {
            if ($processed >= $maxRows) {
                break;
            }

            if (! is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }

            $processed++;

            $assoc = [];
            foreach ($header as $idx => $key) {
                $assoc[$key] = isset($row[$idx]) ? trim((string) $row[$idx]) : '';
            }

            $item = [
                'nama_kota' => $assoc['nama_kota'] ?? '',
                'provinsi' => $assoc['provinsi'] ?? '',
                'kode_pos' => $assoc['kode_pos'] ?? '',
                'latitude' => ($assoc['latitude'] ?? '') !== '' ? (float) $assoc['latitude'] : null,
                'longitude' => ($assoc['longitude'] ?? '') !== '' ? (float) $assoc['longitude'] : null,
                'status' => ($assoc['status'] ?? '') !== '' ? strtolower((string) $assoc['status']) : 'aktif',
            ];

            $errors = $this->validateRow($item);

            if (! empty($errors)) {
                $invalid++;
            } else {
                $validRows[] = $item;
            }

            if ($mode === 'preview' && count($preview) < $previewLimit) {
                $preview[] = $item + ['_errors' => $errors];
            }
        }

        if ($mode === 'preview') {
            return response()->json([
                'data' => $preview,
                'total' => $processed,
                'invalid' => $invalid,
            ]);
        }

        if ($processed === 0) {
            return back()->with('error', 'File tidak berisi data.');
        }

        if ($invalid > 0) {
            return back()->with('error', 'Import dibatalkan: masih ada baris yang tidak valid. Perbaiki dulu.');
        }

        $count = 0;

        DB::transaction(function () use (&$count, $validRows): void {
            foreach ($validRows as $row) {
                Kota::updateOrCreate(
                    [
                        'nama_kota' => $row['nama_kota'],
                        'provinsi' => $row['provinsi'],
                    ],
                    [
                        'kode_pos' => $row['kode_pos'],
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'status' => $row['status'],
                    ]
                );

                $count++;
            }
        });

        return back()->with('success', "Import kota berhasil. Total diproses: {$count} baris.");
    }

    private function respondImportError(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->withErrors(['file' => $message]);
    }

    private function readCsvRows(Request $request, $file): array
    {
        $path = $file->getRealPath();
        if (! $path) {
            throw new \RuntimeException('File CSV tidak dapat dibaca.');
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('File CSV tidak dapat dibuka.');
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            throw new \RuntimeException('File CSV kosong.');
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $headerRow = fgetcsv($handle, 0, $delimiter);
        // Penjelasan: Menggunakan is_array saja sudah cukup untuk memeriksa output fgetcsv.
        // fgetcsv mengembalikan false jika gagal, bukan array kosong dengan count 0. Ini menyelesaikan warning strict dari PHPStan.
        if (! is_array($headerRow)) {
            fclose($handle);
            throw new \RuntimeException('Header CSV tidak valid.');
        }

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return [$headerRow, $rows];
    }

    private function readExcelRows(Request $request, $file): array
    {
        // ✅ pakai maatwebsite/excel bila tersedia
        if (! class_exists(Excel::class)) {
            throw new \RuntimeException('Import Excel belum tersedia: paket maatwebsite/excel belum terpasang.');
        }

        $import = new class implements ToArray
        {
            public array $data = [];

            public function array(array $array): array
            {
                $this->data = $array;

                return $array;
            }
        };

        $sheets = Excel::toArray($import, $file);

        $sheet0 = $sheets[0] ?? [];
        if (! is_array($sheet0) || count($sheet0) === 0) {
            throw new \RuntimeException('File Excel kosong.');
        }

        $headerRow = $sheet0[0] ?? null;
        if (! is_array($headerRow) || count($headerRow) === 0) {
            throw new \RuntimeException('Header Excel tidak valid.');
        }

        $rows = array_slice($sheet0, 1);

        return [$headerRow, $rows];
    }

    private function detectDelimiter(string $line): string
    {
        $comma = substr_count($line, ',');
        $semi = substr_count($line, ';');

        return $semi > $comma ? ';' : ',';
    }

    private function normalizeHeader(array $headerRow): array
    {
        $headerRow[0] = $this->stripUtf8Bom((string) ($headerRow[0] ?? ''));

        $normalized = [];
        foreach ($headerRow as $h) {
            $key = strtolower(trim((string) $h));
            $key = str_replace([' ', '-'], '_', $key);
            $normalized[] = $key;
        }

        return $normalized;
    }

    private function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    private function rowIsEmpty(array $row): bool
    {
        if (count($row) === 0) {
            return true;
        }

        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }

    private function validateRow(array $item): array
    {
        $errors = [];

        if (mb_strlen($item['nama_kota']) < 2) {
            $errors[] = 'nama_kota minimal 2 karakter';
        }

        if (mb_strlen($item['provinsi']) < 2) {
            $errors[] = 'provinsi minimal 2 karakter';
        }

        $kodePosLen = mb_strlen((string) $item['kode_pos']);
        if ($kodePosLen < 3 || $kodePosLen > 10) {
            $errors[] = 'kode_pos 3–10 karakter';
        }

        if (! in_array($item['status'], ['aktif', 'nonaktif'], true)) {
            $errors[] = 'status harus aktif/nonaktif';
        }

        if ($item['latitude'] !== null && ($item['latitude'] < -90 || $item['latitude'] > 90)) {
            $errors[] = 'latitude tidak valid';
        }

        if ($item['longitude'] !== null && ($item['longitude'] < -180 || $item['longitude'] > 180)) {
            $errors[] = 'longitude tidak valid';
        }

        return $errors;
    }
}
