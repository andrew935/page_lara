<?php

namespace App\Http\Controllers;

use App\Billing\Plan;
use App\Billing\Subscription;
use App\Models\Promotion;
use App\Models\User;
use App\Support\AccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('domains.index'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    /**
     * Show the registration form.
     */
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request.
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'terms' => ['required', 'accepted'],
            'plan' => ['nullable', 'string', 'in:free,starter,business,enterprise'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Assign default "User" role if it exists.
        $userRole = Role::firstOrCreate(['name' => 'user']);
        $user->assignRole($userRole);

        Auth::login($user);

        // Create / attach a dedicated account for this user (prevents seeing shared account domains)
        $account = AccountResolver::current();

        // Apply active promotion for new signups (Max for free during a configured window)
        $promotion = Promotion::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderByDesc('starts_at')
            ->first();

        if ($promotion) {
            $promoPlan = Plan::where('slug', $promotion->promo_plan_slug)->where('active', true)->first();
            $freePlan = Plan::where('slug', 'free')->where('active', true)->first();

            if ($promoPlan && $freePlan) {
                $promoEndsAt = now()->addDays((int) $promotion->duration_days);

                Subscription::updateOrCreate(
                    ['account_id' => $account->id],
                    [
                        'plan_id' => $promoPlan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                        'promo_ends_at' => $promoEndsAt,
                        'promo_source_promotion_id' => $promotion->id,
                    ]
                );
            }
        } elseif (!empty($data['plan'])) {
            // If user selected a specific plan and no promotion is active
            $selectedPlan = Plan::where('slug', $data['plan'])->where('active', true)->first();
            if ($selectedPlan) {
                Subscription::updateOrCreate(
                    ['account_id' => $account->id],
                    [
                        'plan_id' => $selectedPlan->id,
                        'status' => 'active',
                        'starts_at' => now(),
                    ]
                );

                // If it's a paid plan, redirect to billing page to collect payment
                if ($selectedPlan->price_cents > 0) {
                    return redirect()->route('billing.index')
                        ->with('info', 'Please add a payment method to activate your ' . $selectedPlan->name . ' plan subscription.');
                }
            }
        }

        return redirect()->route('domains.index');
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

