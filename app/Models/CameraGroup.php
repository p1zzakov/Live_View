<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CameraGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    public function cameras(): BelongsToMany
    {
        return $this->belongsToMany(Camera::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('access_level');
    }
}
