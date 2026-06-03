<?php

namespace App\Http\Controllers;

use App\Models\Rute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RuteController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(
            [
                'kota_asal_id' => [
                    'required',
                    Rule::exists('kota', 'id')->where('status', 'aktif'),
                ],
                'kota_tujuan_id' => [
                    'required',
                    'different:kota_asal_id',
                    Rule::exists('kota', 'id')->where('status', 'aktif'),
                ],
                'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            ],
            [
                'kota_tujuan_id.different' => 'Kota tujuan tidak boleh sama dengan kota asal.',
            ]
        );

        $exists = Rute::query()
            ->where('kota_asal_id', $validated['kota_asal_id'])
            ->where('kota_tujuan_id', $validated['kota_tujuan_id'])
            ->exists();

        if ($exists) {
            return redirect()
                ->route('settings.index', ['tab' => 'rute'])
                ->with('error', 'Rute ini sudah ada.');
        }

        Rute::query()->create($validated);

        return redirect()
            ->route('settings.index', ['tab' => 'rute'])
            ->with('success', 'Rute berhasil ditambahkan.');
    }

    public function update(Request $request, Rute $rute): RedirectResponse
    {
        $validated = $request->validate(
            [
                'kota_asal_id' => [
                    'required',
                    Rule::exists('kota', 'id')->where('status', 'aktif'),
                ],
                'kota_tujuan_id' => [
                    'required',
                    'different:kota_asal_id',
                    Rule::exists('kota', 'id')->where('status', 'aktif'),
                ],
                'status' => ['required', Rule::in(['aktif', 'nonaktif'])],
            ],
            [
                'kota_tujuan_id.different' => 'Kota tujuan tidak boleh sama dengan kota asal.',
            ]
        );

        $exists = Rute::query()
            ->where('kota_asal_id', $validated['kota_asal_id'])
            ->where('kota_tujuan_id', $validated['kota_tujuan_id'])
            ->where('id', '!=', $rute->id)
            ->exists();

        if ($exists) {
            return redirect()
                ->route('settings.index', ['tab' => 'rute'])
                ->with('error', 'Rute ini sudah ada.');
        }

        $rute->fill($validated)->save();

        return redirect()
            ->route('settings.index', ['tab' => 'rute'])
            ->with('success', 'Rute berhasil diperbarui.');
    }

    public function destroy(Rute $rute): RedirectResponse
    {
        $rute->delete();

        return redirect()
            ->route('settings.index', ['tab' => 'rute'])
            ->with('success', 'Rute berhasil dihapus.');
    }
}
