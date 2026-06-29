<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'assignee', 'due_at', 'priority', 'done'];

    protected $casts = [
        'due_at' => 'datetime',
        'done' => 'boolean',
    ];
}
