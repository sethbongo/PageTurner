<?php

namespace Database\Seeders;

use App\Models\BackupMonitoring;
use Illuminate\Database\Seeder;

class BackupMonitoringSeeder extends Seeder
{
    public function run(): void
    {
        $backups = [
            [
                'backup_name' => 'daily-backup-db',
                'disk' => 'backups',
                'type' => 'full',
            ],
            [
                'backup_name' => 'daily-backup-files',
                'disk' => 'backups',
                'type' => 'incremental',
            ],
            [
                'backup_name' => 'weekly-backup-full',
                'disk' => 'backups',
                'type' => 'full',
            ],
        ];

        foreach ($backups as $backup) {
            for ($i = 0; $i < 10; $i++) {
                $completedAt = fake()->dateTimeBetween('-30 days', 'now');

                BackupMonitoring::create(array_merge($backup, [
                    'path' => "backups/" . $backup['backup_name'] . "/" . $completedAt->format('Y-m-d-His'),
                    'size_bytes' => rand(500000000, 5000000000), // 500MB to 5GB
                    'status' => fake()->randomElement(['completed', 'completed', 'completed', 'failed']),
                    'started_at' => $completedAt->clone()->subMinutes(rand(5, 30)),
                    'completed_at' => $completedAt,
                    'duration_seconds' => rand(300, 3600),
                    'verified' => rand(0, 1) === 1,
                    'verified_at' => rand(0, 1) === 1 ? $completedAt->clone()->addHours(rand(1, 24)) : null,
                    'health_status' => fake()->randomElement(['healthy', 'healthy', 'healthy', 'warning', 'critical']),
                    'next_backup_scheduled_at' => now()->addDays(1),
                ]));
            }
        }
    }
}
