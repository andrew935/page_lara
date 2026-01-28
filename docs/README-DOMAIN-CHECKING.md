# Domain Checking Modes

This application supports two modes for checking domain availability: **Server-Based** (free) and **Cloudflare Workers** (paid).

## 🔄 Switching Between Modes

### Via Web Interface (Recommended)

1. Login to your dashboard
2. Navigate to **Settings → Domain Check Settings**
3. Select your preferred mode
4. Click **Save Configuration**
5. Follow the on-screen instructions

### Via Command Line

Edit `docker/env.docker` or `.env`:

```env
# For Server Mode (default, free)
DOMAIN_CHECK_MODE=server

# For Cloudflare Mode (paid, $5/month)
DOMAIN_CHECK_MODE=cloudflare
CLOUDFLARE_WEBHOOK_SECRET=your-secret-here
```

---

## 📊 Mode Comparison

| Feature | Server Mode | Cloudflare Mode |
|---------|-------------|-----------------|
| **Cost** | Free | $5/month |
| **Speed** | 10 domains/minute | 50 concurrent workers |
| **500 domains** | ~50 minutes | ~2 minutes |
| **Server Load** | Medium | None (offloaded) |
| **Scalability** | Up to ~1,000 domains | 10,000+ domains |
| **Setup** | Already configured | Requires Cloudflare account |

---

## 🖥️ Server Mode (Default)

### How It Works

- Laravel scheduler runs every minute
- Checks 10 domains per minute
- Uses server's queue workers
- All domains checked within check interval

### Requirements

- Docker running
- Scheduler service active
- 4GB+ RAM (for 500 domains)

### Commands

```bash
# Start server checking
docker compose up -d scheduler

# View scheduler logs
docker compose logs scheduler -f

# Check status
docker compose ps
```

### When to Use

✅ Up to 500 domains  
✅ Cost-conscious  
✅ Don't need instant checks  
✅ Have server resources

---

## ☁️ Cloudflare Workers Mode

### How It Works

- Cloudflare Cron triggers every 20 minutes
- Queues all 500 domains at once
- 50 workers check domains in parallel
- Results sent back to your Laravel API
- All domains checked in 1-2 minutes

### Requirements

- Cloudflare account with [Workers Paid plan](https://dash.cloudflare.com/workers/plans) ($5/month)
- Node.js 18+ (for deployment)
- Wrangler CLI

### Setup

#### 1. Upgrade Cloudflare Plan

Visit https://dash.cloudflare.com/workers/plans and upgrade to **Workers Paid** ($5/month).

#### 2. Deploy Worker (One-Time)

```bash
# Install dependencies
cd cloudflare
npm install

# Login to Cloudflare
npx wrangler login

# Create queues
npx wrangler queues create domain-check-queue
npx wrangler queues create domain-check-dlq

# Set secrets
npx wrangler secret put LARAVEL_API_URL
# Enter: https://your-domain.com

npx wrangler secret put WEBHOOK_SECRET
# Generate with: openssl rand -hex 32
# Enter the generated secret

# Deploy worker
npm run deploy
```

#### 3. Configure Laravel

Add to `docker/env.docker`:

```env
DOMAIN_CHECK_MODE=cloudflare
CLOUDFLARE_WEBHOOK_SECRET=your-secret-from-step-2
```

Restart Laravel:

```bash
docker compose restart app
```

#### 4. Stop Server Scheduler

```bash
docker compose stop scheduler
```

### Testing

```bash
# View real-time logs
cd cloudflare
npx wrangler tail

# Manually trigger check cycle
curl -X POST https://domain-checker.YOUR-SUBDOMAIN.workers.dev/trigger \
  -H "Authorization: Bearer YOUR_SECRET"
```

### When to Use

✅ 500+ domains  
✅ Need fast checking (< 5 min)  
✅ Want to reduce server load  
✅ Have budget ($5/month)  
✅ Need global edge checking

---

## 🔧 Troubleshooting

### Server Mode Not Checking

```bash
# Check scheduler is running
docker compose ps scheduler

# View scheduler logs
docker compose logs scheduler -f

# Restart scheduler
docker compose restart scheduler

# Check config
docker compose exec app php artisan config:show domain
```

### Cloudflare Mode Not Working

```bash
# Check worker logs
cd cloudflare
npx wrangler tail

# Test webhook endpoint
curl -X GET "https://your-domain.com/api/cf/domains/due?limit=5" \
  -H "Authorization: Bearer YOUR_SECRET"

# Verify secrets
npx wrangler secret list
```

### Switching Modes

**From Server → Cloudflare:**
1. Deploy Cloudflare worker (see setup above)
2. Update DOMAIN_CHECK_MODE=cloudflare
3. Stop scheduler: `docker compose stop scheduler`

**From Cloudflare → Server:**
1. Update DOMAIN_CHECK_MODE=server
2. Start scheduler: `docker compose up -d scheduler`
3. (Optional) Delete Cloudflare worker to stop charges

---

## 💰 Cost Analysis

### Server Mode
- **Monthly cost:** $0 (server already paid for)
- **Best for:** 50-500 domains

### Cloudflare Mode
- **Cloudflare Workers Paid:** $5/month
- **Queue operations:** Included (10M/month)
- **Total:** $5/month
- **Best for:** 500+ domains needing fast checks

---

## 📚 Additional Resources

- [Cloudflare Worker Setup Guide](cloudflare/README.md)
- [Laravel Scheduler Documentation](https://laravel.com/docs/scheduling)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

---

## 🆘 Support

For issues or questions:
1. Check the troubleshooting section above
2. View logs: `docker compose logs -f`
3. Check Cloudflare dashboard for worker status
4. Review `cloudflare/README.md` for detailed Cloudflare setup

