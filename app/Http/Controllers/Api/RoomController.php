<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoomRequest;
use App\Models\Room;
use App\Models\ScheduleEntry;
use App\Models\School;

class RoomController extends Controller
{
    public function index()
    {
        return Room::withCount('section')->orderBy('name')->get();
    }

    public function store(RoomRequest $request)
    {
        $derslik = School::aktif()->rooms()->create($request->validated());

        return response()->json($derslik, 201);
    }

    public function update(RoomRequest $request, Room $derslik)
    {
        $derslik->update($request->validated());

        return $derslik;
    }

    public function destroy(Room $derslik)
    {
        // sections.room_id nullOnDelete: sube dersliksiz kalir, silinmez.
        // Ama schedule_entries.room_id CASCADE - uretilmis program
        // satirlari sessizce ucar. Bu yuzden onceden kontrol ediyoruz.
        $programdaVar = ScheduleEntry::where('room_id', $derslik->id)->exists();

        if ($programdaVar) {
            return response()->json([
                'message' => "{$derslik->name} silinemez: üretilmiş programda kullanılıyor. Önce programı sıfırlayın.",
            ], 422);
        }

        $derslik->delete();

        return response()->json(null, 204);
    }
}
