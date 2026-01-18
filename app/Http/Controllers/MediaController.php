<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index()
    {
        $mediaFiles = MediaFile::with('uploader')
            ->latest()
            ->paginate(20);

        return view('media.index', compact('mediaFiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');
        
        $mediaFile = MediaFile::create([
            'name' => $file->hashName(),
            'original_name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $this->getFileType($file->getMimeType()),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('media.index')
            ->with('success', 'تم رفع الملف بنجاح');
    }

    public function destroy(MediaFile $mediaFile)
    {
        Storage::disk('public')->delete($mediaFile->path);
        $mediaFile->delete();

        return redirect()->route('media.index')
            ->with('success', 'تم حذف الملف بنجاح');
    }

    private function getFileType($mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            return 'video';
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return 'audio';
        } elseif (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])) {
            return 'document';
        }

        return 'other';
    }
}


