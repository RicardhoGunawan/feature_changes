<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /**
     * Upload profile photo for the authenticated user.
     */
    public function uploadPhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        $user = $request->user();

        // 1. Delete old photo if exists
        if ($user->profile_photo) {
            // Check if photo is in our local storage
            $oldPath = str_replace('/storage/', '', $user->profile_photo);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        // 2. Store new photo
        $path = $request->file('photo')->store('photos', 'public');
        $photoUrl = '/storage/' . $path;

        // 3. Update database
        $user->update([
            'profile_photo' => $photoUrl,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Foto profil berhasil diperbarui',
            'data' => [
                'photo_url' => $photoUrl,
            ]
        ]);
    }
}
