<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Healthcare extends Model
{
    use HasFactory;
    protected $fillable = ['service_name', 'title', 'service', 'description', 'video'];
}
