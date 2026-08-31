<?php

namespace App\Http\Controllers;

use App\Http\Requests\LevelRequest;
use App\Models\Level;
use App\Models\School;

class LevelController extends Controller
{
    public function index()
    {
        // withCount: sube sayisini tek sorguda getiriyor.
        // Kullanici silmeden ONCE kac sube bagli oldugunu goruyor.
        $seviyeler = Level::withCount('sections')->orderBy('grade_number')->get();

        return view('levels.index', ['seviyeler' => $seviyeler]);
    }

    public function create()
    {
        return view('levels.create');
    }

    public function store(LevelRequest $request)
    {
        $validated = $request->validated();

        School::first()->levels()->create([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye eklendi.');
    }

    public function edit(Level $seviye)
    {
        return view('levels.edit', ['seviye' => $seviye]);
    }

    public function update(LevelRequest $request, Level $seviye)
    {
        $validated = $request->validated();

        $seviye->update([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye güncellendi.');
    }

    public function destroy(Level $seviye)
    {
        // Sadece subeleri koruyorum. Mufredat tanimlari ve ogretmen
        // yetkileri seviyenin kendi parcasi; onlari da korusaydim
        // kullanici seviyeyi silmek icin once butun derslerden ve
        // ogretmenlerden bu seviyeyi kaldirmak zorunda kalirdi.
        // Sube ise bagimsiz bir varlik, ogrencileriyle birlikte durur.
        $subeSayisi = $seviye->sections()->count();

        if ($subeSayisi > 0) {
            return redirect()
                ->route('seviyeler.index')
                ->with('error', "{$seviye->name} silinemez: bu seviyeye bağlı {$subeSayisi} şube var. Önce şubeleri silin.");
        }

        $seviye->delete();

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye silindi.');
    }
}
