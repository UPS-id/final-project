<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Todo extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'is_done',
        'priority',
        'due_at',
        'reminder_at',
    ];

    protected $casts = [
        'is_done'     => 'boolean',
        'due_at'      => 'datetime',
        'reminder_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && ! $this->is_done && $this->due_at->isPast();
    }
}
