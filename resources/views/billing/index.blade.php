@extends('layouts.master')

@section('styles')
<script src="https://js.stripe.com/v3/"></script>
@endsection

@section('content')
    <!-- Start::page-header -->
    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
        <div>
            <h1 class="page-title fw-medium fs-20 mb-0">Billing & Subscription</h1>
            <div class="text-muted fs-12">Manage your subscription and payment methods</div>
        </div>
    </div>
    <!-- End::page-header -->

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="ri-information-line me-2"></i>
            {{ session('info') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="ri-alert-line me-2"></i>
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Current Plan -->
    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Current Plan</div>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h4 class="fw-semibold mb-2">{{ $currentPlan->name ?? 'Free' }}</h4>
                            <p class="text-muted mb-2">
                                @if(config('app.beta_mode') && $currentPlan && $currentPlan->price_cents > 0)
                                    <span style="text-decoration: line-through; color: #999;">${{ number_format($currentPlan->price_cents / 100, 2) }} / month</span>
                                    <span class="badge bg-success ms-1">Free Beta</span>
                                @elseif($currentPlan && $currentPlan->price_cents > 0)
                                    ${{ number_format($currentPlan->price_cents / 100, 2) }} / month
                                @else
                                    No charge
                                @endif
                            </p>
                            <div class="mb-2">
                                <span class="badge bg-primary">{{ $subscription->status ?? 'N/A' }}</span>
                                @if($nextPlan)
                                    <span class="badge bg-warning ms-2">Downgrading to {{ $nextPlan->name }} on {{ $subscription->renews_at->format('M d, Y') }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <div class="text-muted small">
                                @if($subscription && $subscription->renews_at)
                                    Next billing date: {{ $subscription->renews_at->format('F d, Y') }}
                                @endif
                            </div>
                            @if($subscription && $subscription->last_payment_at)
                                <div class="text-muted small">
                                    Last payment: {{ $subscription->last_payment_at->format('M d, Y') }}
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($currentPlan)
                    <div class="mt-3 pt-3 border-top">
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Max Domains</div>
                                <div class="fw-semibold fs-16">{{ $currentPlan->max_domains }}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Check Interval</div>
                                <div class="fw-semibold fs-16">{{ $currentPlan->check_interval_minutes }} min</div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-muted small mb-1">Monthly Price</div>
                                <div class="fw-semibold fs-16">
                                    @if(config('app.beta_mode') && $currentPlan->price_cents > 0)
                                        <span style="text-decoration: line-through; color: #999;">${{ number_format($currentPlan->price_cents / 100, 2) }}</span>
                                        <span class="text-success">Free Beta</span>
                                    @elseif($currentPlan->price_cents > 0)
                                        ${{ number_format($currentPlan->price_cents / 100, 2) }}
                                    @else
                                        Free
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method -->
    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Payment Method</div>
                </div>
                <div class="card-body">
                    @if(config('app.beta_mode'))
                        <div class="alert alert-success mb-0">
                            <i class="ri-gift-line me-2"></i>
                            <strong>Beta Period:</strong> No payment method required during beta. Enjoy all features for free!
                        </div>
                    @else
                        @if($subscription && $subscription->stripe_payment_method_id)
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="ri-bank-card-line fs-18 me-2"></i>
                                    <span>Payment method on file</span>
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentMethodModal">
                                    Update Payment Method
                                </button>
                            </div>
                        @else
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="ri-alert-line fs-18 me-2 text-warning"></i>
                                    <span class="text-muted">No payment method added yet</span>
                                    @if($currentPlan && $currentPlan->price_cents > 0)
                                        <span class="badge bg-warning ms-2">Required for paid plan</span>
                                    @endif
                                </div>
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#paymentMethodModal">
                                    <i class="ri-add-line me-1"></i>
                                    Add Payment Method
                                </button>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Available Plans -->
    <div class="row mt-3">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Available Plans</div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($plans as $plan)
                        <div class="col-lg-3 col-md-6">
                            <div class="card border {{ $currentPlan && $currentPlan->id === $plan->id ? 'border-primary' : '' }} h-100">
                                <div class="card-body text-center">
                                    <h5 class="fw-semibold mb-3">{{ $plan->name }}</h5>
                                    <div class="mb-3">
                                        <span class="fs-24 fw-bold">
                                            @if(config('app.beta_mode') && $plan->price_cents > 0)
                                                <span style="text-decoration: line-through; color: #999; font-size: 0.6em;">${{ number_format($plan->price_cents / 100, 0) }}/mo</span>
                                                <span class="text-success d-block">Free Beta</span>
                                            @elseif($plan->price_cents > 0)
                                                ${{ number_format($plan->price_cents / 100, 0) }}
                                                <span class="text-muted">/month</span>
                                            @else
                                                Free
                                            @endif
                                        </span>
                                    </div>
                                    
                                    <div class="text-start mb-3">
                                        <div class="mb-2">
                                            <i class="ri-check-line text-success me-2"></i>
                                            <span>Up to {{ $plan->max_domains }} domains</span>
                                        </div>
                                        @if($plan->slug !== 'free')
                                        <div class="mb-2">
                                            <small class="text-muted">
                                                Your domains: <strong class="{{ $currentDomainCount > $plan->max_domains ? 'text-danger' : '' }}">{{ $currentDomainCount }}</strong> / {{ $plan->max_domains }}
                                                @if($currentDomainCount > $plan->max_domains)
                                                    <span class="text-danger">({{ $currentDomainCount - $plan->max_domains }} over limit)</span>
                                                @endif
                                            </small>
                                        </div>
                                        @endif
                                        <div class="mb-2">
                                            <i class="ri-check-line text-success me-2"></i>
                                            <span>{{ $plan->check_interval_minutes }} min checks</span>
                                        </div>
                                        <div class="mb-2">
                                            <i class="ri-check-line text-success me-2"></i>
                                            <span>Email & Telegram alerts</span>
                                        </div>
                                        @if(isset($plan->history_retention_days) && $plan->history_retention_days > 0)
                                        <div class="mb-2">
                                            <i class="ri-check-line text-success me-2"></i>
                                            <span>{{ $plan->history_retention_days }}-day history</span>
                                        </div>
                                        @endif
                                        @if($plan->price_cents > 0)
                                        <div class="mb-2">
                                            <i class="ri-check-line text-success me-2"></i>
                                            <span>SSL check (certificate validity)</span>
                                        </div>
                                        @endif
                                    </div>

                                    @if(config('app.beta_mode'))
                                        @if($currentPlan && $currentPlan->id === $plan->id)
                                            <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                                        @else
                                            <button type="button" class="btn btn-primary w-100 beta-select-btn" data-plan-slug="{{ $plan->slug }}">
                                                Select Plan (Free Beta)
                                            </button>
                                        @endif
                                    @else
                                        @if($currentPlan && $currentPlan->id === $plan->id)
                                            <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                                        @elseif($plan->price_cents > 0)
                                            @if($currentPlan && $plan->price_cents > $currentPlan->price_cents)
                                                <button type="button" class="btn btn-primary w-100 upgrade-btn" data-plan-slug="{{ $plan->slug }}" data-plan-name="{{ $plan->name }}" data-plan-price="{{ $plan->price_cents }}">
                                                    Upgrade
                                                </button>
                                            @elseif($currentPlan && $plan->price_cents < $currentPlan->price_cents)
                                                <button type="button" class="btn btn-warning w-100 downgrade-btn" data-plan-slug="{{ $plan->slug }}" data-plan-name="{{ $plan->name }}">
                                                    Downgrade
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-primary w-100 subscribe-btn" data-plan-slug="{{ $plan->slug }}" data-plan-name="{{ $plan->name }}" data-plan-price="{{ $plan->price_cents }}">
                                                    Subscribe
                                                </button>
                                            @endif
                                        @else
                                            @if($currentPlan && $currentPlan->price_cents > 0)
                                                <button type="button" class="btn btn-outline-secondary w-100 cancel-btn">
                                                    Cancel Subscription
                                                </button>
                                            @else
                                                <button class="btn btn-outline-secondary w-100" disabled>Free Plan</button>
                                            @endif
                                        @endif
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Method Modal -->
    <div class="modal fade" id="paymentMethodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add/Update Payment Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($currentPlan && $currentPlan->price_cents > 0 && !$subscription->stripe_payment_method_id && $proratedAmount)
                    <div class="alert alert-info mb-3">
                        <h6 class="mb-2"><i class="ri-information-line me-1"></i> Payment Information</h6>
                        <p class="mb-1"><strong>Today's Charge:</strong> ${{ number_format($proratedAmount / 100, 2) }}</p>
                        <p class="mb-1 text-muted small">Prorated for {{ number_format($daysRemaining, 2) }} day(s) remaining in {{ now()->format('F') }}</p>
                        <hr class="my-2">
                        <p class="mb-0 text-muted small">
                            <i class="ri-calendar-line me-1"></i>
                            Starting {{ now()->addMonth()->endOfMonth()->format('F d, Y') }}, you'll be charged the full amount of ${{ number_format($currentPlan->price_cents / 100, 2) }}/month on the last day of each month.
                        </p>
                    </div>
                    @endif
                    
                    <form id="payment-method-form">
                        <div class="mb-3">
                            <label class="form-label">Card Details</label>
                            <div id="card-element" class="form-control" style="height: 40px; padding-top: 10px;"></div>
                            <div id="card-errors" class="text-danger mt-2"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    @if($currentPlan && $currentPlan->price_cents > 0 && !$subscription->stripe_payment_method_id && $proratedAmount)
                        <button type="button" class="btn btn-primary" id="save-payment-method">
                            <i class="ri-bank-card-line me-1"></i>
                            Pay ${{ number_format($proratedAmount / 100, 2) }} Now
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" id="save-payment-method">Save Payment Method</button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Subscribe Modal -->
    <div class="modal fade" id="subscribeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subscribe to <span id="subscribe-plan-name"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <p class="mb-2">You will be charged a prorated amount for the remaining days in this month.</p>
                        <p class="mb-0">Starting next month, you will be charged the full amount on the last day of each month.</p>
                    </div>
                    <div class="alert alert-warning">
                        <p class="mb-0"><strong>Automatic Billing:</strong> Your card will be charged automatically each month. No advance reminders will be sent. You can cancel anytime.</p>
                    </div>
                    <form id="subscribe-form">
                        <input type="hidden" id="subscribe-plan-slug">
                        <div class="mb-3">
                            <label class="form-label">Card Details</label>
                            <div id="subscribe-card-element" class="form-control" style="height: 40px; padding-top: 10px;"></div>
                            <div id="subscribe-card-errors" class="text-danger mt-2"></div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms-accept" required>
                            <label class="form-check-label" for="terms-accept">
                                I agree to the <a href="{{ route('terms') }}" target="_blank">Terms of Service</a> and authorize automatic monthly billing
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirm-subscribe">Subscribe Now</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    let elements = null;
    let cardElement = null;
    let subscribeCardElement = null;

    // Initialize Stripe Elements only when needed
    function initPaymentMethodElement() {
        if (!elements) {
            elements = stripe.elements();
        }
        if (!cardElement) {
            const cardElementContainer = document.getElementById('card-element');
            if (cardElementContainer) {
                // Clear any existing content
                cardElementContainer.innerHTML = '';
                try {
                    cardElement = elements.create('card', {
                        hidePostalCode: true, // Hide postal code field
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#32325d',
                                '::placeholder': {
                                    color: '#aab7c4'
                                }
                            }
                        }
                    });
                    cardElement.mount('#card-element');
                    cardElement.on('change', (event) => {
                        const displayError = document.getElementById('card-errors');
                        if (displayError) {
                            displayError.textContent = event.error ? event.error.message : '';
                        }
                    });
                } catch (error) {
                    console.error('Error creating payment method element:', error);
                }
            }
        }
    }

    function initSubscribeElement() {
        if (!elements) {
            elements = stripe.elements();
        }
        if (!subscribeCardElement) {
            const subscribeCardElementContainer = document.getElementById('subscribe-card-element');
            if (subscribeCardElementContainer) {
                // Clear any existing content
                subscribeCardElementContainer.innerHTML = '';
                try {
                    subscribeCardElement = elements.create('card', {
                        hidePostalCode: true, // Hide postal code field
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#32325d',
                                '::placeholder': {
                                    color: '#aab7c4'
                                }
                            }
                        }
                    });
                    subscribeCardElement.mount('#subscribe-card-element');
                    subscribeCardElement.on('change', (event) => {
                        const displayError = document.getElementById('subscribe-card-errors');
                        if (displayError) {
                            displayError.textContent = event.error ? event.error.message : '';
                        }
                    });
                } catch (error) {
                    console.error('Error creating subscribe element:', error);
                }
            }
        }
    }

    function destroyPaymentMethodElement() {
        if (cardElement) {
            cardElement.unmount();
            cardElement = null;
        }
    }

    function destroySubscribeElement() {
        if (subscribeCardElement) {
            subscribeCardElement.unmount();
            subscribeCardElement = null;
        }
    }

    // Initialize elements when modals are opened
    const paymentMethodModal = document.getElementById('paymentMethodModal');
    if (paymentMethodModal) {
        paymentMethodModal.addEventListener('show.bs.modal', () => {
            initPaymentMethodElement();
        });
        paymentMethodModal.addEventListener('hidden.bs.modal', () => {
            destroyPaymentMethodElement();
        });
    }

    const subscribeModal = document.getElementById('subscribeModal');
    if (subscribeModal) {
        subscribeModal.addEventListener('show.bs.modal', () => {
            initSubscribeElement();
        });
        subscribeModal.addEventListener('hidden.bs.modal', () => {
            destroySubscribeElement();
        });
    }

    // Save payment method
    document.getElementById('save-payment-method').addEventListener('click', async (event) => {
        if (!cardElement) {
            alert('Please wait for the payment form to load');
            return;
        }

        const button = event.target;
        button.disabled = true;
        button.textContent = 'Processing...';

        const {paymentMethod, error} = await stripe.createPaymentMethod({
            type: 'card',
            card: cardElement,
        });

        if (error) {
            document.getElementById('card-errors').textContent = error.message;
            button.disabled = false;
            button.textContent = 'Save Payment Method';
        } else {
            // Send payment method to server
            fetch('{{ route('billing.payment-method') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    payment_method_id: paymentMethod.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    // Trigger custom event for payment required check
                    document.dispatchEvent(new CustomEvent('paymentMethodAdded'));
                    
                    alert(data.message);
                    location.reload();
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                button.disabled = false;
                button.textContent = 'Save Payment Method';
            });
        }
    });

    // Subscribe button handlers
    document.querySelectorAll('.subscribe-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const planSlug = btn.dataset.planSlug;
            const planName = btn.dataset.planName;
            document.getElementById('subscribe-plan-name').textContent = planName;
            document.getElementById('subscribe-plan-slug').value = planSlug;
            new bootstrap.Modal(document.getElementById('subscribeModal')).show();
        });
    });

    // Confirm subscribe
    document.getElementById('confirm-subscribe').addEventListener('click', async (event) => {
        if (!subscribeCardElement) {
            alert('Please wait for the payment form to load');
            return;
        }

        const button = event.target;
        const termsCheckbox = document.getElementById('terms-accept');
        
        // Check if terms are accepted
        if (!termsCheckbox.checked) {
            alert('Please accept the Terms of Service to continue');
            return;
        }
        
        button.disabled = true;
        button.textContent = 'Processing...';

        const {paymentMethod, error} = await stripe.createPaymentMethod({
            type: 'card',
            card: subscribeCardElement,
        });

        if (error) {
            document.getElementById('subscribe-card-errors').textContent = error.message;
            button.disabled = false;
            button.textContent = 'Subscribe Now';
        } else {
            const planSlug = document.getElementById('subscribe-plan-slug').value;
            
            fetch('{{ route('billing.subscribe') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    plan_slug: planSlug,
                    payment_method_id: paymentMethod.id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.message) {
                    // Trigger custom event for payment required check
                    document.dispatchEvent(new CustomEvent('paymentMethodAdded'));
                    
                    alert(data.message);
                    location.reload();
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
                button.disabled = false;
                button.textContent = 'Subscribe Now';
            });
        }
    });

    // Upgrade button handlers
    document.querySelectorAll('.upgrade-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const planSlug = btn.dataset.planSlug;
            const planName = btn.dataset.planName;
            
            if (confirm(`Upgrade to ${planName}? You will be charged a prorated amount for the remaining days in this billing cycle.`)) {
                btn.disabled = true;
                btn.textContent = 'Processing...';
                
                fetch('{{ route('billing.upgrade') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_slug: planSlug
                    })
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    location.reload();
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                    btn.disabled = false;
                    btn.textContent = 'Upgrade';
                });
            }
        });
    });

    // Downgrade button handlers
    document.querySelectorAll('.downgrade-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const planSlug = btn.dataset.planSlug;
            const planName = btn.dataset.planName;
            
            if (confirm(`Downgrade to ${planName}? The change will take effect at the end of your current billing period.`)) {
                btn.disabled = true;
                btn.textContent = 'Processing...';
                
                fetch('{{ route('billing.downgrade') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        plan_slug: planSlug
                    })
                })
                .then(response => {
                    return response.json().then(data => ({ status: response.status, data }));
                })
                .then(({ status, data }) => {
                    if (status === 422 && data.excess_domains) {
                        // User has too many domains
                        alert(`${data.message}\n\nGo to Domains page to delete ${data.excess_domains} domain(s) before downgrading.`);
                        btn.disabled = false;
                        btn.textContent = 'Downgrade';
                    } else if (status === 200 || status === 201) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to schedule downgrade');
                        btn.disabled = false;
                        btn.textContent = 'Downgrade';
                    }
                })
                .catch(error => {
                    alert('Error: ' + (error.message || 'Failed to schedule downgrade'));
                    btn.disabled = false;
                    btn.textContent = 'Downgrade';
                });
            }
        });
    });

    // Cancel subscription handler
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            if (confirm('Cancel your subscription? You will be downgraded to the Free plan at the end of your billing period.')) {
                btn.disabled = true;
                btn.textContent = 'Processing...';
                
                fetch('{{ route('billing.cancel') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => {
                    return response.json().then(data => ({ status: response.status, data }));
                })
                .then(({ status, data }) => {
                    if (status === 422 && data.excess_domains) {
                        // User has too many domains
                        alert(`${data.message}\n\nGo to Domains page to delete ${data.excess_domains} domain(s) before canceling.`);
                        btn.disabled = false;
                        btn.textContent = 'Cancel Subscription';
                    } else if (status === 200 || status === 201) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message || 'Failed to cancel subscription');
                        btn.disabled = false;
                        btn.textContent = 'Cancel Subscription';
                    }
                })
                .catch(error => {
                    alert('Error: ' + (error.message || 'Failed to cancel subscription'));
                    btn.disabled = false;
                    btn.textContent = 'Cancel Subscription';
                });
            }
        });
    });

    // Beta plan selection (no payment required)
    document.querySelectorAll('.beta-select-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const planSlug = btn.dataset.planSlug;
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch('{{ route('billing.beta-select') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ plan_slug: planSlug })
            })
            .then(response => response.json().then(data => ({ status: response.status, data })))
            .then(({ status, data }) => {
                if (status === 422) {
                    alert(data.message || 'Cannot switch to this plan.');
                    btn.disabled = false;
                    btn.textContent = 'Select Plan (Free Beta)';
                } else {
                    alert(data.message || 'Plan updated.');
                    location.reload();
                }
            })
            .catch(error => {
                alert('Error: ' + (error.message || 'Failed to update plan'));
                btn.disabled = false;
                btn.textContent = 'Select Plan (Free Beta)';
            });
        });
    });

    // Force payment method addition for paid plans without payment (skip during beta)
    @if(!config('app.beta_mode') && $currentPlan && $currentPlan->price_cents > 0 && (!$subscription || !$subscription->stripe_payment_method_id))
    (function() {
        const paymentRequired = true;
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentMethodModal'));
        
        // Show modal on page load
        setTimeout(() => {
            paymentModal.show();
        }, 500);

        // Intercept all link clicks
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a');
            if (link && !link.closest('#paymentMethodModal')) {
                // Allow clicks within the payment modal
                const href = link.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('#paymentMethodModal')) {
                    e.preventDefault();
                    e.stopPropagation();
                    paymentModal.show();
                    
                    // Show toast/alert
                    const toast = document.createElement('div');
                    toast.className = 'alert alert-warning position-fixed top-0 start-50 translate-middle-x mt-3';
                    toast.style.zIndex = '9999';
                    toast.innerHTML = '<i class="ri-alert-line me-2"></i>Please add a payment method to continue';
                    document.body.appendChild(toast);
                    setTimeout(() => toast.remove(), 3000);
                    
                    return false;
                }
            }
        }, true);

        // Intercept sidebar navigation
        document.querySelectorAll('.app-sidebar a, .main-sidebar a, .sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                paymentModal.show();
                return false;
            }, true);
        });

        // Prevent modal from being closed without payment
        const modalElement = document.getElementById('paymentMethodModal');
        modalElement.addEventListener('hide.bs.modal', function(e) {
            if (paymentRequired) {
                e.preventDefault();
                e.stopPropagation();
                
                // Show warning
                const warning = modalElement.querySelector('.modal-body');
                let alertDiv = warning.querySelector('.payment-required-alert');
                if (!alertDiv) {
                    alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger payment-required-alert mb-3';
                    alertDiv.innerHTML = '<strong>Payment Required!</strong> You must add a payment method to use the {{ $currentPlan->name }} plan.';
                    warning.insertBefore(alertDiv, warning.firstChild);
                }
                return false;
            }
        });

        // Listen for successful payment method addition
        document.addEventListener('paymentMethodAdded', function() {
            paymentRequired = false;
            location.reload(); // Reload page to update UI
        });
    })();
    @endif
</script>
@endsection
