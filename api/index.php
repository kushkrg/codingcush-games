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

/** The signed-in player, who must also be an admin. */
function require_admin(PDO $db): int
{
    $userId = require_user($db);
    $query = $db->prepare('SELECT username FROM games_users WHERE id = ?');
    $query->execute([$userId]);
    if (!is_admin((string) $query->fetchColumn())) {
        fail(403, 'Admins only.');
    }
    return $userId;
}

/** Looks a player up by username for an admin action. */
function user_by_username(PDO $db, mixed $username): array
{
    $query = $db->prepare('SELECT id, username, email, name, banned FROM games_users WHERE username = ?');
    $query->execute([strtolower(trim((string) $username))]);
    $row = $query->fetch();
    if (!$row) {
        fail(404, 'No player has that username.');
    }
    return $row;
}

function is_banned(PDO $db, int $userId): bool
{
    $query = $db->prepare('SELECT banned FROM games_users WHERE id = ?');
    $query->execute([$userId]);
    return (int) $query->fetchColumn() === 1;
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
    note_seen($db, (int) $row['user_id']);
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
        if (!settings()['site']['signups_open']) {
            fail(403, 'New accounts are paused for a while. You can still play without one.');
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
        $userId = create_user($db, $email, password_hash($password, PASSWORD_DEFAULT), (string) ($in['name'] ?? ''), 'email');
        count_event($db, 'vyomastra', 'login', 0, 'email_new');
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
        count_event($db, 'vyomastra', 'login', 0, 'email');
        note_seen($db, (int) $user['id']);
        reply(200, ['token' => start_session($db, (int) $user['id']), 'email' => $email]);

    case 'logout':
        $userId = require_user($db);
        $db->prepare('DELETE FROM games_sessions WHERE user_id = ?')->execute([$userId]);
        reply(200, ['ok' => true]);

    case 'account_delete':
        $userId = require_user($db);
        foreach (['games_sessions', 'games_saves', 'games_scores', 'games_runs'] as $table) {
            $db->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$userId]);
        }
        $db->prepare('DELETE FROM games_users WHERE id = ?')->execute([$userId]);
        forget_board_cache('vyomastra');
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
        store_photo($db, $userId, is_banned($db, $userId) ? '' : ($in['save']['photo'] ?? ''));
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
        // Everyone asking for the same board within a few seconds gets the same ready-made answer.
        $kept = board_cache_file($game, $level, $limit, $photos);
        if (is_file($kept) && time() - (int) filemtime($kept) < BOARD_CACHE_SECONDS) {
            $ready = @file_get_contents($kept);
            if ($ready !== false && $ready !== '') {
                echo $ready;
                exit;
            }
        }
        $query = $db->prepare("SELECT s.name, s.ship, s.score, u.username, u.photo FROM games_scores s LEFT JOIN games_users u ON u.id = s.user_id WHERE s.game = ? AND s.level = ? ORDER BY s.score DESC, s.updated_at ASC LIMIT $limit");
        $query->execute([$game, $level]);
        $rows = array_map(fn(array $row): array => ['name' => $row['name'], 'username' => (string) $row['username'], 'ship' => $row['ship'], 'score' => (int) $row['score']]
            + ($photos ? ['photo' => (string) $row['photo']] : []), $query->fetchAll());
        $answer = json_encode(['scores' => $rows], JSON_UNESCAPED_UNICODE);
        @file_put_contents($kept, $answer, LOCK_EX);
        echo $answer;
        exit;

    case 'run_start':
        // Called when a mission begins. The token it returns must come back with the score,
        // and not too soon: a score cannot be posted without a run having been played.
        $userId = require_user($db);
        $in = body();
        $game = game_id($in['game'] ?? '');
        $level = (int) ($in['level'] ?? 0);
        if ($level < 1 || $level > GAMES[$game]['levels']) {
            fail(400, 'Unknown level.');
        }
        $db->prepare('DELETE FROM games_runs WHERE started_at < ? OR (user_id = ? AND game = ? AND level = ?)')
            ->execute([time() - RUN_HOURS * 3600, $userId, $game, $level]);
        $run = bin2hex(random_bytes(32));
        $db->prepare('INSERT INTO games_runs (token_hash, user_id, game, level, started_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([hash('sha256', $run), $userId, $game, $level, time()]);
        reply(200, ['run' => $run]);

    case 'score_put':
        $userId = require_user($db);
        $in = body();
        $game = game_id($in['game'] ?? '');
        $level = (int) ($in['level'] ?? 0);
        $score = (int) ($in['score'] ?? 0);
        if ($level < 1 || $level > GAMES[$game]['levels']) {
            fail(400, 'Unknown level.');
        }
        if ($score < 1 || $score > GAMES[$game]['max_scores'][$level]) {
            fail(400, 'That score is not possible.');
        }
        if (is_banned($db, $userId)) {
            fail(403, 'This account cannot post scores.');
        }
        if (!game_settings($game)['scores_open']) {
            fail(503, 'The leaderboard is paused for a while. Your progress is still saved.');
        }
        $run = (string) ($in['run'] ?? '');
        $query = $db->prepare('SELECT started_at FROM games_runs WHERE token_hash = ? AND user_id = ? AND game = ? AND level = ?');
        $query->execute([hash('sha256', $run), $userId, $game, $level]);
        $started = $query->fetch();
        if (!preg_match('/^[a-f0-9]{64}$/', $run) || !$started) {
            fail(400, 'This score does not belong to a run. Reload the game and play again.');
        }
        if (time() - (int) $started['started_at'] < GAMES[$game]['min_seconds']) {
            fail(400, 'That run was too short to count.');
        }
        // A run pays out once.
        $db->prepare('DELETE FROM games_runs WHERE token_hash = ?')->execute([hash('sha256', $run)]);
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
        if ($best === $score) {
            forget_board_cache($game, $level);   # a new best: the board must show it straight away
        }
        reply(200, ['best' => $best]);

    case 'event':
        // Anonymous counting: how often something happened today. No account, no address kept.
        // count_event() drops unknown games and names, so nothing odd is stored.
        $in = body();
        if (($in['name'] ?? '') !== 'login') {   // sign-ins are counted by the server only
            count_event($db, (string) ($in['game'] ?? ''), (string) ($in['name'] ?? ''), (int) ($in['level'] ?? 0), (string) ($in['detail'] ?? ''));
        }
        reply(200, ['ok' => true]);

    case 'site_config':
        // What the admin panel has switched on: notices, maintenance and per-game tuning.
        header('Cache-Control: public, max-age=60');
        $site = settings()['site'];
        $games = [];
        foreach (array_keys(GAMES) as $id) {
            $one = game_settings($id);
            $games[$id] = [
                'status' => (string) $one['status'],
                'notice' => (string) $one['notice'],
                'coin_boost' => max(1.0, min(5.0, (float) $one['coin_boost'])),
                'boost_until' => (int) $one['boost_until'],
                'continue_cost' => max(1, min(50, (int) $one['continue_cost'])),
                'continue_lives' => max(1, min(9, (int) $one['continue_lives'])),
                'scores_open' => (bool) $one['scores_open'],
            ];
        }
        reply(200, [
            'notice' => $site['notice'] === '' ? null : ['text' => (string) $site['notice'], 'link' => (string) $site['notice_link'], 'tone' => (string) $site['notice_tone']],
            'maintenance' => ['on' => (bool) $site['maintenance'], 'message' => (string) $site['maintenance_message']],
            'signups_open' => (bool) $site['signups_open'],
            'games' => $games,
        ]);

    case 'client_error':
        $in = body();
        count_error($db, (string) ($in['message'] ?? ''), (string) ($in['place'] ?? ''), (string) ($in['version'] ?? ''));
        reply(200, ['ok' => true]);

    case 'admin_stats':
        require_admin($db);
        $since = gmdate('Y-m-d', time() - 13 * 86400);
        $events = $db->prepare('SELECT day, name, level, detail, total FROM games_events WHERE day >= ? ORDER BY day DESC, name, level, detail');
        $events->execute([$since]);
        $errors = $db->prepare('SELECT day, message, place, version, total, last_at FROM games_errors WHERE day >= ? ORDER BY last_at DESC LIMIT 100');
        $errors->execute([$since]);
        $backups = array_map('basename', glob(data_folder() . '/backups/games-*.sqlite') ?: []);
        sort($backups);
        reply(200, [
            'players' => (int) $db->query('SELECT COUNT(*) FROM games_users')->fetchColumn(),
            'banned' => (int) $db->query('SELECT COUNT(*) FROM games_users WHERE banned = 1')->fetchColumn(),
            'scores' => (int) $db->query('SELECT COUNT(*) FROM games_scores')->fetchColumn(),
            'events' => $events->fetchAll(),
            'errors' => $errors->fetchAll(),
            'backups' => $backups,
        ]);

    case 'admin_player':
        require_admin($db);
        $player = user_by_username($db, $_GET['username'] ?? '');
        $scores = $db->prepare('SELECT level, score, updated_at FROM games_scores WHERE user_id = ? ORDER BY level');
        $scores->execute([(int) $player['id']]);
        reply(200, ['username' => $player['username'], 'name' => $player['name'], 'email' => $player['email'],
            'banned' => (int) $player['banned'] === 1, 'scores' => $scores->fetchAll()]);

    case 'admin_score_delete':
        // Removes one player's score on one level (level 0 = every level).
        require_admin($db);
        $in = body();
        $player = user_by_username($db, $in['username'] ?? '');
        $level = (int) ($in['level'] ?? 0);
        if ($level > 0) {
            $gone = $db->prepare('DELETE FROM games_scores WHERE user_id = ? AND level = ?');
            $gone->execute([(int) $player['id'], $level]);
        } else {
            $gone = $db->prepare('DELETE FROM games_scores WHERE user_id = ?');
            $gone->execute([(int) $player['id']]);
        }
        forget_board_cache('vyomastra');
        reply(200, ['removed' => $gone->rowCount()]);

    case 'admin_photo_clear':
        require_admin($db);
        $player = user_by_username($db, body()['username'] ?? '');
        $db->prepare('UPDATE games_users SET photo = NULL WHERE id = ?')->execute([(int) $player['id']]);
        forget_board_cache('vyomastra');
        reply(200, ['ok' => true]);

    case 'admin_ban':
        // A banned player can still play and keep their save, but posts no scores and shows no photo.
        require_admin($db);
        $in = body();
        $player = user_by_username($db, $in['username'] ?? '');
        if (is_admin((string) $player['username'])) {
            fail(400, 'An admin cannot be banned.');
        }
        $banned = !empty($in['banned']) ? 1 : 0;
        $db->prepare('UPDATE games_users SET banned = ? WHERE id = ?')->execute([$banned, (int) $player['id']]);
        if ($banned === 1) {
            $db->prepare('DELETE FROM games_scores WHERE user_id = ?')->execute([(int) $player['id']]);
            $db->prepare('UPDATE games_users SET photo = NULL WHERE id = ?')->execute([(int) $player['id']]);
        }
        forget_board_cache('vyomastra');
        reply(200, ['banned' => $banned === 1]);

    case 'providers':
        $on = social_providers();
        reply(200, ['google' => isset($on['google']), 'facebook' => isset($on['facebook'])]);

    default:
        fail(404, 'Unknown action.');
}
