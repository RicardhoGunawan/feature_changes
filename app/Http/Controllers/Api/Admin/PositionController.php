<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends BaseAdminController
{
    public function index(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $positions = Position::with(['parent', 'department'])->orderBy('level', 'desc')->get();
        return response()->json(['success' => true, 'data' => $positions]);
    }

    public function store(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        $validated = $request->validate([
            'name' => 'required|string',
            'parent_id' => 'nullable|exists:positions,id',
            'department_id' => 'nullable|exists:departments,id',
            'level' => 'required|integer'
        ]);

        if ($id) {
            $position = Position::findOrFail($id);
            $position->update($validated);
        } else {
            $position = Position::create($validated);
        }

        return response()->json(['success' => true, 'data' => $position]);
    }

    public function destroy(Request $request)
    {
        $this->validatePermission($request, 'view_roles');
        $id = $request->get('id');
        $position = Position::findOrFail($id);

        if ($position->users()->exists()) {
            return response()->json(['success' => false, 'message' => 'Jabatan tidak bisa dihapus karena masih memiliki karyawan aktif.'], 422);
        }

        $position->delete();
        return response()->json(['success' => true, 'message' => 'Jabatan berhasil dihapus']);
    }
}
