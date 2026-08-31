<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoomRequest;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\School;

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

    public function store(RoomRequest $request)
    {
        School::first()->rooms()->create($request->validated());

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik eklendi.');
    }

    public function edit(Room $derslik)
    {
        return view('rooms.edit', ['derslik' => $derslik]);
    }

    public function update(RoomRequest $request, Room $derslik)
    {
        $derslik->update($request->validated());

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik güncellendi.');
    }

    public function destroy(Room $derslik)
    {
        // sections.room_id nullOnDelete: sube dersliksiz kalir, silinmez.
        // Ama schedule_entries.room_id CASCADE — uretilmis program
        // satirlari sessizce ucar. Program bos oldugu surece bu kontrol
        // hicbir seyi engellemiyor, faz 2 sonrasi devreye girecek.
        $programdaVar = ScheduleEntry::where('room_id', $derslik->id)->exists();

        if ($programdaVar) {
            return redirect()
                ->route('derslikler.index')
                ->with('error', "{$derslik->name} silinemez: üretilmiş programda kullanılıyor. Önce programı sıfırlayın.");
        }

        $derslik->delete();

        return redirect()
            ->route('derslikler.index')
            ->with('success', 'Derslik silindi.');
    }
}
