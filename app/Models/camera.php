<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    /**
     * Get the users that are assigned to this camera.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
