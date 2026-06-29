<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'source', 'service',
        'status', 'pic', 'value', 'notes', 'source_ref', 'last_contact_at',
    ];

    protected $casts = [
        'value' => 'integer',
        'last_contact_at' => 'datetime',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }
}
