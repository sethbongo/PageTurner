# Backup System - Quick Reference

## File Structure

```
App/
├── Console/
│   └── Commands/
│       ├── OrderCleanupPending.php       (NEW) Hourly cleanup
│       ├── SessionCleanup.php            (NEW) Daily cleanup
│       ├── LogRotate.php                 (NEW) Weekly rotation
│       ├── NotificationPrune.php         (NEW) Weekly prune
│       └── AuditArchive.php              (NEW) Monthly archive
├── Http/
│   └── Controllers/
│       └── BackupController.php          (NEW) Backup management
├── Models/
│   └── AuditLog.php                      (NEW) Audit log model
└── Notifications/
    ├── BackupNotifiable.php              (NEW) Notification routing
    ├── BackupFailureNotification.php     (NEW) Failure alerts
    └── BackupSuccessNotification.php     (NEW) Success reports

config/
└── backup.php                             (NEW) Backup configuration

database/
└── migrations/
    └── 2024_05_15_000001_create_audit_logs_table.php  (NEW)

resources/views/admin/
└── backups/
    └── index.blade.php                   (NEW) Backup dashboard

routes/
└── console.php                            (UPDATED) Scheduled tasks

.env
├── BACKUP_ADMIN_EMAIL                    (ADD) Admin email
├── BACKUP_ENCRYPTION_PASSWORD            (ADD) Encryption key
└── BACKUP_SLACK_WEBHOOK                  (ADD) Slack webhook

Documentation:
├── BACKUP_SETUP_GUIDE.md                 (NEW) Full setup guide
└── BACKUP_QUICK_REFERENCE.md             (NEW) This file
```

## Quick Commands

### Run Backup Now

```bash
php artisan backup:run
php artisan backup:clean
```

### Test Scheduler

```bash
php artisan schedule:list        # View scheduled tasks
php artisan schedule:work        # Run in foreground
php artisan schedule:test        # Test specific command
```

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep backup
```

### Database Restore

```bash
gunzip backup-file.sql.gz
mysql -u user -p database < backup-file.sql
```

## Key Configuration

**Backup Schedule:**

- Daily at 02:00 AM UTC
- Cleanup at 03:00 AM UTC

**Retention:**

- Daily: 7 days
- Weekly: 4 weeks
- Monthly: 12 months

**Storage Locations:**

- Local: `storage/app/backups`
- Optional: S3 bucket (configured in .env)

## Environment Variables (Add to .env)

```bash
BACKUP_ADMIN_EMAIL=admin@example.com
BACKUP_ENCRYPTION_PASSWORD=your_password
BACKUP_SLACK_WEBHOOK=https://hooks.slack.com/...
MYSQLDUMP_PATH=/usr/bin/mysqldump
```

## Cron Job Setup

**Add to crontab (`crontab -e`):**

```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

## Scheduled Tasks Overview

| Task                  | Frequency | Time       | Status |
| --------------------- | --------- | ---------- | ------ |
| backup:run            | Daily     | 02:00      | ✓      |
| backup:clean          | Daily     | 03:00      | ✓      |
| order:cleanup-pending | Hourly    | Every hour | ✓      |
| session:cleanup       | Daily     | Daily      | ✓      |
| log:rotate            | Weekly    | Mon 01:00  | ✓      |
| report:daily-sales    | Daily     | 06:00      | ✓      |
| notification:prune    | Weekly    | Sun 04:00  | ✓      |
| audit:archive         | Monthly   | 1st 05:00  | ✓      |
| backup:monitor        | Weekly    | Sun 07:00  | ✓      |

## Admin Dashboard

**URL:** `/admin/backups`

**Features:**

- View backup status (Healthy/Warning/Critical)
- Trigger manual backups
- Download/delete backup files
- View retention policy
- See cron setup instructions

## Troubleshooting

| Issue                       | Solution                                    |
| --------------------------- | ------------------------------------------- |
| Scheduler not running       | Check crontab: `crontab -l \| grep artisan` |
| Backup timing out           | Increase timeout in config/backup.php       |
| MYSQLDUMP not found         | Set MYSQLDUMP_PATH in .env                  |
| Emails not sending          | Verify MAIL\_\* vars in .env                |
| Can't access /admin/backups | Verify user is admin                        |

## Next Steps

1. ✓ Files created
2. → Run: `php artisan migrate`
3. → Add to .env: BACKUP_ADMIN_EMAIL, etc.
4. → Setup cron job on server
5. → Test: `php artisan schedule:work`
6. → Access: `/admin/backups`

## Testing Before Production

```bash
# Test dry-run (no actual backup)
php artisan backup:run --dry-run

# Test scheduler in foreground
php artisan schedule:work

# Verify tasks execute
# Check logs for: "Backup completed successfully"
tail -f storage/logs/laravel.log
```

## Performance Notes

- Backup runs at 02:00 AM to avoid peak traffic
- Maintenance tasks distributed throughout day
- Logs rotated weekly, audit logs archived monthly
- Session cleanup prevents database bloat
- All long-running tasks use `withoutOverlapping()`

## Success Indicators

✅ First backup completes within 2 minutes  
✅ No email errors in logs  
✅ Dashboard shows "Healthy" status  
✅ Cron runs every minute (check logs)  
✅ Scheduled tasks execute on time

---

**For detailed setup:** See `BACKUP_SETUP_GUIDE.md`
