<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SlotPhotoController extends Controller
{
    /**
     * Serve a photo directly from the database.
     * URL: GET /slot-photos/{id}
     *
     * Returns the raw image binary with proper Content-Type header.
     * Includes cache headers for performance on all devices.
     */
    public function show(int $id)
    {
        $photo = DB::table('slot_photos')
            ->where('id', $id)
            ->select(['photo_data', 'mime_type', 'filename'])
            ->first();

        if (! $photo || ! $photo->photo_data) {
            abort(404);
        }

        // PostgreSQL bytea comes as a stream resource or hex-escaped string
        $data = $photo->photo_data;
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return response($data, 200, [
            'Content-Type' => $photo->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'inline; filename="' . ($photo->filename ?? 'photo.jpg') . '"',
            'Cache-Control' => 'public, max-age=604800', // 7 days cache
            'ETag' => md5($id),
        ]);
    }

    /**
     * Download a photo from the database.
     * URL: GET /slot-photos/{id}/download
     */
    public function download(int $id)
    {
        $photo = DB::table('slot_photos')
            ->where('id', $id)
            ->select(['photo_data', 'mime_type', 'filename'])
            ->first();

        if (! $photo || ! $photo->photo_data) {
            abort(404);
        }

        $data = $photo->photo_data;
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        return response($data, 200, [
            'Content-Type' => $photo->mime_type ?? 'image/jpeg',
            'Content-Disposition' => 'attachment; filename="' . ($photo->filename ?? 'photo.jpg') . '"',
        ]);
    }
}
