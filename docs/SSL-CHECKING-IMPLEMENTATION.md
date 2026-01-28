# SSL Certificate Checking Implementation

## Overview

SSL certificate checking is a **paid plan feature** that validates SSL certificates for monitored domains. This feature is automatically enabled for all paid plans and disabled for the free plan.

## Plan-Based SSL Checking

### Free Plan
- **SSL checking**: ❌ Disabled
- **SSL status**: Always shows "Unknown" (null)
- **Behavior**: Domain uptime is checked, but SSL certificate validation is skipped

### Paid Plans (Starter, Business, Enterprise)
- **SSL checking**: ✅ Enabled
- **SSL status**: Shows "Valid" or "Invalid" based on certificate check
- **Behavior**: Full SSL certificate validation including:
  - Certificate validity
  - Certificate expiration date
  - Hostname matching
  - Certificate chain validation

## How It Works

1. **Domain Check Process**:
   - When a domain is checked, the system determines the user's plan
   - If plan has `price_cents > 0` (paid plan), SSL checking is enabled
   - If plan has `price_cents = 0` (free plan), SSL checking is disabled

2. **SSL Validation**:
   - Checks certificate validity
   - Verifies certificate hasn't expired
   - Validates certificate matches the domain hostname
   - Checks certificate chain

3. **Database Storage**:
   - `ssl_valid`: `null` (free plan), `true` (valid), or `false` (invalid)
   - Updated during each domain check for paid plans

## Implementation Details

### Code Location
- **Job**: `app/Jobs/CheckDomainJob.php`
- **Service**: `app/Services/DomainCheckService.php`
- **Logic**: Plan-based SSL checking enabled for `price_cents > 0`

### Key Code
```php
// In CheckDomainJob.php
$plan = $subscription?->plan;
$checkSsl = $plan && $plan->price_cents > 0; // Paid plans only
$result = $checker->check($domain->domain, $checkSsl);
```

## UI Display

The domains table shows SSL status with color-coded badges:

- **"Unknown" (gray badge)**: Free plan - SSL not checked
- **"Valid" (green badge)**: Paid plan - SSL certificate is valid
- **"Invalid" (red badge)**: Paid plan - SSL certificate has issues

## Testing

To verify SSL checking works correctly:

1. **Free Plan**:
   - Add a domain
   - Check domain
   - SSL status should show "Unknown"

2. **Paid Plan**:
   - Upgrade to Starter, Business, or Enterprise
   - Check domain
   - SSL status should show "Valid" or "Invalid" based on actual certificate

## Notes

- SSL checking is performed during regular domain health checks
- For paid plans, SSL status is updated on every check
- For free plans, `ssl_valid` remains `null` (not checked)
- SSL checking adds minimal overhead to domain checks (~100-200ms)
