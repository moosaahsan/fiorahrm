<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Performance\EmployeeOfTheMonth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CelebrationService
{
    public function forUser(User $user, int $days = 7): array
    {
        $days = max(1, min($days, 30));
        $cacheKey = "celebrations:v6:{$user->id}:{$days}";

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        Auth::setUser($user);
        $user->loadMissing('employee');

        $payload = $this->buildPayload($user, $days);

        if (! empty($payload['celebrations'])) {
            Cache::put($cacheKey, $payload, 300);
        }

        return $payload;
    }

    public function clearCacheForUser(int $userId): void
    {
        foreach ([7, 14, 30] as $days) {
            Cache::forget("celebrations:v6:{$userId}:{$days}");
            Cache::forget("celebrations:v5:{$userId}:{$days}");
            Cache::forget("celebrations:v4:{$userId}:{$days}");
            Cache::forget("celebrations:v3:{$userId}:{$days}");
            Cache::forget("celebrations:{$userId}:{$days}");
        }
    }

    private function buildPayload(User $user, int $days): array
    {
        $timezone = app_settings('app_timezone') ?? 'Asia/Karachi';
        $start = now($timezone);

        $teamId = $user->employee?->team_id;
        $employeeId = $user->employee?->id;

        $processed = $this->collectBirthdaysAndAnniversaries(
            $this->teamScopedEmployeeQuery($teamId, $employeeId),
            $start,
            $days
        );

        $processed = $processed->merge(
            $this->collectEmployeeOfTheMonth($teamId, $employeeId)
        );

        $sorted = $processed->sortBy(fn ($item) => [
            $item['type'] === 'eotm' ? 0 : 1,
            $item['days_until'],
        ])->values();

        return [
            'window_days' => $days,
            'timezone' => $timezone,
            'scope' => [
                'mode' => 'team',
                'team_id' => $teamId,
            ],
            'celebrations' => $sorted->map(fn ($item) => $this->formatCelebration($item))->all(),
        ];
    }

    /**
     * Only same-team employees (or self when team is not assigned).
     */
    private function teamScopedEmployeeQuery(?int $teamId, ?int $employeeId): Builder
    {
        if ($teamId) {
            return Employee::query()->where('team_id', $teamId);
        }

        if ($employeeId) {
            return Employee::query()->where('id', $employeeId);
        }

        return Employee::query()->whereRaw('1=0');
    }

    private function collectBirthdaysAndAnniversaries(Builder $empQuery, Carbon $start, int $days): Collection
    {
        $isSqlite = DB::getDriverName() === 'sqlite';
        $dobSql = $isSqlite ? "strftime('%m-%d', dob)" : "DATE_FORMAT(dob, '%m-%d')";
        $jdSql = $isSqlite ? "strftime('%m-%d', joining_date)" : "DATE_FORMAT(joining_date, '%m-%d')";

        $startMD = $start->format('m-d');
        $endMD = $start->copy()->addDays($days)->format('m-d');

        $employees = $empQuery
            ->with('team:id,name')
            ->whereNull('resign_date')
            ->where(function ($query) use ($startMD, $endMD, $dobSql, $jdSql) {
                $query->where(function ($q) use ($startMD, $endMD, $dobSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$dobSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$dobSql >= ?", [$startMD])
                            ->orWhereRaw("$dobSql <= ?", [$endMD]);
                    }
                })->orWhere(function ($q) use ($startMD, $endMD, $jdSql) {
                    if ($startMD <= $endMD) {
                        $q->whereRaw("$jdSql BETWEEN ? AND ?", [$startMD, $endMD]);
                    } else {
                        $q->whereRaw("$jdSql >= ?", [$startMD])
                            ->orWhereRaw("$jdSql <= ?", [$endMD]);
                    }
                });
            })
            ->get(['id', 'name', 'position', 'profile_pic', 'dob', 'joining_date', 'team_id']);

        $processed = collect();

        foreach ($employees as $emp) {
            if ($emp->dob) {
                $dob = Carbon::parse($emp->dob, $start->timezone);
                $bday = $dob->copy()->year($start->year)->startOfDay();
                if ($bday->format('m-d') < $start->format('m-d')) {
                    $bday->addYear();
                }

                $daysUntil = calendar_days_until($start, $bday);
                if ($daysUntil >= 0 && $daysUntil <= $days) {
                    $processed->push([
                        'employee' => $emp,
                        'type' => 'birthday',
                        'date' => $bday,
                        'days_until' => $daysUntil,
                        'is_today' => $daysUntil === 0,
                    ]);
                }
            }

            if ($emp->joining_date) {
                $jd = Carbon::parse($emp->joining_date, $start->timezone);
                $anniv = $jd->copy()->year($start->year)->startOfDay();
                if ($anniv->format('m-d') < $start->format('m-d')) {
                    $anniv->addYear();
                }

                $years = $anniv->year - $jd->year;
                $daysUntil = calendar_days_until($start, $anniv);

                if ($years > 0 && $daysUntil >= 0 && $daysUntil <= $days) {
                    $processed->push([
                        'employee' => $emp,
                        'type' => 'anniversary',
                        'date' => $anniv,
                        'years' => $years,
                        'days_until' => $daysUntil,
                        'is_today' => $daysUntil === 0,
                    ]);
                }
            }
        }

        return $processed;
    }

    /**
     * Latest EOTM — only if the winner belongs to the viewer's team.
     */
    private function collectEmployeeOfTheMonth(?int $teamId, ?int $employeeId): Collection
    {
        $latest = EmployeeOfTheMonth::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->first();

        if (! $latest) {
            return collect();
        }

        $query = EmployeeOfTheMonth::query()
            ->with(['employee' => fn ($q) => $q->withTrashed()->with('team:id,name')])
            ->where('year', $latest->year)
            ->where('month', $latest->month)
            ->whereHas('employee');

        if ($teamId) {
            $query->whereHas('employee', fn ($q) => $q->where('team_id', $teamId));
        } elseif ($employeeId) {
            $query->where('employee_id', $employeeId);
        } else {
            return collect();
        }

        return $query->get()
            ->filter(fn ($eotm) => $eotm->employee !== null)
            ->map(fn ($eotm) => [
                'employee' => $eotm->employee,
                'type' => 'eotm',
                'date' => Carbon::create($eotm->year, $eotm->month, 1),
                'days_until' => 0,
                'is_today' => false,
                'eotm_month' => $eotm->month,
                'eotm_year' => $eotm->year,
                'bio_comments' => $eotm->bio_comments,
            ]);
    }

    private function formatCelebration(array $item): array
    {
        /** @var Employee $employee */
        $employee = $item['employee'];

        $payload = [
            'type' => $item['type'],
            'date' => $item['date']->toDateString(),
            'days_until' => $item['days_until'],
            'is_today' => (bool) ($item['is_today'] ?? false),
            'employee' => $this->formatEmployee($employee),
        ];

        if ($item['type'] === 'anniversary') {
            $payload['years'] = $item['years'];
        }

        if ($item['type'] === 'eotm') {
            $payload['month'] = $item['eotm_month'];
            $payload['year'] = $item['eotm_year'];
            $payload['month_label'] = Carbon::create($item['eotm_year'], $item['eotm_month'], 1)->format('F Y');
            $payload['bio_comments'] = $item['bio_comments'];
        }

        return $payload;
    }

    private function formatEmployee(Employee $employee): array
    {
        $picUrl = get_profile_picture_url($employee->profile_pic);
        if ($picUrl && ! str_starts_with($picUrl, 'http')) {
            $picUrl = url($picUrl);
        }

        return [
            'id' => $employee->id,
            'name' => $employee->name,
            'position' => $employee->position,
            'profile_pic_url' => $picUrl,
            'team' => $employee->team
                ? ['id' => $employee->team->id, 'name' => $employee->team->name]
                : null,
        ];
    }
}
