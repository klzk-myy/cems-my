# Sanctions Auto-Update System

## Overview

CEMS-MY now includes automated sanctions list updates to ensure BNM AML/CFT compliance within the required 24-hour window.

## Supported Sources

| Source | List Type | Format | URL | Status |
|--------|-----------|--------|-----|--------|
| UN Security Council | UNSCR | XML | https://scsanctions.un.org/... | Auto |
| US OFAC SDN | OFAC | XML | https://www.treasury.gov/... | Auto |
| EU Consolidated | EU | CSV | https://webgate.ec.europa.eu/... | Auto |
| Malaysia MOHA | MOHA | CSV | Manual only | Manual |

## Automated Schedule

- **Daily at 03:00**: Download and import all enabled lists
- **Daily at 08:00**: Check status and alert if failures
- **Monthly**: Full customer rescreening (existing behavior)

## Commands

### Update Sanctions Lists

```bash
# Update all enabled sources
php artisan sanctions:update

# Update specific source
php artisan sanctions:update --source=un
php artisan sanctions:update --source=ofac
php artisan sanctions:update --source=eu

# Run synchronously (for debugging)
php artisan sanctions:update --sync
```

### Check Status

```bash
# Show all lists status
php artisan sanctions:status

# Show details for specific list
php artisan sanctions:status --list="UN Security Council"
```

## Configuration

Edit `.env` to configure:

```env
# Enable/disable sources
SANCTIONS_UN_ENABLED=true
SANCTIONS_OFAC_ENABLED=true
SANCTIONS_EU_ENABLED=true
SANCTIONS_MOHA_ENABLED=false

# Custom URLs (optional)
SANCTIONS_UN_URL=https://...
SANCTIONS_OFAC_URL=https://...

# Notification recipients
SANCTIONS_COMPLIANCE_EMAIL=compliance@example.com
SANCTIONS_ADMIN_EMAIL=admin@example.com

# System user for automated updates
SANCTIONS_SYSTEM_USER_ID=1
```

## Change Detection

The system automatically detects:
- **New entries**: Added to sanctions lists
- **Removed entries**: Removed from sanctions lists
- **Significant changes**: >10% change in entry count

When changes are detected:
1. Compliance alert is created
2. Change log entry is recorded
3. Automatic customer rescreening is triggered

## Monitoring

### Log Files

- `storage/logs/sanctions-update.log` - Update operations
- `storage/logs/sanctions-status-check.log` - Status checks
- `storage/logs/laravel.log` - Detailed error information

### Database Tables

- `sanction_lists` - List metadata and update status
- `sanction_entries` - Individual sanctioned entities
- `sanctions_change_logs` - Detailed change tracking

## Troubleshooting

### Update Failed

1. Check logs: `tail -f storage/logs/sanctions-update.log`
2. Verify URL is accessible: `curl -I <url>`
3. Retry manually: `php artisan sanctions:update --source=<name>`
4. Check network connectivity from server

### No Changes Detected

1. Verify checksum: Compare `last_checksum` field
2. Check archive: Files stored in `storage/app/archive/sanctions/`
3. Manual comparison: Download and compare with previous

### Customer Rescreening Not Triggered

1. Verify rescreening is enabled: `config/sanctions.php`
2. Check queue worker is running
3. Manual trigger: `php artisan compliance:rescreen`

## Compliance Notes

- **BNM Requirement**: Update within 24 hours of list publication
- **Audit Trail**: All imports logged with checksums
- **Rescreening**: Customers automatically rescreened against new entries
- **Retention**: Archive files kept for 30 days (configurable)

## Support

Contact the compliance team for:
- Adding new sanctions sources
- Custom parsing requirements
- Rescreening policy questions
