<?php
/**
 * CodingCush Games API: accounts, cloud saves and leaderboards for every game on the site.
 *
 * One endpoint, chosen with ?action=...  All bodies and replies are JSON.
 *   register  POST {email, password, name}       -> {token, email}
 *   login     POST {email, password}             -> {token, email}
 *   logout    POST                               (needs token)
 *   save_get  GET  ?game=                        (needs token) -> {save}
 *   save_put  POST {game, name, save}            (needs token)
 *   scores    GET  ?game=&level=&limit=          -> {scores: [{name, ship, score}]}
 *   score_put POST {game, level, name, ship, score} (needs token) -> {best}
 * The token goes in the "Authorization: Bearer <token>" header.
 */
declare(strict_types=1);

const SESSION_DAYS = 90;
const MAX_SAVE_BYTES = 20000;
const MAX_ATTEMPTS = 8;          // failed sign-ins allowed per address per 15 minutes
const GAMES = [
    // game id => highest level and the largest score a level can plausibly give
    'vyomastra' => ['levels' => 8, 'max_score' => 400000],
];

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function reply(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

function fail(int $status, string $message): never
{
    reply($status, ['message' => $message]);
}

set_exception_handler(function (Throwable $error): void {
    error_log('games api: ' . $error->getMessage());
    fail(500, 'Server error. Please try again.');
});

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    fail(500, 'The games API is not set up yet (config.php is missing).');
}
$config = require $configFile;
$db = new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

function body(): array
{
    $raw = file_get_contents('php://input', false, null, 0, MAX_SAVE_BYTES + 2000);
    $data = json_decode($raw === false ? '' : $raw, true);
    return is_array($data) ? $data : [];
}

function clean_name(mixed $value): string
{
    $name = strtoupper(trim(preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) $value) ?? ''));
    $name = substr($name, 0, 12);
    return $name === '' ? 'PILOT' : $name;
}

function game_id(mixed $value): string
{
    $game = (string) $value;
    if (!isset(GAMES[$game])) {
        fail(400, 'Unknown game.');
    }
    return $game;
}

function client_address(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

/** Returns the signed-in user's id, or stops with 401. */
function require_user(PDO $db): int
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $header = $value;
            }
        }
    }
    if (!preg_match('/^Bearer\s+([A-Za-z0-9]{64})$/', $header, $match)) {
        fail(401, 'Please sign in.');
    }
    $query = $db->prepare('SELECT user_id FROM games_sessions WHERE token_hash = ? AND expires_at > ?');
    $query->execute([hash('sha256', $match[1]), time()]);
    $row = $query->fetch();
    if (!$row) {
        fail(401, 'Your sign-in has expired. Please sign in again.');
    }
    return (int) $row['user_id'];
}

function start_session(PDO $db, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $db->prepare('DELETE FROM games_sessions WHERE expires_at < ?')->execute([time()]);
    $db->prepare('INSERT INTO games_sessions (token_hash, user_id, expires_at) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $token), $userId, time() + SESSION_DAYS * 86400]);
    return $token;
}

function too_many_attempts(PDO $db): bool
{
    $db->prepare('DELETE FROM games_attempts WHERE made_at < ?')->execute([time() - 900]);
    $query = $db->prepare('SELECT COUNT(*) AS total FROM games_attempts WHERE address = ?');
    $query->execute([client_address()]);
    return (int) $query->fetch()['total'] >= MAX_ATTEMPTS;
}

function note_attempt(PDO $db): void
{
    $db->prepare('INSERT INTO games_attempts (address, made_at) VALUES (?, ?)')->execute([client_address(), time()]);
}

$action = (string) ($_GET['action'] ?? '');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$posts = ['register', 'login', 'logout', 'save_put', 'score_put'];
if (in_array($action, $posts, true) && $method !== 'POST') {
    fail(405, 'Use POST for this action.');
}

switch ($action) {
    case 'register':
        $in = body();
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        $password = (string) ($in['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
            fail(400, 'Enter a valid email address.');
        }
        if (strlen($password) < 8 || strlen($password) > 200) {
            fail(400, 'The password must be 8 characters or more.');
        }
        if (too_many_attempts($db)) {
            fail(429, 'Too many tries. Wait 15 minutes and try again.');
        }
        note_attempt($db);
        $exists = $db->prepare('SELECT id FROM games_users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) {
            fail(409, 'This email already has an account. Sign in instead.');
        }
        $db->prepare('INSERT INTO games_users (email, pass_hash, name, created_at) VALUES (?, ?, ?, ?)')
            ->execute([$email, password_hash($password, PASSWORD_DEFAULT), clean_name($in['name'] ?? ''), time()]);
        reply(200, ['token' => start_session($db, (int) $db->lastInsertId()), 'email' => $email]);

    case 'login':
        $in = body();
        $email = strtolower(trim((string) ($in['email'] ?? '')));
        if (too_many_attempts($db)) {
            fail(429, 'Too many tries. Wait 15 minutes and try again.');
        }
        $query = $db->prepare('SELECT id, pass_hash FROM games_users WHERE email = ?');
        $query->execute([$email]);
        $user = $query->fetch();
        if (!$user || !password_verify((string) ($in['password'] ?? ''), $user['pass_hash'])) {
            note_attempt($db);
            fail(401, 'Wrong email or password.');
        }
        reply(200, ['token' => start_session($db, (int) $user['id']), 'email' => $email]);

    case 'logout':
        $userId = require_user($db);
        $db->prepare('DELETE FROM games_sessions WHERE user_id = ?')->execute([$userId]);
        reply(200, ['ok' => true]);

    case 'save_get':
        $userId = require_user($db);
        $query = $db->prepare('SELECT data FROM games_saves WHERE user_id = ? AND game = ?');
        $query->execute([$userId, game_id($_GET['game'] ?? '')]);
        $row = $query->fetch();
        reply(200, ['save' => $row ? json_decode($row['data'], true) : null]);

    case 'save_put':
        $userId = require_user($db);
        $in = body();
        $game = game_id($in['game'] ?? '');
        if (!is_array($in['save'] ?? null)) {
            fail(400, 'Nothing to save.');
        }
        $data = json_encode($in['save'], JSON_UNESCAPED_UNICODE);
        if ($data === false || strlen($data) > MAX_SAVE_BYTES) {
            fail(413, 'The save is too large.');
        }
        $db->beginTransaction();
        $query = $db->prepare('SELECT user_id FROM games_saves WHERE user_id = ? AND game = ?');
        $query->execute([$userId, $game]);
        if ($query->fetch()) {
            $db->prepare('UPDATE games_saves SET data = ?, updated_at = ? WHERE user_id = ? AND game = ?')
                ->execute([$data, time(), $userId, $game]);
        } else {
            $db->prepare('INSERT INTO games_saves (user_id, game, data, updated_at) VALUES (?, ?, ?, ?)')
                ->execute([$userId, $game, $data, time()]);
        }
        if (isset($in['name'])) {
            $db->prepare('UPDATE games_users SET name = ? WHERE id = ?')->execute([clean_name($in['name']), $userId]);
        }
        $db->commit();
        reply(200, ['ok' => true]);

    case 'scores':
        $game = game_id($_GET['game'] ?? '');
        $level = (int) ($_GET['level'] ?? 1);
        $limit = max(1, min(50, (int) ($_GET['limit'] ?? 20)));
        $query = $db->prepare("SELECT name, ship, score FROM games_scores WHERE game = ? AND level = ? ORDER BY score DESC, updated_at ASC LIMIT $limit");
        $query->execute([$game, $level]);
        $rows = array_map(fn(array $row): array => ['name' => $row['name'], 'ship' => $row['ship'], 'score' => (int) $row['score']], $query->fetchAll());
        reply(200, ['scores' => $rows]);

    case 'score_put':
        $userId = require_user($db);
        $in = body();
        $game = game_id($in['game'] ?? '');
        $level = (int) ($in['level'] ?? 0);
        $score = (int) ($in['score'] ?? 0);
        if ($level < 1 || $level > GAMES[$game]['levels']) {
            fail(400, 'Unknown level.');
        }
        if ($score < 1 || $score > GAMES[$game]['max_score']) {
            fail(400, 'That score is not possible.');
        }
        $ship = substr(preg_replace('/[^a-z0-9_]/', '', strtolower((string) ($in['ship'] ?? ''))) ?? '', 0, 16);
        $name = clean_name($in['name'] ?? '');
        $db->beginTransaction();
        $query = $db->prepare('SELECT score FROM games_scores WHERE user_id = ? AND game = ? AND level = ?');
        $query->execute([$userId, $game, $level]);
        $row = $query->fetch();
        $best = $score;
        if (!$row) {
            $db->prepare('INSERT INTO games_scores (user_id, game, level, name, ship, score, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $game, $level, $name, $ship, $score, time()]);
        } elseif ($score > (int) $row['score']) {
            $db->prepare('UPDATE games_scores SET name = ?, ship = ?, score = ?, updated_at = ? WHERE user_id = ? AND game = ? AND level = ?')
                ->execute([$name, $ship, $score, time(), $userId, $game, $level]);
        } else {
            $best = (int) $row['score'];
        }
        $db->commit();
        reply(200, ['best' => $best]);

    default:
        fail(404, 'Unknown action.');
}
