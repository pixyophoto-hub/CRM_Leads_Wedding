<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Automation extends Model
{
    protected $fillable = ['name', 'trigger', 'action', 'active'];

    protected $casts = ['active' => 'boolean'];
}
