<?php

namespace App\Http\Controllers;

use App\Models\Level;
use App\Models\School;
use Illuminate\Http\Request;

class LevelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $seviyeler = Level::withCount('sections')->orderBy('grade_number')->get();

        return view('levels.index', ['seviyeler' => $seviyeler]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('levels.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'grade_number' => 'required|integer|min:1|max:12|unique:levels,grade_number',
        ]);

        $okul = School::first();

        $okul->levels()->create([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye eklendi.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Level $seviye)
    {
        return view('levels.edit', ['seviye' => $seviye]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Level $seviye)
    {
        $validated = $request->validate([
            'grade_number' => 'required|integer|min:1|max:12|unique:levels,grade_number,' . $seviye->id,
        ]);

        $seviye->update([
            'name' => $validated['grade_number'] . '. Sınıf',
            'grade_number' => $validated['grade_number'],
        ]);

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye güncellendi.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Level $seviye)
    {
        $seviye->delete();

        return redirect()
            ->route('seviyeler.index')
            ->with('success', 'Seviye silindi.');
    }
}
