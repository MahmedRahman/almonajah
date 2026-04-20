<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
