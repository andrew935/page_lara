<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Billing\Subscription;
use App\Billing\Payment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhooks
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'customer.subscription.created':
                $this->handleSubscriptionCreated($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handleSubscriptionUpdated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handleSubscriptionDeleted($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handleInvoicePaymentSucceeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handleInvoicePaymentFailed($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            // Ignore upcoming invoice events - we don't send reminders
            case 'invoice.upcoming':
                Log::info('Invoice upcoming event received (ignored - no reminders sent)', [
                    'invoice_id' => $event->data->object->id ?? 'unknown',
                ]);
                break;

            default:
                Log::info('Stripe webhook received', ['type' => $event->type]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle subscription created
     */
    protected function handleSubscriptionCreated($stripeSubscription): void
    {
        Log::info('Subscription created in Stripe', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'customer_id' => $stripeSubscription->customer,
            'status' => $stripeSubscription->status,
        ]);

        // Find local subscription and update with Stripe ID
        if (isset($stripeSubscription->metadata->account_id)) {
            $subscription = Subscription::where('account_id', $stripeSubscription->metadata->account_id)->first();
            
            if ($subscription && !$subscription->stripe_subscription_id) {
                $subscription->update([
                    'stripe_subscription_id' => $stripeSubscription->id,
                    'status' => $this->mapStripeStatus($stripeSubscription->status),
                ]);
            }
        }
    }

    /**
     * Map Stripe subscription status to local status
     */
    protected function mapStripeStatus(string $stripeStatus): string
    {
        return match($stripeStatus) {
            'active', 'trialing' => 'active',
            'past_due', 'unpaid', 'incomplete' => 'past_due',
            'canceled', 'incomplete_expired' => 'canceled',
            default => 'active',
        };
    }

    /**
     * Handle successful payment intent
     */
    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        Log::info('Payment succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'customer_id' => $paymentIntent->customer,
        ]);

        // Update subscription status if needed
        if ($paymentIntent->customer) {
            $subscription = Subscription::where('stripe_customer_id', $paymentIntent->customer)
                ->where('status', 'past_due')
                ->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'active',
                    'last_payment_at' => now(),
                ]);

                Log::info('Subscription reactivated after payment', [
                    'subscription_id' => $subscription->id,
                ]);
            }
        }
    }

    /**
     * Handle failed payment intent
     */
    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'customer_id' => $paymentIntent->customer,
            'last_payment_error' => $paymentIntent->last_payment_error?->message,
        ]);

        // Mark subscription as past_due
        if ($paymentIntent->customer) {
            $subscription = Subscription::where('stripe_customer_id', $paymentIntent->customer)
                ->where('status', 'active')
                ->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'past_due',
                ]);

                Log::info('Subscription marked as past_due', [
                    'subscription_id' => $subscription->id,
                ]);
            }
        }
    }

    /**
     * Handle subscription updated
     */
    protected function handleSubscriptionUpdated($stripeSubscription): void
    {
        Log::info('Subscription updated', [
            'stripe_subscription_id' => $stripeSubscription->id,
            'status' => $stripeSubscription->status,
            'current_period_end' => $stripeSubscription->current_period_end,
        ]);

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $updates = [
                'status' => $this->mapStripeStatus($stripeSubscription->status),
            ];

            // Update renewal date based on Stripe's billing cycle
            if (isset($stripeSubscription->current_period_end)) {
                $updates['renews_at'] = \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end);
            }

            // Check if subscription is canceled
            if ($stripeSubscription->cancel_at_period_end) {
                $updates['canceled_at'] = now();
                Log::info('Subscription will cancel at period end', [
                    'subscription_id' => $subscription->id,
                    'period_end' => $updates['renews_at'] ?? 'unknown',
                ]);
            }

            $subscription->update($updates);
        }
    }

    /**
     * Handle subscription deleted
     */
    protected function handleSubscriptionDeleted($stripeSubscription): void
    {
        Log::info('Subscription deleted', [
            'stripe_subscription_id' => $stripeSubscription->id,
        ]);

        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();

        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'ends_at' => now(),
            ]);
        }
    }

    /**
     * Handle successful invoice payment (automatic recurring payment)
     */
    protected function handleInvoicePaymentSucceeded($invoice): void
    {
        Log::info('Invoice payment succeeded (automatic billing)', [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_paid,
            'customer_id' => $invoice->customer,
            'subscription_id' => $invoice->subscription ?? 'none',
        ]);

        if ($invoice->customer) {
            $subscription = Subscription::where('stripe_customer_id', $invoice->customer)->first();

            if ($subscription) {
                $updates = [
                    'last_payment_at' => now(),
                    'status' => 'active',
                ];

                // Update renewal date from invoice
                if (isset($invoice->period_end)) {
                    $updates['renews_at'] = \Carbon\Carbon::createFromTimestamp($invoice->period_end);
                }

                // Apply any scheduled plan changes
                if ($subscription->next_plan_id) {
                    $updates['plan_id'] = $subscription->next_plan_id;
                    $updates['next_plan_id'] = null;
                    
                    Log::info('Applied scheduled plan change on renewal', [
                        'subscription_id' => $subscription->id,
                        'new_plan_id' => $updates['plan_id'],
                    ]);
                }

                $subscription->update($updates);

                // Log payment to database
                Payment::create([
                    'account_id' => $subscription->account_id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                    'stripe_invoice_id' => $invoice->id,
                    'stripe_charge_id' => $invoice->charge ?? null,
                    'amount_cents' => $invoice->amount_paid,
                    'currency' => strtoupper($invoice->currency),
                    'status' => 'succeeded',
                    'type' => 'subscription',
                    'period_start' => isset($invoice->period_start) ? \Carbon\Carbon::createFromTimestamp($invoice->period_start) : null,
                    'period_end' => isset($invoice->period_end) ? \Carbon\Carbon::createFromTimestamp($invoice->period_end) : null,
                    'description' => $invoice->description ?? "Monthly subscription payment",
                    'metadata' => [
                        'invoice_number' => $invoice->number ?? null,
                        'hosted_invoice_url' => $invoice->hosted_invoice_url ?? null,
                    ],
                    'paid_at' => now(),
                ]);

                Log::info('Payment logged to database', [
                    'subscription_id' => $subscription->id,
                    'amount_paid' => $invoice->amount_paid / 100,
                    'next_renewal' => $updates['renews_at'] ?? 'unknown',
                ]);
            }
        }
    }

    /**
     * Handle failed invoice payment
     */
    protected function handleInvoicePaymentFailed($invoice): void
    {
        Log::warning('Invoice payment failed (automatic billing)', [
            'invoice_id' => $invoice->id,
            'amount' => $invoice->amount_due,
            'customer_id' => $invoice->customer,
            'attempt_count' => $invoice->attempt_count ?? 1,
        ]);

        if ($invoice->customer) {
            $subscription = Subscription::where('stripe_customer_id', $invoice->customer)->first();

            if ($subscription) {
                $subscription->update([
                    'status' => 'past_due',
                ]);

                // Log failed payment to database
                Payment::create([
                    'account_id' => $subscription->account_id,
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'stripe_payment_intent_id' => $invoice->payment_intent ?? null,
                    'stripe_invoice_id' => $invoice->id,
                    'amount_cents' => $invoice->amount_due,
                    'currency' => strtoupper($invoice->currency),
                    'status' => 'failed',
                    'type' => 'subscription',
                    'description' => "Failed subscription payment - Attempt #{$invoice->attempt_count}",
                    'metadata' => [
                        'attempt_count' => $invoice->attempt_count ?? 1,
                        'hosted_invoice_url' => $invoice->hosted_invoice_url ?? null,
                    ],
                    'failed_at' => now(),
                    'failure_reason' => $invoice->last_finalization_error->message ?? 'Payment failed',
                ]);

                Log::warning('Failed payment logged to database', [
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'attempt' => $invoice->attempt_count ?? 1,
                ]);

                // TODO: Send notification to user about failed payment
                // You could dispatch a job/notification here
            }
        }
    }
}
