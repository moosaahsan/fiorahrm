<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class Team extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'name',
        'department_id',
        'description',
        'is_active',
        'leader_id',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Users assigned to monitor this team.
     */
    public function monitoringUsers()
    {
        return $this->belongsToMany(User::class, 'user_teams');
    }
}
