<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Shift;
use App\Models\OfficeLocation;
use Illuminate\Http\Request;

class ScheduleController extends BaseAdminController
{
    public function shifts(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $shifts = Shift::all()->map(function ($s) {
            return [
                'id' => $s->id,
                'shift_name' => $s->name,
                'start_time' => substr($s->start_time, 0, 5),
                'end_time' => substr($s->end_time, 0, 5),
                'late_tolerance_minutes' => $s->late_tolerance_minutes
            ];
        });
        return response()->json(['success' => true, 'data' => $shifts]);
    }

    public function storeShift(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $validated = $request->validate([
            'shift_name' => 'required|string',
            'start_time' => 'required',
            'end_time' => 'required',
            'late_tolerance_minutes' => 'integer',
        ]);

        $shift = Shift::create([
            'name' => $validated['shift_name'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'late_tolerance_minutes' => $validated['late_tolerance_minutes'] ?? 0,
        ]);

        return response()->json(['success' => true, 'data' => $shift]);
    }

    public function locations(Request $request)
    {
        $this->validatePermission($request, 'manage_location');
        return response()->json(['success' => true, 'data' => OfficeLocation::all()]);
    }

    public function storeLocation(Request $request)
    {
        $this->validatePermission($request, 'manage_location');
        $validated = $request->validate([
            'name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'required|integer',
        ]);
        $location = OfficeLocation::create($validated);
        return response()->json(['success' => true, 'data' => $location]);
    }
}
