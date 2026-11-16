<?php
/**
 * Shared pieces of the CodingCush Games API: configuration, the database,
 * sign-in sessions and small helpers. Included by index.php and oauth.php.
 */
declare(strict_types=1);

const SESSION_DAYS = 90;
const MAX_SAVE_BYTES = 60000;
const MAX_ATTEMPTS = 8;          // failed sign-ins allowed per address per 15 minutes
const GAMES = [
    // game id => highest level, the largest score each level can plausibly give (about
    // four times what a clean run earns), and the shortest time a scoring run can take.
    'vyomastra' => [
        'levels' => 8,
        'max_scores' => [1 => 40000, 2 => 50000, 3 => 100000, 4 => 90000, 5 => 130000, 6 => 110000, 7 => 150000, 8 => 200000],
        'min_seconds' => 30,
    ],
];
const RUN_HOURS = 6;             // a run token is good for this long
const BACKUP_KEEP = 14;          // daily database copies kept
const EVENT_NAMES = ['session', 'signin', 'level_start', 'level_complete', 'level_fail', 'continue_used'];
const MAX_EVENT_ROWS_PER_DAY = 2000;
const MAX_ERROR_ROWS_PER_DAY = 200;

/** Optional settings from config.php (see config.sample.php); empty when the file is absent. */
function site_config(): array
{
    static $config = null;
    if ($config === null) {
        $file = __DIR__ . '/config.php';
        $config = is_file($file) ? (array) require $file : [];
    }
    return $config;
}

/** Private folder for the SQLite file: outside the web folder when the server allows it. */
function data_folder(): string
{
    // The real site (in .../public_html/games) keeps its data in games-data. Any other copy,
    // such as the staging site in games-staging, gets a folder of its own (games-staging-data),
    // so testing can never touch real players' accounts.
    $site = basename(dirname(__DIR__));
    $folder = dirname(__DIR__, 3) . '/' . ($site === 'games' ? 'games' : $site) . '-data';
    if (!is_dir($folder) && !@mkdir($folder, 0700, true)) {
        $folder = __DIR__ . '/data';
        if (!is_dir($folder)) {
            mkdir($folder, 0700, true);
        }
        if (!is_file($folder . '/.htaccess')) {
            file_put_contents($folder . '/.htaccess', "Require all denied\n");
        }
    }
    return $folder;
}

/** Sign-in providers that have keys in config.php: ['google' => ['id' => ..., 'secret' => ...], ...]. */
function social_providers(): array
{
    $found = [];
    foreach (['google', 'facebook'] as $name) {
        $keys = site_config()[$name] ?? null;
        if (is_array($keys) && !empty($keys['id']) && !empty($keys['secret'])) {
            $found[$name] = $keys;
        }
    }
    return $found;
}

/**
 * Opens the database. With no config.php the API keeps everything in one SQLite file
 * that needs no password: outside the web folder when the server allows it, otherwise
 * in api/data/, which is closed to the web. A config.php (see config.sample.php)
 * switches to MySQL.
 */
function open_database(): PDO
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $config = site_config();
    if (isset($config['dsn'])) {
        $db = new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, $options);
        add_usernames($db);
        add_photos($db);
        add_later_tables($db);
        return $db;
    }
    $folder = data_folder();
    $db = new PDO('sqlite:' . $folder . '/games.sqlite', null, null, $options);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA busy_timeout = 4000');
    $db->exec('CREATE TABLE IF NOT EXISTS games_users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT NOT NULL UNIQUE, pass_hash TEXT NOT NULL, name TEXT NOT NULL DEFAULT \'PILOT\', created_at INTEGER NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS games_sessions (token_hash TEXT NOT NULL PRIMARY KEY, user_id INTEGER NOT NULL, expires_at INTEGER NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS games_saves (user_id INTEGER NOT NULL, game TEXT NOT NULL, data TEXT NOT NULL, updated_at INTEGER NOT NULL, PRIMARY KEY (user_id, game))');
    $db->exec('CREATE TABLE IF NOT EXISTS games_scores (user_id INTEGER NOT NULL, game TEXT NOT NULL, level INTEGER NOT NULL, name TEXT NOT NULL, ship TEXT NOT NULL DEFAULT \'\', score INTEGER NOT NULL, updated_at INTEGER NOT NULL, PRIMARY KEY (user_id, game, level))');
    $db->exec('CREATE INDEX IF NOT EXISTS games_scores_board ON games_scores (game, level, score)');
    $db->exec('CREATE TABLE IF NOT EXISTS games_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, address TEXT NOT NULL, made_at INTEGER NOT NULL)');
    add_usernames($db);
    add_photos($db);
    add_later_tables($db);
    maybe_backup($db, $folder);
    return $db;
}

/**
 * Tables and columns added after launch. Written so the same statements work on
 * SQLite and MySQL, and so running them again does nothing.
 */
function add_later_tables(PDO $db): void
{
    $db->exec('CREATE TABLE IF NOT EXISTS games_runs (token_hash CHAR(64) NOT NULL PRIMARY KEY, user_id INTEGER NOT NULL, game VARCHAR(32) NOT NULL, level INTEGER NOT NULL, started_at INTEGER NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS games_events (day CHAR(10) NOT NULL, game VARCHAR(32) NOT NULL, name VARCHAR(32) NOT NULL, level INTEGER NOT NULL, detail VARCHAR(16) NOT NULL, total INTEGER NOT NULL, PRIMARY KEY (day, game, name, level, detail))');
    $db->exec('CREATE TABLE IF NOT EXISTS games_errors (day CHAR(10) NOT NULL, hash CHAR(40) NOT NULL, message TEXT NOT NULL, place VARCHAR(160) NOT NULL, version VARCHAR(16) NOT NULL, total INTEGER NOT NULL, last_at INTEGER NOT NULL, PRIMARY KEY (day, hash))');
    try {
        $db->query('SELECT banned FROM games_users LIMIT 1');
    } catch (PDOException $missing) {
        $db->exec('ALTER TABLE games_users ADD COLUMN banned INTEGER NOT NULL DEFAULT 0');
    }
}

/**
 * Once a day, the first request copies the whole database into backups/ next to it
 * and throws away copies older than BACKUP_KEEP days. SQLite only: a MySQL database
 * is backed up by the host.
 */
function maybe_backup(PDO $db, string $folder): void
{
    $backups = $folder . '/backups';
    $marker = $backups . '/last-backup.txt';
    if (is_file($marker) && time() - (int) filemtime($marker) < 86400) {
        return;
    }
    if (!is_dir($backups) && !@mkdir($backups, 0700, true)) {
        return;
    }
    if (!is_file($backups . '/.htaccess')) {
        @file_put_contents($backups . '/.htaccess', "Require all denied\n");
    }
    @touch($marker);   // first, so two requests at the same moment do not both copy
    $target = $backups . '/games-' . gmdate('Y-m-d') . '.sqlite';
    if (!is_file($target)) {
        try {
            $db->exec('VACUUM INTO ' . $db->quote($target));   // a clean, consistent copy
        } catch (PDOException $old) {
            @copy($folder . '/games.sqlite', $target);          // very old SQLite: plain copy
        }
    }
    $copies = glob($backups . '/games-*.sqlite') ?: [];
    sort($copies);
    foreach (array_slice($copies, 0, max(count($copies) - BACKUP_KEEP, 0)) as $stale) {
        @unlink($stale);
    }
}

const BOARD_CACHE_SECONDS = 30;

/** Where finished leaderboard answers are kept for a few seconds, so a busy board is not rebuilt for every visitor. */
function board_cache_file(string $game, int $level, int $limit, bool $photos): string
{
    $folder = data_folder() . '/cache';
    if (!is_dir($folder)) {
        @mkdir($folder, 0700, true);
        @file_put_contents($folder . '/.htaccess', "Require all denied\n");
    }
    return sprintf('%s/board-%s-%d-%d-%d.json', $folder, $game, $level, $limit, $photos ? 1 : 0);
}

/** Throws away kept leaderboard answers for a game (one level, or every level with 0) after a score changes. */
function forget_board_cache(string $game, int $level = 0): void
{
    $pattern = sprintf('%s/cache/board-%s-%s-*.json', data_folder(), $game, $level > 0 ? (string) $level : '*');
    foreach (glob($pattern) ?: [] as $file) {
        @unlink($file);
    }
}

/** Adds one to a named counter for today. Unknown names and odd details are dropped. */
function count_event(PDO $db, string $game, string $name, int $level, string $detail): void
{
    if (!in_array($name, EVENT_NAMES, true) || !preg_match('/^[a-z0-9_]{0,16}$/', $detail) || $level < 0 || $level > 99) {
        return;
    }
    $day = gmdate('Y-m-d');
    $key = [$day, $game, $name, $level, $detail];
    $bump = $db->prepare('UPDATE games_events SET total = total + 1 WHERE day = ? AND game = ? AND name = ? AND level = ? AND detail = ?');
    $bump->execute($key);
    if ($bump->rowCount() > 0) {
        return;
    }
    $rows = $db->prepare('SELECT COUNT(*) FROM games_events WHERE day = ?');
    $rows->execute([$day]);
    if ((int) $rows->fetchColumn() >= MAX_EVENT_ROWS_PER_DAY) {
        return;
    }
    try {
        $db->prepare('INSERT INTO games_events (day, game, name, level, detail, total) VALUES (?, ?, ?, ?, ?, 1)')->execute($key);
    } catch (PDOException $race) {
        $bump->execute($key);   // someone else added the row in between
    }
}

/** Records an error reported by a player's browser; the same error on the same day is counted, not repeated. */
function count_error(PDO $db, string $message, string $place, string $version): void
{
    $message = substr(trim($message), 0, 400);
    $place = substr(trim($place), 0, 160);
    $version = substr(preg_replace('/[^0-9A-Za-z.\-]/', '', $version) ?? '', 0, 16);
    if ($message === '') {
        return;
    }
    $day = gmdate('Y-m-d');
    $hash = sha1($message . '|' . $place);
    $bump = $db->prepare('UPDATE games_errors SET total = total + 1, last_at = ? WHERE day = ? AND hash = ?');
    $bump->execute([time(), $day, $hash]);
    if ($bump->rowCount() > 0) {
        return;
    }
    $rows = $db->prepare('SELECT COUNT(*) FROM games_errors WHERE day = ?');
    $rows->execute([$day]);
    if ((int) $rows->fetchColumn() >= MAX_ERROR_ROWS_PER_DAY) {
        return;
    }
    try {
        $db->prepare('INSERT INTO games_errors (day, hash, message, place, version, total, last_at) VALUES (?, ?, ?, ?, ?, 1, ?)')
            ->execute([$day, $hash, $message, $place, $version, time()]);
    } catch (PDOException $race) {
        $bump->execute([time(), $day, $hash]);
    }
}

/**
 * Usernames were added after launch: on a database made before that, add the column
 * and give every existing player one. Does nothing once the column exists.
 */
/** Profile photos shown on the leaderboard live in their own column; add it to an older database. */
function add_photos(PDO $db): void
{
    try {
        $db->query('SELECT photo FROM games_users LIMIT 1');
        return;
    } catch (PDOException $missing) {
        $db->exec('ALTER TABLE games_users ADD COLUMN photo TEXT');
    }
    // Photos already saved inside cloud saves before this column existed.
    foreach ($db->query('SELECT user_id, data FROM games_saves')->fetchAll() as $row) {
        $save = json_decode($row['data'], true);
        store_photo($db, (int) $row['user_id'], is_array($save) ? ($save['photo'] ?? '') : '');
    }
}

/** Keeps a player's photo (a small base64 JPEG) for the leaderboard, or clears it. Anything else is ignored. */
function store_photo(PDO $db, int $userId, mixed $photo): void
{
    $clean = null;
    if (is_string($photo) && $photo !== '' && strlen($photo) <= 40000) {
        $bytes = base64_decode($photo, true);
        if ($bytes !== false && str_starts_with($bytes, "\xFF\xD8\xFF")) {
            $clean = $photo;
        }
    }
    $db->prepare('UPDATE games_users SET photo = ? WHERE id = ?')->execute([$clean, $userId]);
}

function add_usernames(PDO $db): void
{
    try {
        $db->query('SELECT username FROM games_users LIMIT 1');
        return;
    } catch (PDOException $missing) {
        $db->exec('ALTER TABLE games_users ADD COLUMN username VARCHAR(16)');
        $db->exec('CREATE UNIQUE INDEX games_users_username ON games_users (username)');
    }
    foreach ($db->query('SELECT id, name, email FROM games_users WHERE username IS NULL')->fetchAll() as $row) {
        $db->prepare('UPDATE games_users SET username = ? WHERE id = ?')
            ->execute([free_username($db, $row['name'] === 'PILOT' ? strstr($row['email'], '@', true) : $row['name']), $row['id']]);
    }
}

/** Players with these usernames are admins: every mission is open to them. More can be added in config.php under 'admins'. */
const ADMIN_USERNAMES = ['kushkrg'];

function is_admin(string $username): bool
{
    $extra = site_config()['admins'] ?? [];
    return $username !== '' && in_array($username, array_merge(ADMIN_USERNAMES, is_array($extra) ? $extra : []), true);
}

const USERNAME_PATTERN = '/^[a-z][a-z0-9_]{2,15}$/';
const RESERVED_USERNAMES = ['admin', 'administrator', 'moderator', 'support', 'codingcush', 'vyomastra', 'system', 'official', 'guest', 'pilot', 'null'];

/** Why a username cannot be used, or '' when it is fine. Does not check whether it is taken. */
function username_problem(string $username): string
{
    if (!preg_match(USERNAME_PATTERN, $username)) {
        return 'Use 3 to 16 characters: small letters, numbers and _ only, starting with a letter.';
    }
    if (in_array($username, RESERVED_USERNAMES, true)) {
        return 'That username is not available.';
    }
    return '';
}

function username_taken(PDO $db, string $username): bool
{
    $query = $db->prepare('SELECT id FROM games_users WHERE username = ?');
    $query->execute([$username]);
    return (bool) $query->fetch();
}

/** A username nobody has yet, based on a name or the first part of an email address. */
function free_username(PDO $db, string $hint): string
{
    $base = substr(preg_replace('/[^a-z0-9_]/', '', strtolower($hint)) ?? '', 0, 11);
    $base = ltrim($base, '0123456789_');
    if (strlen($base) < 3) {
        $base = 'pilot';
    }
    if (username_problem($base) === '' && !is_admin($base) && !username_taken($db, $base)) {
        return $base;
    }
    do {
        $candidate = $base . '_' . random_int(100, 9999);
    } while (username_taken($db, $candidate));
    return $candidate;
}

/** Adds a player and returns their id. */
function create_user(PDO $db, string $email, string $passHash, string $name): int
{
    $hint = clean_name($name) === 'PILOT' ? (string) strstr($email, '@', true) : $name;
    $db->prepare('INSERT INTO games_users (email, pass_hash, name, created_at, username) VALUES (?, ?, ?, ?, ?)')
        ->execute([$email, $passHash, clean_name($name), time(), free_username($db, $hint)]);
    return (int) $db->lastInsertId();
}

function clean_name(mixed $value): string
{
    $name = strtoupper(trim(preg_replace('/[^A-Za-z0-9 _\-]/', '', (string) $value) ?? ''));
    $name = substr($name, 0, 12);
    return $name === '' ? 'PILOT' : $name;
}

function client_address(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
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
