<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CarouselImage extends Model
{
    protected $fillable = [
        'image_path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
