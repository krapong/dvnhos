<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loginname' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $loginname = $this->input('loginname');
        $password = $this->input('password');

        // Check against HOSxP opduser table
        $opduser = DB::connection('hosxp')->table('opduser')
            ->where('loginname', $loginname)
            ->where(function ($query) {
                $query->where('account_disable', '!=', 'Y')
                      ->orWhereNull('account_disable');
            })
            ->first();

        // Validate password
        $isValid = false;
        if ($opduser) {
            $md5Password = strtoupper(md5($password));
            if (
                $opduser->passweb === $md5Password || 
                $opduser->password === $password || 
                ($opduser->password_text !== null && $opduser->password_text === $password)
            ) {
                $isValid = true;
            } else {
                \Illuminate\Support\Facades\Log::warning("Login failed for {$loginname}: Password mismatch. Expected MD5: {$opduser->passweb}, Got: {$md5Password}");
            }
        } else {
            \Illuminate\Support\Facades\Log::warning("Login failed for {$loginname}: User not found or disabled.");
        }

        if (! $isValid) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'loginname' => trans('auth.failed'),
            ]);
        }
        
        // Ensure local user exists
        $localUser = User::updateOrCreate(
            ['hosxp_loginname' => $loginname],
            [
                'name' => iconv('TIS-620', 'UTF-8//IGNORE', $opduser->name ?? $loginname),
                'password' => null, // We don't store passwords locally anymore
            ]
        );
        
        // We don't log the user in directly here anymore, return the user for 2FA check
        // Auth::login($localUser, $this->boolean('remember'));
        // RateLimiter::clear($this->throttleKey());
        
        return $localUser;
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'loginname' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('loginname')).'|'.$this->ip());
    }
}
