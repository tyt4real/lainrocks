<?php

/**
 * LdapAccount — thin wrapper around the lldap REST API.
 * Handles registration, login (bind), password reset, and profile reads.
 *
 * Config is pulled from your existing config.php:
 *   $config['lldap']['url']      e.g. http://lldap:17170
 *   $config['lldap']['admin_dn'] e.g. uid=admin,ou=people,dc=lain,dc=rocks
 *   $config['lldap']['admin_pw'] (admin password)
 *   $config['lldap']['base_dn']  e.g. dc=lain,dc=rocks
 *   $config['lldap']['ldap_url'] e.g. ldap://lldap:3890
 *   $config['lldap']['default_groups'] e.g. ['mail_users','nextcloud_users','forgejo_users']
 */
class LdapAccount
{
    private string $apiUrl;
    private string $ldapUrl;
    private string $baseDn;
    private string $adminDn;
    private string $adminPw;
    private array  $defaultGroups;
    private ?string $jwtToken = null;

    public function __construct(array $config)
    {
        $lldap               = $config['lldap'];
        $this->apiUrl        = rtrim($lldap['url'], '/');
        $this->ldapUrl       = $lldap['ldap_url'];
        $this->baseDn        = $lldap['base_dn'];
        $this->adminDn       = $lldap['admin_dn'];
        $this->adminPw       = $lldap['admin_pw'];
        $this->defaultGroups = $lldap['default_groups'] ?? [];
    }

    /* ── Public API ──────────────────────────────────────────────── */

    /**
     * Register a new user and add them to default groups.
     * Returns ['ok' => true] or ['ok' => false, 'error' => '...']
     */
    public function register(string $uid, string $email, string $password, string $firstName, string $lastName): array
    {
        $uid = strtolower(trim($uid));

        if (!$this->isValidUid($uid))         return ['ok' => false, 'error' => 'Username may only contain lowercase letters, digits, hyphens and underscores (3–32 chars).'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'error' => 'Invalid email address.'];
        if (strlen($password) < 8)            return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];

        $token = $this->getAdminToken();
        if (!$token) return ['ok' => false, 'error' => 'Could not connect to authentication server.'];

        // Create user via lldap GraphQL API
        $mutation = <<<GQL
        mutation {
            createUser(user: {
                id: "{$uid}",
                email: "{$email}",
                displayName: "{$firstName} {$lastName}",
                firstName: "{$firstName}",
                lastName: "{$lastName}"
            }) { id }
        }
        GQL;

        $res = $this->gql($mutation, $token);
        if (isset($res['errors'])) {
            $msg = $res['errors'][0]['message'] ?? 'Registration failed.';
            if (str_contains($msg, 'already exists') || str_contains($msg, 'duplicate')) {
                return ['ok' => false, 'error' => 'Username or email already taken.'];
            }
            return ['ok' => false, 'error' => $msg];
        }

        // Set password
        $pwResult = $this->setPassword($uid, $password, $token);
        if (!$pwResult) return ['ok' => false, 'error' => 'User created but password could not be set. Contact admin.'];

        // Add to default groups
        foreach ($this->defaultGroups as $group) {
            $this->addToGroup($uid, $group, $token);
        }

        return ['ok' => true];
    }

    /**
     * Authenticate a user via LDAP bind.
     * Returns ['ok' => true, 'uid' => '...'] or ['ok' => false, 'error' => '...']
     */
    public function login(string $uid, string $password): array
    {
        $uid = strtolower(trim($uid));
        $dn  = "uid={$uid},ou=people,{$this->baseDn}";

        $ds = @ldap_connect($this->ldapUrl);
        if (!$ds) return ['ok' => false, 'error' => 'Cannot reach authentication server.'];

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $bound = @ldap_bind($ds, $dn, $password);
        ldap_unbind($ds);

        if (!$bound) return ['ok' => false, 'error' => 'Invalid username or password.'];

        return ['ok' => true, 'uid' => $uid];
    }

    /**
     * Get profile data for a user (readonly bind).
     */
    public function getProfile(string $uid): ?array
    {
        $token = $this->getAdminToken();
        if (!$token) return null;

        $query = <<<GQL
        query {
            user(userId: "{$uid}") {
                id email displayName firstName lastName
                groups { id displayName }
                creationDate
            }
        }
        GQL;

        $res = $this->gql($query, $token);
        return $res['data']['user'] ?? null;
    }

    /**
     * Change password — requires knowing the current password first (bind check).
     */
    public function changePassword(string $uid, string $currentPassword, string $newPassword): array
    {
        if (strlen($newPassword) < 8) return ['ok' => false, 'error' => 'New password must be at least 8 characters.'];

        $check = $this->login($uid, $currentPassword);
        if (!$check['ok']) return ['ok' => false, 'error' => 'Current password is incorrect.'];

        $token = $this->getAdminToken();
        if (!$token) return ['ok' => false, 'error' => 'Cannot reach authentication server.'];

        $ok = $this->setPassword($uid, $newPassword, $token);
        return $ok ? ['ok' => true] : ['ok' => false, 'error' => 'Password change failed.'];
    }

    /**
     * Initiate a password reset — sends reset email via lldap if SMTP is configured.
     * Returns ['ok' => true] or ['ok' => false, 'error' => '...']
     */
    public function requestPasswordReset(string $uid): array
    {
        $uid   = strtolower(trim($uid));
        $token = $this->getAdminToken();
        if (!$token) return ['ok' => false, 'error' => 'Cannot reach authentication server.'];

        $res = $this->apiPost('/auth/reset/step1/' . urlencode($uid), [], null);

        // lldap returns 200 even if user not found (to prevent enumeration)
        return ['ok' => true];
    }

    /**
     * Complete password reset using the token sent by lldap.
     */
    public function completePasswordReset(string $uid, string $resetToken, string $newPassword): array
    {
        if (strlen($newPassword) < 8) return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];

        $payload = [
            'user_id'  => $uid,
            'token'    => $resetToken,
            'password' => $newPassword,
        ];

        $res = $this->apiPost('/auth/reset/step2', $payload, null);
        if (isset($res['error'])) return ['ok' => false, 'error' => $res['error']];

        return ['ok' => true];
    }

    /* ── Internal helpers ────────────────────────────────────────── */

    private function isValidUid(string $uid): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]{3,32}$/', $uid);
    }

    private function getAdminToken(): ?string
    {
        if ($this->jwtToken) return $this->jwtToken;

        // Extract plain uid from full DN like "uid=admin,ou=people,dc=..."
        preg_match('/uid=([^,]+)/', $this->adminDn, $m);
        $adminUid = $m[1] ?? 'admin';

        $payload = ['username' => $adminUid, 'password' => $this->adminPw];
        $res     = $this->apiPost('/auth/simple/login', $payload, null);

        $this->jwtToken = $res['token'] ?? null;
        return $this->jwtToken;
    }

    private function setPassword(string $uid, string $password, string $token): bool
    {
        $mutation = <<<GQL
        mutation {
            changeUserPassword(userId: "{$uid}", password: "{$password}") { ok }
        }
        GQL;

        $res = $this->gql($mutation, $token);
        return ($res['data']['changeUserPassword']['ok'] ?? false) === true;
    }

    private function addToGroup(string $uid, string $groupName, string $token): void
    {
        // First resolve group id
        $query = <<<GQL
        query { group(groupId: 0) { id } }
        GQL;

        $listQuery = 'query { groups { id displayName } }';
        $res = $this->gql($listQuery, $token);
        $groups = $res['data']['groups'] ?? [];

        $groupId = null;
        foreach ($groups as $g) {
            if ($g['displayName'] === $groupName) { $groupId = $g['id']; break; }
        }
        if ($groupId === null) return;

        $mutation = <<<GQL
        mutation {
            addUserToGroup(userId: "{$uid}", groupId: {$groupId}) { ok }
        }
        GQL;

        $this->gql($mutation, $token);
    }

    private function gql(string $query, string $token): array
    {
        return $this->apiPost('/api/graphql', ['query' => $query], $token);
    }

    private function apiPost(string $path, array $payload, ?string $token): array
    {
        $ch = curl_init($this->apiUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_filter([
                'Content-Type: application/json',
                $token ? "Authorization: Bearer {$token}" : null,
            ]),
            CURLOPT_TIMEOUT        => 5,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);

        return json_decode($body ?: '{}', true) ?? [];
    }
}
