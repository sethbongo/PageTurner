# Automated Backup and Maintenance System (4.2) - Setup Guide

## Overview

This document covers the complete setup and configuration of the automated backup and maintenance system for the PageTurner bookstore application. The system provides:

- **Automated Daily Backups** at 02:00 AM (UTC)
- **Weekly Full Backups** every Sunday
- **Retention Policy** enforcement (7 daily, 4 weekly, 12 monthly)
- **Automated Maintenance Tasks** (session cleanup, log rotation, etc.)
- **Email Notifications** for backup failures and success summaries
- **Admin Dashboard** for manual backup triggers and management

---

## Architecture & Components

### 4.2.1 Database Backup Scheduling

**Package Used:** `spatie/laravel-backup` (v9.3+)

**Configuration File:** `config/backup.php`

**Backup Strategy:**

- **Daily backups** at 02:00 AM UTC (optimized to avoid DST issues)
- **Database dumps** using mysqldump with UTF-8 encoding
- **File backups** including book covers and system files
- **Compression** using GZIP format
- **Encryption** support (optional, configure via `BACKUP_ENCRYPTION_PASSWORD`)

**Retention Policy:**

```php
'keep_count' => [
    'keep_daily_backups_for_days' => 7,
    'keep_weekly_backups_for_weeks' => 4,
    'keep_monthly_backups_for_months' => 12,
]
```

### 4.2.2 Maintenance Scheduling

**Scheduled Tasks** (defined in `routes/console.php`):

| Task                    | Frequency | Time            | Description                             |
| ----------------------- | --------- | --------------- | --------------------------------------- |
| `backup:run`            | Daily     | 02:00 AM        | Full database and files backup          |
| `backup:clean`          | Daily     | 03:00 AM        | Remove old backups per retention policy |
| `order:cleanup-pending` | Hourly    | Every hour      | Cancel pending orders > 24 hours old    |
| `session:cleanup`       | Daily     | Daily           | Clear expired sessions                  |
| `log:rotate`            | Weekly    | Monday 01:00 AM | Archive and compress old logs           |
| `report:daily-sales`    | Daily     | 06:00 AM        | Generate daily sales report             |
| `notification:prune`    | Weekly    | Sunday 04:00 AM | Delete notifications > 90 days old      |
| `audit:archive`         | Monthly   | 1st at 05:00 AM | Archive audit logs > 1 year old         |
| `backup:monitor`        | Weekly    | Sunday 07:00 AM | Backup health checks                    |

---

## Setup Instructions

### 1. Install Dependencies

The spatie/laravel-backup package is already in `composer.json`. Run:

```bash
composer install
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

### 2. Environment Configuration

Add the following to your `.env` file:

```bash
# Email Configuration for Backup Notifications
BACKUP_ADMIN_EMAIL=admin@pageturner-bookstore.com
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_FROM_ADDRESS=notifications@pageturner-bookstore.com
MAIL_FROM_NAME="${APP_NAME}"

# Backup Encryption (Optional)
BACKUP_ENCRYPTION_PASSWORD=your_secure_password_here

# Slack Notifications (Optional)
BACKUP_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK

# MySQL Paths (if not in system PATH)
# MYSQLDUMP_PATH=/usr/bin/mysqldump
# MYSQL_RESTORE_PATH=/usr/bin/mysql
```

### 3. Database Migrations

Run the migration to create the audit logs table:

```bash
php artisan migrate
```

This creates the `audit_logs` table used by the `audit:archive` command.

### 4. Storage Configuration

Ensure the following directories exist and have proper permissions:

```bash
# Create backup storage directories
mkdir -p storage/app/backups
mkdir -p storage/logs/archives
chmod 755 storage/app/backups
chmod 755 storage/logs/archives

# For S3 backup destination (optional)
# Configure AWS credentials in .env
AWS_ACCESS_KEY_ID=your_key
AWS_SECRET_ACCESS_KEY=your_secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-backup-bucket
```

### 5. Server Cron Configuration

**Critical Step:** Configure your server to run Laravel's scheduler every minute.

Add this line to your server's **crontab** (via `crontab -e`):

```bash
* * * * * cd /path/to/pageturner-bookstore && php artisan schedule:run >> /dev/null 2>&1
```

**For Shared Hosting:**
If you don't have access to crontab, ask your hosting provider to set up a Cron job that calls:

```
php /home/username/public_html/pageturner-bookstore/artisan schedule:run
```

**Verify Cron is Working:**

```bash
# Check if cron runs successfully
tail -f /var/log/syslog | grep artisan

# Or check Laravel logs
tail -f storage/logs/laravel.log | grep "schedule:run"
```

### 6. File Storage Disks

Configure backup destinations in `config/backup.php`. The system supports multiple disks:

**Local Storage (Default):**

```php
'destination' => [
    'disks' => [
        'local',
    ],
],
```

**S3 Cloud Storage (Optional):**

```php
'destination' => [
    'disks' => [
        'local',
        's3',
    ],
],
```

Then configure S3 in `config/filesystems.php`:

```php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
    'url' => env('AWS_URL'),
    'endpoint' => env('AWS_ENDPOINT'),
],
```

---

## Admin Dashboard

### Accessing the Backup Management Dashboard

Navigate to: `/admin/backups`

**Requirements:** Must be logged in as an admin user.

### Dashboard Features

1. **Backup Status Card**
    - Shows current backup health (Healthy, Warning, Critical)
    - Displays last backup time
    - Color-coded status indicators

2. **Storage Usage Card**
    - Shows total backup storage used
    - Number of backup files

3. **Quick Actions**
    - Trigger Backup Now button
    - Manual backup initiation for emergency situations

4. **Backup Files List**
    - View all backup files with dates and sizes
    - Download individual backups
    - Delete old backups

5. **Backup Schedule Information**
    - Displays automated backup schedule
    - Shows retention policy details
    - Lists all maintenance tasks

6. **Server Cron Configuration**
    - Shows the exact cron command needed for your server

---

## Manual Backup Operations

### Trigger an Immediate Backup

**Via Admin Dashboard:**

1. Go to `/admin/backups`
2. Click "🔄 Trigger Backup Now"
3. Wait for confirmation message

**Via Artisan Command:**

```bash
php artisan backup:run
```

### Clean Up Old Backups

**Via Artisan Command:**

```bash
php artisan backup:clean
```

This respects the retention policy defined in `config/backup.php`.

### List Scheduled Tasks

View all scheduled tasks and their next run times:

```bash
php artisan schedule:list
```

### Test Scheduler

Test if the scheduler is working:

```bash
php artisan schedule:work
```

This will run the scheduler in the foreground and show all tasks as they execute.

---

## Email Notifications

### Backup Failure Notifications

When a backup fails, an email is automatically sent to `BACKUP_ADMIN_EMAIL`.

**Email Contents:**

- Error message and details
- Server hostname
- Link to backup configuration dashboard
- Timestamp of failure

**Notification Class:** `App\Notifications\BackupFailureNotification`

### Weekly Backup Summary Notifications

Every Sunday at 07:00 AM (UTC), a backup health check email is sent.

**Email Contents:**

- Backup status (Healthy, Warning, Critical)
- Last backup date and time
- Storage usage
- Backup file count
- Recommendations for action (if needed)

---

## Custom Artisan Commands

### 1. Order Cleanup Command

```bash
php artisan order:cleanup-pending
```

- Cancels pending orders older than 24 hours
- Runs hourly automatically
- Logs all actions

### 2. Session Cleanup Command

```bash
php artisan session:cleanup
```

- Removes expired sessions from database
- Respects `config/session.php` lifetime setting
- Reduces database bloat

### 3. Log Rotation Command

```bash
php artisan log:rotate
```

- Archives logs older than 7 days
- Compresses with GZIP format
- Stores in `storage/logs/archives/`
- Runs weekly every Monday

### 4. Notification Prune Command

```bash
php artisan notification:prune
```

- Deletes notification records older than 90 days
- Frees up database space
- Runs weekly every Sunday

### 5. Audit Archive Command

```bash
php artisan audit:archive
```

- Archives audit logs older than 1 year
- Marks records as archived instead of deleting
- Maintains compliance/audit trails
- Runs monthly on the 1st

---

## Monitoring & Health Checks

### Backup Health Monitoring

The system automatically monitors:

1. **Last Backup Age**
    - Alert if backup is older than 24 hours
    - Critical alert if older than 48 hours

2. **Storage Usage**
    - Default max: 5000 MB (configurable in `config/backup.php`)
    - Alerts if storage exceeds limit

3. **Backup Success/Failure**
    - Email notifications on failure
    - Logs all events to `storage/logs/laravel.log`

### Checking Backup Health

```bash
# Run health checks manually
php artisan backup:monitor

# View detailed backup status
php artisan backup:list

# Test a dry-run backup
php artisan backup:run --dry-run
```

### Viewing Logs

```bash
# Check Laravel logs for backup events
tail -f storage/logs/laravel.log | grep -i backup

# View specific error
grep -i "backup failed" storage/logs/laravel.log
```

---

## Troubleshooting

### Issue: Scheduler is not running

**Solution:**

1. Verify cron job exists: `crontab -l | grep artisan`
2. Check if Laravel can execute: `php artisan tinker` (should work)
3. Verify file permissions: `ls -la storage/logs/`
4. Check server logs: `tail -f /var/log/syslog`

### Issue: Backup command is timing out

**Solution:**

- Increase timeout in `config/backup.php`:
    ```php
    'source' => [
        'files' => [
            'timeout' => 3600, // 1 hour instead of 30 min
        ],
    ],
    ```

### Issue: MYSQLDUMP not found

**Solution:**

1. Find mysqldump location: `which mysqldump`
2. Add to `.env`: `MYSQLDUMP_PATH=/usr/bin/mysqldump`
3. Or add it to system PATH

### Issue: Backup files are taking too much space

**Solution:**

- Reduce retention policy in `config/backup.php`
- Remove unnecessary files from backup exclusions
- Enable compression and encryption
- Consider cloud storage (S3)

### Issue: Email notifications not sending

**Solution:**

1. Verify `.env` email configuration
2. Test email: `php artisan tinker` then `Mail::raw('test', fn($m) => $m->to('admin@example.com'));`
3. Check `BACKUP_ADMIN_EMAIL` is set correctly
4. Verify notification class exists in `App\Notifications\`

### Issue: Cannot access /admin/backups

**Solution:**

1. Verify user is logged in as admin
2. Check `isAdmin` policy/middleware exists
3. Verify route is registered in `routes/web.php`
4. Clear route cache: `php artisan route:cache --clear`

---

## Security Considerations

1. **Encryption:** Configure `BACKUP_ENCRYPTION_PASSWORD` in `.env`
2. **File Permissions:** Ensure `storage/app/backups` is not web-accessible
3. **Access Control:** Only admins can trigger/manage backups
4. **Logging:** All backup operations are logged with user ID and IP
5. **Retention:** Automatic cleanup of old backups prevents unauthorized access
6. **S3 Bucket Policy:** If using S3, configure private bucket with encryption

---

## Performance Optimization

### Large Database Optimization

For databases larger than 1 GB:

1. Schedule backup during low-traffic hours (currently 02:00 AM UTC)
2. Exclude unnecessary tables in `config/backup.php`:
    ```php
    'ignore_tables' => [
        'sessions',
        'failed_jobs',
        'log_channel_usage',
    ],
    ```
3. Use compression to reduce storage
4. Consider incremental backups for very large databases

### Reducing Backup Time

1. Exclude large directories:
    ```php
    'exclude' => [
        base_path('vendor'),
        base_path('node_modules'),
        base_path('storage/logs'),
    ],
    ```
2. Disable file backups if only database backup needed
3. Use `--only-db` flag: `php artisan backup:run --only-db`

---

## Backup Restoration

### Restoring from Database Dump

```bash
# Find your backup
ls -la storage/app/backups/

# Extract if compressed
gunzip backup-file.sql.gz

# Restore database
mysql -u username -p database_name < backup-file.sql
```

### Full Application Restore

```bash
# 1. Restore database
mysql -u username -p database_name < database-dump.sql

# 2. Restore uploaded files
# Copy from backup location: storage/app/backups/
# To public storage: storage/app/public/

# 3. Verify installation
php artisan migrate:status
php artisan cache:clear
```

---

## Summary of Implementation

✅ **Completed Components:**

1. ✓ Backup configuration file (`config/backup.php`)
2. ✓ Custom artisan commands (5 total)
3. ✓ Scheduler configuration (`routes/console.php`)
4. ✓ Backup controller with admin endpoints
5. ✓ Admin dashboard view with UI
6. ✓ Email notification classes
7. ✓ Audit logs migration and model
8. ✓ Web routes for backup management

**Next Steps:**

1. Install composer dependencies: `composer install`
2. Publish vendor assets: `php artisan vendor:publish`
3. Run migrations: `php artisan migrate`
4. Configure `.env` with email settings
5. Set up server cron job
6. Test via `/admin/backups` dashboard

---

## Support & Additional Resources

- [Spatie Laravel Backup Documentation](https://spatie.be/docs/laravel-backup/v9/introduction)
- [Laravel Scheduler Documentation](https://laravel.com/docs/11.x/scheduling)
- [Laravel Mail Documentation](https://laravel.com/docs/11.x/mail)

---

**Version:** 1.0  
**Last Updated:** May 15, 2026  
**System:** PageTurner Bookstore v1.0
