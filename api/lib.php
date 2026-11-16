<?php
/**
 * Shared pieces of the CodingCush Games API: configuration, the database,
 * sign-in sessions and small helpers. Included by index.php and oauth.php.
 */
declare(strict_types=1);

const SESSION_DAYS = 90;
const MAX_SAVE_BYTES = 20000;
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
        return new PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null, $options);
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
    return $db;
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
