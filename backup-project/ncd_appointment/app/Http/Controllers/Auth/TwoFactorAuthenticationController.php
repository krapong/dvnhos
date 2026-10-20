<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthenticationController extends Controller
{
    /**
     * Show the 2FA challenge view.
     */
    public function challenge(Request $request)
    {
        if (! $request->session()->has('2fa:user:id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge');
    }

    /**
     * Verify the 2FA code during login.
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $userId = $request->session()->get('2fa:user:id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user) {
            return redirect()->route('login');
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->code);

        if ($valid) {
            Auth::login($user, $request->session()->get('2fa:remember', false));
            $request->session()->forget('2fa:user:id');
            $request->session()->forget('2fa:remember');
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->withErrors(['code' => 'รหัสยืนยันตัวตนไม่ถูกต้อง']);
    }

    /**
     * Set up 2FA (Generate secret and show form)
     */
    public function setup(Request $request)
    {
        $user = Auth::user();
        
        if ($user->google2fa_secret) {
            return back()->with('status', '2FA is already enabled.');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Save secret to session temporarily
        $request->session()->put('2fa_setup_secret', $secret);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->hosxp_loginname ?? $user->name,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $svg = $writer->writeString($qrCodeUrl);

        return Inertia::render('Profile/TwoFactorSetup', [
            'qrCodeSvg' => $svg,
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm 2FA setup with a code.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = $request->session()->get('2fa_setup_secret');

        if (! $secret) {
            return back()->withErrors(['code' => 'Session expired. Please try again.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $request->code);

        if ($valid) {
            $user = Auth::user();
            $user->google2fa_secret = $secret;
            $user->save();

            $request->session()->forget('2fa_setup_secret');

            return redirect()->route('profile.edit')->with('status', 'two-factor-authentication-enabled');
        }

        return back()->withErrors(['code' => 'รหัสไม่ถูกต้อง กรุณาลองใหม่']);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request)
    {
        $user = Auth::user();
        $user->google2fa_secret = null;
        $user->save();

        return redirect()->route('profile.edit')->with('status', 'two-factor-authentication-disabled');
    }
}
