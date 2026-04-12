# CEMS-MY Notification System Implementation Summary

## Overview
Complete notification system for CEMS-MY Laravel application with 8 notification types supporting email, in-app, and broadcast channels.

## Files Created

### Notification Classes (8 files)
- `app/Notifications/TransactionFlaggedNotification.php`
- `app/Notifications/StrDeadlineApproachingNotification.php`
- `app/Notifications/StrSubmissionFailedNotification.php`
- `app/Notifications/ComplianceCaseAssignedNotification.php`
- `app/Notifications/DataBreachAlertNotification.php`
- `app/Notifications/LargeTransactionNotification.php`
- `app/Notifications/SanctionsMatchNotification.php`
- `app/Notifications/SystemHealthAlertNotification.php`

### Email Templates (8 files)
- `resources/views/emails/transaction-flagged.blade.php`
- `resources/views/emails/str-deadline.blade.php`
- `resources/views/emails/str-submission-failed.blade.php`
- `resources/views/emails/compliance-case-assigned.blade.php`
- `resources/views/emails/data-breach.blade.php`
- `resources/views/emails/large-transaction.blade.php`
- `resources/views/emails/sanctions-match.blade.php`
- `resources/views/emails/system-health.blade.php`
- `resources/views/emails/layouts/email.blade.php` (master layout)

### Database & Models
- `database/migrations/2026_04_12_052819_create_notifications_table.php` (Laravel built-in)
- `database/migrations/2026_04_15_000002_create_user_notification_preferences_table.php`
- `app/Models/UserNotificationPreference.php`

### Commands
- `app/Console/Commands/SendNotificationDigest.php` - Daily digest command
- `app/Console/Commands/TestNotification.php` - Test notification delivery

### Configuration
- `config/notifications.php` - Notification system configuration
- `app/Providers/NotificationServiceProvider.php` - Service provider
- Updated `app/Models/User.php` - Added notification preferences methods

### Tests
- `tests/Feature/NotificationSystemTest.php` - Comprehensive test suite

## Supported Channels
- **Email** - Via Laravel Mail with SMTP
- **Database** - In-app notifications stored in database
- **Broadcast** - Real-time notifications via Laravel Echo/Pusher
- **SMS** - Stub for Twilio integration (configurable)

## Usage Examples

### Send Transaction Flagged Notification
```php
use App\Notifications\TransactionFlaggedNotification;

$user->notify(new TransactionFlaggedNotification($flaggedTransaction, $flaggedBy));
```

### Send STR Deadline Approaching
```php
use App\Notifications\StrDeadlineApproachingNotification;

$user->notify(new StrDeadlineApproachingNotification($strReport, $daysRemaining));
```

### Get User Notification Preferences
```php
$preference = $user->getNotificationPreference('transaction_flagged');
$isEmailEnabled = $preference->isEmailEnabled();
```

## Artisan Commands

### Send Digest
```bash
php artisan notifications:send-digest --period=24h
php artisan notifications:send-digest --user=1 --dry-run
```

### Test Notifications
```bash
php artisan notifications:test transaction_flagged --user=1 --dry-run
php artisan notifications:test all
```

## Configuration Options

Key settings in `config/notifications.php`:
- Default channels per notification type
- SMS/Twilio configuration
- Webhook settings
- Digest frequency
- Rate limiting
- BNM compliance settings

## BNM Compliance
- STR deadline tracking (3 working days)
- Large transaction threshold (RM 50,000)
- 7-year notification retention
- Critical notifications cannot be disabled

## Migration Commands
```bash
# Run notification table migration
php artisan migrate --path=database/migrations/2026_04_15_000002_create_user_notification_preferences_table.php

# Run all tests
php artisan test --filter=NotificationSystemTest
```
