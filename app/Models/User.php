<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
        'role',
        'gender',
        'profile_image',
        'bio',
        'location',
        'locked_until',
        'failed_login_attempts',
        'verification_code',
        'verification_code_sent_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'locked_until' => 'datetime',
        'verification_code_sent_at' => 'datetime',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function moodboardItems()
    {
        return $this->hasMany(MoodboardItem::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin()
    {
        return $this->is_admin === true;
    }
    
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin' || $this->is_admin === true;
    }
    
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    
    public function getRoleLabelAttribute()
    {
        $roles = [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'bride' => 'Bride',
            'user' => 'User',
        ];
        
        return $roles[$this->role] ?? 'User';
    }
    
    public function isLocked()
    {
        return $this->locked_until && now()->lt($this->locked_until);
    }
    
    public function getDisplayNameAttribute()
    {
        return $this->name ?? $this->email;
    }
    
    public function getAvatarUrlAttribute()
    {
        if ($this->profile_image) {
            return asset($this->profile_image);
        }
        
        if ($this->gender === 'female') {
            return '/images/default-female-avatar.png';
        } elseif ($this->gender === 'male') {
            return '/images/default-male-avatar.png';
        }
        
        return '/images/default-avatar.png';
    }
    
    public function getInitialsAttribute()
    {
        $words = explode(' ', $this->name ?? 'User');
        $initials = '';
        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $initials .= strtoupper($word[0]);
            }
        }
        return substr($initials, 0, 2);
    }
}