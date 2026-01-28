# Domain Expiration Checking

This document explains how domain expiration date checking works in the system.

## Overview

The system can check when domain registrations expire by querying WHOIS data. This helps you monitor domain expiration dates and avoid losing domains due to expired registrations.

**Note: Domain expiration checking is a paid plan feature.** Only users with Starter, Business, or Enterprise plans can check domain expiration dates. Free plan users will see an "Upgrade" link in the expiration column.

## How It Works

1. **Paid Plans Only**: Domain expiration checking is only available for paid plans (Starter, Business, Enterprise). Free plan users cannot check expiration dates.

2. **Automatic Checking**: When domains are checked for uptime/SSL, the system also checks expiration dates (once per day to avoid rate limits) - **only for paid plans**.

2. **WHOIS Query Methods**: The system tries multiple methods to get expiration dates:
   - **Command-line `whois`** (if available on the server) - Most reliable
   - **WHOIS API service** (if configured) - Requires API key
   - **Fallback methods** - Limited support

3. **Storage**: Expiration dates are stored in the `expires_at` field, and the last check time is stored in `expires_checked_at`.

## Configuration

### Option 1: Command-line WHOIS (Recommended)

If your server has the `whois` command installed, no configuration is needed. The system will automatically use it.

**Installation:**
- **Linux/Debian**: `apt-get install whois`
- **Linux/RHEL**: `yum install whois`
- **macOS**: `brew install whois`
- **Windows**: Install from [Sysinternals](https://docs.microsoft.com/en-us/sysinternals/downloads/whois) or use WSL

### Option 2: WHOIS API Service (Optional)

If command-line `whois` is not available, you can use a WHOIS API service:

1. Sign up for a WHOIS API service (e.g., [WhoisXML API](https://whoisxmlapi.com/))
2. Get your API key
3. Add to `.env`:

```env
WHOIS_API_KEY=your_api_key_here
WHOIS_API_URL=https://www.whoisxmlapi.com/whoisserver/WhoisService
```

## Display

Expiration dates are shown in the domains table with color-coded badges:

- **Red "Expired" badge**: Domain has already expired
- **Yellow badge with days**: Domain expires within 30 days (e.g., "15 days")
- **Gray text**: Domain expires in more than 30 days (shows date)

## Supported TLDs

The system supports **all TLDs** including but not limited to:

- **Common TLDs**: .com, .net, .org, .info, .biz
- **New gTLDs**: .top, .xyz, .online, .site, .store
- **Country TLDs**: .us, .uk, .de, .fr, .nl, .au, .ca, .io, .co, .me, .tv, .cc
- **Multi-part TLDs**: .co.uk, .com.au, .gov.uk, etc.

The command-line `whois` tool automatically handles most TLDs by querying the appropriate WHOIS server. The system includes enhanced parsing patterns to handle various WHOIS output formats used by different registrars and TLD registries.

## Features

- **Automatic checking**: Expiration dates are checked automatically during domain health checks
- **Rate limiting**: Expiration checks are limited to once per day per domain to avoid WHOIS rate limits
- **Visual indicators**: Color-coded badges show expiration status
- **Tooltips**: Hover over expiration dates to see full expiration date
- **Universal TLD support**: Works with all TLDs including .top, .xyz, .info, and hundreds of others

## Manual Expiration Check

Expiration dates are checked automatically, but you can also trigger a manual check by:

1. Running a manual domain check (the expiration will be checked if it's been more than 24 hours since the last check)
2. The system will automatically check expiration during the next scheduled domain check

## Troubleshooting

### Expiration dates not showing

1. **Check if `whois` command is available**:
   ```bash
   docker compose exec app which whois
   ```

2. **If using API, verify API key**:
   - Check `.env` file has `WHOIS_API_KEY` set
   - Verify API key is valid and has credits/queries remaining

3. **Check logs**:
   ```bash
   docker compose exec app php artisan log:show
   ```
   Look for "Failed to check domain expiration" messages

### Rate limiting

WHOIS servers may rate limit queries. The system automatically:
- Limits expiration checks to once per day per domain
- Logs errors if queries fail
- Continues to work even if expiration checks fail

### Some domains don't show expiration

Some domains or TLDs may not provide expiration dates in WHOIS data, or the WHOIS server may be unavailable. This is normal and the system will continue to try checking on subsequent runs.

## Technical Details

- **Service**: `App\Services\DomainExpirationService`
- **Database fields**: `expires_at`, `expires_checked_at`
- **Check frequency**: Once per 24 hours per domain
- **Integration**: Automatically called from `CheckDomainJob`

## Future Enhancements

Potential improvements:
- Email alerts when domains are expiring soon
- Bulk expiration checking
- Expiration date history tracking
- Integration with domain registrar APIs for more accurate data
