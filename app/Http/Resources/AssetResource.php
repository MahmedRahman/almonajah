<?php

namespace App\Http\Resources;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'file_name' => $this->file_name,
            'speaker_name' => $this->speaker_name,
            'relative_path' => $this->relative_path,
            'thumbnail_path' => $this->thumbnail_path,
            'cover_path' => $this->cover_path,
            'extension' => $this->extension,
            'orientation' => $this->orientation,
            'duration_seconds' => $this->duration_seconds,
            'duration_formatted' => $this->duration_formatted,
            'site_description' => $this->site_description,
            'topics' => $this->topics,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'web_video_relative_path' => $this->when($this->isVideo(), $this->web_video_relative_path),
            'video_relative_path' => $this->when($this->isVideo(), $this->getWebPlaybackRelativePath()),
            'video_url' => $this->when($this->isVideo(), function () {
                $path = $this->getWebPlaybackRelativePath();

                return $path ? $this->publicStorageUrl($path) : null;
            }),
            'video_url_master' => $this->when($this->isVideo(), function () {
                $playback = $this->getWebPlaybackRelativePath();
                if (! $playback || ! $this->relative_path) {
                    return null;
                }
                if (Asset::normalizeStoragePathKey($playback) === Asset::normalizeStoragePathKey($this->relative_path)) {
                    return null;
                }
                if (! Storage::disk('public')->exists($this->relative_path)) {
                    return null;
                }

                return $this->publicStorageUrl($this->relative_path);
            }),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'hls_versions' => $this->whenLoaded('hlsVersions', function () {
                return $this->hlsVersions->map(fn ($version) => [
                    'id' => $version->id,
                    'resolution' => $version->resolution,
                    'width' => $version->width,
                    'height' => $version->height,
                    'playlist_path' => $version->playlist_path,
                    'master_playlist_path' => $version->master_playlist_path,
                ]);
            }),
            'audio_files' => $this->whenLoaded('audioFiles', function () {
                return $this->audioFiles->map(fn ($audioFile) => [
                    'id' => $audioFile->id,
                    'format' => $audioFile->format,
                    'bitrate' => $audioFile->bitrate,
                    'duration_seconds' => $audioFile->duration_seconds,
                    'file_path' => $audioFile->file_path,
                ]);
            }),
        ];
    }

    protected function publicStorageUrl(string $relativePath): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        return '/storage/'.$relativePath;
    }
}
