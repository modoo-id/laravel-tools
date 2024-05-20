<?php

namespace App\Http\Controllers\SSO;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\SSOProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SSOController extends Controller
{
    protected $provider;

    public function __construct()
    {
        $this->provider = app(SSOProvider::class);
    }

    public function login(Request $request)
    {
        $authorizationUrl = $this->provider->getAuthorizationUrl();
        session(['oauth2state' => $this->provider->getState()]);

        return redirect($authorizationUrl);
    }

    public function callback(Request $request)
    {
        if (! $request->has('state') || $request->state !== session('oauth2state')) {
            session()->forget('oauth2state');

            return redirect('/login')->withErrors('Invalid state');
        }

        try {
            $token = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->code,
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }

        session(['access_token' => $token]);

        $user = $this->provider->mappingUser($token);

        Auth::login($user);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    public function logout()
    {
        $query = http_build_query([
            'continue' => config('app.url'),
        ]);

        return inertia()->location(config('services.sso_server.logout_url').'?'.$query);
    }
}
