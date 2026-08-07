<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveDay extends Model
{
    use HasFactory;

    protected $table = 'leave_days';

    protected $fillable = [
        'leave_id',
        'leave_date',
        'status',
    ];

    protected $casts = [
        'leave_date' => 'date',
    ];

    // 🔁 Relationship with Leave
    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }
}
