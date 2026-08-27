<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\Room;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        $subeler = Section::with(['level', 'room'])
            ->join('levels', 'sections.level_id', '=', 'levels.id')
            ->orderBy('levels.grade_number')
            ->orderBy('sections.name')
            ->select('sections.*')
            ->get();

        return view('sections.index', ['subeler' => $subeler]);
    }

    public function create()
    {
        return view('sections.create', [
            'seviyeler' => Level::orderBy('grade_number')->get(),
            'derslikler' => Room::whereDoesntHave('section')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'name' => 'required|string|max:10',
            'room_id' => 'nullable|exists:rooms,id|unique:sections,room_id',
        ]);

        Section::create($validated);

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube eklendi.');
    }

    public function edit(Section $sube)
    {
        return view('sections.edit', [
            'sube' => $sube,
            'seviyeler' => Level::orderBy('grade_number')->get(),
            'derslikler' => Room::where(function ($sorgu) use ($sube) {
                $sorgu->whereDoesntHave('section')->orWhere('id', $sube->room_id);
            })->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Section $sube)
    {
        $validated = $request->validate([
            'level_id' => 'required|exists:levels,id',
            'name' => 'required|string|max:10',
            'room_id' => 'nullable|exists:rooms,id|unique:sections,room_id,' . $sube->id,
        ]);

        $sube->update($validated);

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube güncellendi.');
    }

    public function destroy(Section $sube)
    {
        $sube->delete();

        return redirect()
            ->route('subeler.index')
            ->with('success', 'Şube silindi.');
    }
}
