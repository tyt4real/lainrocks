<?php

require_once './scripts/AutheliaAccount.php';
require_once './scripts/LdapAccount.php';
require_once './scripts/registerfunctionstwig.php';


$config = require __DIR__ . '/../config.php';
$twig   = registerWithTwig($locale ?? detectLocale(), $translations ?? loadTranslations($locale ?? detectLocale()));
$authelia = new AutheliaAccount($config);
$ldap   = new LdapAccount($config);

$action = $_GET['action'] ?? 'login';
$flash  = [];

function getClientIp(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return explode(',', $ip)[0];
}

function checkRateLimit(string $action, string $ip, int $maxAttempts = 5, int $windowSeconds = 300): bool {
    $rateLimitDir = __DIR__ . '/../data/rate_limit';
    if (!is_dir($rateLimitDir)) {
        @mkdir($rateLimitDir, 0755, true);
    }
    $file = $rateLimitDir . '/' . $action . '_' . md5($ip) . '.json';
    $now = time();

    $attempts = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        if (is_array($data)) {
            $attempts = array_filter($data, fn($ts) => $now - $ts < $windowSeconds);
        }
    }

    if (count($attempts) >= $maxAttempts) {
        return false;
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode(array_values($attempts)));
    return true;
}

function clearRateLimit(string $action, string $ip): void {
    $rateLimitDir = __DIR__ . '/../data/rate_limit';
    $file = $rateLimitDir . '/' . $action . '_' . md5($ip) . '.json';
    if (file_exists($file)) {
        @unlink($file);
    }
}

function transError(string $msg, array $translations): string {
    $map = [
        'Invalid username format.'             => 'account.flash.invalid_username',
        'Invalid email.'                       => 'account.flash.invalid_email',
        'Password must be at least 10 characters long.' => 'account.flash.password_too_short',
        'Password must contain at least one uppercase letter.' => 'account.flash.password_needs_uppercase',
        'Password must contain at least one lowercase letter.' => 'account.flash.password_needs_lowercase',
        'Password must contain at least one number.'         => 'account.flash.password_needs_number',
        'Password must contain at least one special character.' => 'account.flash.password_needs_special',
        'Cannot authenticate to LLDAP.'        => 'account.flash.cannot_auth_lldap',
        'User created but password could not be set.' => 'account.flash.user_created_no_password',
        'Cannot connect to LDAP.'              => 'account.flash.cannot_connect_ldap',
        'Invalid username or password.'        => 'account.flash.invalid_username_or_password',
        'Current password is incorrect.'       => 'account.flash.current_password_incorrect',
        'Password could not be updated.'       => 'account.flash.password_could_not_be_updated',
        'Unknown GraphQL error'                => 'account.flash.unknown_error',
        'Unknown error'                        => 'account.flash.unknown_error',
    ];
    $key = $map[$msg] ?? null;
    if ($key !== null) {
        return t($translations, $key);
    }
    return $msg;
}

switch ($action) {

    case 'authelia-callback':
        if (isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=profile');
            exit;
        }

        $error = $_GET['error'] ?? '';
        $errorDescription = $_GET['error_description'] ?? '';

        if ($error !== '') {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.auth_failed', ['error' => htmlspecialchars($errorDescription ?: $error, ENT_QUOTES)])];
            echo $twig->render('account/login.html.twig', [
                'flash'            => $flash,
                'registered'       => false,
                'redirect'         => '',
                'authelia_login_url' => $authelia->getLoginUrl(''),
                'ldap_enabled'     => false,
            ]);
            break;
        }

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        if ($code === '') {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.missing_auth_code')];
            echo $twig->render('account/login.html.twig', [
                'flash'            => $flash,
                'registered'       => false,
                'redirect'         => '',
                'authelia_login_url' => $authelia->getLoginUrl(''),
                'ldap_enabled'     => false,
            ]);
            break;
        }

        $res = $authelia->handleCallback($code, $state);
        if ($res['ok']) {
            clearRateLimit('login', getClientIp());
            $_SESSION['lain_uid'] = $res['uid'];

            $redirect = '?page=account&action=profile';
            if ($state !== '' && preg_match('/^[A-Za-z0-9_=-]+$/', $state)) {
                $decoded = base64_decode($state, true);
                if ($decoded !== false && preg_match('/^[?A-Za-z0-9_=&%-]+$/', $decoded)) {
                    $redirect = $decoded;
                }
            } elseif ($state !== '' && preg_match('/^[0-9a-f]+$/i', $state)) {
                // State is just a random hex nonce from getLoginUrl() without redirect
                // keep default profile redirect
            }

            header('Location: ' . $redirect);
            exit;
        }

        $flash = ['type' => 'err', 'msg' => transError($res['error'], $translations)];
        echo $twig->render('account/login.html.twig', [
            'flash'            => $flash,
            'registered'       => false,
            'redirect'         => '',
            'authelia_login_url' => $authelia->getLoginUrl(''),
            'ldap_enabled'     => false,
        ]);
        break;

    case 'authelia-code':
        // OIDC code display endpoint for Telegram bot paste-back flow.
        // Shows the authorization code so the user can paste it into /link oidc <code>.
        if (isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=profile');
            exit;
        }

        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        if ($code === '') {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.missing_auth_code')];
            echo $twig->render('account/login.html.twig', [
                'flash'            => $flash,
                'registered'       => false,
                'redirect'         => '',
                'authelia_login_url' => $authelia->getLoginUrl(''),
                'ldap_enabled'     => false,
            ]);
            break;
        }

        echo $twig->render('account/authelia_code.html.twig', [
            'code'   => htmlspecialchars($code, ENT_QUOTES),
            'state'  => htmlspecialchars($state, ENT_QUOTES),
        ]);
        break;

    case 'register':
        $ip = getClientIp();
        if (!checkRateLimit('register', $ip)) {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.too_many_register')];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $ldap->register(
                trim($_POST['uid']        ?? ''),
                trim($_POST['email']      ?? ''),
                $_POST['password']        ?? '',
                trim($_POST['first_name'] ?? ''),
                trim($_POST['last_name']  ?? '')
            );
            if ($res['ok']) {
                // Autologin after registration
                $login = $ldap->login(trim($_POST['uid']), $_POST['password']);
                if ($login['ok']) {
                    $_SESSION['lain_uid'] = $login['uid'];
                    header('Location: ?page=account&action=profile');
                    exit;
                }
                $flash = ['type' => 'ok', 'msg' => t($translations, 'account.flash.account_created')];
                header('Location: ?page=account&action=login&registered=1');
                exit;
            }
            $flash = ['type' => 'err', 'msg' => transError($res['error'], $translations)];
        }
        echo $twig->render('account/register.html.twig', [
            'flash'  => $flash,
            'post'   => $_POST,
        ]);
        break;

    case 'login':
        if (isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=profile');
            exit;
        }

        $loginUrl = $authelia->getLoginUrl($_GET['redirect'] ?? '');

        echo $twig->render('account/login.html.twig', [
            'flash'          => $flash,
            'registered'     => isset($_GET['registered']),
            'redirect'       => $_GET['redirect'] ?? '',
            'authelia_login_url' => $loginUrl,
            'ldap_enabled'   => false,
        ]);
        break;

    case 'logout':
        session_destroy();
        header('Location: ?page=account&action=login');
        exit;

    case 'profile':
        if (!isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=login');
            exit;
        }
        $profile = $ldap->getProfile($_SESSION['lain_uid']);
        echo $twig->render('account/profile.html.twig', [
            'profile' => $profile,
            'uid'     => $_SESSION['lain_uid'],
        ]);
        break;

    case 'change-password':
        if (!isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
                $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.passwords_do_not_match')];
            } else {
                $res = $ldap->changePassword(
                    $_SESSION['lain_uid'],
                    $_POST['current_password'] ?? '',
                    $_POST['new_password']     ?? ''
                );
                $flash = $res['ok']
                    ? ['type' => 'ok',  'msg' => t($translations, 'account.flash.password_changed')]
                    : ['type' => 'err', 'msg' => transError($res['error'], $translations)];
            }
        }
        echo $twig->render('account/change_password.html.twig', ['flash' => $flash]);
        break;

    case 'reset-password':
        $ip = getClientIp();
        if (!checkRateLimit('reset', $ip)) {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.too_many_reset')];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ldap->requestPasswordReset(trim($_POST['uid'] ?? ''));

            $flash = ['type' => 'ok', 'msg' => t($translations, 'account.flash.reset_sent')];
        }
        echo $twig->render('account/reset_request.html.twig', ['flash' => $flash]);
        break;

    case 'reset-confirm':
        $ip = getClientIp();
        if (!checkRateLimit('reset', $ip)) {
            $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.too_many_reset')];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
                $flash = ['type' => 'err', 'msg' => t($translations, 'account.flash.passwords_do_not_match_reset')];
            } else {
                $res = $ldap->completePasswordReset(
                    trim($_POST['uid']   ?? ''),
                    trim($_POST['token'] ?? ''),
                    $_POST['new_password'] ?? ''
                );
                if ($res['ok']) {
                    header('Location: ?page=account&action=login&reset=1');
                    exit;
                }
                $flash = ['type' => 'err', 'msg' => transError($res['error'], $translations)];
            }
        }
        echo $twig->render('account/reset_confirm.html.twig', [
            'flash' => $flash,
            'uid'   => $_GET['uid']   ?? $_POST['uid']   ?? '',
            'token' => $_GET['token'] ?? $_POST['token'] ?? '',
        ]);
        break;

    default:
        header('Location: ?page=account&action=login');
        exit;
}
