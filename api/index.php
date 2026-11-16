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
 *   providers GET                                 -> {google: bool, facebook: bool}
 *   score_put POST {game, level, name, ship, score} (needs token) -> {best}
 * The token goes in the "Authorization: Bearer <token>" header.
 */
declare(strict_types=1);

require __DIR__ . '/lib.php';

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

$db = open_database();

function body(): array
{
    $raw = file_get_contents('php://input', false, null, 0, MAX_SAVE_BYTES + 2000);
    $data = json_decode($raw === false ? '' : $raw, true);
    return is_array($data) ? $data : [];
}

function game_id(mixed $value): string
{
    $game = (string) $value;
    if (!isset(GAMES[$game])) {
        fail(400, 'Unknown game.');
    }
    return $game;
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
        $userId = create_user($db, $email, password_hash($password, PASSWORD_DEFAULT), (string) ($in['name'] ?? ''));
        reply(200, ['token' => start_session($db, $userId), 'email' => $email]);

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

    case 'account_delete':
        $userId = require_user($db);
        foreach (['games_sessions', 'games_saves', 'games_scores'] as $table) {
            $db->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$userId]);
        }
        $db->prepare('DELETE FROM games_users WHERE id = ?')->execute([$userId]);
        reply(200, ['ok' => true]);

    case 'me':
        $userId = require_user($db);
        $query = $db->prepare('SELECT email, username, created_at FROM games_users WHERE id = ?');
        $query->execute([$userId]);
        $row = $query->fetch();
        reply(200, ['email' => $row['email'], 'username' => (string) $row['username'], 'joined_at' => (int) $row['created_at'], 'admin' => is_admin((string) $row['username'])]);

    case 'username_set':
        $userId = require_user($db);
        $username = strtolower(trim((string) (body()['username'] ?? '')));
        $problem = username_problem($username);
        if ($problem !== '') {
            fail(400, $problem);
        }
        $current = $db->prepare('SELECT username FROM games_users WHERE id = ?');
        $current->execute([$userId]);
        if (is_admin($username) && (string) $current->fetchColumn() !== $username) {
            fail(400, 'That username is not available.');
        }
        $query = $db->prepare('SELECT id FROM games_users WHERE username = ?');
        $query->execute([$username]);
        $owner = $query->fetch();
        if ($owner && (int) $owner['id'] !== $userId) {
            fail(409, 'That username is taken. Try another one.');
        }
        try {
            $db->prepare('UPDATE games_users SET username = ? WHERE id = ?')->execute([$username, $userId]);
        } catch (PDOException $clash) {
            fail(409, 'That username is taken. Try another one.');
        }
        reply(200, ['username' => $username]);

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
        store_photo($db, $userId, $in['save']['photo'] ?? '');
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
        // Photos are only sent when asked for (the game asks; the website's small board does not).
        $photos = ($_GET['photos'] ?? '') === '1';
        $query = $db->prepare("SELECT s.name, s.ship, s.score, u.username, u.photo FROM games_scores s LEFT JOIN games_users u ON u.id = s.user_id WHERE s.game = ? AND s.level = ? ORDER BY s.score DESC, s.updated_at ASC LIMIT $limit");
        $query->execute([$game, $level]);
        $rows = array_map(fn(array $row): array => ['name' => $row['name'], 'username' => (string) $row['username'], 'ship' => $row['ship'], 'score' => (int) $row['score']]
            + ($photos ? ['photo' => (string) $row['photo']] : []), $query->fetchAll());
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

    case 'providers':
        $on = social_providers();
        reply(200, ['google' => isset($on['google']), 'facebook' => isset($on['facebook'])]);

    default:
        fail(404, 'Unknown action.');
}
