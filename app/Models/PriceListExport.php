<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListExport extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'generated_at' => 'datetime',
    ];
}
