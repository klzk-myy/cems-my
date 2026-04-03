# CEMS-MY - Currency Exchange Management System (Malaysia)

<p align="center">
  <img src="https://img.shields.io/badge/version-1.0-blue" alt="Version">
  <img src="https://img.shields.io/badge/php-8.3%2B-purple" alt="PHP 8.3+">
  <img src="https://img.shields.io/badge/laravel-11.x-red" alt="Laravel 11.x">
  <img src="https://img.shields.io/badge/license-MIT-green" alt="License">
</p>

## Overview

**CEMS-MY** is a comprehensive Currency Exchange Management System designed specifically for Malaysian Money Services Businesses (MSB). Built on Laravel 11.x, it provides enterprise-grade features for managing foreign currency trading operations while ensuring compliance with Bank Negara Malaysia (BNM) regulations and the Personal Data Protection Act (PDPA).

## Key Features

### Core Functionality
- **Real-time Foreign Currency Trading**: Buy/Sell transactions with automatic rate calculations
- **Multi-Currency Support**: USD, EUR, GBP, SGD, and more
- **Till Management**: Daily opening/closing with variance tracking
- **Stock Position Tracking**: Real-time foreign currency inventory with weighted average cost
- **Customer Management**: Complete customer profiles with risk rating
- **Sanction Screening**: Automated compliance checks against international lists

### Compliance & Security
- **BNM AML/CFT Compliance**: Enhanced Due Diligence (EDD) for transactions ≥ RM 50,000
- **Audit Trail**: Comprehensive logging of all operations
- **Role-Based Access Control**: Admin, Manager, Teller, and Compliance Officer roles
- **Data Encryption**: AES-256 encryption for sensitive data
- **Session Management**: Secure session handling with timeout warnings

### Accounting & Reporting
- **MIA-Compliant Accounting**: Malaysian Institute of Accountants standards
- **Double-Entry Bookkeeping**: Automated journal entries
- **Monthly Revaluation**: Unrealized P&L calculations
- **Financial Statements**: Balance Sheet, Income Statement
- **Compliance Reports**: BNM-ready audit reports
- **Custom Report Builder**: Flexible reporting with templates

### Advanced Features
- **Transaction Monitoring**: Automated suspicious activity detection
- **Budget Management**: Department-wise budget tracking
- **Bank Reconciliation**: Automated reconciliation with variance analysis
- **Counter Management**: Multi-counter operation support
- **Data Import/Export**: Bulk transaction imports with validation
- **Period Closing**: Secure month-end closing with validation

## System Requirements

### Server Requirements
- **PHP**: >= 8.3
- **Database**: MySQL 8.0+ or PostgreSQL 14+
- **Web Server**: Apache 2.4+ with mod_rewrite or Nginx
- **Extensions**: BCMath, OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON

### Recommended Environment
- **OS**: Ubuntu 22.04 LTS / CentOS 8 / AlmaLinux 8
- **Memory**: 4GB RAM minimum, 8GB recommended
- **Storage**: 50GB SSD minimum
- **Network**: Stable internet connection for rate API

## Quick Start

### Installation

```bash
# Clone the repository
git clone https://github.com/your-org/cems-my.git
cd cems-my

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install JavaScript dependencies (optional, for frontend assets)
npm install && npm run build

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --seed

# Create symbolic link for storage
php artisan storage:link

# Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Configuration

```env
APP_NAME="CEMS-MY"
APP_ENV=production
APP_KEY=base64:your-key-here
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cems_my
DB_USERNAME=cems_user
DB_PASSWORD=your-secure-password

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=true

# Encryption
ENCRYPTION_KEY=your-32-character-encryption-key
```

## Documentation

- [User Manual](docs/USER_MANUAL.md) - Complete user guide
- [Deployment Guide](docs/DEPLOYMENT.md) - Production deployment instructions
- [API Documentation](docs/API.md) - REST API reference
- [Database Schema](docs/DATABASE_SCHEMA.md) - Database documentation
- [Architecture](docs/trading-module-analysis.md) - System architecture
- [Security Analysis](docs/logical-faults-analysis.md) - Security review

## Testing

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test suite
php artisan test --filter=TransactionTest
```

## Security

### Implemented Security Measures
- ✅ Role-based access control (RBAC)
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention via Eloquent ORM
- ✅ XSS protection with output escaping
- ✅ Session security with secure cookies
- ✅ Rate limiting on authentication endpoints
- ✅ Password complexity enforcement (12+ chars)
- ✅ Audit logging for all critical operations

### Security Audit Results
- **Risk Level**: LOW
- **Critical Issues**: 0 (All resolved)
- **High Priority Issues**: 0 (All resolved)
- **Last Security Review**: 2026-04-04

## Compliance

### BNM AML/CFT Requirements
| Requirement | Status | Implementation |
|-------------|--------|----------------|
| Customer Due Diligence | ✅ Complete | Multi-level CDD with risk assessment |
| Enhanced Due Diligence | ✅ Complete | Automatic for ≥ RM 50,000 |
| Transaction Monitoring | ✅ Complete | Real-time monitoring with flagging |
| Record Retention | ✅ Complete | 7-year retention policy |
| Audit Trail | ✅ Complete | Comprehensive logging |
| Suspicious Activity Reporting | ✅ Complete | Automated detection |

### PDPA Compliance
| Requirement | Status |
|-------------|--------|
| Data Encryption | ✅ AES-256 |
| Access Control | ✅ RBAC implemented |
| Audit Trail | ✅ All access logged |
| Data Minimization | ✅ Only necessary data collected |
| Consent Management | ✅ Explicit consent required |

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         CEMS-MY ARCHITECTURE                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │   Web UI     │  │   API        │  │   CLI        │         │
│  │   (Blade)    │  │   (REST)     │  │   (Artisan)  │         │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘         │
│         │                  │                  │                 │
│  ┌──────┴──────────────────┴──────────────────┴──────┐       │
│  │              LARAVEL FRAMEWORK                     │       │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐         │       │
│  │  │ Controllers│  │  Models  │  │ Services │         │       │
│  │  └──────────┘  └──────────┘  └──────────┘         │       │
│  └──────────────────────┬────────────────────────────┘       │
│                          │                                     │
│  ┌───────────────────────┴────────────────────────────┐       │
│  │              DATA LAYER                          │       │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐       │       │
│  │  │ MySQL    │  │ Redis    │  │ Files    │       │       │
│  │  │ Database │  │ Cache    │  │ Storage  │       │       │
│  │  └──────────┘  └──────────┘  └──────────┘       │       │
│  └──────────────────────────────────────────────────┘       │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

## Performance

- **Average Response Time**: < 200ms
- **Database Queries**: Optimized with Eager Loading
- **Caching**: Redis for session and query caching
- **Asset Optimization**: Minified CSS/JS with Laravel Mix

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For support, please contact:
- **Email**: support@cems-my.com
- **Documentation**: https://docs.cems-my.com
- **Issue Tracker**: https://github.com/your-org/cems-my/issues

## Acknowledgments

- Built with [Laravel](https://laravel.com) - The PHP Framework for Web Artisans
- Exchange rates powered by [Open Exchange Rates](https://openexchangerates.org)
- Sanction screening data from international regulatory sources

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a complete list of changes.

---

<p align="center">
  <strong>CEMS-MY</strong> - Empowering Malaysian Money Services Businesses<br>
  <em>Version 1.0 | Released April 2026</em>
</p>
