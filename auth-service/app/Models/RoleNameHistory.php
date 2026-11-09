<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleNameHistory extends Model
{
    protected $table = 'role_name_history';

    protected $fillable = [
        'role_id',
        'previous_name',
        'new_name',
        'changed_by',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
