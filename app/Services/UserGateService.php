<?php

namespace App\Services;

use App\Exceptions\UserGateException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class UserGateService
{
    public function login(string $username, string $password): array
    {
        return $this->send('post', '/auth/login', [
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function me(string $accessToken): array
    {
        return $this->send('get', '/auth/me', [], $accessToken);
    }

    public function refresh(string $refreshToken): array
    {
        return $this->send('post', '/auth/refresh', [
            'refresh_token' => $refreshToken,
        ]);
    }

    public function logout(string $accessToken): bool
    {
        try {
            $this->send('post', '/auth/logout', [], $accessToken);
            return true;
        } catch (UserGateException $e) {
            return false;
        }
    }

    private function send(string $method, string $uri, array $data = [], ?string $token = null): array
    {
        try {
            $request = Http::baseUrl(config('usergate.api_url'))
                ->acceptJson()
                ->timeout(config('usergate.timeout', 10));

            if (config('usergate.api_key')) {
                $request = $request->withHeaders([
                    'X-API-Key' => config('usergate.api_key'),
                ]);
            }

            if ($token) {
                $request = $request->withToken($token);
            }

            $response = $request->{$method}($uri, $data);

            $status = $response->status();
            $body = $response->json() ?? [];

            if ($status >= 200 && $status < 300 && ($body['status'] ?? false) === true) {
                return is_array($body['data'] ?? null) ? $body['data'] : [];
            }

            $kind = match (true) {
                $status === 401 => UserGateException::KIND_UNAUTHORIZED,
                $status === 403 => UserGateException::KIND_FORBIDDEN,
                $status === 422 => UserGateException::KIND_VALIDATION,
                $status === 429 => UserGateException::KIND_RATE_LIMIT,
                $status >= 500 => UserGateException::KIND_SERVER,
                default => UserGateException::KIND_SERVER,
            };

            throw new UserGateException(
                (string) ($body['message'] ?? 'UserGate request failed.'),
                $status,
                $kind,
                is_array($body) ? $body : [],
            );
        } catch (ConnectionException $e) {
            throw new UserGateException(
                'Tidak dapat terhubung ke UserGate.',
                0,
                UserGateException::KIND_NETWORK,
            );
        }
    }
}
