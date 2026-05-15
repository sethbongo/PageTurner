@component('mail::message')
# 🚨 Critical Security Event Alert

**Event:** {{ $eventLabel }}
**Severity:** {{ $severity }}
**Time:** {{ $auditLog->created_at->format('M d, Y H:i:s') }}

---

## Event Details

| Field | Value |
|-------|-------|
| **User** | {{ $auditLog->user?->email ?? 'System' }} |
| **IP Address** | {{ $auditLog->metadata['ip_address'] ?? 'N/A' }} |
| **User Agent** | {{ $auditLog->metadata['user_agent'] ?? 'N/A' }} |
| **Method** | {{ $auditLog->metadata['method'] ?? 'N/A' }} |
| **URL** | {{ $auditLog->metadata['url'] ?? 'N/A' }} |

---

## Affected Resource

@if($auditLog->auditable_type)
    | Field | Value |
    |-------|-------|
    | **Type** | {{ class_basename($auditLog->auditable_type) }} |
    | **ID** | {{ $auditLog->auditable_id }} |
@endif

---

## Changes

@if($auditLog->old_values && $auditLog->new_values)
    ### Modified Fields:
    @foreach($auditLog->new_values as $key => $newValue)
        @if(isset($auditLog->old_values[$key]) && $auditLog->old_values[$key] !== $newValue)
            - **{{ ucfirst(str_replace('_', ' ', $key)) }}**
            - Old: `{{ $auditLog->old_values[$key] }}`
            - New: `{{ $newValue }}`
        @endif
    @endforeach
@elseif($auditLog->new_values)
    ### Created with fields:
    @foreach($auditLog->new_values as $key => $value)
        - {{ ucfirst(str_replace('_', ' ', $key)) }}: `{{ $value }}`
    @endforeach
@elseif($auditLog->old_values)
    ### Deleted fields:
    @foreach($auditLog->old_values as $key => $value)
        - {{ ucfirst(str_replace('_', ' ', $key)) }}: `{{ $value }}`
    @endforeach
@endif

---

## Security Checksum

For integrity verification:
```
{{ $auditLog->checksum }}
```

---

@component('mail::button', ['url' => route('audit.show', $auditLog), 'color' => 'error'])
View Full Details
@endcomponent

**Important:** This is a security alert. Please verify that this action was authorized. If you did not perform this
action, please contact your administrator immediately.

Thanks,
{{ config('app.name') }} Security Team
@endcomponent