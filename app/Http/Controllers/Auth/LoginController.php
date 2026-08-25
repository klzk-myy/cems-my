<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\System\RateLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected RateLimitService $rateLimitService
    ) {}

    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::where('username', $validated['username'])->first();

        if ($user && $user->is_active && Hash::check($validated['password'], $user->password_hash)) {
            try {
                DB::transaction(function () use ($user, $request) {
                    Auth::login($user, (bool) $request->boolean('remember'));
                    $request->session()->regenerate();
                    $request->session()->put('last_activity', time());
                    $user->update(['last_login_at' => now()]);
                    $this->rateLimitService->clearFailedAttempts($request->ip());
                });

                $this->auditService->logWithSeverity('login', [
                    'user_id' => $user->id,
                    'new_values' => ['message' => 'User logged in successfully'],
                ], 'INFO');

                // BNM password policy: force rotation when the password has
                // expired (or was never stamped).
                if ($user->passwordExpired()) {
                    return redirect()
                        ->route('password.change')
                        ->with('warning', 'Your password has expired and must be changed before continuing.');
                }

                return redirect()->intended('/dashboard');
            } catch (\Throwable $e) {
                \Log::error('Login transaction failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        // Record failed login attempt for IP-based auto-blocking
        // BNM requires rate limiting and brute-force protection on login endpoints
        $ip = $request->ip();
        $this->rateLimitService->recordFailedAttempt($ip);

        // Log failed login attempt
        if ($user) {
            $this->auditService->logWithSeverity('login_failed', [
                'user_id' => $user->id,
                'new_values' => ['message' => 'Failed login attempt for IP: '.$ip],
            ], 'WARNING');
        } else {
            // Log unknown username attempts too (potential reconnaissance)
            $this->auditService->logWithSeverity('login_failed_unknown_user', [
                'new_values' => [
                    'username' => $validated['username'],
                    'ip' => $ip,
                ],
            ], 'WARNING');
        }

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request): RedirectResponse
    {
        // Clear MFA session data
        $request->session()->forget('mfa_verified');
        $request->session()->forget('mfa_verified_at');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Forced change-password form shown after login when the password has
     * expired under the BNM rotation policy.
     */
    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', 'min:12', 'different:current_password'],
        ], [
            'password.min' => 'The new password must be at least 12 characters.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password_hash)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $user->password = $validated['password']; // mutator hashes + stamps password_changed_at
        $user->save();

        $this->auditService->logWithSeverity('password_changed_forced_rotation', [
            'user_id' => $user->id,
            'entity_type' => 'User',
            'entity_id' => $user->id,
        ], 'INFO');

        return redirect('/dashboard')->with('success', 'Password updated successfully.');
    }
}
