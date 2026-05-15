# Audit Logging and Compliance Implementation Guide (4.3)

## Overview

A comprehensive audit logging system has been implemented to track all critical system events, provide compliance reporting, and ensure data integrity with tamper-proof checksums. The system logs authentication events, data modifications, security events, and system operations.

---

## Architecture

### Database Schema

#### `audit_logs` Table

```sql
- id (uuid) - Primary key
- user_id (nullable) - User who performed action
- event (string) - Event type (created, updated, deleted, login, logout, etc.)
- auditable_type (string) - Model class name (e.g., App\Models\Book)
- auditable_id (bigint) - Model instance ID
- old_values (json) - Previous attribute values
- new_values (json) - Updated attribute values
- metadata (json) - Request context (IP, URL, method, user agent)
- checksum (string) - SHA-256 tamper-proof hash
- archived (boolean) - Archival status
- created_at, updated_at
```

#### `archived_audit_logs` Table

Identical structure for long-term storage (5+ years).

### Core Components

#### 1. **AuditLog Model** (`app/Models/AuditLog.php`)

- UUID primary key for better security
- Scopes for filtering:
    - `byEvent($event)` - Filter by event type
    - `byUser($userId)` - Filter by user
    - `byModel($modelType)` - Filter by model type
    - `dateRange($from, $to)` - Filter by date range
    - `active()` - Only non-archived logs
    - `archived()` - Only archived logs
- Methods:
    - `getSensitiveFields()` - Lists fields never logged
    - `filterSensitiveData()` - Strips sensitive info
    - `getDiff()` - Returns old/new value comparison
    - `verifyChecksum()` - Validates log integrity

#### 2. **Auditable Trait** (`app/Traits/Auditable.php`)

Automatically logs CRUD operations on any model:

```php
use App\Traits\Auditable;

class Book extends Model {
    use Auditable;
}
```

Events captured:

- `created` - New record creation
- `updated` - Record modification
- `deleted` - Record deletion

#### 3. **AuditService** (`app/Services/AuditService.php`)

Central service for creating audit logs:

```php
// Log a model change
app(AuditService::class)->log(
    'updated',
    $model,
    $oldValues,
    $newValues,
    $metadata
);

// Log authentication events
app(AuditService::class)->logLogin($userId);
app(AuditService::class)->logLogout($userId);
app(AuditService::class)->logFailedLogin($email);
app(AuditService::class)->logPasswordChange($userId);

// Log security events
app(AuditService::class)->log2FAToggle($userId, $enabled);
app(AuditService::class)->logRoleChange($userId, $oldRole, $newRole);

// Log system events
app(AuditService::class)->logBackup('success', 'Full backup completed');
app(AuditService::class)->logImportExport('import', 'csv', 150);
```

**Key Features:**

- Automatic metadata collection (IP, user agent, URL, method)
- Sensitive field filtering (passwords, tokens, etc.)
- SHA-256 checksum generation for tamper-proofing
- Request context preservation

#### 4. **Authentication Listener** (`app/Listeners/AuthenticationEventListener.php`)

Automatically logs Laravel authentication events:

- Login/Logout
- Failed login attempts
- Password reset
- 2FA toggle

#### 5. **Critical Event Notification** (`app/Notifications/CriticalAuditEventNotification.php`)

Sends email alerts for critical security events:

- Role changes
- 2FA disabled
- Password changed
- Force deletes
- Backup failures

---

## Features

### 1. Comprehensive Audit Logging

**Audited Events:**

- **Authentication:** Login, logout, failed attempts, password changes, 2FA enable/disable
- **Data Modifications:** Book CRUD, category changes, order status updates, review moderation
- **Security Events:** Permission changes, role assignments, email verification
- **System Events:** Backup operations, imports/exports, settings changes

**Sensitive Field Exclusions:**
Default excluded fields (from `app/Models/AuditLog.php`):

```php
- password
- password_confirmation
- remember_token
- two_factor_secret
- two_factor_recovery_codes
- api_token, access_token, refresh_token
- card_number, cvv, stripe_token
- payment_method_id, credit_card
- phone, social_security_number
```

### 2. Tamper-Proof Storage

Each audit log has a SHA-256 checksum that can be verified:

```php
$auditService = app(AuditService::class);
$isValid = $auditService->verifyChecksum($auditLog);
```

Checksum is based on:

- Event type
- Model class and ID
- User ID
- Old/new values
- Application key (prevents tampering)

### 3. Admin Dashboard (`/admin/audit`)

**Features:**

- Searchable/filterable logs with multiple filters:
    - Event type
    - Model type
    - User
    - Date range
    - IP address/URL search
    - Critical events only checkbox
    - Show archived checkbox
- Statistics cards:
    - Total logs
    - Today's logs
    - Critical events count
    - Archived logs count
- Detailed view for each log:
    - Request metadata (IP, user agent, URL, method)
    - Side-by-side diff of old/new values
    - Checksum validation indicator
- CSV/PDF export of filtered results
- Pagination (50 logs per page)

**Accessing the Dashboard:**

```
/admin/audit
```

### 4. Export Capabilities

**CSV Export:**

- Headers: ID, User, Event, Model Type, Model ID, IP, URL, Method, Created At, Old Values, New Values
- Accessible via button on dashboard
- Maintains all data integrity

**PDF Export:**

- Professional report format
- Includes metadata and statistics
- Suitable for compliance documentation
- Date-stamped file names

### 5. Real-Time Critical Event Alerts

Critical events automatically trigger email notifications:

- Role changed
- 2FA disabled
- Password changed
- Force deleted
- Backup failed

Email includes:

- Event details
- Request metadata
- Affected resource information
- Changes comparison
- Security checksum for verification
- Link to detailed audit log

---

## Retention Policy

### Active Logs (1 Year)

- Kept in `audit_logs` table
- Indexed for fast queries
- Accessible via dashboard

### Archived Logs (5 Years)

- Moved to `archived_audit_logs` table
- Suitable for compliance audits
- Can be queried if needed

### Automatic Archival

Run the archival command:

```bash
php artisan audit:archive
```

**Options:**

```bash
php artisan audit:archive --days=365  # Archive logs older than 1 year
```

**Schedule for automatic execution:**

```php
// In app/Console/Kernel.php
$schedule->command('audit:archive')
    ->dailyAt('02:00')
    ->onSuccess(function () {
        Log::info('Audit logs archived successfully');
    })
    ->onFailure(function () {
        Log::error('Audit archival failed');
    });
```

---

## Implementation Details

### Adding Audit Logging to Models

Simply add the `Auditable` trait:

```php
<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use Auditable;

    protected $fillable = [
        'name',
        'description',
        // ... other fields
    ];
}
```

**Automatic behavior:**

- Model creation logs `created` event with new values
- Model update logs `updated` event with old/new values
- Model deletion logs `deleted` event with old values

### Custom Audit Events

For complex business logic:

```php
use App\Services\AuditService;

class OrderStatusController extends Controller
{
    public function updateStatus(Order $order, AuditService $auditService)
    {
        $oldStatus = $order->status;
        $order->status = 'shipped';
        $order->save();

        // Log custom event
        $auditService->log(
            'order_shipped',
            $order,
            ['status' => $oldStatus],
            ['status' => 'shipped'],
            [
                'reason' => 'Manually shipped by admin',
                'carrier' => 'UPS',
                'tracking' => '1Z999AA10123456784',
            ]
        );
    }
}
```

### Listening to Authentication Events

The `AuthenticationEventListener` automatically logs:

```php
// Automatically logged by the listener
Auth::attempt(['email' => $email, 'password' => $password]); // Logs login
Auth::logout(); // Logs logout
Auth::guard()->logout(); // Logs logout
```

---

## Configuration

Edit `config/audit.php`:

```php
return [
    // Retention period for active logs (days)
    'retention_days' => 365,

    // Retention period for archived logs (years)
    'archive_retention_years' => 5,

    // Enable/disable globally
    'enabled' => true,

    // Sensitive fields to exclude
    'sensitive_fields' => [
        'password',
        'api_token',
        // ... add more
    ],

    // Critical events triggering notifications
    'critical_events' => [
        'role_changed',
        'force_deleted',
        '2fa_disabled',
        // ... add more
    ],

    // Email recipients for alerts
    'notification_emails' => 'admin@example.com,security@example.com',
];
```

### Environment Variables

Add to `.env`:

```env
AUDIT_ENABLED=true
AUDIT_RETENTION_DAYS=365
AUDIT_ARCHIVE_RETENTION_YEARS=5
AUDIT_NOTIFICATION_EMAILS=admin@example.com,security@example.com
AUDIT_LOG_TO_FILE=false
```

---

## API Routes

### Dashboard

- `GET /admin/audit` - View audit logs with filters
- `GET /admin/audit/{id}` - View single audit log details

### Exports

- `GET /admin/audit/export/csv` - Export filtered logs as CSV
- `GET /admin/audit/export/pdf` - Export filtered logs as PDF

### Statistics

- `GET /admin/audit/api/statistics` - Get statistics (JSON)

    ```json
    {
        "total_logs": 1500,
        "today_logs": 42,
        "critical_events": 3,
        "archived_logs": 0
    }
    ```

- `GET /admin/audit/api/critical` - Get recent critical events (JSON)

---

## Security Considerations

### 1. Access Control

- Dashboard restricted to admin users only
- Use gate: `Gate::define('isAdmin')`
- Routes protected with `access_control:admin` middleware

### 2. Checksum Verification

```php
$isValid = app(AuditService::class)->verifyChecksum($auditLog);
if (!$isValid) {
    // Log integrity compromised
    Log::alert("Audit log $auditLog->id checksum verification failed");
}
```

### 3. Sensitive Data Protection

- Passwords, tokens, payment info never logged
- Customizable exclusion list in config
- Automatic filtering in AuditService

### 4. Tamper Detection

- Each log entry has unique checksum
- Checksums verified on display
- Dashboard shows integrity status
- Can detect unauthorized modifications

---

## Troubleshooting

### Logs Not Recording

1. Check `AUDIT_ENABLED` in `.env`
2. Verify middleware is active: `access_control:admin`
3. Ensure `Auditable` trait is added to models

### Export Issues

- **CSV not downloading:** Check Excel package installation
- **PDF not downloading:** Check DomPDF package installation
    ```bash
    composer require barryvdh/laravel-dompdf
    ```

### Performance Issues

- **Slow queries:** Run migrations to create indexes
- **Large audit logs:** Run archival command regularly
- **Database size:** Monitor archived_audit_logs table

### Checksum Mismatches

- Indicates potential data tampering
- Check database for unauthorized modifications
- Review application logs for errors

---

## Testing

### Manual Testing

```bash
# 1. Run migrations
php artisan migrate

# 2. Create test data
php artisan tinker
>>> $book = Book::create([...])
>>> // Should create audit log

# 3. Check dashboard
# Visit /admin/audit (must be logged in as admin)

# 4. Test export
# Click "Export CSV" or "Export PDF"

# 5. Test archival
php artisan audit:archive
```

### Verification

```bash
# Check audit logs in database
php artisan tinker
>>> AuditLog::count()  // Should show created logs
>>> AuditLog::where('event', 'created')->count()
>>> AuditLog::where('user_id', 1)->get()
```

---

## Maintenance

### Daily

- Automatically logs all system events
- Critical events trigger email alerts

### Weekly

- Review critical events
- Check for anomalies in logs
- Verify email notifications working

### Monthly

- Export compliance report (CSV/PDF)
- Review audit statistics
- Check database size

### Annually

- Run archival command before year-end
- Export final compliance reports
- Review and update retention policies
- Audit checksum integrity

---

## Compliance

This audit logging system supports:

- **SOC 2 Type II:** Complete event logging and audit trails
- **GDPR:** Data modification tracking and right to be forgotten
- **PCI DSS:** Authentication and access control logs
- **HIPAA:** Complete audit trail of medical record changes
- **ISO 27001:** Information security event logging

Maintain logs for compliance period specified by your jurisdiction.

---

## Support

For issues or questions:

1. Check configuration in `config/audit.php`
2. Review environment variables in `.env`
3. Check application logs in `storage/logs/`
4. Verify database migrations: `php artisan migrate:status`
5. Test with: `php artisan audit:archive --help`

---

## Next Steps

1. **Run Migrations:** `php artisan migrate`
2. **Configure Settings:** Update `config/audit.php` and `.env`
3. **Test Dashboard:** Visit `/admin/audit`
4. **Schedule Archival:** Add to application scheduler
5. **Monitor Alerts:** Set up email recipient configuration
6. **Document Policies:** Create retention policies document
7. **Train Admins:** Show team the dashboard and features

---

**Implementation Date:** May 15, 2026  
**Version:** 1.0.0  
**Status:** Production Ready
