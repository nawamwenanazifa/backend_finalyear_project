<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements HasAvatar, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, Auditable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin || $this->role === 'super_admin' || $this->role === 'admin';
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->profile_image) {
            return asset($this->profile_image);
        }

        // Generate initials-based avatar
        $name = urlencode($this->name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&color=ffffff&background=570013&bold=true&size=128";
    }

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
        
        $name = urlencode($this->name ?? 'User');
        return "https://ui-avatars.com/api/?name={$name}&color=ffffff&background=570013&bold=true&size=128";
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