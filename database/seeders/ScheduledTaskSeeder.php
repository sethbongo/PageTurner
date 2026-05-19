<?php

namespace Database\Seeders;

use App\Models\ScheduledTask;
use Illuminate\Database\Seeder;

class ScheduledTaskSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = [
            [
                'name' => 'Database Backup',
                'type' => 'backup',
                'description' => 'Daily database backup at 2 AM',
                'command' => 'backup:run',
                'expression' => '0 2 * * *',
                'enabled' => true,
            ],
            [
                'name' => 'Clear Log Files',
                'type' => 'maintenance',
                'description' => 'Clear old log files weekly',
                'command' => 'logs:clear',
                'expression' => '0 3 * * 0',
                'enabled' => true,
            ],
            [
                'name' => 'Archive Audit Logs',
                'type' => 'archive',
                'description' => 'Archive audit logs older than 90 days',
                'command' => 'audit-logs:archive',
                'expression' => '0 4 1 * *',
                'enabled' => true,
            ],
            [
                'name' => 'Process Import Queue',
                'type' => 'queue',
                'description' => 'Process pending imports every 5 minutes',
                'command' => 'queue:work --timeout=60 --tries=3',
                'expression' => '*/5 * * * *',
                'enabled' => true,
            ],
            [
                'name' => 'Clean Up Temporary Files',
                'type' => 'maintenance',
                'description' => 'Remove temporary export files daily',
                'command' => 'storage:clean-temp',
                'expression' => '0 5 * * *',
                'enabled' => true,
            ],
            [
                'name' => 'Health Check',
                'type' => 'monitoring',
                'description' => 'Hourly health check of system resources',
                'command' => 'system:health-check',
                'expression' => '0 * * * *',
                'enabled' => true,
            ],
            [
                'name' => 'Sync Cache',
                'type' => 'cache',
                'description' => 'Sync cache with database',
                'command' => 'cache:sync',
                'expression' => '*/15 * * * *',
                'enabled' => false,
            ],
        ];

        foreach ($tasks as $task) {
            ScheduledTask::create(array_merge($task, [
                'last_run_at' => fake()->dateTimeBetween('-30 days'),
                'next_run_at' => now()->addMinutes(rand(5, 1440)),
                'last_status' => fake()->randomElement(['completed', 'completed', 'completed', 'failed']),
                'run_count' => rand(10, 100),
                'failure_count' => rand(0, 10),
                'duration_ms' => rand(100, 5000),
            ]));
        }
    }
}
