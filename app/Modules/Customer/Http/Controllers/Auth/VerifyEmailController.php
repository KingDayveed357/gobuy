<?php

namespace App\Modules\Customer\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Services\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyEmailController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        $secondsRemaining = $this->otp->secondsUntilResend($request->user());

        return view('auth.verify-email', compact('secondsRemaining'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        if (! $this->otp->verify($request->user(), $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => 'That code is invalid or has expired. Please request a new one.',
            ]);
        }

        return redirect()->route('account.dashboard')->with('status', 'Your email has been verified.');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.dashboard');
        }

        if (! $this->otp->canResend($request->user())) {
            $seconds = $this->otp->secondsUntilResend($request->user());

            return redirect()->route('verification.notice')->with('resend_error', "Please wait {$seconds} second(s) before requesting a new code.");
        }

        $this->otp->issue($request->user());

        return redirect()->route('verification.notice')->with('status', 'A new verification code is on its way.');
    }
}
