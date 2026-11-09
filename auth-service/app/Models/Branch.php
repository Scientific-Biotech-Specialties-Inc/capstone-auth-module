<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'name',
        'address',
    ];

    public function userProfiles()
    {
        return $this->hasMany(UserProfile::class, 'branch_id');
    }

    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserProfile::class,
            'branch_id',
            'id',
            'id',
            'user_id'
        );
    }
}
