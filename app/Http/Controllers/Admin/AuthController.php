<?php

namespace App\Http\Controllers\Admin;

use App\Dtos\UserGateProfile;
use App\Exceptions\UserGateException;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\UserGateService;
use App\Support\UserGateSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private const INACTIVE_MESSAGE = 'Periksa kembali username dan password atau hubungi admin';

    public function __construct(private readonly UserGateService $usergate)
    {
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $username = $credentials['username'];
        $password = $credentials['password'];

        if (config('usergate.enabled')) {
            try {
                $loginData = $this->usergate->login($username, $password);
                $accessToken = (string) ($loginData['access_token'] ?? '');

                if ($accessToken === '') {
                    throw new UserGateException('Respons UserGate tidak valid.', 500, UserGateException::KIND_SERVER);
                }

                $profile = UserGateProfile::fromArray($this->usergate->me($accessToken));
                $user = $this->resolveLocalUser($profile);

                if (! $this->canLogin($user, $profile)) {
                    return $this->failedLogin($request, $username);
                }

                UserGateSession::store(array_merge($loginData, [
                    'access_token' => $accessToken,
                ]));

                Auth::login($user, $request->boolean('remember'));
                $user->forceFill(['last_login_at' => now()])->save();
                $request->session()->regenerate();

                return redirect()->intended(route('admin.dashboard'));
            } catch (UserGateException $e) {
                if ($e->shouldFallback()) {
                    return $this->fallbackLocalLogin($request, $username, $password);
                }

                return $this->failedLogin($request, $username);
            }
        }

        return $this->fallbackLocalLogin($request, $username, $password);
    }

    public function usergateSso(Request $request)
    {
        $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        try {
            $profile = UserGateProfile::fromArray($this->usergate->me($request->string('access_token')->toString()));

            if (! $profile->isSuperAdmin) {
                return back()->with('error', self::INACTIVE_MESSAGE);
            }

            $user = $this->findOrCreateByProfile($profile);
            $user->forceFill(['is_active' => true])->save();

            $superAdminRole = Role::where('slug', 'super_admin')->first();
            if ($superAdminRole) {
                $user->roles()->syncWithoutDetaching([$superAdminRole->id]);
            }

            UserGateSession::markStateless();

            Auth::login($user);
            $user->forceFill(['last_login_at' => now()])->save();
            $request->session()->regenerate();

            return redirect()->route('admin.dashboard');
        } catch (UserGateException $e) {
            return back()->with('error', self::INACTIVE_MESSAGE);
        }
    }

    public function logout(Request $request)
    {
        $accessToken = UserGateSession::accessToken();

        if ($accessToken) {
            $this->usergate->logout($accessToken);
        }

        UserGateSession::forget();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function resolveLocalUser(UserGateProfile $profile): User
    {
        return DB::transaction(function () use ($profile) {
            $user = User::where('usergate_id', $profile->id)->first();

            if (! $user && $profile->email) {
                $user = User::where('email', $profile->email)->first();
            }

            if (! $user && $profile->username) {
                $user = User::where('username', $profile->username)->first();
            }

            if (! $user) {
                $user = new User();
                $user->usergate_id = $profile->id;
                $user->username = $profile->username;
                $user->email = $profile->email ?? ($profile->username . '@usergate.local');
                $user->name = $profile->fullName ?: $profile->username;
                $user->password = null;
                $user->is_active = false;
                $user->save();
            } else {
                if (! $user->usergate_id) {
                    $user->usergate_id = $profile->id;
                }

                $user->username = $profile->username ?: $user->username;
                $user->name = $profile->fullName ?: $user->name;
                $user->save();
            }

            if ($this->shouldBootstrapSuperAdmin($user)) {
                $user->is_active = true;
                $user->save();

                $role = Role::where('slug', 'super_admin')->first();
                if ($role) {
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            }

            return $user->load('roles.permissions');
        });
    }

    private function findOrCreateByProfile(UserGateProfile $profile): User
    {
        $user = User::where('usergate_id', $profile->id)->first();

        if (! $user && $profile->email) {
            $user = User::where('email', $profile->email)->first();
        }

        if (! $user && $profile->username) {
            $user = User::where('username', $profile->username)->first();
        }

        if (! $user) {
            $user = new User();
            $user->usergate_id = $profile->id;
            $user->email = $profile->email ?? ($profile->username . '@usergate.local');
        }

        $user->username = $profile->username ?: $user->username;
        $user->name = $profile->fullName ?: ($user->name ?: $profile->username);
        $user->password = null;
        $user->save();

        return $user->load('roles.permissions');
    }

    private function shouldBootstrapSuperAdmin(User $user): bool
    {
        if (! $user->is_active && ! $user->roles()->exists()) {
            $localSuperAdminEmail = config('usergate.local_super_admin.email');

            if ($localSuperAdminEmail && strtolower((string) $user->email) === $localSuperAdminEmail) {
                return false;
            }

            return ! User::where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('roles.slug', 'super_admin'))
                ->exists();
        }

        return false;
    }

    private function canLogin(User $user, UserGateProfile $profile): bool
    {
        return $user->is_active
            && $user->roles()->exists()
            && strcasecmp($profile->status, 'ACTIVE') === 0;
    }

    private function failedLogin(Request $request, string $username)
    {
        return back()->withErrors([
            'username' => self::INACTIVE_MESSAGE,
        ])->onlyInput('username');
    }

    private function fallbackLocalLogin(Request $request, string $username, string $password)
    {
        $user = User::where('email', $username)
            ->orWhere('username', $username)
            ->first();

        if (! $user || ! $user->is_active || ! $user->hasRole('super_admin')) {
            return $this->failedLogin($request, $username);
        }

        if (! \Illuminate\Support\Facades\Hash::check($password, (string) $user->password)) {
            return $this->failedLogin($request, $username);
        }

        Auth::login($user, $request->boolean('remember'));
        $user->forceFill(['last_login_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }
}
