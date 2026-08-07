<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Models\Team;
use App\Models\Employee;
use App\Traits\ScopesData;

class CompanyOffDay extends Model
{
    use ScopesData;

    protected $fillable = [
        'title',
        'off_date',
        'start_date',
        'end_date',
        'type',
        'note',
        'team_id',
        'branch_id',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'holiday_team', 'holiday_id', 'team_id')->withTimestamps();
    }

    public function scopeUpcoming($query, ?string $today = null)
    {
        $today = $today ?? now()->toDateString();

        return $query->whereDate('end_date', '>=', $today);
    }

    public function scopeForTeam($query, ?int $teamId)
    {
        return $query->where(function ($q) use ($teamId) {
            $q->whereDoesntHave('teams');
            if ($teamId) {
                $q->orWhereHas('teams', fn ($sq) => $sq->where('teams.id', $teamId));
            }
        });
    }

    public function scopeForBranch($query, ?int $branchId)
    {
        if (! $branchId) {
            return $query;
        }

        return $query->where(function ($q) use ($branchId) {
            $q->whereNull('branch_id')->orWhere('branch_id', $branchId);
        });
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->note ?: ($this->title ?: 'Holiday'),
            'start_date' => $this->start_date instanceof Carbon
                ? $this->start_date->toDateString()
                : $this->start_date,
            'end_date' => $this->end_date instanceof Carbon
                ? $this->end_date->toDateString()
                : $this->end_date,
            'type' => $this->type,
        ];
    }

    public static function upcomingForEmployee(?Employee $employee, int $limit = 6): \Illuminate\Support\Collection
    {
        if (! $employee) {
            return collect();
        }

        return static::query()
            ->upcoming()
            ->forTeam($employee->team_id)
            ->forBranch($employee->branch_id)
            ->orderBy('start_date')
            ->limit($limit)
            ->get()
            ->map(fn (self $holiday) => $holiday->toApiArray())
            ->values();
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Check if a given date falls within this off day range
     *
     * @param  Carbon|string|null  $date
     * @return bool
     */
    public function includesDate($date = null)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date ?? now());

        return $date->between($this->start_date, $this->end_date);
    }

    public static function getHolidayForEmployee($date, ?Employee $employee): ?self
    {
        if (! $employee) {
            return null;
        }

        $date = $date instanceof Carbon ? $date : Carbon::parse($date);

        return static::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->where('type', 'Holiday')
            ->forTeam($employee->team_id)
            ->forBranch($employee->branch_id)
            ->orderBy('start_date')
            ->first();
    }

    /**
     * Holidays overlapping a date range for an employee (team/branch scoped).
     */
    public static function holidaysForEmployeeInRange(
        ?Employee $employee,
        string $from,
        string $to
    ): \Illuminate\Support\Collection {
        if (! $employee) {
            return collect();
        }

        return static::query()
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->where('type', 'Holiday')
            ->forTeam($employee->team_id)
            ->forBranch($employee->branch_id)
            ->orderBy('start_date')
            ->get()
            ->map(fn (self $holiday) => $holiday->toApiArray())
            ->values();
    }

    /**
     * Static helper to check if a date is any off day
     *
     * @param  Carbon|string|null  $date
     * @param  string|null $type  Optional: 'Weekend' or 'Holiday'
     * @return bool
     */
    public static function isOffDay($date = null, $type = null, $team_id = null)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date ?? now());

        $query = self::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);

        if ($type) {
            $query->where('type', $type);
        }

        // A holiday applies if it has NO teams assigned (Global) OR has the specific team
        $query->where(function ($q) use ($team_id) {
            $q->doesntHave('teams');
            if ($team_id) {
                $q->orWhereHas('teams', function ($sq) use ($team_id) {
                    $sq->where('teams.id', $team_id);
                });
            }
        });

        return $query->exists();
    }

    /**
     * Optional: Get the off day record for a given date
     *
     * @param  Carbon|string|null  $date
     * @param  string|null $type
     * @param  int|null $team_id
     * @return CompanyOffDay|null
     */
    public static function getOffDay($date = null, $type = null, $team_id = null)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date ?? now());

        $query = self::query()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date);

        if ($type) {
            $query->where('type', $type);
        }

        $query->where(function ($q) use ($team_id) {
            $q->doesntHave('teams');
            if ($team_id) {
                $q->orWhereHas('teams', function($sq) use ($team_id) {
                    $sq->where('teams.id', $team_id);
                });
            }
        });

        return $query->first();
    }
}
