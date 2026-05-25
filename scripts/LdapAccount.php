<?php

/**
 * LdapAccount — thin wrapper around the lldap REST API.
 * Adds extensive logging/debugging.
 */
class LdapAccount
{
    private string $apiUrl;
    private string $ldapUrl;
    private string $baseDn;
    private string $adminDn;
    private string $adminPw;
    private array $defaultGroups;
    private ?string $jwtToken = null;

    public function __construct(array $config)
    {
        $lldap = $config['lldap'];

        $this->apiUrl = rtrim($lldap['url'], '/');
        $this->ldapUrl = $lldap['ldap_url'];
        $this->baseDn = $lldap['base_dn'];
        $this->adminDn = $lldap['admin_dn'];
        $this->adminPw = $lldap['admin_pw'];
        $this->defaultGroups = $lldap['default_groups'] ?? [];

        $this->log('INIT', [
            'apiUrl' => $this->apiUrl,
            'ldapUrl' => $this->ldapUrl,
            'baseDn' => $this->baseDn,
            'defaultGroups' => $this->defaultGroups
        ]);
    }

    /* ───────────────────────────────────────────────────────────── */

    public function register(
        string $uid,
        string $email,
        string $password,
        string $firstName,
        string $lastName
    ): array {
        $uid = strtolower(trim($uid));

        $this->log('REGISTER_START', [
            'uid' => $uid,
            'email' => $email
        ]);

        if (!$this->isValidUid($uid)) {
            $this->log('REGISTER_FAIL_INVALID_UID', ['uid' => $uid]);

            return [
                'ok' => false,
                'error' => 'Invalid username format.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log('REGISTER_FAIL_INVALID_EMAIL', ['email' => $email]);

            return [
                'ok' => false,
                'error' => 'Invalid email.'
            ];
        }

        if (strlen($password) < 8) {
            $this->log('REGISTER_FAIL_PASSWORD_TOO_SHORT', [
                'uid' => $uid
            ]);

            return [
                'ok' => false,
                'error' => 'Password too short.'
            ];
        }

        $token = $this->getAdminToken();

        if (!$token) {
            $this->log('REGISTER_FAIL_NO_ADMIN_TOKEN');

            return [
                'ok' => false,
                'error' => 'Cannot authenticate to LLDAP.'
            ];
        }

        $query = '
        mutation CreateUser(
            $id: String!,
            $email: String!,
            $displayName: String!,
            $firstName: String!,
            $lastName: String!
        ) {
            createUser(user: {
                id: $id,
                email: $email,
                displayName: $displayName,
                firstName: $firstName,
                lastName: $lastName
            }) {
                id
            }
        }';

        $payload = [
            'query' => $query,
            'variables' => [
                'id' => $uid,
                'email' => $email,
                'displayName' => trim($firstName . ' ' . $lastName),
                'firstName' => $firstName,
                'lastName' => $lastName
            ]
        ];

        $res = $this->apiPost('/api/graphql', $payload, $token);

        $this->log('CREATE_USER_RESPONSE', $res);

        if (isset($res['errors'])) {
            $msg = $res['errors'][0]['message'] ?? 'Unknown GraphQL error';

            $this->log('CREATE_USER_FAILED', [
                'uid' => $uid,
                'error' => $msg
            ]);

            return [
                'ok' => false,
                'error' => $msg
            ];
        }

        $pwResult = $this->setPassword($uid, $password, $token);

        if (!$pwResult) {
            $this->log('SET_PASSWORD_FAILED', [
                'uid' => $uid
            ]);

            return [
                'ok' => false,
                'error' => 'User created but password could not be set.'
            ];
        }

        foreach ($this->defaultGroups as $group) {
            $this->addToGroup($uid, $group, $token);
        }

        $this->log('REGISTER_SUCCESS', ['uid' => $uid]);

        return ['ok' => true];
    }

    /* ───────────────────────────────────────────────────────────── */

    public function login(string $uid, string $password): array
    {
        $uid = strtolower(trim($uid));
        $dn = "uid={$uid},ou=people,{$this->baseDn}";

        $this->log('LOGIN_ATTEMPT', [
            'uid' => $uid,
            'dn' => $dn
        ]);

        $ds = @ldap_connect($this->ldapUrl);

        if (!$ds) {
            $this->log('LDAP_CONNECT_FAILED');

            return [
                'ok' => false,
                'error' => 'Cannot connect to LDAP.'
            ];
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $bound = @ldap_bind($ds, $dn, $password);

        if (!$bound) {
            ldap_get_option($ds, LDAP_OPT_DIAGNOSTIC_MESSAGE, $diag);

            $this->log('LDAP_BIND_FAILED', [
                'uid' => $uid,
                'diag' => $diag ?? null
            ]);
        } else {
            $this->log('LDAP_BIND_SUCCESS', ['uid' => $uid]);
        }

        ldap_unbind($ds);

        if (!$bound) {
            return [
                'ok' => false,
                'error' => 'Invalid username or password.'
            ];
        }

        return [
            'ok' => true,
            'uid' => $uid
        ];
    }

    /* ───────────────────────────────────────────────────────────── */

    public function requestPasswordReset(string $uid): array
    {
        $uid = strtolower(trim($uid));

        $this->log('PASSWORD_RESET_REQUEST', [
            'uid' => $uid
        ]);

        $payload = [
            'user_id' => $uid
        ];

        $res = $this->apiPost(
            '/auth/reset/step1',
            $payload,
            null
        );

        $this->log('PASSWORD_RESET_RESPONSE', $res);

        return ['ok' => true];
    }

    /* ───────────────────────────────────────────────────────────── */

    private function setPassword(
        string $uid,
        string $password,
        string $token
    ): bool {
        $dn = "uid={$uid},ou=people,{$this->baseDn}";

        $this->log('LDAP_PASSWORD_SET_START', [
            'dn' => $dn
        ]);

        $ds = ldap_connect($this->ldapUrl);

        if (!$ds) {
            $this->log('LDAP_CONNECT_FAILED');
            return false;
        }

        ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);

        $bind = @ldap_bind(
            $ds,
            $this->adminDn,
            $this->adminPw
        );

        if (!$bind) {
            ldap_get_option(
                $ds,
                LDAP_OPT_DIAGNOSTIC_MESSAGE,
                $diag
            );

            $this->log('LDAP_ADMIN_BIND_FAILED', [
                'diag' => $diag ?? null
            ]);

            ldap_unbind($ds);

            return false;
        }

        $entry = [
            'userPassword' => $password
        ];

        $result = @ldap_modify(
            $ds,
            $dn,
            $entry
        );

        if (!$result) {
            ldap_get_option(
                $ds,
                LDAP_OPT_DIAGNOSTIC_MESSAGE,
                $diag
            );

            $this->log('LDAP_PASSWORD_MODIFY_FAILED', [
                'dn' => $dn,
                'diag' => $diag ?? null
            ]);
        } else {
            $this->log('LDAP_PASSWORD_MODIFY_SUCCESS', [
                'dn' => $dn
            ]);
        }

        ldap_unbind($ds);

        return $result;
    }

    /* ───────────────────────────────────────────────────────────── */

    private function addToGroup(
        string $uid,
        string $groupName,
        string $token
    ): void {
        $this->log('ADD_TO_GROUP_START', [
            'uid' => $uid,
            'group' => $groupName
        ]);

        $listQuery = 'query { groups { id displayName } }';

        $res = $this->gql($listQuery, $token);

        $groups = $res['data']['groups'] ?? [];

        $groupId = null;

        foreach ($groups as $g) {
            if ($g['displayName'] === $groupName) {
                $groupId = $g['id'];
                break;
            }
        }

        if ($groupId === null) {
            $this->log('GROUP_NOT_FOUND', [
                'group' => $groupName
            ]);

            return;
        }

        $query = '
        mutation AddUserToGroup(
            $userId: String!,
            $groupId: Int!
        ) {
            addUserToGroup(
                userId: $userId,
                groupId: $groupId
            )
        }';

        $payload = [
            'query' => $query,
            'variables' => [
                'userId' => $uid,
                'groupId' => (int) $groupId
            ]
        ];

        $res = $this->apiPost('/api/graphql', $payload, $token);

        $this->log('ADD_TO_GROUP_RESPONSE', $res);
    }

    /* ───────────────────────────────────────────────────────────── */

    private function getAdminToken(): ?string
    {
        if ($this->jwtToken) {
            return $this->jwtToken;
        }

        preg_match('/uid=([^,]+)/', $this->adminDn, $m);

        $adminUid = $m[1] ?? 'admin';

        $this->log('ADMIN_LOGIN_START', [
            'adminUid' => $adminUid
        ]);

        $payload = [
            'username' => $adminUid,
            'password' => $this->adminPw
        ];

        $res = $this->apiPost(
            '/auth/simple/login',
            $payload,
            null
        );

        $this->log('ADMIN_LOGIN_RESPONSE', $res);

        $this->jwtToken = $res['token'] ?? null;

        return $this->jwtToken;
    }

    /* ───────────────────────────────────────────────────────────── */

    private function gql(string $query, string $token): array
    {
        return $this->apiPost(
            '/api/graphql',
            ['query' => $query],
            $token
        );
    }

    /* ───────────────────────────────────────────────────────────── */

    private function apiPost(
        string $path,
        array $payload,
        ?string $token
    ): array {
        $url = $this->apiUrl . $path;

        $this->log('HTTP_POST_START', [
            'url' => $url,
            'payload' => $payload
        ]);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array_filter([
                'Content-Type: application/json',
                $token ? "Authorization: Bearer {$token}" : null,
            ]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $body = curl_exec($ch);

        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($errno) {
            $this->log('CURL_ERROR', [
                'errno' => $errno,
                'error' => $errstr
            ]);
        }

        $this->log('HTTP_POST_RESPONSE', [
            'url' => $url,
            'status' => $status,
            'body_raw' => $body
        ]);

        $decoded = json_decode($body ?: '{}', true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log('JSON_DECODE_ERROR', [
                'error' => json_last_error_msg(),
                'raw_body' => $body
            ]);

            return [];
        }

        return $decoded ?? [];
    }

    /* ───────────────────────────────────────────────────────────── */

    private function isValidUid(string $uid): bool
    {
        return (bool) preg_match('/^[a-z0-9_-]{3,32}$/', $uid);
    }

    /* ───────────────────────────────────────────────────────────── */

    private function log(string $event, array $context = []): void
    {
        $line = sprintf(
            '[LLDAP][%s] %s %s',
            date('Y-m-d H:i:s'),
            $event,
            json_encode(
                $context,
                JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
            )
        );

        error_log($line);
    }

    /* ───────────────────────────────────────────────────────────── */
    public function getProfile(string $uid): ?array
    {
        $this->log('GET_PROFILE_START', ['uid' => $uid]);

        $token = $this->getAdminToken();

        if (!$token) {
            $this->log('GET_PROFILE_FAIL_NO_TOKEN');
            return null;
        }

        $query = '
    query GetUser($uid: String!) {
        user(userId: $uid) {
            id
            email
            displayName
            firstName
            lastName
            creationDate
            groups {
                id
                displayName
            }
        }
    }';

        $payload = [
            'query' => $query,
            'variables' => [
                'uid' => $uid
            ]
        ];

        $res = $this->apiPost('/api/graphql', $payload, $token);

        $this->log('GET_PROFILE_RESPONSE', $res);

        return $res['data']['user'] ?? null;
    }
}
