<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportExportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'module',
        'type',
        'status',
        'format',
        'original_filename',
        'stored_path',
        'filters',
        'columns',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'duplicate_mode',
        'error_summary',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function failures()
    {
        return $this->hasMany(ImportFailure::class);
    }
}
