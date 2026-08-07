<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class Department extends Model
{
    use HasFactory, ScopesData;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'manager_id',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, Team::class);
    }
}
