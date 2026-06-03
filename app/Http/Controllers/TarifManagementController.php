<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TarifRequest;
use App\Models\Tarif;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TarifManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = $request->string('q')->toString();

        $tarif = Tarif::query()
            ->when($q, function ($query) use ($q) {
                $query->where('jenis_layanan', 'like', "%{$q}%")
                    ->orWhere('status', 'like', "%{$q}%");
            })
            ->orderBy('jenis_layanan')
            ->paginate(20)
            ->withQueryString();

        return response()->json(['data' => $tarif]);
    }

    public function store(TarifRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($this->hasOverlap((string) $data['jenis_layanan'])) {
            return back()
                ->withErrors([
                    'jenis_layanan' => 'Tarif untuk layanan ini sudah ada. Silakan edit tarif yang sudah ada.',
                ])
                ->withInput();
        }

        Tarif::create($data);

        return back()->with('success', 'Tarif berhasil ditambahkan.');
    }

    public function update(TarifRequest $request, Tarif $tarifDatum): RedirectResponse
    {
        $data = $request->validated();

        if ($this->hasOverlap((string) $data['jenis_layanan'], (int) $tarifDatum->id)) {
            return back()
                ->withErrors([
                    'jenis_layanan' => 'Tarif untuk layanan ini sudah digunakan oleh entri lain.',
                ])
                ->withInput();
        }

        $tarifDatum->update($data);

        return back()->with('success', 'Tarif berhasil diperbarui.');
    }

    public function destroy(Tarif $tarifDatum): RedirectResponse
    {
        try {
            $tarifDatum->delete();
        } catch (\Exception $e) {
            return back()->with('error', 'Tarif tidak bisa dihapus karena masih dipakai pada data pengiriman.');
        }

        return back()->with('success', 'Tarif berhasil dihapus.');
    }

    /**
     * Memastikan hanya ada 1 pengaturan tarif per jenis layanan
     */
    private function hasOverlap(string $jenis, ?int $ignoreId = null): bool
    {
        $q = Tarif::query()->where('jenis_layanan', $jenis);

        if ($ignoreId !== null) {
            $q->where('id', '!=', $ignoreId);
        }

        return $q->exists();
    }
}
