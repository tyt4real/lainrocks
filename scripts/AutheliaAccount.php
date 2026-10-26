<?php

class AutheliaAccount
{
    private string $authBase;
    private string $clientId;
    private string $clientSecret;
    private string $callbackUrl;
    private array $defaultGroups;
    private ?string $jwtToken = null;
    private ?array $cachedUserinfo = null;

    public function __construct(array $config)
    {
        $authelia = $config['authelia'];

        $this->authBase     = rtrim($authelia['auth_base'], '/');
        $this->clientId     = $authelia['client_id'];
        $this->clientSecret = $authelia['client_secret'];
        $this->callbackUrl  = $authelia['callback_url'];
        $this->defaultGroups = $authelia['default_groups'] ?? [];

        $this->log('INIT', [
            'authBase' => $this->authBase,
            'clientId' => $this->clientId,
            'callbackUrl' => $this->callbackUrl,
        ]);
    }

    public function getLoginUrl(string $redirect = ''): string
    {
        $params = [
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->callbackUrl,
            'response_type' => 'code',
            'scope'         => 'openid profile email groups',
        ];

        if ($redirect !== '') {
            $params['state'] = base64_encode($redirect);
        }

        return $this->authBase . '/api/oidc/authorization?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $state = ''): array
    {
        $this->log('CALLBACK_START', [
            'code_len' => strlen($code),
            'state' => $state,
        ]);

        $token = $this->exchangeCode($code);
        if (!$token) {
            return ['ok' => false, 'error' => 'Failed to obtain token from Authelia.'];
        }

        $userinfo = $this->getUserinfo($token);
        if (!$userinfo) {
            return ['ok' => false, 'error' => 'Failed to fetch user profile.'];
        }

        $uid = strtolower(trim($userinfo['preferred_username'] ?? $userinfo['sub'] ?? ''));

        if ($uid === '') {
            return ['ok' => false, 'error' => 'No username in token response.'];
        }

        // Ensure local LLDAP user exists and has required groups
        $this->ensureLocalUser($uid, $userinfo);

        $this->log('CALLBACK_SUCCESS', ['uid' => $uid]);

        return [
            'ok' => true,
            'uid' => $uid,
            'userinfo' => $userinfo,
        ];
    }

    public function getUserinfo(?string $token = null): ?array
    {
        if ($this->cachedUserinfo !== null) {
            return $this->cachedUserinfo;
        }

        $accessToken = $token ?: $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }

        $url = $this->authBase . '/api/oidc/userinfo';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http !== 200 || empty($body)) {
            $this->log('USERINFO_FAILED', ['http' => $http, 'body' => $body]);
            return null;
        }

        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }

        $this->cachedUserinfo = $data;
        return $data;
    }

    private function exchangeCode(string $code): ?string
    {
        $url = $this->authBase . '/api/oidc/token';
        $payload = http_build_query([
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->callbackUrl,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->log('TOKEN_EXCHANGE', ['http' => $http, 'body_len' => strlen($body ?? '')]);

        if ($http !== 200 || empty($body)) {
            return null;
        }

        $data = json_decode($body, true);
        return $data['access_token'] ?? null;
    }

    private function getAccessToken(): ?string
    {
        if ($this->jwtToken) {
            return $this->jwtToken;
        }
        return null;
    }

    private function ensureLocalUser(string $uid, array $userinfo): void
    {
        // Keep user in sync with LLDAP via GraphQL if needed.
        // For now we just assume Authelia already manages LDAP identity;
        // we only ensure default group membership is present.
        $token = $this->getLldapAdminToken();
        if (!$token) {
            $this->log('ENSURE_USER_SKIP', ['uid' => $uid, 'reason' => 'no_admin_token']);
            return;
        }

        $groups = $this->getLldapUserGroups($uid, $token);
        foreach ($this->defaultGroups as $group) {
            if (!in_array($group, $groups, true)) {
                $this->addToLldapGroup($uid, $group, $token);
            }
        }
    }

    private function getLldapAdminToken(): ?string
    {
        $config = require __DIR__ . '/../config.php';
        $lldap = $config['lldap'];

        $url = rtrim($lldap['url'], '/') . '/auth/simple/login';
        $payload = json_encode([
            'username' => preg_replace('/uid=([^,]+)/', '$1', $lldap['admin_dn']),
            'password' => $lldap['admin_pw'],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($body, true);
        return $data['token'] ?? null;
    }

    private function getLldapUserGroups(string $uid, string $token): array
    {
        $config = require __DIR__ . '/../config.php';
        $url = rtrim($config['lldap']['url'], '/') . '/api/graphql';
        $query = '
            query GetUser($uid: String!) {
                user(userId: $uid) {
                    groups { id displayName }
                }
            }
        ';
        $payload = json_encode(['query' => $query, 'variables' => ['uid' => $uid]]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($body, true);
        $groups = $data['data']['user']['groups'] ?? [];
        return array_map(fn($g) => $g['displayName'], $groups);
    }

    private function addToLldapGroup(string $uid, string $groupName, string $token): void
    {
        $config = require __DIR__ . '/../config.php';
        $url = rtrim($config['lldap']['url'], '/') . '/api/graphql';

        $listQuery = 'query { groups { id displayName } }';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query' => $listQuery]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($body, true);
        $groupId = null;
        foreach ($data['data']['groups'] ?? [] as $g) {
            if ($g['displayName'] === $groupName) {
                $groupId = $g['id'];
                break;
            }
        }
        if ($groupId === null) {
            $this->log('GROUP_NOT_FOUND', ['group' => $groupName]);
            return;
        }

        $mutation = '
            mutation AddUserToGroup($userId: String!, $groupId: Int!) {
                addUserToGroup(userId: $userId, groupId: $groupId) { ok }
            }
        ';
        $payload = json_encode([
            'query' => $mutation,
            'variables' => ['userId' => $uid, 'groupId' => (int) $groupId],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        curl_exec($ch);
        curl_close($ch);

        $this->log('ADD_TO_GROUP', ['uid' => $uid, 'group' => $groupName]);
    }

    private function log(string $event, array $context = []): void
    {
        $line = sprintf(
            '[AUTHELIA][%s] %s %s',
            date('Y-m-d H:i:s'),
            $event,
            json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );
        error_log($line);
    }
}
