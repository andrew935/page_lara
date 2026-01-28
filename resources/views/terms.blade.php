@extends('layouts.master')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="card custom-card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h1 class="fw-bold mb-2">Terms of Service</h1>
                            <p class="text-muted">Last Updated: {{ now()->format('F d, Y') }}</p>
                        </div>

                        <div class="terms-content">
                            <!-- Quick Summary -->
                            <div class="alert alert-info mb-4">
                                <h5 class="alert-heading"><i class="ri-information-line me-2"></i>Key Points Summary</h5>
                                <ul class="mb-0">
                                    <li><strong>Automatic Billing:</strong> Your card is charged automatically each month - no reminders sent</li>
                                    <li><strong>Can Cancel Anytime:</strong> Cancel via billing page - takes effect at end of period</li>
                                    <li><strong>No Refunds:</strong> All payments are final - no refunds for partial months</li>
                                    <li><strong>Upgrades Immediate:</strong> Upgrades happen instantly with prorated charge</li>
                                    <li><strong>Downgrades End of Period:</strong> Downgrades take effect at next billing cycle</li>
                                </ul>
                            </div>

                            <!-- 1. Acceptance of Terms -->
                            <section class="mb-4">
                                <h3>1. Acceptance of Terms</h3>
                                <p>By accessing or using {{ config('app.name') }} ("the Service"), you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the Service.</p>
                            </section>

                            <!-- 2. Service Description -->
                            <section class="mb-4">
                                <h3>2. Service Description</h3>
                                <p>{{ config('app.name') }} is a domain monitoring service that checks your websites' availability and sends notifications via Telegram when issues are detected.</p>
                            </section>

                            <!-- 3. Account Registration -->
                            <section class="mb-4">
                                <h3>3. Account Registration</h3>
                                <h5>3.1 Account Creation</h5>
                                <ul>
                                    <li>You must provide accurate and complete information when creating an account</li>
                                    <li>You are responsible for maintaining the confidentiality of your account credentials</li>
                                    <li>You must notify us immediately of any unauthorized use of your account</li>
                                </ul>
                                <h5>3.2 Account Responsibility</h5>
                                <ul>
                                    <li>You are responsible for all activities that occur under your account</li>
                                    <li>You must be at least 18 years old to create an account</li>
                                    <li>One account per person or organization</li>
                                </ul>
                            </section>

                            <!-- 4. Subscription Plans -->
                            <section class="mb-4">
                                <h3>4. Subscription Plans and Pricing</h3>
                                <h5>4.1 Available Plans</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Plan</th>
                                                <th>Domains</th>
                                                <th>Check Interval</th>
                                                <th>Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Free</td>
                                                <td>Up to 20</td>
                                                <td>60 minutes</td>
                                                <td>$0/month</td>
                                            </tr>
                                            <tr>
                                                <td>Starter</td>
                                                <td>Up to 100</td>
                                                <td>30 minutes</td>
                                                <td>$49/month</td>
                                            </tr>
                                            <tr>
                                                <td>Business</td>
                                                <td>Up to 200</td>
                                                <td>15 minutes</td>
                                                <td>$79/month</td>
                                            </tr>
                                            <tr>
                                                <td>Enterprise</td>
                                                <td>Up to 500</td>
                                                <td>5 minutes</td>
                                                <td>$109/month</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </section>

                            <!-- 5. Billing and Payment -->
                            <section class="mb-4">
                                <h3>5. Billing and Payment</h3>
                                <div class="alert alert-warning">
                                    <strong>Important:</strong> By subscribing to a paid plan, you authorize us to charge your payment method automatically each month.
                                </div>
                                
                                <h5>5.1 Automatic Recurring Payments</h5>
                                <ul>
                                    <li><strong>Automatic charges:</strong> Your card will be charged automatically on your monthly renewal date</li>
                                    <li><strong>No reminders:</strong> No advance reminder emails will be sent before charges</li>
                                    <li><strong>Receipts:</strong> You will receive a receipt email after each successful payment</li>
                                    <li><strong>Payment processor:</strong> Payments are processed through Stripe</li>
                                </ul>

                                <h5>5.2 Billing Cycle</h5>
                                <ul>
                                    <li><strong>Monthly renewal:</strong> All subscriptions renew on the last day of each month</li>
                                    <li><strong>Prorated first payment:</strong> Your first payment will be prorated based on days remaining</li>
                                    <li><strong>Full monthly payments:</strong> Subsequent payments will be for the full monthly amount</li>
                                </ul>

                                <h5>5.3 Failed Payments</h5>
                                <ul>
                                    <li>If a payment fails, Stripe will automatically retry</li>
                                    <li>Your subscription will be marked as "past due"</li>
                                    <li>Service access may be restricted until payment is successful</li>
                                    <li>You will receive email notifications about failed payments</li>
                                </ul>
                            </section>

                            <!-- 6. Plan Changes -->
                            <section class="mb-4">
                                <h3>6. Plan Changes</h3>
                                <h5>6.1 Upgrades</h5>
                                <ul>
                                    <li><strong>Immediate:</strong> Upgrades take effect immediately</li>
                                    <li><strong>Prorated charge:</strong> You will be charged the prorated difference for remaining days</li>
                                    <li><strong>New pricing:</strong> Your next renewal will be at the new plan's full price</li>
                                </ul>

                                <h5>6.2 Downgrades</h5>
                                <ul>
                                    <li><strong>End of period:</strong> Downgrades take effect at the end of your current billing period</li>
                                    <li><strong>Keep access:</strong> You continue to have access to your current plan until the period ends</li>
                                    <li><strong>No refunds:</strong> No refunds or credits for the remaining time on your current plan</li>
                                </ul>
                            </section>

                            <!-- 7. Cancellation -->
                            <section class="mb-4">
                                <h3>7. Cancellation and Refunds</h3>
                                <div class="alert alert-success">
                                    <strong>Easy Cancellation:</strong> You can cancel your subscription at any time by visiting your <a href="{{ route('billing.index') }}" class="alert-link">billing page</a>.
                                </div>

                                <h5>7.1 How to Cancel</h5>
                                <ol>
                                    <li>Log in to your account</li>
                                    <li>Navigate to Billing page</li>
                                    <li>Click "Cancel Subscription"</li>
                                    <li>Confirm cancellation</li>
                                    <li>You'll receive confirmation email</li>
                                </ol>

                                <h5>7.2 Effect of Cancellation</h5>
                                <ul>
                                    <li><strong>End of period:</strong> Cancellation takes effect at the end of your current billing period</li>
                                    <li><strong>Retain access:</strong> You keep access to your plan features until the period ends</li>
                                    <li><strong>No future charges:</strong> No further charges will be made after cancellation</li>
                                    <li><strong>Revert to Free:</strong> Your account will revert to the Free plan</li>
                                </ul>

                                <h5>7.3 Refund Policy</h5>
                                <div class="alert alert-danger">
                                    <strong>No Refunds:</strong> All payments are non-refundable. No refunds or credits for partial months of service.
                                </div>
                            </section>

                            <!-- 8. Free Plan -->
                            <section class="mb-4">
                                <h3>8. Free Plan</h3>
                                <ul>
                                    <li>The Free plan allows monitoring up to 20 domains</li>
                                    <li>Check interval is 60 minutes</li>
                                    <li>No payment method required</li>
                                    <li>No automatic charges</li>
                                    <li>We reserve the right to modify or discontinue the Free plan at any time</li>
                                </ul>
                            </section>

                            <!-- Additional sections in collapsed accordion -->
                            <div class="accordion" id="additionalTerms">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#usage">
                                            9. Service Usage & Acceptable Use
                                        </button>
                                    </h2>
                                    <div id="usage" class="accordion-collapse collapse" data-bs-parent="#additionalTerms">
                                        <div class="accordion-body">
                                            <p>You agree not to:</p>
                                            <ul>
                                                <li>Use the Service for any illegal purpose</li>
                                                <li>Attempt to gain unauthorized access to the Service</li>
                                                <li>Interfere with or disrupt the Service</li>
                                                <li>Use automated scripts to add excessive domains</li>
                                                <li>Resell or redistribute the Service</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#privacy">
                                            10. Data and Privacy
                                        </button>
                                    </h2>
                                    <div id="privacy" class="accordion-collapse collapse" data-bs-parent="#additionalTerms">
                                        <div class="accordion-body">
                                            <p>We collect domain URLs, check results, and notification settings. Your data is used solely to provide the Service. We do not sell your data to third parties. Payment information is handled by Stripe.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#disclaimers">
                                            11. Disclaimers and Limitations
                                        </button>
                                    </h2>
                                    <div id="disclaimers" class="accordion-collapse collapse" data-bs-parent="#additionalTerms">
                                        <div class="accordion-body">
                                            <p><strong>THE SERVICE IS PROVIDED "AS-IS" WITHOUT WARRANTIES.</strong> We do not guarantee detection of all outages. We are not liable for any indirect, incidental, or consequential damages. Our total liability shall not exceed the amount you paid in the last 12 months.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact -->
                            <section class="mt-5 pt-4 border-top">
                                <h3>Contact Information</h3>
                                <p>If you have questions about these Terms, please contact us through the support page.</p>
                            </section>

                            <!-- Agreement -->
                            <div class="alert alert-primary mt-4">
                                <p class="mb-0"><strong>By using {{ config('app.name') }}, you acknowledge that you have read, understood, and agree to be bound by these Terms of Service.</strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .terms-content h3 {
            color: #495057;
            font-size: 1.5rem;
            font-weight: 600;
            margin-top: 2rem;
            margin-bottom: 1rem;
        }
        .terms-content h5 {
            color: #6c757d;
            font-size: 1.1rem;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }
        .terms-content p, .terms-content li {
            line-height: 1.8;
            color: #495057;
        }
        .terms-content ul, .terms-content ol {
            margin-bottom: 1rem;
        }
        .terms-content section {
            padding-bottom: 1.5rem;
        }
    </style>
@endsection
