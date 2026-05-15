<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AuditLogsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $auditLogs;

    public function __construct($auditLogs)
    {
        $this->auditLogs = $auditLogs;
    }

    public function collection()
    {
        return $this->auditLogs;
    }

    public function headings(): array
    {
        return [
            'ID',
            'User',
            'Event',
            'Model Type',
            'Model ID',
            'IP Address',
            'URL',
            'Method',
            'Created At',
            'Old Values',
            'New Values',
        ];
    }

    public function map($auditLog): array
    {
        return [
            $auditLog->id,
            $auditLog->user?->email ?? 'System',
            $auditLog->event,
            $auditLog->auditable_type,
            $auditLog->auditable_id,
            $auditLog->metadata['ip_address'] ?? 'N/A',
            $auditLog->metadata['url'] ?? 'N/A',
            $auditLog->metadata['method'] ?? 'N/A',
            $auditLog->created_at->format('Y-m-d H:i:s'),
            json_encode($auditLog->old_values),
            json_encode($auditLog->new_values),
        ];
    }
}
