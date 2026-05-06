<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nvr extends Model
{
    protected $fillable = [
        'name',
        'vendor',
        'ip_address',
        'http_port',
        'rtsp_port',
        'credentials',
        'api_endpoint',
        'is_active',
        'last_health_check',
    ];

    protected $casts = [
        'credentials' => 'array',  // Изменили с encrypted:array на array
        'is_active' => 'boolean',
        'last_health_check' => 'datetime',
    ];

    // Ручное шифрование credentials
    public function setCredentialsAttribute($value)
    {
        $this->attributes['credentials'] = json_encode($value);
    }

    public function getCredentialsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function cameras(): HasMany
    {
        return $this->hasMany(Camera::class);
    }
}
