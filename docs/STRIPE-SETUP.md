# Stripe Payment Integration - Setup Guide

## Overview

The Stripe payment integration has been successfully implemented with the following features:

- **New Pricing Tiers:**
  - Free: 20 domains, $0/month
  - Starter: 100 domains, $49/month
  - Business: 200 domains, $79/month
  - Enterprise: 500 domains, $109/month

- **Prorated Billing:** Users pay based on days remaining in the current month
- **End-of-Month Billing:** All subscriptions renew on the last day of each month
- **Immediate Upgrades:** Charge prorated difference and activate instantly
- **Scheduled Downgrades:** Take effect at next billing cycle
- **Free Plan:** No payment method required

## Setup Instructions

### 1. Environment Configuration

Add these variables to your `.env` file:

```env
# Stripe API Keys
STRIPE_KEY=pk_live_your_publishable_key_here
STRIPE_SECRET=sk_live_your_secret_key_here
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret_here
```

**Getting Your Keys:**
1. Go to [https://dashboard.stripe.com/apikeys](https://dashboard.stripe.com/apikeys)
2. Copy your Publishable key (starts with `pk_live_`)
3. Copy your Secret key (starts with `sk_live_`)

**For Testing:**
Use test mode keys instead:
- `pk_test_...` for STRIPE_KEY
- `sk_test_...` for STRIPE_SECRET

### 2. Run Database Migrations

```bash
php artisan migrate
```

This will add the following fields to the `subscriptions` table:
- `stripe_customer_id`
- `stripe_subscription_id`
- `stripe_payment_method_id`
- `next_plan_id` (for scheduled downgrades)
- `prorated_amount_cents`
- `last_payment_at`

### 3. Seed Plans

Update the plans in your database:

```bash
php artisan db:seed --class=AccountSetupSeeder
```

This creates/updates the four plans with new pricing.

### 4. Configure Stripe Webhooks

1. Go to [https://dashboard.stripe.com/webhooks](https://dashboard.stripe.com/webhooks)
2. Click "Add endpoint"
3. Set the endpoint URL: `https://yourdomain.com/api/stripe/webhook`
4. Select these events to listen for:
   - `customer.subscription.created` (new subscription)
   - `customer.subscription.updated` (plan changes, status updates)
   - `customer.subscription.deleted` (cancellations)
   - `invoice.payment_succeeded` (successful automatic billing)
   - `invoice.payment_failed` (failed automatic billing)
   - `payment_intent.succeeded` (one-time payments success)
   - `payment_intent.payment_failed` (one-time payments failure)
   - **Note:** `invoice.upcoming` is NOT needed - we don't send reminders
5. Click "Add endpoint"
6. Copy the "Signing secret" (starts with `whsec_`)
7. Add it to your `.env` as `STRIPE_WEBHOOK_SECRET`

### 5. Test the Integration

#### Using Test Cards (Test Mode)

Stripe provides test card numbers:

| Card Number | Result |
|-------------|--------|
| 4242 4242 4242 4242 | Success |
| 4000 0000 0000 0002 | Card declined |
| 4000 0025 0000 3155 | Requires authentication |

Use any future expiration date, any 3-digit CVC, and any ZIP code.

#### Testing Workflow

1. Log in to your application
2. Navigate to `/billing`
3. Select a paid plan (Starter, Business, or Enterprise)
4. Enter test card details
5. Confirm payment
6. Verify subscription is activated

### 6. Configure Stripe Smart Retries (Optional)

In Stripe Dashboard → Settings → Billing:
- Enable "Smart Retries" for automatic payment retry logic
- Configure how many times to retry failed payments
- Set up dunning emails for failed payments

### 7. Test Automatic Billing

Since Stripe handles billing automatically, you don't need a manual command. To test:

1. Create a test subscription with a test card
2. Use Stripe Dashboard → Billing → Subscriptions
3. Find your test subscription
4. Click "..." → "Advance subscription to next billing period"
5. Stripe will immediately process the renewal
6. Check your webhooks received `invoice.payment_succeeded`
7. Verify your database was updated

## File Structure

### New Files Created

```
app/
├── Billing/Services/
│   └── StripeService.php          # Core Stripe integration logic
├── Console/Commands/
│   └── ProcessMonthlyBilling.php  # Monthly billing command
└── Http/Controllers/
    ├── PaymentController.php      # Web billing UI controller
    └── Api/
        └── StripeWebhookController.php  # Webhook handler

resources/views/
└── billing/
    └── index.blade.php            # Billing & subscription page

tests/Feature/
└── StripePaymentTest.php         # Payment flow tests

database/migrations/
└── 2026_01_25_*_add_stripe_fields_to_subscriptions_table.php
```

### Modified Files

```
app/Billing/
├── Subscription.php              # Added Stripe fields
└── Services/
    └── PlanRulesService.php      # Updated default from 50 to 20 domains

app/Http/Controllers/Api/
└── SubscriptionController.php    # Added payment requirement logic

database/seeders/
└── AccountSetupSeeder.php        # Updated plans with new pricing

app/Console/
└── Kernel.php                    # Added billing command to schedule

config/
└── services.php                  # Added Stripe configuration

routes/
├── web.php                       # Added billing routes
└── api.php                       # Added webhook route
```

## Routes Added

### Web Routes (Authenticated)

```
GET  /billing                     # View billing page
POST /billing/payment-method      # Add/update payment method
POST /billing/subscribe           # Subscribe to a paid plan
POST /billing/upgrade             # Upgrade plan (immediate)
POST /billing/downgrade           # Downgrade plan (scheduled)
POST /billing/cancel              # Cancel subscription
```

### API Routes (Public)

```
POST /api/stripe/webhook          # Stripe webhook endpoint (verified by signature)
```

## How It Works

### Subscription Flow (Fully Automatic)

1. **User Selects Plan:** User visits `/billing` and selects a paid plan
2. **Enter Payment:** Stripe Elements securely collects card details
3. **Create Customer:** System creates a Stripe customer
4. **Create Subscription:** Stripe subscription is created with automatic billing enabled
5. **Activate Plan:** Subscription is activated immediately
6. **Auto-Renewal:** **Stripe automatically charges the card every month** - no manual action needed!

### Automatic Monthly Billing

**How Stripe handles recurring payments:**

1. **7 Days Before:** Stripe sends reminder email (optional)
2. **Billing Date:** Stripe automatically charges the saved payment method
3. **Success:** Webhook updates your database, user keeps access
4. **Failure:** Stripe retries according to your retry rules
5. **Multiple Failures:** Subscription marked as `past_due`, user notified

**You don't need to do anything!** Stripe manages:
- Charging customers automatically
- Handling payment failures
- Sending receipts
- Managing retry logic

### Upgrade Flow

1. **User Clicks Upgrade:** On billing page, clicks "Upgrade" button
2. **Calculate Difference:** System calculates prorated price difference
3. **Charge Difference:** Stripe charges the difference
4. **Immediate Activation:** New plan limits apply instantly
5. **No Refund:** Previous payment is not refunded (prorated forward)

### Downgrade Flow

1. **User Clicks Downgrade:** On billing page, clicks "Downgrade" button
2. **Schedule Change:** System sets `next_plan_id` in database
3. **Current Access:** User keeps current plan until renewal date
4. **Auto-Apply:** On renewal date, billing command applies the downgrade
5. **New Pricing:** Next billing charges the lower plan price

### Monthly Billing

The monthly billing is **fully automatic via Stripe**:

1. Stripe tracks each subscription's billing cycle
2. On renewal date, Stripe automatically charges the customer
3. Webhooks notify your application of successful/failed payments
4. Your database updates automatically via webhook handlers
5. For scheduled downgrades, webhooks apply the plan change

**No cron job needed for billing!** Stripe handles everything.

## Testing

Run the payment tests:

```bash
php artisan test --filter=StripePaymentTest
```

Tests cover:
- Prorated billing calculations
- Upgrade/downgrade validation
- Payment requirement enforcement
- Subscription cancellation
- Plan API endpoints

## Troubleshooting

### Webhook Not Receiving Events

1. Check webhook URL is publicly accessible
2. Verify `STRIPE_WEBHOOK_SECRET` is set correctly
3. Check Laravel logs: `storage/logs/laravel.log`
4. Test webhook in Stripe Dashboard → Webhooks → "Send test webhook"

### Payment Fails

1. Check Stripe API keys are correct (live vs test mode)
2. Verify card details are valid test cards
3. Check Laravel logs for detailed error messages
4. Review Stripe Dashboard → Logs for API errors

### Subscription Not Activating

1. Verify migration ran successfully
2. Check `subscriptions` table has Stripe fields
3. Ensure `AccountSetupSeeder` ran to create plans
4. Check for JavaScript errors in browser console

### Monthly Billing Not Running

1. Verify cron/scheduler is running: `php artisan schedule:run`
2. Check command is scheduled: `php artisan schedule:list`
3. Manually test: `php artisan billing:process-monthly`
4. Review logs for any errors

## Security Notes

1. **Never Commit API Keys:** Keep `.env` out of version control
2. **Use HTTPS:** Stripe requires HTTPS for webhooks in production
3. **Verify Webhooks:** Controller validates webhook signatures
4. **No Card Storage:** Card details never touch your server (Stripe.js handles)
5. **Rate Limiting:** Consider adding rate limits to payment endpoints

## Going Live

### Pre-Launch Checklist

- [ ] Replace test Stripe keys with live keys
- [ ] Update webhook endpoint to production URL
- [ ] Test payment flow with real card
- [ ] Verify webhook events are received
- [ ] Test monthly billing command
- [ ] Review error handling and logging
- [ ] Set up monitoring for failed payments
- [ ] Configure Stripe email receipts
- [ ] Test all plan upgrades/downgrades
- [ ] Verify domain limits are enforced

### Monitoring

Monitor these in production:

- Failed payment rates
- Webhook delivery failures
- Subscription churn
- Monthly billing command execution
- Past-due subscriptions

Check Stripe Dashboard daily for:
- Failed payments
- Disputes/chargebacks
- Revenue reports

## Support

For Stripe-specific issues:
- Stripe Documentation: https://stripe.com/docs
- Stripe Support: https://support.stripe.com
- Stripe Status: https://status.stripe.com

For application issues:
- Check `storage/logs/laravel.log`
- Review webhook logs in Stripe Dashboard
- Run tests to verify integration
