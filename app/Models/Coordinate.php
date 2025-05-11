<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coordinate extends Model
{
    protected $fillable = [
        'point',
        'description',
        'utm_north',
        'utm_east',
        'latitude_decimal',
        'longitude_decimal',
        'latitude_gms',
        'longitude_gms',
        'elevation',
        'datum',
        'utm_zone',
        'central_meridian',
        'service_id',
    ];

    protected $casts = [
        'utm_north' => 'float',
        'utm_east' => 'float',
        'latitude_decimal' => 'float',
        'longitude_decimal' => 'float',
        'elevation' => 'float',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
} 