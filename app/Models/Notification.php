<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    
    protected $fillable = [
        'title',
        'message',
        'type',
        'icon',
        'link',
        'is_read',
        'user_id',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    public function getIconAttribute($value)
    {
        return $value ?? match($this->type) {
            'success' => '✅',
            'warning' => '⚠️',
            'danger' => '❌',
            default => 'ℹ️',
        };
    }
}