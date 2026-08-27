<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\School;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function index()
    {
        $derslikler = Room::withCount('section')->orderBy('name')->get();

        return view('rooms.index', ['derslikler' => $derslikler]);
    }

    public function create()
    {
        return view('rooms.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name',
        ]);

        $okul = School::first();
        $okul->rooms()->create($validated);

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik eklendi.');
    }

    public function edit(Room $derslik)
    {
        return view('rooms.edit', ['derslik' => $derslik]);
    }

    public function update(Request $request, Room $derslik)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:rooms,name,' . $derslik->id,
        ]);

        $derslik->update($validated);

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik güncellendi.');
    }

    public function destroy(Room $derslik)
    {
        $derslik->delete();

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik silindi.');
    }
}
