<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Exceptions\InvalidBackupDestination;

class BackupController extends Controller
{
    public function index()
    {
        $this->authorize('isAdmin');

        try {
            $backups = $this->getBackupsList();
            $backupStatus = $this->getBackupStatus();
            $diskUsage = $this->getDiskUsage();

            return view('admin.backups.index', compact(
                'backups',
                'backupStatus',
                'diskUsage'
            ));
        } catch (\Exception $e) {
            Log::error('Failed to load backups page', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to load backups');
        }
    }

    public function trigger(Request $request)
    {
        $this->authorize('isAdmin');

        try {
            Artisan::call('backup:run', ['--only-db' => false]);

            Log::info('Manual backup triggered by admin', [
                'admin_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Backup initiated successfully. This may take a few minutes.',
            ]);
        } catch (\Exception $e) {
            Log::error('Manual backup failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cleanup(Request $request)
    {
        $this->authorize('isAdmin');

        try {
            Artisan::call('backup:clean');

            Log::info('Manual backup cleanup triggered by admin', [
                'admin_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Backup cleanup initiated. Old backups will be removed per retention policy.',
            ]);
        } catch (\Exception $e) {
            Log::error('Manual backup cleanup failed', [
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cleanup backups: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download($filename)
    {
        $this->authorize('isAdmin');

        try {
            $disk = Storage::disk('local');
            $path = 'backups/' . $filename;

            if (!$disk->exists($path)) {
                return response()->json(['error' => 'Backup file not found'], 404);
            }

            Log::info('Backup file downloaded by admin', [
                'filename' => $filename,
                'admin_id' => auth()->id(),
            ]);

            return Storage::disk('local')->download($path);
        } catch (\Exception $e) {
            Log::error('Failed to download backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to download backup'], 500);
        }
    }

    public function delete(Request $request, $filename)
    {
        $this->authorize('isAdmin');

        try {
            $disk = Storage::disk('local');
            $path = 'backups/' . $filename;

            if (!$disk->exists($path)) {
                return response()->json(['error' => 'Backup file not found'], 404);
            }

            $disk->delete($path);

            Log::warning('Backup file deleted by admin', [
                'filename' => $filename,
                'admin_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Backup file deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete backup', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete backup file',
            ], 500);
        }
    }

    protected function getBackupsList()
    {
        $backups = [];
        $disk = Storage::disk('local');
        $path = 'backups';

        if (!$disk->exists($path)) {
            return $backups;
        }

        $files = $disk->files($path);

        foreach ($files as $file) {
            $backups[] = [
                'filename' => basename($file),
                'path' => $file,
                'size' => $disk->size($file),
                'date' => $disk->lastModified($file),
                'readable_date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                'readable_size' => $this->formatBytes($disk->size($file)),
            ];
        }

        // Sort by date descending
        usort($backups, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $backups;
    }

    protected function getBackupStatus()
    {
        try {
            // Get the last backup file
            $backups = $this->getBackupsList();

            if (empty($backups)) {
                return [
                    'status' => 'no_backup',
                    'message' => 'No backups found',
                    'lastBackup' => null,
                ];
            }

            $lastBackup = $backups[0];
            $lastBackupTime = $lastBackup['date'];
            $hoursAgo = (time() - $lastBackupTime) / 3600;

            if ($hoursAgo < 24) {
                $status = 'healthy';
                $message = 'Last backup: ' . $lastBackup['readable_date'];
            } elseif ($hoursAgo < 48) {
                $status = 'warning';
                $message = 'Last backup over 24 hours ago';
            } else {
                $status = 'critical';
                $message = 'No recent backups found!';
            }

            return [
                'status' => $status,
                'message' => $message,
                'lastBackup' => $lastBackup['readable_date'],
                'hoursAgo' => round($hoursAgo, 1),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unable to determine backup status',
            ];
        }
    }

    protected function getDiskUsage()
    {
        try {
            $disk = Storage::disk('local');
            $path = 'backups';

            if (!$disk->exists($path)) {
                return [
                    'total' => 0,
                    'readable_total' => '0 B',
                    'file_count' => 0,
                ];
            }

            $files = $disk->files($path);
            $totalSize = 0;

            foreach ($files as $file) {
                $totalSize += $disk->size($file);
            }

            return [
                'total' => $totalSize,
                'readable_total' => $this->formatBytes($totalSize),
                'file_count' => count($files),
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'readable_total' => 'Unknown',
                'file_count' => 0,
            ];
        }
    }

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
