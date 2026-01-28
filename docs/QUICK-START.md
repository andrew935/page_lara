# 🚀 Quick Start - Stripe Payment Integration

## ⚡ 5-Minute Setup

### 1. Get Stripe Keys (2 min)
```
1. Go to: https://dashboard.stripe.com/apikeys
2. Copy Publishable key (pk_live_...)
3. Copy Secret key (sk_live_...)
```

### 2. Update .env (1 min)
```env
STRIPE_KEY=pk_live_your_key_here
STRIPE_SECRET=sk_live_your_secret_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```

### 3. Run Commands (2 min)
```bash
php artisan migrate
php artisan db:seed --class=AccountSetupSeeder
```

### 4. Configure Webhook (Optional for later)
```
Stripe Dashboard → Webhooks → Add endpoint
URL: https://yourdomain.com/api/stripe/webhook
Events: payment_intent.succeeded, payment_intent.payment_failed, 
        invoice.payment_succeeded, invoice.payment_failed
```

## 📋 New Plans

| Plan | Domains | Price | Interval |
|------|---------|-------|----------|
| Free | 20 | $0 | 60 min |
| Starter | 100 | $49/mo | 30 min |
| Business | 200 | $79/mo | 15 min |
| Enterprise | 500 | $109/mo | 10 min |

## 🧪 Test Cards

| Card Number | Result |
|-------------|--------|
| 4242 4242 4242 4242 | ✅ Success |
| 4000 0000 0000 0002 | ❌ Decline |
| 4000 0025 0000 3155 | 🔐 Auth Required |

*Use any future date, any CVC, any ZIP*

## 🔗 Key URLs

- **Billing Page:** `/billing`
- **Webhook Endpoint:** `/api/stripe/webhook`
- **Plans API:** `/api/plans`

## 💡 How It Works

### Automatic Recurring Payments ⚡
**Stripe handles everything automatically!**
- User subscribes once with their card
- Every month, Stripe automatically charges the card
- No manual payments needed
- No reminders sent - just automatic billing
- Users can cancel anytime
- No cron jobs for billing
- Smart retry on payment failures

### Prorated Billing
Sign up on Jan 15 (31-day month):
- Days left: 16
- $49 plan × 16 days ÷ 31 days = **$25.29 charged**
- Next month: Full **$49** on last day

### Upgrade (Immediate ⚡)
1. Pay prorated difference
2. New plan active instantly
3. New limits apply now

### Downgrade (End of Period 📅)
1. Schedule for next billing
2. Keep current plan until renewal
3. Auto-switch at month end

### Free Plan
- No payment needed
- Can upgrade anytime
- Can downgrade from paid

## 🛠️ Commands

```bash
# Run tests
php artisan test --filter=StripePaymentTest

# Check webhook logs
tail -f storage/logs/laravel.log | grep Stripe
```

**Note:** No manual billing command needed! Stripe handles all recurring payments automatically.

## 📂 Key Files

```
app/Billing/Services/StripeService.php       # Core logic
app/Http/Controllers/PaymentController.php   # Billing UI
resources/views/billing/index.blade.php      # Billing page
routes/web.php                               # Added routes
```

## ⚠️ Important Notes

1. **Database must be running** before migration
2. **Use test keys** for development (pk_test_...)
3. **Webhook needs public URL** to receive events
4. **Billing is automatic** - Stripe charges customers every month
5. **Free plan** requires no payment method
6. **Smart Retries** enabled in Stripe Dashboard for failed payments

## 📞 Need Help?

Check these files:
- `STRIPE-SETUP.md` - Detailed setup guide
- `IMPLEMENTATION-SUMMARY.md` - What was built
- `README.md` - Updated with Stripe info
- `storage/logs/laravel.log` - Error logs

## ✅ You're Done!

Visit `/billing` and test with Stripe test cards! 🎉

---

**Pro Tip:** Start with test mode (pk_test_...) and switch to live mode when ready for production.
