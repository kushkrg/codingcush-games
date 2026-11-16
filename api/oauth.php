<?php
/**
 * Sign in with Google or Facebook.
 *
 *   /api/oauth.php?provider=google&back=/vyomastra/play/   starts: sends the browser to the provider
 *   /api/oauth.php?code=...&state=...                      the provider sends the browser back here
 *
 * On return the server swaps the code for the player's email, finds or creates
 * the account, opens a session and sends the browser back to the game with the
 * session in the page address after the "#" (that part never reaches a server
 * or a log). The game picks it up and wipes it from the address.
 *
 * Keys live in config.php (see config.sample.php). The address to register with
 * each provider as the "redirect URI" is  https://<this site>/api/oauth.php
 */
declare(strict_types=1);

require __DIR__ . '/lib.php';

const COOKIE = 'vy_oauth';
const DEFAULT_BACK = '/vyomastra/play/';
const ENDPOINTS = [
    'google' => [
        'auth' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token' => 'https://oauth2.googleapis.com/token',
        'user' => 'https://openidconnect.googleapis.com/v1/userinfo',
        'scope' => 'openid email profile',
    ],
    'facebook' => [
        'auth' => 'https://www.facebook.com/dialog/oauth',
        'token' => 'https://graph.facebook.com/oauth/access_token',
        'user' => 'https://graph.facebook.com/me?fields=id,name,email',
        'scope' => 'email,public_profile',
    ],
];

header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');

function safe_back(mixed $value): string
{
    $path = (string) $value;
    return preg_match('#^/[A-Za-z0-9/_\-]{0,80}$#', $path) ? $path : DEFAULT_BACK;
}

/** Sends the browser back to the game with either a session or a message. */
function finish(string $back, array $fields): never
{
    setcookie(COOKIE, '', ['expires' => 1, 'path' => '/api/', 'httponly' => true, 'samesite' => 'Lax']);
    header('Location: ' . $back . '#' . http_build_query($fields, '', '&', PHP_QUERY_RFC3986), true, 302);
    exit;
}

function give_up(string $back, string $message): never
{
    finish($back, ['vy_error' => $message]);
}

/** Small HTTPS client: GET, or POST when $post is given. Returns the decoded JSON or null. */
function fetch_json(string $url, ?array $post = null, ?string $bearer = null): ?array
{
    $curl = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($bearer !== null) {
        $headers[] = 'Authorization: Bearer ' . $bearer;
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $raw = curl_exec($curl);
    curl_close($curl);
    $data = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($data) ? $data : null;
}

set_exception_handler(function (Throwable $error): void {
    error_log('games oauth: ' . $error->getMessage());
    give_up(DEFAULT_BACK, 'Sign-in failed. Please try again.');
});

$secure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
$self = ($secure ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . strtok((string) ($_SERVER['REQUEST_URI'] ?? '/api/oauth.php'), '?');
$providers = social_providers();

// ---- step 1: leave for the provider
if (isset($_GET['provider'])) {
    $name = (string) $_GET['provider'];
    $back = safe_back($_GET['back'] ?? DEFAULT_BACK);
    if (!isset($providers[$name])) {
        give_up($back, 'That sign-in is not switched on yet.');
    }
    $state = bin2hex(random_bytes(16));
    setcookie(COOKIE, json_encode(['p' => $name, 's' => $state, 'b' => $back]), [
        'expires' => time() + 600, 'path' => '/api/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax',
    ]);
    $ends = array_merge(ENDPOINTS[$name], $providers[$name]['endpoints'] ?? []);
    $query = http_build_query([
        'client_id' => $providers[$name]['id'],
        'redirect_uri' => $self,
        'response_type' => 'code',
        'scope' => $ends['scope'],
        'state' => $state,
    ]);
    header('Location: ' . $ends['auth'] . (str_contains($ends['auth'], '?') ? '&' : '?') . $query, true, 302);
    exit;
}

// ---- step 2: back from the provider
$memo = json_decode((string) ($_COOKIE[COOKIE] ?? ''), true);
$back = safe_back(is_array($memo) ? ($memo['b'] ?? '') : '');
$name = is_array($memo) ? (string) ($memo['p'] ?? '') : '';
if (!is_array($memo) || !isset($providers[$name]) || !hash_equals((string) ($memo['s'] ?? ''), (string) ($_GET['state'] ?? ''))) {
    give_up($back, 'Sign-in could not be checked. Please try again.');
}
if (!isset($_GET['code'])) {
    give_up($back, 'Sign-in was cancelled.');
}
$ends = array_merge(ENDPOINTS[$name], $providers[$name]['endpoints'] ?? []);
$token = fetch_json($ends['token'], [
    'client_id' => $providers[$name]['id'],
    'client_secret' => $providers[$name]['secret'],
    'code' => (string) $_GET['code'],
    'redirect_uri' => $self,
    'grant_type' => 'authorization_code',
]);
if (empty($token['access_token'])) {
    give_up($back, 'Sign-in failed. Please try again.');
}
$person = fetch_json($ends['user'], null, (string) $token['access_token']);
$email = strtolower(trim((string) ($person['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    give_up($back, 'No email address was shared, so an account could not be made. Use email sign-in instead.');
}
if ($name === 'google' && ($person['email_verified'] ?? false) !== true && ($person['email_verified'] ?? '') !== 'true') {
    give_up($back, 'That Google email address is not verified.');
}

$db = open_database();
$query = $db->prepare('SELECT id FROM games_users WHERE email = ?');
$query->execute([$email]);
$user = $query->fetch();
if ($user) {
    $userId = (int) $user['id'];
} else {
    // No password is ever set for this account: the stored hash is of random bytes nobody knows.
    $db->prepare('INSERT INTO games_users (email, pass_hash, name, created_at) VALUES (?, ?, ?, ?)')
        ->execute([$email, password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT), clean_name($person['name'] ?? ''), time()]);
    $userId = (int) $db->lastInsertId();
}
finish($back, ['vy_token' => start_session($db, $userId), 'vy_email' => $email]);
