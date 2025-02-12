<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = ['page_name', 'key', 'value' , 'json_value'];

    protected $casts = [
        'json_value' => 'array', // Automatically cast JSON to array
    ];
}
