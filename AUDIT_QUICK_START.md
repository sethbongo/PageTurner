# Audit Logging - Quick Start Guide

## Installation (5 minutes)

### 1. Run Database Migrations

```bash
php artisan migrate
```

This creates:

- `audit_logs` table - Active audit logs (1 year)
- `archived_audit_logs` table - Archive storage (5+ years)

### 2. Configure Environment Variables

Edit `.env`:

```env
AUDIT_ENABLED=true
AUDIT_RETENTION_DAYS=365
AUDIT_NOTIFICATION_EMAILS=admin@example.com,security@example.com
```

### 3. Clear Cache

```bash
php artisan config:clear
```

---

## First Steps (10 minutes)

### 1. Access the Dashboard

```
http://yourapp.local/admin/audit
```

(Requires admin login)

### 2. View Current Audit Logs

- Dashboard shows all recent audit logs
- Statistics cards display key metrics
- Table shows 50 most recent logs with pagination

### 3. Test Filters

Try filtering by:

- Event type (created, updated, deleted, login, etc.)
- Model type (Book, Order, User, etc.)
- User (who made the change)
- Date range
- Critical events only

### 4. Export a Report

Click:

- **Export CSV** - For spreadsheet analysis
- **Export PDF** - For compliance documentation

### 5. View a Log Entry

Click "View" on any log to see:

- Detailed request metadata
- Side-by-side diff of changes
- Checksum integrity status
- Timestamp and user info

---

## Automatic Event Logging

Once you run migrations, these events are automatically logged:

### For Any Model with Auditable Trait

```php
Book::create([...]) // Logs "created" event
$book->update([...]) // Logs "updated" event
$book->delete() // Logs "deleted" event
```

### Already Auditable Models

- `Book` - All CRUD operations
- `Order` - Status changes and modifications
- `Category` - Name, description changes
- `Review` - Creation and moderation
- `User` - Role changes, email updates

---

## Common Tasks

### Add Audit Logging to a New Model

**Step 1:** Add the trait

```php
use App\Traits\Auditable;

class MyModel extends Model {
    use Auditable;
}
```

**Step 2:** Done! All CRUD operations now logged automatically.

### Log a Custom Event

```php
use App\Services\AuditService;

class OrderController extends Controller {
    public function shipOrder(Order $order, AuditService $auditService)
    {
        $order->status = 'shipped';
        $order->save();

        // Log the specific event
        $auditService->log(
            'order_shipped',
            $order,
            ['status' => 'pending'],
            ['status' => 'shipped'],
            ['tracking_number' => '123ABC']
        );
    }
}
```

### Log Authentication Events

Already automatic! These are logged:

- User login
- User logout
- Failed login attempt
- Password change

### Schedule Automatic Archival

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Archive logs older than 1 year daily at 2 AM
    $schedule->command('audit:archive')
        ->dailyAt('02:00');
}
```

Then run:

```bash
php artisan audit:archive
```

---

## Checking Logs Manually

### Via Tinker

```bash
php artisan tinker

# Count total logs
>>> AuditLog::count()

# Find logs by event
>>> AuditLog::where('event', 'created')->count()

# Find logs by user
>>> AuditLog::where('user_id', 1)->get()

# Find logs by model
>>> AuditLog::where('auditable_type', 'App\\Models\\Book')->count()

# Find recent logs
>>> AuditLog::latest()->limit(10)->get()

# Verify checksum
>>> $log = AuditLog::first()
>>> app(AuditService::class)->verifyChecksum($log) // true or false
```

### Via Database Client

```sql
-- Count logs by event
SELECT event, COUNT(*) as count FROM audit_logs GROUP BY event;

-- Recent critical events
SELECT * FROM audit_logs
WHERE event IN ('role_changed', 'force_deleted', '2fa_disabled')
ORDER BY created_at DESC
LIMIT 10;

-- Logs by specific user
SELECT * FROM audit_logs
WHERE user_id = 1
ORDER BY created_at DESC;

-- Check archived logs
SELECT COUNT(*) FROM archived_audit_logs;
```

---

## Understanding the Dashboard

### Statistics Cards

- **Total Logs** - All audit entries ever created
- **Today's Logs** - Logs created in last 24 hours
- **Critical Events** - High-security events (role changes, 2FA disabled, etc.)
- **Archived Logs** - Logs moved to long-term storage

### Log Table Columns

| Column     | Meaning                                                      |
| ---------- | ------------------------------------------------------------ |
| Date/Time  | When the action occurred                                     |
| User       | Who performed the action (email or "System")                 |
| Event      | What type of action (created, updated, deleted, login, etc.) |
| Model      | What was affected (Book, Order, User, etc.)                  |
| IP Address | Where the request came from                                  |
| Status     | Active or Archived                                           |
| Actions    | "View" to see full details                                   |

### Color Coding

- 🔴 **Red** - Critical events (role_changed, force_deleted, 2fa_disabled)
- 🟡 **Yellow** - CRUD operations (created, deleted)
- 🟢 **Green** - Other events (updated, login, logout)

---

## Alert Configuration

### Critical Events That Trigger Emails

These events send email notifications to `AUDIT_NOTIFICATION_EMAILS`:

- `role_changed` - User role changed
- `force_deleted` - Record permanently deleted
- `2fa_disabled` - Two-factor authentication disabled
- `password_changed` - User password changed
- `backup_failed` - Backup operation failed
- `failed_login` - Failed login attempt
- `permission_granted` - New permission given
- `permission_revoked` - Permission removed

### Email Includes

- Event details
- What changed (old vs new values)
- Who did it (email address)
- When it happened
- Where it came from (IP address)
- Link to view full details in dashboard

### Customize Email Recipients

Edit `.env`:

```env
AUDIT_NOTIFICATION_EMAILS=admin@example.com,security@example.com,auditor@example.com
```

---

## Exported Data

### CSV Export Contains

| Column     | Description                            |
| ---------- | -------------------------------------- |
| ID         | Unique log identifier                  |
| User       | Email of user who performed action     |
| Event      | Type of event (created, updated, etc.) |
| Model Type | What was affected                      |
| Model ID   | ID of affected record                  |
| IP Address | Request origin IP                      |
| URL        | Full request URL                       |
| Method     | HTTP method (GET, POST, PUT, DELETE)   |
| Created At | Timestamp of action                    |
| Old Values | JSON of previous values                |
| New Values | JSON of new values                     |

### PDF Export Contains

- Professional formatted report
- Date range and filter information
- Complete log table
- Event color coding
- Page numbers
- Compliance footer

---

## Troubleshooting

### Q: I don't see any logs in the dashboard

**A:**

1. Make sure you're logged in as an admin user
2. Run migrations: `php artisan migrate`
3. Check `AUDIT_ENABLED=true` in `.env`
4. Create some test data: `Book::create(['title' => 'Test'])`

### Q: Emails aren't sending for critical events

**A:**

1. Verify `AUDIT_NOTIFICATION_EMAILS` in `.env`
2. Check Laravel mail configuration in `config/mail.php`
3. For local testing, use Mailtrap or MailHog
4. Test mail: `Mail::raw('test', fn($m) => $m->to('your@email.com'))`

### Q: How do I delete old logs?

**A:**
Use the archival command (keeps logs in archive table):

```bash
php artisan audit:archive
```

To permanently delete logs older than 5 years:

```bash
php artisan audit:archive --days=1825  # ~5 years
```

### Q: Checksum showing as invalid?

**A:**
This indicates potential tampering. Review:

1. Application error logs
2. Direct database queries (check if logs were modified)
3. Application key changes (restores to fresh app key)

### Q: Dashboard is slow?

**A:**

1. Archive old logs: `php artisan audit:archive`
2. Check database indexes: `php artisan tinker`
3. Reduce filter results (use date range)

---

## Security Notes

### What's NOT Logged

These sensitive fields are automatically excluded:

- Passwords
- Two-factor authentication secrets
- API tokens and access tokens
- Payment card information
- Social security numbers
- Phone numbers

### What IS Logged

- User IP address and browser info
- Complete request URL and method
- All model changes (old and new values)
- User email and role
- Exact timestamp
- Tamper-proof checksum

### Preventing Tampering

Each audit log has a SHA-256 checksum that includes:

- Event type
- Model information
- Changed values
- User ID
- Application key

Changing any data after logging invalidates the checksum.

---

## Performance Tips

### 1. Archive Regularly

Run archival before logs get too large:

```bash
php artisan audit:archive
```

### 2. Use Filters on Dashboard

Don't load all logs at once—use filters to find what you need

### 3. Export Large Datasets

For historical analysis, export to CSV rather than viewing in browser

### 4. Schedule Automatic Archival

Add to scheduler for hands-free maintenance:

```bash
0 2 * * * cd /path && php artisan audit:archive
```

---

## Next Steps

1. ✅ Run migrations: `php artisan migrate`
2. ✅ Configure `.env` with notification email
3. ✅ Visit `/admin/audit` dashboard
4. ✅ Create some test data to see logs
5. ✅ Try exporting to CSV/PDF
6. ✅ Schedule automatic archival in scheduler
7. ✅ Review critical events regularly

---

## Need Help?

- **Dashboard not working?** Check if you're logged in as admin
- **Missing logs?** Verify `Auditable` trait added to models
- **Emails not sending?** Check `AUDIT_NOTIFICATION_EMAILS` and mail config
- **Database issues?** Run `php artisan migrate:status`

For complete documentation, see `AUDIT_LOGGING_GUIDE.md`

---

**Quick Reference:**

- Dashboard: `/admin/audit`
- Run archival: `php artisan audit:archive`
- Check logs: `php artisan tinker` → `AuditLog::count()`
- Clear cache: `php artisan config:clear`

Happy auditing! 🔐
