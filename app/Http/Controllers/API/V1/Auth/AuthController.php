<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Otp;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Notifications\User\ForgotPassword as ForgotPasswordNotification;
use Illuminate\Support\Facades\Mail;
use App\Constants\Roles;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserResourceWithSensitive;
use App\Events\User\ConfirmEmail as ConfirmEmailEvent;
use GuzzleHttp\Client;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255'],
				'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
				'password' => ['required', 'string', 'confirmed'],
                'referral_id' => ['sometimes', 'nullable','string', 'exists:users,referral_id'],
                'role' => ['sometimes', 'required', 'string', 'regex:(creator|user)'],
			]);
			
			if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $user = User::create([
                'name' => $request->name,
                'email' =>$request->email,
                'password' => Hash::make($request->password),
                'public_id' => uniqid(rand()),
                'email_token' => Str::random(16),
                'referral_id' => strtoupper(Str::random(6)) . "-" . date('ymd'),
            ]);

            if (!is_null($request->referral_id)) {
                $referrer = User::where('referral_id', $request->referral_id)->first();
                $user->referrer_id = $referrer->id;
                $user->save();
            }

            event(new ConfirmEmailEvent($user));
            $user->assignRole(Roles::USER);
            if (!is_null($request->role) && $request->role === Roles::CREATOR) {
                $user->assignRole(Roles::CREATOR);
            }
            //add wallet
            $user->wallet()->create([
                'public_id' => uniqid(rand()),
            ]);
            $token = JWTAuth::fromUser($user);
            $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $user->id)->first();
            return $this->respondWithSuccess("Registration successful", [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } catch(\Exception $exception) {
			Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function socialMediaSignIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => ['required_if:provider,google', 'string', 'max:255'],
				'email' => ['required_if:provider,google', 'string', 'email', 'max:255',],
				'provider' => ['required', 'string', 'regex:(google|apple)',],
                'referral_id' => ['sometimes', 'nullable','string', 'exists:users,referral_id',],
                'id_token' => ['required_if:provider,google,apple', 'string',],
                'sign_in_type' => ['required', 'string', 'regex:(register|login)',],
                'sign_in_source' => ['required_if:provider,google', 'string', 'regex:(ios|android)',],
			]);

            if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $socialIsValid = false;
            $picture = NULL;
            $name = $request->name;
            $email = $request->email;
            //verify the request
            switch ($request->provider) {
                case 'google':
                    $iosAppClientId = env('GOOGLE_IOS_CLIENT_ID');
                    $androidAppClientId = env('GOOGLE_ANDROID_CLIENT_ID');
                    $client_id = $request->sign_in_source == 'ios' ? $iosAppClientId : $androidAppClientId;
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
                    }  catch(\Exception $exception) {
                        Log::error($exception);
                    }
                    break;
                default:
                    return $this->respondBadRequest("Invalid social media provider supplied");
            }

            if (!$socialIsValid) {
                return $this->respondBadRequest("An invalid token was supplied");
            }

            //check if user exists
            $user = User::where('email', $email)->first();
            if (is_null($user)) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(8)),
                    'public_id' => uniqid(rand()),
                    'email_token' => Str::random(16),
                    'referral_id' => strtoupper(Str::random(6)) . "-" . date('ymd'),
                ]);
                event(new ConfirmEmailEvent($user));
                $user->assignRole(Roles::USER);
                $user->wallet()->create([
                    'public_id' => uniqid(rand()),
                ]);
                if (!is_null($picture) && $picture !== '') {
                    $user->assets()->create([
                        'public_id' => uniqid(rand()),
                        'storage_provider' => 'google',
                        'storage_provider_id' => Str::random(8),
                        'url' => $picture,
                        'purpose' => 'profile-picture',
                        'asset_type' => 'image',
                        'mime_type' => 'image/jpeg',
                    ]);
                }
            }

            $token = JWTAuth::fromUser($user);
            $user = User::with('roles', 'profile_picture', 'wallet')->where('id', $user->id)->first();
            return $this->respondWithSuccess("Registration successful", [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } catch(\Exception $exception) {
			Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
				'email' => ['required', 'string', 'email', 'max:255'],
				'password' => ['required', 'string',],
                'role' => ['sometimes', 'required', 'string', 'regex:(creator|user)'],
			]);
			
			if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }
    
			if(Auth::attempt(['email' => $request->email, 'password' => $request->password])){
				$user = Auth::user();
                if (!is_null($request->role) && $request->role === Roles::CREATOR) {
                    if (!$user->hasRole(Roles::CREATOR)) {
                        $user->assignRole(Roles::CREATOR);
                    }
                }
				$token = JWTAuth::fromUser($user);
                $wallet = $user->wallet()->first();
                if (is_null($wallet)) {
                    $user->wallet()->create([
                        'public_id' => uniqid(rand()),
                    ]);
                }
                $user = User::with('roles', 'profile_picture', 'wallet')->where('id', Auth::user()->id)->first();
				return $this->respondWithSuccess("Login successful", [
					'user' => new UserResourceWithSensitive($user),
					'token' => $token,
				]);
			} else{
				return $this->respondBadRequest("User credentials do not match our record");
			}
        } catch(\Exception $exception) {
			Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
	}

    public function loginViaOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
				'code' => ['required', 'string', 'exists:otps,code',],
			]);
			
			if ($validator->fails()) {
				return $this->respondBadRequest("Invalid or missing input fields", $validator->errors()->toArray());
            }

            $otp = Otp::where('code', $request->code)->where('purpose', 'authentication')->first();

            if (is_null($otp)) {
                return $this->respondBadRequest("Invalid OTP provided");
            }

            if ($otp->expires_at->lt(now())) {
                return $this->respondBadRequest("Access code has expired");
            }

            $otp->expires_at = now();//expire the token since it has been used
            $otp->save();
            $token = JWTAuth::fromUser($otp->user);
            $user = User::with('roles', 'profile_picture', 'wallet')->where('public_id', $otp->user->public_id)->first();
            return $this->respondWithSuccess("Login successful", [
                'user' => new UserResourceWithSensitive($otp->user),
                'token' => $token,
            ]);

        } catch(\Exception $exception) {
			Log::error($exception);
			return $this->respondInternalError("Oops, an error occurred. Please try again later.");
		}
    }
	
	public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);
        if ($validator->fails()) {
            return $this->respondBadRequest("Invalid or missing input fields",  $validator->errors()->toArray());
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
        ]);
        if ($validator->fails()) {
            return $this->respondBadRequest("Invalid or missing input fields",  $validator->errors()->toArray());
        }
        
        $user = User::with('roles', 'profile_picture', 'wallet')->where('password_token', $request->token)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->password_token = null;
            $user->save();
            $token = JWTAuth::fromUser($user);
            return $this->respondWithSuccess("Password reset successfully", [
                'user' => new UserResourceWithSensitive($user),
                'token' => $token,
            ]);
        } else {
            return $this->respondBadRequest("Token has expired");
        }
    }

    public function verifyEmail(Request $request)
    {
        $user = User::with('roles', 'profile_picture', 'wallet')->where('email_token', $request->token)->first();
        if ($user) {
            $user->email_verified = 1;
            $user->email_token = '';
            $user->save();
            return $this->respondWithSuccess("Email verified successfully", ['user' => new UserResourceWithSensitive($user), 'token' => JWTAuth::fromUser($user)]);
        } else {
            return $this->respondBadRequest("Token has expired");
        }
    }
}
