<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersistentLogin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_type',
        'user_id',
        'token_hash',
        'device_id',
        'device_name',
        'pin_hash',
        'pin_enabled',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'pin_enabled' => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Get the user (client or rider) associated with this persistent login.
     */
    public function user()
    {
        if ($this->user_type === 'client') {
            return $this->belongsTo(Client::class, 'user_id');
        }
        return $this->belongsTo(Rider::class, 'user_id');
    }

    /**
     * Check if the persistent login is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Update last used timestamp.
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Scope to find valid (non-expired) tokens.
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to find by token hash.
     */
    public function scopeByToken($query, string $tokenHash)
    {
        return $query->where('token_hash', $tokenHash);
    }
}
