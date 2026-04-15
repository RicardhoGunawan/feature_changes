<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $holidays = Holiday::orderBy('date', 'asc')->get();
        return response()->json(['success' => true, 'data' => $holidays]);
    }

    public function store(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $id = $request->get('id');
        $validated = $request->validate([
            'name' => 'required|string',
            'date' => 'required|date|unique:holidays,date,' . $id,
            'type' => 'required|in:national,company'
        ]);

        if ($id) {
            $holiday = Holiday::findOrFail($id);
            $holiday->update($validated);
        } else {
            $holiday = Holiday::create($validated);
        }

        return response()->json(['success' => true, 'data' => $holiday]);
    }

    public function destroy(Request $request)
    {
        $this->validatePermission($request, 'manage_schedule');
        $id = $request->get('id');
        Holiday::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Hari libur berhasil dihapus']);
    }
}
