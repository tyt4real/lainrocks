<?php

require_once './scripts/LdapAccount.php';
require_once './scripts/registerfunctionstwig.php';

session_start();

$config = require __DIR__ . '/../config.php';
$twig   = registerWithTwig();
$ldap   = new LdapAccount($config);

$action  = $_GET['action'] ?? 'login';
$flash   = [];

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

switch ($action) {

    case 'register':
        $ip = getClientIp();
        if (!$checkRateLimit('register', $ip)) {
            $flash = ['type' => 'err', 'msg' => 'Too many registration attempts. Please try again later.'];
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
                $flash = ['type' => 'ok', 'msg' => 'Account created! Please log in.'];
                header('Location: ?page=account&action=login&registered=1');
                exit;
            }
            $flash = ['type' => 'err', 'msg' => $res['error']];
        }
        echo $twig->render('account/register.html.twig', [
            'flash'  => $flash,
            'post'   => $_POST,
        ]);
        break;


    case 'login':
        $ip = getClientIp();
        if (isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=profile'); exit;
        }
        if (!$checkRateLimit('login', $ip)) {
            $flash = ['type' => 'err', 'msg' => 'Too many login attempts. Please try again later.'];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $res = $ldap->login(trim($_POST['uid'] ?? ''), $_POST['password'] ?? '');
            if ($res['ok']) {
                clearRateLimit('login', $ip);
                $_SESSION['lain_uid'] = $res['uid'];
                $redirect = $_POST['redirect'] ?? '?page=account&action=profile';
                header('Location: ' . $redirect);
                exit;
            }
            $flash = ['type' => 'err', 'msg' => $res['error']];
        }
        echo $twig->render('account/login.html.twig', [
            'flash'      => $flash,
            'registered' => isset($_GET['registered']),
            'redirect'   => $_GET['redirect'] ?? '',
        ]);
        break;

    case 'logout':
        session_destroy();
        header('Location: ?page=account&action=login');
        exit;

    case 'profile':
        if (!isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=login'); exit;
        }
        $profile = $ldap->getProfile($_SESSION['lain_uid']);
        echo $twig->render('account/profile.html.twig', [
            'profile' => $profile,
            'uid'     => $_SESSION['lain_uid'],
        ]);
        break;

    case 'change-password':
        if (!isset($_SESSION['lain_uid'])) {
            header('Location: ?page=account&action=login'); exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
                $flash = ['type' => 'err', 'msg' => 'New passwords do not match.'];
            } else {
                $res = $ldap->changePassword(
                    $_SESSION['lain_uid'],
                    $_POST['current_password'] ?? '',
                    $_POST['new_password']     ?? ''
                );
                $flash = $res['ok']
                    ? ['type' => 'ok',  'msg' => 'Password changed successfully.']
                    : ['type' => 'err', 'msg' => $res['error']];
            }
        }
        echo $twig->render('account/change_password.html.twig', ['flash' => $flash]);
        break;

//does not work, this implementation highkey does not work.
    case 'reset-password':
        $ip = getClientIp();
        if (!$checkRateLimit('reset', $ip)) {
            $flash = ['type' => 'err', 'msg' => 'Too many reset attempts. Please try again later.'];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ldap->requestPasswordReset(trim($_POST['uid'] ?? ''));

            $flash = ['type' => 'ok', 'msg' => 'If that account exists, a reset link has been sent to the associated email.'];
        }
        echo $twig->render('account/reset_request.html.twig', ['flash' => $flash]);
        break;

    case 'reset-confirm':
        $ip = getClientIp();
        if (!$checkRateLimit('reset', $ip)) {
            $flash = ['type' => 'err', 'msg' => 'Too many reset attempts. Please try again later.'];
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_POST['new_password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
                $flash = ['type' => 'err', 'msg' => 'Passwords do not match.'];
            } else {
                $res = $ldap->completePasswordReset(
                    trim($_POST['uid']   ?? ''),
                    trim($_POST['token'] ?? ''),
                    $_POST['new_password'] ?? ''
                );
                if ($res['ok']) {
                    header('Location: ?page=account&action=login&reset=1'); exit;
                }
                $flash = ['type' => 'err', 'msg' => $res['error']];
            }
        }
        echo $twig->render('account/reset_confirm.html.twig', [
            'flash' => $flash,
            'uid'   => $_GET['uid']   ?? $_POST['uid']   ?? '',
            'token' => $_GET['token'] ?? $_POST['token'] ?? '',
        ]);
        break;

    default:
        header('Location: ?page=account&action=login'); exit;
}
