# Mailing Module - Configuration Quick Reference

**Last Updated:** 2026-01-29

---

## Quick Start

### Minimal Configuration

Add to your `.env` file:

```env
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_api_key_here
```

That's it! All other settings have sensible defaults.

---

## Essential Configurations

### Production Setup

```env
# Required
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=your_production_key

# Recommended
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_AUTO_SYNC=true
MAILING_SYNC_USE_QUEUE=true
MAILING_WEBHOOK_ENABLED=true
MAILING_WEBHOOK_SECRET=generate_random_string_here

# Performance
MAILING_PERFORMANCE_QUERY_CACHE=true
MAILING_PERFORMANCE_CONNECTION_POOLING=true

# Security
MAILING_SECURITY_CAPTCHA_ENABLED=true
MAILING_SECURITY_RECAPTCHA_SITE_KEY=your_site_key
MAILING_SECURITY_RECAPTCHA_SECRET_KEY=your_secret_key
```

---

## Configuration Sections at a Glance

| Section | Key Variable | Default | Purpose |
|---------|-------------|---------|---------|
| **API** | `MAILING_URL` | Required | Mailrelay API endpoint |
| **Sync** | `MAILING_AUTO_SYNC` | `true` | Auto-sync subscribers |
| **Cache** | `MAILING_CACHE_ENABLED` | `true` | Enable caching |
| **Webhooks** | `MAILING_WEBHOOK_ENABLED` | `true` | Process webhooks |
| **Tracking** | `MAILING_TRACK_OPENS` | `true` | Track email opens |
| **Import** | `MAILING_IMPORT_BATCH_SIZE` | `1000` | Import batch size |
| **Sending** | `MAILING_SENDING_SERVER_ROTATION` | `round-robin` | Server rotation |
| **Bounces** | `MAILING_BOUNCE_HANDLER_ENABLED` | `false` | Handle bounces |
| **Templates** | `MAILING_TEMPLATE_CACHE_ENABLED` | `true` | Cache templates |
| **Automation** | `MAILING_AUTOMATION_ENABLED` | `true` | Enable automation |
| **Security** | `MAILING_SECURITY_CAPTCHA_ENABLED` | `false` | Enable CAPTCHA |

---

## Common Use Cases

### 1. Enable Double Opt-In

```env
MAILING_LIST_DOUBLE_OPTIN_DEFAULT=true
MAILING_LIST_SEND_WELCOME_EMAIL_DEFAULT=true
```

### 2. Set Up Bounce Handling

```env
MAILING_BOUNCE_HANDLER_ENABLED=true
MAILING_BOUNCE_HANDLER_TYPE=imap
MAILING_BOUNCE_HANDLER_HOST=imap.gmail.com
MAILING_BOUNCE_HANDLER_PORT=993
MAILING_BOUNCE_HANDLER_USERNAME=bounces@yourdomain.com
MAILING_BOUNCE_HANDLER_PASSWORD=your_password
MAILING_BOUNCE_HANDLER_ENCRYPTION=ssl
MAILING_BOUNCE_HARD_ACTION=unsubscribe
```

### 3. Enable Email Verification

```env
MAILING_VERIFICATION_ADVANCED_ENABLED=true
MAILING_VERIFICATION_SERVICE=zerobounce
MAILING_VERIFICATION_API_KEY=your_zerobounce_key
MAILING_VERIFICATION_AUTO_IMPORT=true
```

### 4. Configure DKIM Signing

```env
MAILING_DKIM_ENABLED=true
MAILING_DKIM_SELECTOR=default
MAILING_DKIM_PRIVATE_KEY_PATH=/path/to/private.key
MAILING_DKIM_DOMAIN=yourdomain.com
```

### 5. Increase Performance

```env
# Aggressive caching
MAILING_CACHE_TTL_SUBSCRIBERS=7200
MAILING_CACHE_TTL_GROUPS=7200
MAILING_PERFORMANCE_QUERY_CACHE=true
MAILING_PERFORMANCE_QUERY_CACHE_TTL=30

# Larger batches
MAILING_SYNC_BATCH_SIZE=500
MAILING_IMPORT_BATCH_SIZE=2000

# Connection pooling
MAILING_PERFORMANCE_CONNECTION_POOLING=true
MAILING_PERFORMANCE_MAX_CONNECTIONS=200
```

### 6. Enable Debugging

```env
MAILING_DEBUG=true
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=debug
MAILING_LOG_REQUESTS=true
MAILING_LOG_RESPONSES=true
MAILING_LOG_REQUEST_BODY=true
MAILING_LOG_RESPONSE_BODY=true
```

---

## Queue Configuration

### Required Queues

| Queue | Purpose | Workers |
|-------|---------|---------|
| `mailing` | Subscriber sync | 2-3 |
| `webhooks` | Webhook processing | 1-2 |
| `tracking` | Open/click tracking | 1 |
| `imports` | CSV/Excel imports | 1 |
| `automation` | Workflows | 2 |

### Supervisor Config

```ini
[program:mailing-workers]
command=php /path/to/artisan queue:work --queue=mailing,webhooks,automation --tries=3
numprocs=3
autostart=true
autorestart=true
user=www-data
```

---

## Testing Configuration

### Sandbox Mode

```env
MAILING_SANDBOX_MODE=true
MAILING_MOCK_API=true
MAILING_DEBUG=true
MAILING_CAMPAIGN_TEST_MODE=true
MAILING_CAMPAIGN_TEST_EMAILS=test@example.com
```

---

## Security Checklist

- [ ] `MAILING_API_KEY` is set and secure
- [ ] `MAILING_WEBHOOK_SECRET` is random and strong
- [ ] `MAILING_WEBHOOK_VERIFY_SIGNATURE=true`
- [ ] `MAILING_SECURITY_CAPTCHA_ENABLED=true` (production)
- [ ] `MAILING_SECURITY_IP_RATE_LIMITING=true`
- [ ] `MAILING_VALIDATION_BLOCK_DISPOSABLE=true`
- [ ] `MAILING_DKIM_ENABLED=true` (if possible)
- [ ] Webhook IP whitelist configured (optional)

---

## Performance Checklist

- [ ] Redis cache configured
- [ ] `MAILING_CACHE_ENABLED=true`
- [ ] `MAILING_PERFORMANCE_QUERY_CACHE=true`
- [ ] `MAILING_PERFORMANCE_CONNECTION_POOLING=true`
- [ ] Queue workers running
- [ ] Supervisor configured
- [ ] Database indexes created
- [ ] `MAILING_SYNC_USE_QUEUE=true`

---

## Troubleshooting Quick Fixes

### API Not Connecting

```env
MAILING_TIMEOUT=60
MAILING_CONNECT_TIMEOUT=20
MAILING_RETRY_MAX_ATTEMPTS=5
```

### Queue Jobs Stuck

```bash
php artisan queue:restart
supervisorctl restart mailing-workers:*
php artisan cache:clear
```

### Cache Issues

```bash
php artisan cache:forget 'mailing:*'
# Or temporarily disable
MAILING_CACHE_ENABLED=false
```

### Import Timeouts

```env
MAILING_IMPORT_BATCH_SIZE=500
MAILING_IMPORT_MAX_EXECUTION_TIME=14400
```

---

## Environment-Specific Configs

### Development

```env
MAILING_URL=https://staging.mailrelay.com/api/v1
MAILING_API_KEY=staging_key
MAILING_CACHE_ENABLED=false
MAILING_DEBUG=true
MAILING_SANDBOX_MODE=true
```

### Staging

```env
MAILING_URL=https://staging.mailrelay.com/api/v1
MAILING_API_KEY=staging_key
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_DEBUG=false
MAILING_LOGGING_ENABLED=true
```

### Production

```env
MAILING_URL=https://your-account.mailrelay.com/api/v1
MAILING_API_KEY=production_key
MAILING_CACHE_ENABLED=true
MAILING_CACHE_DRIVER=redis
MAILING_DEBUG=false
MAILING_LOGGING_ENABLED=true
MAILING_LOGGING_LEVEL=error
MAILING_NOTIFY_ON_ERROR=true
```

---

## Accessing Configuration in Code

### Basic Usage

```php
// Get entire config
$config = config('mailing');

// Get specific section
$syncConfig = config('mailing.sync');

// Get specific value with default
$batchSize = config('mailing.sync.batch_size', 100);

// Check if feature is enabled
if (config('mailing.automation.enabled')) {
    // Run automation
}
```

### Helper Methods

```php
// Check if mailing is enabled
if (app('mailing')->isEnabled()) {
    // ...
}

// Get API URL
$apiUrl = app('mailing')->getApiUrl();

// Get cache TTL for subscribers
$ttl = config('mailing.cache.ttl.subscribers', 3600);
```

---

## Important Notes

1. **Never commit `.env` to version control** - Use `.env.example` as template
2. **Use different API keys** for staging/production
3. **Enable caching in production** - Reduces API calls
4. **Configure queues properly** - Critical for performance
5. **Monitor queue sizes** - Alert when > 5000 jobs
6. **Set up bounce handler** - Improves deliverability
7. **Enable DKIM if possible** - Better inbox placement
8. **Use CAPTCHA in production** - Prevents spam subscriptions

---

## Getting Help

- Full documentation: `docs/CONFIG_MIGRATION_REPORT.md`
- Environment variables: `.env.example`
- Config file: `config/mailing.php`
- Support: support@yourdomain.com

---

**Quick Reference Version 1.0** | Generated: 2026-01-29
