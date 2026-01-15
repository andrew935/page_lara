# Domain Monitor Pro

Real-time domain and SSL monitoring with instant alerts. Monitor up to 500 domains, track SSL certificates, and get notified via Telegram, email, or webhooks.

## Features

- **Real-Time Domain Monitoring** - Check domain availability at configurable intervals (10-60 minutes)
- **SSL Certificate Tracking** - Monitor SSL validity and expiration dates
- **Instant Alerts** - Get notified via Telegram, email, or custom webhooks when domains go down
- **Bulk Domain Import** - Import hundreds of domains at once from JSON feeds
- **Auto Import from Feed** - Automatically sync domains from external feeds daily at 6 AM
- **Multi-Account Support** - Each user has their own isolated domain list
- **Cloudflare Workers Integration** - Optional high-speed checking via Cloudflare Workers

## How It Works

### Architecture Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Landing Page  │────▶│   User Login    │────▶│   Dashboard     │
│   (welcome)     │     │   /login        │     │   /domains      │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                                        │
                                                        ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Domain Monitoring System                      │
├─────────────────┬─────────────────┬─────────────────────────────┤
│  Domain List    │  Settings       │  Notifications              │
│  - Add domains  │  - Check interval│  - Telegram bot            │
│  - Import bulk  │  - Auto import  │  - Webhook URL              │
│  - Check status │  - Feed URL     │  - Email alerts             │
└─────────────────┴─────────────────┴─────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Checking Modes                                │
├────────────────────────────┬────────────────────────────────────┤
│   Server Mode (Free)       │   Cloudflare Mode ($5/month)       │
│   - Laravel Queue Workers  │   - Cloudflare Workers             │
│   - ~50 min for 500 domains│   - ~2 min for 500 domains         │
│   - Uses server resources  │   - Edge-distributed checking      │
└────────────────────────────┴────────────────────────────────────┘
```

### Domain Checking Flow

1. **Scheduler Trigger** - Laravel scheduler or Cloudflare cron triggers domain checks
2. **Fetch Due Domains** - System gets domains that haven't been checked within their interval
3. **HTTP Check** - HEAD request to each domain to verify availability
4. **SSL Check** - Validates SSL certificate and checks expiration
5. **Update Status** - Domain status updated in database (live/down/ssl_error)
6. **Send Alerts** - If status changed to down, notifications are sent

### Notification Flow

```
Domain Status Change (live → down)
        │
        ▼
┌───────────────────┐
│ Check Notify Flag │
└───────────────────┘
        │
        ▼
┌───────────────────────────────────────────┐
│            Notification Channels           │
├─────────────┬─────────────┬───────────────┤
│  Telegram   │   Webhook   │    Email      │
│  Bot API    │   POST JSON │   (future)    │
└─────────────┴─────────────┴───────────────┘
```

## Plans

| Feature | Free | Pro ($59/mo) | Max ($99/mo) |
|---------|------|--------------|--------------|
| Domains | 50 | 200 | 500 |
| Check Interval | 60 min | 30 min | 10 min |
| SSL Monitoring | Basic | ✓ | Advanced |
| Telegram Alerts | ✓ | ✓ | ✓ |
| Webhook Alerts | - | ✓ | ✓ |
| Auto Feed Import | - | - | ✓ |
| Priority Support | - | ✓ | ✓ |

## Installation

### Requirements

- PHP 8.1+
- MySQL 8.0
- Composer
- Node.js (for assets)
- Docker (recommended)

### Docker Setup (Recommended)

1. **Clone the repository:**
```bash
git clone https://github.com/andrew935/page_lara.git
cd page_lara
```

2. **Copy environment file:**
```bash
cp docker/env.docker.example docker/env.docker
```

3. **Configure environment:**
```bash
# Edit docker/env.docker with your settings
nano docker/env.docker
```

4. **Build and start:**
```bash
docker compose up -d --build
```

5. **Initialize application:**
```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
```

6. **Access the app:**
- Local: `http://localhost:8000`
- Production: Set `APP_PORT=80` in environment

### Manual Setup

1. **Install dependencies:**
```bash
composer install
npm install && npm run build
```

2. **Configure environment:**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Run migrations:**
```bash
php artisan migrate --seed
```

4. **Start server:**
```bash
php artisan serve
```

## Configuration

### Domain Check Mode

Set in `docker/env.docker` or `.env`:

```env
# Server mode (free, uses Laravel queue)
DOMAIN_CHECK_MODE=server

# Cloudflare mode (faster, requires Cloudflare Workers)
DOMAIN_CHECK_MODE=cloudflare
CLOUDFLARE_WEBHOOK_SECRET=your-secret-here
```

### Cloudflare Workers Setup

See [cloudflare/README.md](cloudflare/README.md) for detailed setup instructions.

Quick setup:
```bash
cd cloudflare
npm install
wrangler login
wrangler secret put WEBHOOK_SECRET
wrangler secret put LARAVEL_API_URL
npm run deploy
```

### Auto Import Feed

Enable in **Domains → Settings**:
1. Set your feed URL (default: `https://assetscdn.net/api/domains/latest`)
2. Check "Auto-import daily at 6:00 AM"
3. The system will delete existing domains and import fresh ones daily

## Scheduled Tasks

The Laravel scheduler handles:

| Task | Schedule | Description |
|------|----------|-------------|
| `domains:check` | Based on interval | Check domains (server mode) |
| `domains:auto-import` | Daily at 6 AM | Auto-import from feed |
| `queue:work` | Continuous | Process queued jobs |

### Cron Setup (Non-Docker)

Add to crontab:
```bash
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

## API Endpoints

### Cloudflare Worker API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/cf/domains/due` | GET | Get domains due for checking |
| `/api/cf/domains/results` | POST | Submit check results |

### Internal API

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/domains` | GET | List all domains |
| `/domains` | POST | Add new domain |
| `/domains/{id}` | PUT | Update domain |
| `/domains/{id}` | DELETE | Delete domain |
| `/domains/import-json` | POST | Bulk import domains |
| `/domains/import-latest` | POST | Import from feed |

## File Structure

```
├── app/
│   ├── Billing/              # Plans, subscriptions
│   ├── Console/Commands/     # Artisan commands
│   ├── Domains/Services/     # Domain business logic
│   ├── Http/Controllers/     # Web & API controllers
│   ├── Jobs/                 # Queue jobs
│   ├── Models/               # Eloquent models
│   └── Services/             # Core services
├── cloudflare/               # Cloudflare Worker code
├── config/
│   └── domain.php            # Domain check configuration
├── database/
│   ├── migrations/           # Database schema
│   └── seeders/              # Default data
├── docker/                   # Docker configuration
├── resources/views/          # Blade templates
│   ├── auth/                 # Login/Register pages
│   ├── domains/              # Domain management views
│   ├── layouts/              # Page layouts
│   └── welcome.blade.php     # Landing page
└── routes/
    ├── web.php               # Web routes
    └── api.php               # API routes
```

## Troubleshooting

### Domains not being checked

1. Check if scheduler is running:
```bash
docker compose logs scheduler
```

2. Verify check mode:
```bash
docker compose exec app php artisan config:show domain.check_mode
```

3. Manual check:
```bash
docker compose exec app php artisan domains:check
```

### Cloudflare Worker not connecting

1. Verify secrets are set:
```bash
cd cloudflare && wrangler secret list
```

2. Check Laravel webhook secret matches:
```bash
docker compose exec app printenv | grep CLOUDFLARE
```

3. Test API endpoint:
```bash
curl -H "Authorization: Bearer YOUR_SECRET" https://your-domain.com/api/cf/domains/due
```

### Container issues

```bash
# View logs
docker compose logs -f

# Restart all containers
docker compose down && docker compose up -d

# Rebuild containers
docker compose up -d --build --force-recreate
```

## License

This project is proprietary software. All rights reserved.

## Support

- Email: info@tech-robot-automation.com
- Phone: +971 50 586 6567
