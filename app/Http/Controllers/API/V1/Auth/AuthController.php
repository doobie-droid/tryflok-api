<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Constants\Roles;
use App\Events\User\ConfirmEmail as ConfirmEmailEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResourceWithSensitive;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\User\ForgotPassword as ForgotPasswordNotification;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Jobs\Users\SendEmailToReferrer as SendEmailToReferrerJob;
use Illuminate\Support\Facades\Cookie;
use Aws\CloudFront\CloudFrontClient;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'max:20', 'unique:users,username', 'regex:/^[A-Za-z0-9_]*$/'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'confirmed'],
                'referral_id' => ['sometimes', 'nullable','string'],
                'firebase_token' => ['sometimes', 'nullable','string'],
                'phone_number' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
                'public_id' => uniqid(rand()),
                'email_token' => Str::random(16) . 'YmdHis',
                'phone_number' => isset($request->phone_number) ? $request->phone_number : null,
                'referral_id' => strtoupper(Str::random(6)) . '-' . date('Ymd'),
            ]);

            if (! is_null($request->referral_id)) {
                $referrer = User::where('referral_id', $request->referral_id)->orWhere('username', $request->referral_id)->first();
                if (! is_null($referrer)) {
                    $user->referrer_id = $referrer->id;
                    $user->save();
                    SendEmailToReferrerJob::dispatch([
                        'referrer' => $referrer,
                        'user' => $user,
                    ]);
                }
            }

            if (! is_null($request->firebase_token)) {
                $user->notificationTokens()->create([
                    'token' => $request->firebase_token,
                ]);
            }

            event(new ConfirmEmailEvent($user));
            $user->assignRole(Roles::USER);
            //add wallet
            $user->wallet()->create([]);
            $token = JWTAuth::fromUser($user);
            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $user->id)->first();
            return $this->respondWithSuccess('Registration successful', [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function socialMediaSignIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required_if:provider,google', 'string', 'max:255'],
                'email' => ['required_if:provider,google', 'string', 'email', 'max:255'],
                'provider' => ['required', 'string', 'in:google,apple,google-web'],
                'referral_id' => ['sometimes', 'nullable','string'],
                'id_token' => ['required_if:provider,google,apple', 'string'],
                'sign_in_type' => ['required', 'string', 'in:register,login'],
                'sign_in_source' => ['required_if:provider,google', 'string', 'in:ios,android,web'],
                'firebase_token' => ['sometimes', 'nullable','string'],
                'phone_number' => ['sometimes', 'nullable', 'string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $socialIsValid = false;
            $picture = null;
            $name = $request->name;
            $email = $request->email;
            //verify the request
            switch ($request->provider) {
                case 'google':
                    $iosAppClientId = config('services.google.ios_client_id');
                    $androidAppClientId = config('services.google.android_client_id');
                    $webClientId = config('services.google.web_client_id');
                    if ($request->sign_in_source === 'ios') {
                        $client_id = $iosAppClientId;
                    } elseif ($request->sign_in_source === 'android') {
                        $client_id = $androidAppClientId;
                    } else {
                        $client_id = $webClientId;
                    }
                    $client = new \Google_Client(['client_id' => $client_id ]);
                    $payload = $client->verifyIdToken($request->id_token);
                    if ($payload) {
                        $socialIsValid = true;
                        $picture = $payload['picture'];
                    }
                    break;
                case 'apple':
                    $apiClient = new Client([
                        'base_uri' => 'https://appleid.apple.com/auth/',
                    ]);
                    $response = $apiClient->request('GET', 'keys');
                    $data = json_decode($response->getBody()->getContents(), true);
                    try {
                        $decode = JWT::decode($request->id_token, JWK::parseKeySet($data), [ 'RS256' ]);
                        $email = $decode->email;
                        $socialIsValid = true;
                    } catch (\Exception $exception) {
                        Log::error($exception);
                    }
                    break;
                default:
                    return $this->respondBadRequest('Invalid social media provider supplied');
            }

            if (! $socialIsValid) {
                return $this->respondBadRequest('An invalid token was supplied');
            }

            //check if user exists
            $user = User::where('email', $email)->first();
            if (is_null($user)) {
                $username = preg_replace("/[^a-zA-Z0-9]+/", "", $name) . "_" . date('Ymd');
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'username' => $username,
                    'password' => Hash::make(Str::random(8)),
                    'public_id' => uniqid(rand()),
                    'email_token' => Str::random(16),
                    'referral_id' => strtoupper(Str::random(6)) . '-' . date('Ymd'),
                    'phone_number' => isset($request->phone_number) ? $request->phone_number : null,
                ]);
                event(new ConfirmEmailEvent($user));
                $user->assignRole(Roles::USER);
                $user->wallet()->create([]);

                if (! is_null($request->referral_id)) {
                    $referrer = User::where('referral_id', $request->referral_id)->orWhere('username', $request->referral_id)->first();
                    if (! is_null($referrer)) {
                        $user->referrer_id = $referrer->id;
                        $user->save();
                    }
                }
            }

            if (is_null($user->wallet()->first())) {
                $user->wallet()->create([]);
            }

            if (! is_null($request->firebase_token)) {
                $firebase_token = $user->notificationTokens()->where('token', $request->firebase_token)->first();
                if (is_null($firebase_token)) {
                    $user->notificationTokens()->create([
                        'token' => $request->firebase_token,
                    ]);
                }
            }

            $token = JWTAuth::fromUser($user);
            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $user->id)->first();
            $wallet = $user->wallet()->first();
            if (is_null($wallet)) {
                $user->wallet()->create([]);
            }
            return $this->respondWithSuccess('Registration successful', [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
                'firebase_token' => ['sometimes', 'nullable','string'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            if (Auth::attempt(['email' => $request->username, 'password' => $request->password]) || Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
                $user = Auth::user();
                $token = JWTAuth::fromUser($user);
                $wallet = $user->wallet()->first();
                if (is_null($wallet)) {
                    $user->wallet()->create([]);
                }
                $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', Auth::user()->id)->first();

                if (! is_null($request->firebase_token)) {
                    $firebase_token = $user->notificationTokens()->where('token', $request->firebase_token)->first();
                    if (is_null($firebase_token)) {
                        $user->notificationTokens()->create([
                            'token' => $request->firebase_token,
                        ]);
                    }
                }
                
                $key = $request->user()->id;
                $cookies = '';
                $cookies = "Authorization=Bearer {$token };";
                $secure = true;
                $path = '/';
                $domain = '.tryflok.com';
                $time_in_minutes = 2 * 60;
                Cookie::queue($key, $token, $time_in_minutes, $path, $domain, $secure);
                return $this->respondWithSuccess('Login successful', [
                    'user' => new UserResourceWithSensitive($user),
                    'token' => $token,
                ]);
            } else {
                return $this->respondBadRequest('User credentials do not match our record');
            }
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function loginViaOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => ['required', 'string', 'exists:otps,code'],
            ]);

            if ($validator->fails()) {
                return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
            }

            $otp = Otp::where('code', $request->code)->where('purpose', 'authentication')->first();

            if (is_null($otp)) {
                return $this->respondBadRequest('Invalid OTP provided');
            }

            if ($otp->expires_at->lt(now())) {
                return $this->respondBadRequest('Access code has expired');
            }

            $otp->expires_at = now();//expire the token since it has been used
            $otp->save();
            $token = JWTAuth::fromUser($otp->user);
            $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $otp->user->id)->first();
            if (is_null($user->wallet()->first())) {
                $user->wallet()->create([]);
            }
            return $this->respondWithSuccess('Login successful', [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }

    public function refreshToken(Request $request)
    {
        $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->withCount('digiversesCreated')->where('id', $request->user()->id)->first();
        return $this->respondWithSuccess('Token refreshed successfully', [
            'user' => new UserResourceWithSensitive($user),
            'token' => auth()->refresh(),
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);
        if ($validator->fails()) {
            return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password_token = Str::random(32);
            $user->save();
            $user->notify(new ForgotPasswordNotification($user));
        }

        return $this->respondWithSuccess('Thank you, an email would be sent to your shortly');
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'firebase_token' => ['sometimes', 'nullable','string'],
        ]);
        if ($validator->fails()) {
            return $this->respondBadRequest('Invalid or missing input fields', $validator->errors()->toArray());
        }

        $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->where('password_token', $request->token)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->password_token = null;
            $user->save();
            $token = JWTAuth::fromUser($user);
            if (! is_null($request->firebase_token)) {
                $firebase_token = $user->notificationTokens()->where('token', $request->firebase_token)->first();
                if (is_null($firebase_token)) {
                    $user->notificationTokens()->create([
                        'token' => $request->firebase_token,
                    ]);
                }
            }
            $wallet = $user->wallet()->first();
            if (is_null($wallet)) {
                $user->wallet()->create([]);
            }
            return $this->respondWithSuccess('Password reset successfully', [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } else {
            return $this->respondBadRequest('Token has expired');
        }
    }

    public function verifyEmail(Request $request)
    {
        $user = User::with('roles', 'profile_picture', 'wallet', 'paymentAccounts', 'referrer')->where('email_token', $request->token)->first();
        if ($user) {
            $user->email_verified = 1;
            $user->email_token = '';
            $user->save();
            return $this->respondWithSuccess('Email verified successfully', ['user' => new UserResourceWithSensitive($user), 'token' => JWTAuth::fromUser($user)]);
        } else {
            return $this->respondBadRequest('Token has expired');
        }
    }

    public function respondUnauthenticated()
    {
        try {
            return $this->respondUnauthorized('Invalid token provided');
        } catch (\Exception $exception) {
            Log::error($exception);
            return $this->respondInternalError('Oops, an error occurred. Please try again later.');
        }
    }
}
