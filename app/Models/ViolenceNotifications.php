<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViolenceNotifications extends Model
{
    protected $fillable = [
        'note',
        'camera_num',
        'video_path',
        'prediction',
        'confidence',
        'user_id',
    ];
}
