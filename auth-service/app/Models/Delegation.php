<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delegation extends Model
{
    protected $fillable = [
        'delegator_user_id',
        'delegate_user_id',
        'role_id',
        'start_date',
        'end_date',
        'status',
        'revoked_at',
        'revoked_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'revoked_at' => 'datetime',
        ];
    }

    public function delegator()
    {
        return $this->belongsTo(User::class, 'delegator_user_id');
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function revokedBy()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    /**
     * Whether this delegation is currently in effect: not revoked, not past
     * its end date, and today falls within [start_date, end_date].
     * This is the live truth used by resolution logic — `status` is a
     * cached/display value that a scheduled job or check-on-read keeps in
     * sync with this.
     */
    public function isCurrentlyActive(): bool
    {
        if ($this->status === 'revoked') {
            return false;
        }

        $today = now()->startOfDay();
        return $today->greaterThanOrEqualTo($this->start_date->startOfDay())
            && $today->lessThanOrEqualTo($this->end_date->startOfDay());
    }

    public function scopeCurrentlyActive($query)
    {
        $today = now()->toDateString();
        return $query->where('status', '!=', 'revoked')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}
