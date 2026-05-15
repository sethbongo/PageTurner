<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ImportFailure extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_export_log_id',
        'row_number',
        'attribute',
        'errors',
        'values',
    ];

    protected $casts = [
        'errors' => 'array',
        'values' => 'array',
    ];

    public function log()
    {
        return $this->belongsTo(ImportExportLog::class, 'import_export_log_id');
    }
}
