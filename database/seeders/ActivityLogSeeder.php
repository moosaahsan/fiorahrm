<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();

        foreach (range(1, 100) as $i) {
            ActivityLog::create([
                'user_id'    => $users[array_rand($users)] ?? null,
                'action'     => collect(['view', 'edit', 'update', 'delete', 'restore'])->random(),
                'module'     => 'LateArrival',
                'description'=> 'Dummy log #' . $i,
                'ip_address' => fake()->ipv4,
                'user_agent' => fake()->userAgent(),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
