<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'nav_group'];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users()
    {
        return $this->hasMany(UserProfile::class, 'role_id', 'id');
    }

    public function nameHistory()
    {
        return $this->hasMany(RoleNameHistory::class)->orderByDesc('changed_at');
    }

    public function delegations()
    {
        return $this->hasMany(Delegation::class);
    }
}
