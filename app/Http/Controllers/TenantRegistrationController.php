<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\RegistrationValidationService;
use App\Services\SimpleCaptchaService;
use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;

class TenantRegistrationController extends Controller
{
    public function __construct(
        private SimpleCaptchaService $captchaService,
        private RegistrationValidationService $validationService,
        private TenantRegistrationService $registrationService
    ) {}

    /**
     * Show registration form
     */
    public function showRegistrationForm()
    {
        // Generate captcha
        $captcha = $this->captchaService->generate();

        // Get available plans
        $plans = SubscriptionPlan::where('is_active', true)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        return view('registration.form', [
            'captcha_question' => $captcha['question'],
            'plans' => $plans,
        ]);
    }

    /**
     * Process registration
     */
    /**
     * Step 1: Initiate registration (validate & send code)
     */
    public function initiateRegistration(Request $request)
    {
        // Basic Laravel validation
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'domain' => 'required|string|alpha_dash|max:255',
            'cuit' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:255',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'plan_type' => 'required|in:trial,paid',
            'plan_id' => 'nullable|required_if:plan_type,paid|exists:subscription_plans,id',
            'billing_cycle' => 'required_if:plan_type,paid|in:monthly,yearly',
            'captcha_answer' => 'required',
            'terms' => 'accepted',
        ]);

        // Additional security validation
        $errors = $this->validationService->validate($validated);

        if (! empty($errors)) {
            return back()->withErrors($errors)->withInput();
        }

        // Generate verification code
        $code = (string) random_int(100000, 999999);

        // Store data in cache (expires in 30 minutes)
        // Use a unique key based on session or email to avoid collisions
        $key = 'registration_'.md5($validated['email']);
        \Cache::put($key, [
            'data' => $validated,
            'code' => $code,
        ], now()->addMinutes(30));

        // Store the key in session so we know which registration to confirm
        session(['registration_key' => $key]);

        // Send email
        try {
            \Mail::to($validated['email'])->send(new \App\Mail\TenantVerificationCodeMail($code, $validated['company_name']));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email: '.$e->getMessage());

            return back()->with('error', 'Error al enviar el correo de verificación. Por favor intenta nuevamente.')->withInput();
        }

        return redirect()->route('tenant.register.confirm.form');
    }

    /**
     * Step 2: Show confirmation form
     */
    public function showConfirmationForm()
    {
        $key = session('registration_key');
        if (! $key || ! \Cache::has($key)) {
            return redirect()->route('tenant.register.form')
                ->with('error', 'La sesión de registro ha expirado. Por favor comienza de nuevo.');
        }

        $cached = \Cache::get($key);

        return view('registration.confirmation', [
            'email' => $cached['data']['email'],
            'company_name' => $cached['data']['company_name'],
            'domain' => $cached['data']['domain'],
        ]);
    }

    /**
     * Step 3: Verify code and create tenant
     */
    public function confirmAndCreate(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $key = session('registration_key');
        if (! $key || ! \Cache::has($key)) {
            return redirect()->route('tenant.register.form')
                ->with('error', 'La sesión de registro ha expirado. Por favor comienza de nuevo.');
        }

        $cached = \Cache::get($key);

        if ($request->code !== $cached['code']) {
            return back()->with('error', 'Código incorrecto. Por favor verifica e intenta nuevamente.');
        }

        // Code is valid, proceed with registration
        $data = $cached['data'];

        // Register tenant
        $result = $this->registrationService->registerTenant($data);

        if (! $result['success']) {
            return back()->with('error', $result['message']);
        }

        // Clear cache and session
        \Cache::forget($key);
        session()->forget('registration_key');

        // Check if should redirect to checkout
        if (($result['redirect_to_checkout'] ?? false) && isset($result['checkout_url'])) {
            return redirect($result['checkout_url'])
                ->with('success', $result['message']);
        }

        // Success - Trial enabled, redirect to landing or success page
        return redirect()->route('landing')
            ->with('success', $result['message']);
    }

    /**
     * AJAX: Generate new captcha
     */
    public function generateCaptcha()
    {
        $captcha = $this->captchaService->generate();

        return response()->json(['question' => $captcha['question']]);
    }

    /**
     * AJAX: Check if domain is available
     */
    public function checkDomainAvailability(Request $request)
    {
        $domain = $request->input('domain');

        if (empty($domain)) {
            return response()->json(['available' => false]);
        }

        $exists = \App\Models\Tenant::where('domain', $domain)->exists();

        return response()->json(['available' => ! $exists]);
    }

    /**
     * Show verify email notice
     */
    public function showVerifyEmailNotice()
    {
        return view('registration.verify-email-notice');
    }

    /**
     * Verify email with token
     */
    public function verifyEmail($token)
    {
        $tenant = \App\Models\Tenant::where('email_verification_token', $token)->first();

        if (! $tenant) {
            return redirect()->route('landing')
                ->with('error', 'Token de verificación inválido.');
        }

        // Mark as verified
        $tenant->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        // Redirect to tenant login
        $url = "https://{$tenant->domain}.emporiodigital.test/app/login";

        return redirect()->away($url)
            ->with('success', 'Email verificado. Ya puedes iniciar sesión.');
    }
}
