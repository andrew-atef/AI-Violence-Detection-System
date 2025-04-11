<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use URL;

class ViolenceNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'note' => $this->note,
            'camera_num' => $this->camera_num,
            'video_path' => URL::to('/')  .Storage::url($this->video_path),
            'prediction' => $this->prediction,
            'confidence' => $this->confidence,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at,
        ];
    }
}
