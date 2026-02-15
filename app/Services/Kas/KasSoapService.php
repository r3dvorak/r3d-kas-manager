<?php

namespace App\Services\Kas;

use App\Models\AppSetting;
use RuntimeException;
use SoapClient;
use Throwable;

class KasSoapService
{
    /** @var array<string, string> */
    private array $sessionCache = [];

    public function createClient(?string $wsdl = null): SoapClient
    {
        $wsdl ??= (string) env('KAS_WSDL', 'https://kasapi.kasserver.com/soap/wsdl/KasApi.wsdl');

        return new SoapClient($wsdl, [
            'trace' => true,
            'exceptions' => true,
            'connection_timeout' => 25,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
        ]);
    }

    /**
     * Executes one KAS action and handles auth mode fallback:
     * - plain: use password directly
     * - session: use KasAuth token
     * - auto (default): try plain, fallback to session on kas_auth_type_disabled
     *
     * @return array{auth_mode:string,data:array}
     */
    public function callAction(
        SoapClient $client,
        string $kasLogin,
        string $password,
        string $action,
        array $params = [],
        array $options = []
    ): array {
        $mode = strtolower((string) ($options['auth_mode'] ?? env('KAS_AUTH_MODE', 'auto')));
        $attempts = match ($mode) {
            'plain' => ['plain'],
            'session' => ['session'],
            default => ['plain', 'session'],
        };

        $errors = [];

        foreach ($attempts as $authMode) {
            try {
                $auth = $authMode === 'session'
                    ? $this->buildSessionAuth($kasLogin, $password, $options)
                    : [
                        'kas_login' => $kasLogin,
                        'kas_auth_type' => 'plain',
                        'kas_auth_data' => $password,
                    ];

                $payload = array_merge($auth, [
                    'kas_action' => $action,
                    'KasRequestParams' => empty($params) ? new \stdClass() : $params,
                ]);

                $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $raw = $client->KasApi($json);

                AppSetting::setValue('kas_last_success_at', date('Y-m-d H:i:s'));
                // Reset last error info on success.
                AppSetting::setValue('kas_last_error_at', '');
                AppSetting::setValue('kas_last_error_action', '');
                AppSetting::setValue('kas_last_error_message', '');

                return [
                    'auth_mode' => $authMode,
                    'data' => $this->normalizeResponse($raw),
                ];
            } catch (Throwable $e) {
                $message = $e->getMessage();
                $errors[] = "{$authMode}: {$message}";

                AppSetting::setValue('kas_last_error_at', date('Y-m-d H:i:s'));
                AppSetting::setValue('kas_last_error_action', $action);
                // Truncate to keep settings small and safe.
                AppSetting::setValue('kas_last_error_message', mb_substr($message, 0, 500));

                // In auto mode we only fallback to session on explicit auth-type block.
                if ($mode === 'auto' && $authMode === 'plain' && str_contains(strtolower($message), 'kas_auth_type_disabled')) {
                    continue;
                }

                // If mode is explicit, or error is unrelated, fail immediately.
                if ($mode !== 'auto' || $authMode !== 'plain') {
                    throw new RuntimeException("KAS API call failed for action '{$action}' ({$authMode}): {$message}", 0, $e);
                }

                throw new RuntimeException("KAS API call failed for action '{$action}': {$message}", 0, $e);
            }
        }

        throw new RuntimeException(
            "KAS API call failed for action '{$action}'. Attempts: " . implode(' | ', $errors)
        );
    }

    private function buildSessionAuth(string $kasLogin, string $password, array $options): array
    {
        $token = (string) ($options['session_token'] ?? env('KAS_SESSION_TOKEN', ''));
        if ($token === '') {
            $token = $this->createSessionToken($kasLogin, $password, $options);
        }

        return [
            'kas_login' => $kasLogin,
            'kas_auth_type' => 'session',
            'kas_auth_data' => $token,
        ];
    }

    private function createSessionToken(string $kasLogin, string $password, array $options): string
    {
        $cacheKey = sha1($kasLogin . '|' . $password);
        if (isset($this->sessionCache[$cacheKey])) {
            return $this->sessionCache[$cacheKey];
        }

        $authWsdl = (string) env('KAS_AUTH_WSDL', 'https://kasapi.kasserver.com/soap/wsdl/KasAuth.wsdl');
        $otp = (string) ($options['otp'] ?? env('KAS_2FA_OTP', ''));
        $lifetime = (int) env('KAS_SESSION_LIFETIME', 1800);
        $updateLifetime = filter_var((string) env('KAS_SESSION_UPDATE_LIFETIME', 'true'), FILTER_VALIDATE_BOOLEAN) ? 'Y' : 'N';

        $payload = [
            'kas_login' => $kasLogin,
            'kas_auth_type' => 'plain',
            'kas_auth_data' => $password,
            'session_lifetime' => $lifetime,
            'session_update_lifetime' => $updateLifetime,
        ];

        if ($otp !== '') {
            $payload['session_2fa'] = $otp;
        }

        $client = $this->createClient($authWsdl);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $raw = $client->KasAuth($json);
        } catch (Throwable $e) {
            throw new RuntimeException("KasAuth session creation failed: {$e->getMessage()}", 0, $e);
        }

        $token = $this->extractSessionToken($raw);
        if ($token === '') {
            if ($otp === '') {
                throw new RuntimeException(
                    'KasAuth did not return a session token. Set KAS_2FA_OTP (current Authy code) or pass --otp='
                );
            }

            throw new RuntimeException('KasAuth did not return a session token.');
        }

        $this->sessionCache[$cacheKey] = $token;
        return $token;
    }

    private function extractSessionToken(mixed $raw): string
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $this->extractTokenFromArray($decoded);
            }

            return trim($raw);
        }

        if (is_object($raw)) {
            $raw = json_decode(json_encode($raw), true) ?? [];
        }

        if (is_array($raw)) {
            return $this->extractTokenFromArray($raw);
        }

        return '';
    }

    private function extractTokenFromArray(array $data): string
    {
        foreach (['session', 'session_id', 'kas_session', 'token', 'Session'] as $key) {
            if (isset($data[$key]) && is_string($data[$key]) && trim($data[$key]) !== '') {
                return trim($data[$key]);
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $token = $this->extractTokenFromArray($value);
                if ($token !== '') {
                    return $token;
                }
            } elseif (is_string($value) && preg_match('/^[A-Za-z0-9._-]{16,}$/', trim($value))) {
                return trim($value);
            }
        }

        return '';
    }

    private function normalizeResponse(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }

        if (is_object($raw)) {
            return json_decode(json_encode($raw), true) ?? [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }

            return ['Response' => ['Raw' => $raw]];
        }

        return ['Response' => ['Raw' => $raw]];
    }
}
