<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ScopesData;

class Branch extends Model
{
    use ScopesData;
    protected $fillable = ['name', 'code', 'address', 'timezone', 'is_active'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }
}
