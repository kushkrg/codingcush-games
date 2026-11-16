<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Session;
use PDO;
use PDOException;

/**
 * Admin → Games: reads and changes the CodingCush Games database.
 *
 * The games site (games.codingcush.com, folder public_html/games) keeps its accounts,
 * saves, scores and daily counters in its own database: by default the SQLite file
 * domains/codingcush.com/games-data/games.sqlite, or MySQL when games/api/config.php
 * says so. Both sites live on the same hosting account, so this class opens that
 * database directly; nothing goes over the network and no extra password is needed.
 *
 * The staging copy (staging-games.codingcush.com) has a database of its own, and the
 * admin can switch between the two (Session key games_source).
 */
class GamesData
{
    /** Games shown in the admin. Keep in step with GAMES in games/api/lib.php. */
    public const GAMES = [
        'vyomastra' => [
            'name' => 'Vyomastra',
            'genre' => 'Space shooter',
            'levels' => 8,
            'level_names' => ['Low Orbit', 'Rock Belt', 'Ice Moon', 'Storm Giant', 'The Hive', 'Scrap Sea', 'Armada', 'The Core'],
            'page' => '/vyomastra/',
            'play' => '/vyomastra/play/',
            'image' => '/assets/vyomastra-480.webp',
        ],
    ];

    public const SOURCES = [
        'live' => ['label' => 'Live site', 'host' => 'https://games.codingcush.com', 'folder' => 'games', 'data' => 'games-data'],
        'staging' => ['label' => 'Staging', 'host' => 'https://staging-games.codingcush.com', 'folder' => 'staging-games', 'data' => 'staging-games-data'],
    ];

    /** Platforms the game reports with each visit ("web_guest", "app_member"...). */
    public const PLATFORMS = ['web' => 'Browser', 'app' => 'Installed app', 'win' => 'Windows', 'mac' => 'macOS', 'other' => 'Other'];

    /** Defaults for the settings the games read (see SETTING_DEFAULTS in games/api/lib.php). */
    public const SETTING_DEFAULTS = [
        'site' => ['notice' => '', 'notice_link' => '', 'notice_tone' => 'info', 'maintenance' => false, 'maintenance_message' => '', 'signups_open' => true, 'extra_admins' => []],
        'game.vyomastra' => ['status' => 'live', 'notice' => '', 'coin_boost' => 1.0, 'boost_until' => 0, 'continue_cost' => 2, 'continue_lives' => 3, 'scores_open' => true],
    ];

    public const GAME_STATUSES = ['live' => 'Live', 'beta' => 'Beta', 'maintenance' => 'Maintenance', 'hidden' => 'Hidden'];

    private static ?PDO $pdo = null;
    private static string $problem = '';
    private static ?array $columns = null;

    /* ------------------------------------------------------------------
     *  Connection
     * ----------------------------------------------------------------*/

    public static function source(): string
    {
        $chosen = (string) Session::get('games_source', 'live');
        return isset(self::SOURCES[$chosen]) ? $chosen : 'live';
    }

    public static function setSource(string $source): void
    {
        Session::set('games_source', isset(self::SOURCES[$source]) ? $source : 'live');
        self::$pdo = null;
        self::$columns = null;
    }

    public static function host(): string
    {
        return self::SOURCES[self::source()]['host'];
    }

    /** Folder that holds the SQLite file, its backups and the leaderboard cache. */
    public static function folder(): string
    {
        return dirname(BASE_PATH) . '/' . self::SOURCES[self::source()]['data'];
    }

    private static function gamesConfig(): array
    {
        $file = BASE_PATH . '/' . self::SOURCES[self::source()]['folder'] . '/api/config.php';
        if (!is_file($file)) {
            return [];
        }
        try {
            $config = (static fn(string $path) => require $path)($file);
            return is_array($config) ? $config : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function usesMysql(): bool
    {
        return isset(self::gamesConfig()['dsn']);
    }

    /** Why the database could not be opened, for the page to show. */
    public static function problem(): string
    {
        return self::$problem;
    }

    public static function db(): ?PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $config = self::gamesConfig();
            if (isset($config['dsn'])) {
                self::$pdo = new PDO((string) $config['dsn'], $config['user'] ?? null, $config['password'] ?? null, $options);
            } else {
                $file = self::folder() . '/games.sqlite';
                if (!is_file($file)) {
                    self::$problem = 'No games database yet at ' . self::SOURCES[self::source()]['data'] . '/games.sqlite. It is created the first time the games site is used.';
                    return null;
                }
                self::$pdo = new PDO('sqlite:' . $file, null, null, $options);
                self::$pdo->exec('PRAGMA busy_timeout = 4000');
            }
            self::$pdo->exec('CREATE TABLE IF NOT EXISTS games_settings (name VARCHAR(64) NOT NULL PRIMARY KEY, value TEXT NOT NULL, updated_at INTEGER NOT NULL)');
        } catch (\Throwable $e) {
            self::$pdo = null;
            self::$problem = 'Could not open the games database: ' . $e->getMessage();
        }
        return self::$pdo;
    }

    private static function isSqlite(): bool
    {
        return self::db() !== null && self::db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    /** Columns of games_users (some were added after launch and may be missing on an old database). */
    private static function userColumns(): array
    {
        if (self::$columns === null) {
            self::$columns = [];
            try {
                $row = self::db()?->query('SELECT * FROM games_users LIMIT 1')->fetch();
                if ($row) {
                    self::$columns = array_keys($row);
                } else {
                    $q = self::db()?->query('SELECT * FROM games_users LIMIT 0');
                    for ($i = 0; $q && $i < $q->columnCount(); $i++) {
                        self::$columns[] = (string) ($q->getColumnMeta($i)['name'] ?? '');
                    }
                }
            } catch (\Throwable $e) {
                self::$columns = [];
            }
        }
        return self::$columns;
    }

    private static function hasColumn(string $name): bool
    {
        return in_array($name, self::userColumns(), true);
    }

    private static function hasTable(string $name): bool
    {
        try {
            self::db()?->query("SELECT 1 FROM $name LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function one(string $sql, array $args = []): int
    {
        try {
            $q = self::db()?->prepare($sql);
            if (!$q) {
                return 0;
            }
            $q->execute($args);
            return (int) $q->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private static function rows(string $sql, array $args = []): array
    {
        try {
            $q = self::db()?->prepare($sql);
            if (!$q) {
                return [];
            }
            $q->execute($args);
            return $q->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* ------------------------------------------------------------------
     *  Dates: the games count by UTC day (Y-m-d)
     * ----------------------------------------------------------------*/

    public static function since(int $days): string
    {
        return gmdate('Y-m-d', time() - ($days - 1) * 86400);
    }

    /** Last $days UTC days, oldest first, as Y-m-d. */
    public static function dayList(int $days, int $offset = 0): array
    {
        $out = [];
        for ($i = $days - 1 + $offset; $i >= $offset; $i--) {
            $out[] = gmdate('Y-m-d', time() - $i * 86400);
        }
        return $out;
    }

    /* ------------------------------------------------------------------
     *  Counters
     * ----------------------------------------------------------------*/

    /** Event rows between two days (inclusive): name, level, detail, day, total. */
    private static function events(string $from, string $to, ?string $game = null): array
    {
        if (!self::hasTable('games_events')) {
            return [];
        }
        $sql = 'SELECT day, game, name, level, detail, total FROM games_events WHERE day >= ? AND day <= ?';
        $args = [$from, $to];
        if ($game !== null) {
            $sql .= ' AND game = ?';
            $args[] = $game;
        }
        return self::rows($sql, $args);
    }

    private static function sum(array $rows, string $name, ?callable $where = null): int
    {
        $total = 0;
        foreach ($rows as $r) {
            if ($r['name'] === $name && ($where === null || $where($r))) {
                $total += (int) $r['total'];
            }
        }
        return $total;
    }

    /** Headline numbers for a period ending today, plus the same for the period before it. */
    public static function summary(int $days): array
    {
        $now = self::period(self::since($days), gmdate('Y-m-d'));
        $before = self::period(gmdate('Y-m-d', time() - (2 * $days - 1) * 86400), gmdate('Y-m-d', time() - $days * 86400));
        return [$now, $before];
    }

    private static function period(string $from, string $to): array
    {
        $rows = self::events($from, $to);
        $fromTs = strtotime($from . ' 00:00:00 UTC');
        $toTs = strtotime($to . ' 23:59:59 UTC');
        $guest = static fn(array $r): bool => str_ends_with((string) $r['detail'], '_guest');
        $member = static fn(array $r): bool => str_ends_with((string) $r['detail'], '_member');
        $newLogin = static fn(array $r): bool => str_ends_with((string) $r['detail'], '_new');
        $starts = self::sum($rows, 'level_start');
        $wins = self::sum($rows, 'level_complete');
        return [
            'sessions' => self::sum($rows, 'session'),
            'guest_sessions' => self::sum($rows, 'session', $guest),
            'member_sessions' => self::sum($rows, 'session', $member),
            'app_sessions' => self::sum($rows, 'session', static fn(array $r): bool => str_starts_with((string) $r['detail'], 'app_')),
            'starts' => $starts,
            'wins' => $wins,
            'fails' => self::sum($rows, 'level_fail'),
            'win_rate' => $starts > 0 ? round($wins / $starts * 100, 1) : 0.0,
            'continues' => self::sum($rows, 'continue_used'),
            'logins' => self::sum($rows, 'login', static fn(array $r): bool => !str_ends_with((string) $r['detail'], '_new')),
            'signups' => self::one('SELECT COUNT(*) FROM games_users WHERE created_at >= ? AND created_at <= ?', [$fromTs, $toTs]),
            'active' => self::hasColumn('last_seen') ? self::one('SELECT COUNT(*) FROM games_users WHERE last_seen >= ?', [$fromTs]) : 0,
            'installs' => self::sum($rows, 'install'),
            'page_views' => self::sum($rows, 'page_view'),
            'play_clicks' => self::sum($rows, 'play_click'),
            'downloads' => self::sum($rows, 'download'),
            'errors' => self::hasTable('games_errors') ? self::one('SELECT COALESCE(SUM(total), 0) FROM games_errors WHERE day >= ? AND day <= ?', [$from, $to]) : 0,
            'new_logins' => self::sum($rows, 'login', $newLogin),
        ];
    }

    /** Totals that do not depend on the date range. */
    public static function allTime(): array
    {
        return [
            'players' => self::one('SELECT COUNT(*) FROM games_users'),
            'banned' => self::hasColumn('banned') ? self::one('SELECT COUNT(*) FROM games_users WHERE banned = 1') : 0,
            'with_photo' => self::hasColumn('photo') ? self::one("SELECT COUNT(*) FROM games_users WHERE photo IS NOT NULL AND photo <> ''") : 0,
            'saves' => self::one('SELECT COUNT(*) FROM games_saves'),
            'scores' => self::one('SELECT COUNT(*) FROM games_scores'),
            'sessions' => self::hasTable('games_events') ? self::one("SELECT COALESCE(SUM(total), 0) FROM games_events WHERE name = 'session'") : 0,
            'starts' => self::hasTable('games_events') ? self::one("SELECT COALESCE(SUM(total), 0) FROM games_events WHERE name = 'level_start'") : 0,
            'first_day' => (string) (self::rows('SELECT MIN(day) AS d FROM games_events')[0]['d'] ?? ''),
        ];
    }

    /** One row per day: label, sessions, plays, wins, signups, views, logins. */
    public static function daily(int $days): array
    {
        $list = self::dayList($days);
        $out = [];
        foreach ($list as $day) {
            $out[$day] = ['label' => date('j M', strtotime($day)), 'day' => $day, 'sessions' => 0, 'guest' => 0, 'member' => 0, 'plays' => 0, 'wins' => 0, 'signups' => 0, 'views' => 0, 'logins' => 0, 'installs' => 0, 'downloads' => 0];
        }
        foreach (self::events($list[0], end($list)) as $r) {
            $d = $r['day'];
            if (!isset($out[$d])) {
                continue;
            }
            $t = (int) $r['total'];
            switch ($r['name']) {
                case 'session':
                    $out[$d]['sessions'] += $t;
                    if (str_ends_with((string) $r['detail'], '_guest')) { $out[$d]['guest'] += $t; }
                    if (str_ends_with((string) $r['detail'], '_member')) { $out[$d]['member'] += $t; }
                    break;
                case 'level_start': $out[$d]['plays'] += $t; break;
                case 'level_complete': $out[$d]['wins'] += $t; break;
                case 'page_view': $out[$d]['views'] += $t; break;
                case 'login': $out[$d]['logins'] += $t; break;
                case 'install': $out[$d]['installs'] += $t; break;
                case 'download': $out[$d]['downloads'] += $t; break;
            }
        }
        $fromTs = strtotime($list[0] . ' 00:00:00 UTC');
        foreach (self::rows('SELECT created_at FROM games_users WHERE created_at >= ?', [$fromTs]) as $u) {
            $d = gmdate('Y-m-d', (int) $u['created_at']);
            if (isset($out[$d])) {
                $out[$d]['signups']++;
            }
        }
        return array_values($out);
    }

    /** Sessions by platform and by guest / signed in. */
    public static function platforms(int $days): array
    {
        $byPlatform = [];
        $byPlayer = ['guest' => 0, 'member' => 0, 'unknown' => 0];
        foreach (self::events(self::since($days), gmdate('Y-m-d')) as $r) {
            if ($r['name'] !== 'session') {
                continue;
            }
            $t = (int) $r['total'];
            $parts = explode('_', (string) $r['detail']);
            $platform = isset(self::PLATFORMS[$parts[0]]) ? $parts[0] : 'unknown';
            $byPlatform[$platform] = ($byPlatform[$platform] ?? 0) + $t;
            $who = $parts[1] ?? '';
            $byPlayer[isset($byPlayer[$who]) ? $who : 'unknown'] += $t;
        }
        arsort($byPlatform);
        return ['platform' => $byPlatform, 'player' => $byPlayer];
    }

    /** Sign-ins by method; new accounts by method (from the accounts themselves). */
    public static function logins(int $days): array
    {
        $methods = [];
        foreach (self::events(self::since($days), gmdate('Y-m-d')) as $r) {
            if ($r['name'] === 'login') {
                $key = (string) $r['detail'];
                $methods[$key] = ($methods[$key] ?? 0) + (int) $r['total'];
            }
        }
        $signups = [];
        if (self::hasColumn('signup_via')) {
            foreach (self::rows("SELECT signup_via AS via, COUNT(*) AS n FROM games_users WHERE created_at >= ? GROUP BY signup_via", [strtotime(self::since($days) . ' 00:00:00 UTC')]) as $r) {
                $signups[$r['via'] !== '' ? $r['via'] : 'before tracking'] = (int) $r['n'];
            }
        }
        arsort($methods);
        arsort($signups);
        return ['logins' => $methods, 'signups' => $signups];
    }

    /** Website (games.codingcush.com) page views and Play clicks by page. */
    public static function pages(int $days): array
    {
        $views = [];
        $clicks = [];
        $installs = [];
        $downloads = [];
        foreach (self::events(self::since($days), gmdate('Y-m-d'), 'hub') as $r) {
            if ($r['name'] === 'download') { $downloads[(string) $r['detail']] = ($downloads[(string) $r['detail']] ?? 0) + (int) $r['total']; }
            $page = (string) $r['detail'];
            if ($r['name'] === 'page_view') { $views[$page] = ($views[$page] ?? 0) + (int) $r['total']; }
            if ($r['name'] === 'play_click') { $clicks[$page] = ($clicks[$page] ?? 0) + (int) $r['total']; }
            if ($r['name'] === 'install') { $installs[$page] = ($installs[$page] ?? 0) + (int) $r['total']; }
        }
        arsort($views);
        arsort($downloads);
        return ['views' => $views, 'clicks' => $clicks, 'installs' => $installs, 'downloads' => $downloads];
    }

    /** Per mission: starts, wins, losses, win rate, stars and what ended failed runs. */
    public static function levels(string $game, int $days): array
    {
        $info = self::GAMES[$game];
        $out = [];
        for ($l = 1; $l <= $info['levels']; $l++) {
            $out[$l] = ['level' => $l, 'name' => $info['level_names'][$l - 1] ?? ('Mission ' . $l), 'starts' => 0, 'wins' => 0, 'fails' => 0, 'continues' => 0, 'stars' => [1 => 0, 2 => 0, 3 => 0], 'causes' => [], 'boss_fails' => 0];
        }
        foreach (self::events(self::since($days), gmdate('Y-m-d'), $game) as $r) {
            $l = (int) $r['level'];
            if (!isset($out[$l])) {
                continue;
            }
            $t = (int) $r['total'];
            $detail = (string) $r['detail'];
            switch ($r['name']) {
                case 'level_start': $out[$l]['starts'] += $t; break;
                case 'continue_used': $out[$l]['continues'] += $t; break;
                case 'level_complete':
                    $out[$l]['wins'] += $t;
                    if (preg_match('/^stars([123])$/', $detail, $m)) { $out[$l]['stars'][(int) $m[1]] += $t; }
                    break;
                case 'level_fail':
                    $out[$l]['fails'] += $t;
                    $cause = explode('_', $detail)[0] ?: 'unknown';
                    $out[$l]['causes'][$cause] = ($out[$l]['causes'][$cause] ?? 0) + $t;
                    if (str_ends_with($detail, '_boss')) { $out[$l]['boss_fails'] += $t; }
                    break;
            }
        }
        foreach ($out as &$row) {
            $row['win_rate'] = $row['starts'] > 0 ? round($row['wins'] / $row['starts'] * 100, 1) : 0.0;
            arsort($row['causes']);
        }
        return array_values($out);
    }

    /** Raw daily counters for the CSV export. */
    public static function exportEvents(int $days): array
    {
        return self::events(self::since($days), gmdate('Y-m-d'));
    }

    /* ------------------------------------------------------------------
     *  Players
     * ----------------------------------------------------------------*/

    public const PLAYER_SORTS = ['newest' => 'created_at DESC', 'oldest' => 'created_at ASC', 'active' => 'last_seen DESC', 'name' => 'username ASC'];

    /** One page of players with their score totals. */
    public static function players(string $search, string $sort, int $page, int $perPage = 40, string $filter = ''): array
    {
        $where = [];
        $args = [];
        if ($search !== '') {
            $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.name LIKE ?)';
            $like = '%' . $search . '%';
            array_push($args, $like, $like, $like);
        }
        if ($filter === 'banned' && self::hasColumn('banned')) {
            $where[] = 'u.banned = 1';
        } elseif (in_array($filter, ['google', 'facebook', 'email'], true) && self::hasColumn('signup_via')) {
            $where[] = 'u.signup_via = ?';
            $args[] = $filter;
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $order = self::PLAYER_SORTS[$sort] ?? self::PLAYER_SORTS['newest'];
        if (!self::hasColumn('last_seen') && $sort === 'active') {
            $order = self::PLAYER_SORTS['newest'];
        }
        $total = self::one('SELECT COUNT(*) FROM games_users u' . $whereSql, $args);
        $cols = 'u.id, u.email, u.name, u.username, u.created_at'
            . (self::hasColumn('banned') ? ', u.banned' : ', 0 AS banned')
            . (self::hasColumn('signup_via') ? ', u.signup_via' : ", '' AS signup_via")
            . (self::hasColumn('last_seen') ? ', u.last_seen' : ', 0 AS last_seen')
            . (self::hasColumn('photo') ? ", CASE WHEN u.photo IS NOT NULL AND u.photo <> '' THEN 1 ELSE 0 END AS has_photo" : ', 0 AS has_photo');
        $offset = max(0, ($page - 1) * $perPage);
        $list = self::rows("SELECT $cols, (SELECT COUNT(*) FROM games_scores s WHERE s.user_id = u.id) AS levels_scored, (SELECT COALESCE(SUM(s.score), 0) FROM games_scores s WHERE s.user_id = u.id) AS score_total, (SELECT MAX(updated_at) FROM games_saves v WHERE v.user_id = u.id) AS saved_at FROM games_users u$whereSql ORDER BY $order LIMIT $perPage OFFSET $offset", $args);
        return ['total' => $total, 'rows' => $list];
    }

    public static function player(int $id): ?array
    {
        $rows = self::rows('SELECT * FROM games_users WHERE id = ?', [$id]);
        if (!$rows) {
            return null;
        }
        $p = $rows[0];
        unset($p['pass_hash']);
        $p['scores'] = self::rows('SELECT game, level, name, ship, score, updated_at FROM games_scores WHERE user_id = ? ORDER BY game, level', [$id]);
        $p['saves'] = [];
        foreach (self::rows('SELECT game, data, updated_at FROM games_saves WHERE user_id = ?', [$id]) as $s) {
            $data = json_decode((string) $s['data'], true);
            $p['saves'][$s['game']] = ['updated_at' => (int) $s['updated_at'], 'bytes' => strlen((string) $s['data']), 'data' => is_array($data) ? $data : []];
        }
        $p['sessions'] = self::one('SELECT COUNT(*) FROM games_sessions WHERE user_id = ? AND expires_at > ?', [$id, time()]);
        foreach (self::GAMES as $game => $info) {
            foreach ($p['scores'] as &$score) {
                if ($score['game'] === $game) {
                    $score['rank'] = 1 + self::one('SELECT COUNT(*) FROM games_scores WHERE game = ? AND level = ? AND score > ?', [$game, (int) $score['level'], (int) $score['score']]);
                }
            }
            unset($score);
        }
        return $p;
    }

    /** CSV rows of every player (no password data). */
    public static function exportPlayers(): array
    {
        $cols = 'id, username, name, email, created_at'
            . (self::hasColumn('signup_via') ? ', signup_via' : '')
            . (self::hasColumn('last_seen') ? ', last_seen' : '')
            . (self::hasColumn('banned') ? ', banned' : '');
        return self::rows("SELECT $cols FROM games_users ORDER BY id");
    }

    public static function setBanned(int $id, bool $banned): void
    {
        self::db()?->prepare('UPDATE games_users SET banned = ? WHERE id = ?')->execute([$banned ? 1 : 0, $id]);
        if ($banned) {
            self::db()?->prepare('DELETE FROM games_scores WHERE user_id = ?')->execute([$id]);
            self::db()?->prepare('UPDATE games_users SET photo = NULL WHERE id = ?')->execute([$id]);
        }
        self::forgetBoards();
    }

    public static function clearPhoto(int $id): void
    {
        self::db()?->prepare('UPDATE games_users SET photo = NULL WHERE id = ?')->execute([$id]);
        self::forgetBoards();
    }

    /** Removes scores: one level of one game, or all of a player's scores (level 0). Returns rows removed. */
    public static function deleteScores(int $userId, string $game, int $level): int
    {
        if ($level > 0) {
            $q = self::db()?->prepare('DELETE FROM games_scores WHERE user_id = ? AND game = ? AND level = ?');
            $q?->execute([$userId, $game, $level]);
        } else {
            $q = self::db()?->prepare('DELETE FROM games_scores WHERE user_id = ?');
            $q?->execute([$userId]);
        }
        self::forgetBoards();
        return $q ? $q->rowCount() : 0;
    }

    /** Deletes an account with its save, scores and sign-ins, the same way the in-game "delete account" does. */
    public static function deletePlayer(int $id): void
    {
        foreach (['games_sessions', 'games_saves', 'games_scores', 'games_runs'] as $table) {
            if (self::hasTable($table)) {
                self::db()?->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$id]);
            }
        }
        self::db()?->prepare('DELETE FROM games_users WHERE id = ?')->execute([$id]);
        self::forgetBoards();
    }

    /** Signs a player out everywhere. */
    public static function endSessions(int $id): int
    {
        $q = self::db()?->prepare('DELETE FROM games_sessions WHERE user_id = ?');
        $q?->execute([$id]);
        return $q ? $q->rowCount() : 0;
    }

    public static function usernameExists(string $username): bool
    {
        return self::one('SELECT COUNT(*) FROM games_users WHERE username = ?', [$username]) > 0;
    }

    public static function isAdminUsername(string $username): bool
    {
        $extra = self::settings()['site']['extra_admins'] ?? [];
        return in_array($username, array_merge(['kushkrg'], is_array($extra) ? $extra : []), true);
    }

    /* ------------------------------------------------------------------
     *  Leaderboards
     * ----------------------------------------------------------------*/

    public static function board(string $game, int $level, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $photo = self::hasColumn('photo') ? ", CASE WHEN u.photo IS NOT NULL AND u.photo <> '' THEN 1 ELSE 0 END AS has_photo" : ', 0 AS has_photo';
        return self::rows("SELECT s.user_id, s.name, s.ship, s.score, s.updated_at, u.username$photo FROM games_scores s LEFT JOIN games_users u ON u.id = s.user_id WHERE s.game = ? AND s.level = ? ORDER BY s.score DESC, s.updated_at ASC LIMIT $limit", [$game, $level]);
    }

    public static function boardSizes(string $game): array
    {
        $out = [];
        foreach (self::rows('SELECT level, COUNT(*) AS n, MAX(score) AS top FROM games_scores WHERE game = ? GROUP BY level', [$game]) as $r) {
            $out[(int) $r['level']] = ['n' => (int) $r['n'], 'top' => (int) $r['top']];
        }
        return $out;
    }

    /** Thumbnail of a player's photo as a data: URL, or ''. */
    public static function photo(int $id): string
    {
        if (!self::hasColumn('photo')) {
            return '';
        }
        $rows = self::rows('SELECT photo FROM games_users WHERE id = ?', [$id]);
        $photo = (string) ($rows[0]['photo'] ?? '');
        return $photo !== '' ? 'data:image/jpeg;base64,' . $photo : '';
    }

    /** The games site keeps finished leaderboard answers for 30 seconds; drop them after a change. */
    public static function forgetBoards(): void
    {
        foreach (glob(self::folder() . '/cache/board-*.json') ?: [] as $file) {
            @unlink($file);
        }
    }

    /* ------------------------------------------------------------------
     *  Errors and backups
     * ----------------------------------------------------------------*/

    public static function errors(int $days): array
    {
        if (!self::hasTable('games_errors')) {
            return [];
        }
        return self::rows('SELECT day, message, place, version, total, last_at FROM games_errors WHERE day >= ? ORDER BY last_at DESC LIMIT 300', [self::since($days)]);
    }

    public static function backups(): array
    {
        $out = [];
        foreach (glob(self::folder() . '/backups/games-*.sqlite') ?: [] as $file) {
            $out[] = ['name' => basename($file), 'bytes' => (int) filesize($file), 'made_at' => (int) filemtime($file)];
        }
        usort($out, static fn(array $a, array $b): int => strcmp($b['name'], $a['name']));
        return $out;
    }

    /** Full path of a backup file by its name, or null when the name is not one of ours. */
    public static function backupPath(string $name): ?string
    {
        if (!preg_match('/^games-\d{4}-\d{2}-\d{2}(-\d{6})?\.sqlite$/', $name)) {
            return null;
        }
        $path = self::folder() . '/backups/' . $name;
        return is_file($path) ? $path : null;
    }

    /** Copies the SQLite database now. Returns the new file's name. */
    public static function backupNow(): string
    {
        if (!self::isSqlite()) {
            throw new \RuntimeException('The games use MySQL: Hostinger backs that database up (hPanel > Files > Backups).');
        }
        $dir = self::folder() . '/backups';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true)) {
            throw new \RuntimeException('Could not create the backups folder.');
        }
        if (!is_file($dir . '/.htaccess')) {
            @file_put_contents($dir . '/.htaccess', "Require all denied\n");
        }
        $name = 'games-' . gmdate('Y-m-d-His') . '.sqlite';
        self::db()->exec('VACUUM INTO ' . self::db()->quote($dir . '/' . $name));
        return $name;
    }

    public static function databaseSize(): int
    {
        $file = self::folder() . '/games.sqlite';
        return self::isSqlite() && is_file($file) ? (int) filesize($file) : 0;
    }

    /* ------------------------------------------------------------------
     *  Settings the games read (games_settings, JSON by name)
     * ----------------------------------------------------------------*/

    public static function settings(): array
    {
        $out = self::SETTING_DEFAULTS;
        foreach (self::rows('SELECT name, value, updated_at FROM games_settings') as $row) {
            $value = json_decode((string) $row['value'], true);
            if (is_array($value) && isset($out[$row['name']])) {
                $out[$row['name']] = array_merge($out[$row['name']], $value);
                $out[$row['name']]['_updated_at'] = (int) $row['updated_at'];
            }
        }
        return $out;
    }

    public static function saveSetting(string $name, array $value): void
    {
        if (!isset(self::SETTING_DEFAULTS[$name])) {
            throw new \InvalidArgumentException('Unknown setting.');
        }
        $clean = array_intersect_key($value, self::SETTING_DEFAULTS[$name]);
        $json = json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $db = self::db();
        if ($db === null) {
            throw new \RuntimeException(self::problem());
        }
        $update = $db->prepare('UPDATE games_settings SET value = ?, updated_at = ? WHERE name = ?');
        $update->execute([$json, time(), $name]);
        if ($update->rowCount() === 0) {
            try {
                $db->prepare('INSERT INTO games_settings (name, value, updated_at) VALUES (?, ?, ?)')->execute([$name, $json, time()]);
            } catch (PDOException $exists) {
                $update->execute([$json, time(), $name]);   // same value saved twice: nothing changed
            }
        }
    }
}
