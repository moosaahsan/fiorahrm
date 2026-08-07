<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $fillable = [
        'interview_type',
        'name',
        'cnic',
        'phone',
        'address',
        'email',
        'experience',
        'qualification',
        'communication_skills',
        'reference',
        'source',
        'cv_path',
        'cnic_front_path',
        'cnic_back_path',
        'interviewers',
        'remarks',
        'status',
        'interview_date',
        'training_start_date',
        'training_end_date',
        'training_status',
        'round_dates',
        'created_by',
        'on_hold_date',
        'job_posting_id',
        'category',
        'position_applied',
        'assigned_to',
    ];

    protected $casts = [
        'interview_date' => 'datetime',
        'interviewers' => 'array',
        'remarks' => 'array',
        'round_dates' => 'array',
        'on_hold_date' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function jobPosting()
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }
}
