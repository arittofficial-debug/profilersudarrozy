<?php

namespace App\Http\Middleware;

use App\Dtos\UserGateProfile;
use App\Exceptions\UserGateException;
use App\Services\UserGateService;
use App\Support\UserGateSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SyncUserGateSession
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->usergate_id || UserGateSession::isStateless()) {
            return $next($request);
        }

        if (now()->timestamp < (int) session()->get(UserGateSession::KEY_SYNC_AFTER, 0)) {
            return $next($request);
        }

        $accessToken = UserGateSession::accessToken();

        if (! $accessToken) {
            return $this->forceLogout($request, 'Sesi UserGate tidak valid. Silakan login kembali.');
        }

        $service = app(UserGateService::class);

        try {
            $profile = UserGateProfile::fromArray($service->me($accessToken));

            if (strcasecmp($profile->status, 'ACTIVE') !== 0) {
                return $this->forceLogout($request, RequireActiveAccess::MESSAGE);
            }

            $user->fill([
                'name' => $profile->fullName ?: $user->name,
                'username' => $profile->username ?: $user->username,
                'last_synced_at' => now(),
            ])->save();

            session()->put(
                UserGateSession::KEY_SYNC_AFTER,
                now()->addMinutes((int) config('usergate.sync_interval', 5))->timestamp
            );

            return $next($request);
        } catch (UserGateException $e) {
            if ($e->kind === UserGateException::KIND_UNAUTHORIZED) {
                $refreshToken = UserGateSession::refreshToken();

                if ($refreshToken) {
                    try {
                        $data = $service->refresh($refreshToken);

                        if (! empty($data['access_token'])) {
                            UserGateSession::store($data);

                            return $next($request);
                        }
                    } catch (UserGateException $refreshException) {
                        // Fall through to logout below.
                    }
                }

                return $this->forceLogout($request, RequireActiveAccess::MESSAGE);
            }

            if (in_array($e->kind, [UserGateException::KIND_NETWORK, UserGateException::KIND_SERVER, UserGateException::KIND_RATE_LIMIT], true)) {
                session()->put(
                    UserGateSession::KEY_SYNC_AFTER,
                    now()->addMinutes((int) config('usergate.sync_interval', 5))->timestamp
                );

                return $next($request);
            }

            return $this->forceLogout($request, RequireActiveAccess::MESSAGE);
        }
    }

    private function forceLogout(Request $request, string $message)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('error', $message);
    }
}
