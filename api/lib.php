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
    // game id => highest level and the largest score a level can plausibly give
    'vyomastra' => ['levels' => 8, 'max_score' => 400000],
];

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
    $folder = dirname(__DIR__, 3) . '/games-data';
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
    return $db;
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
