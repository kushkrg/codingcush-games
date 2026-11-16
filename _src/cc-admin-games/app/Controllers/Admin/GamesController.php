<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\GamesData;

/**
 * Admin → Games: everything about CodingCush Games (games.codingcush.com) in one place:
 * overview, the games, analytics, players, leaderboards, website and game settings,
 * error reports and database backups.
 */
class GamesController
{
    private const RANGES = [1 => 'Today', 7 => 'Last 7 days', 30 => 'Last 30 days', 90 => 'Last 90 days', 365 => 'Last 12 months'];

    public function middleware(): void
    {
        Auth::requirePermission('games.manage');
    }

    private function days(Request $request): int
    {
        $days = (int) $request->get('days', 30);
        return isset(self::RANGES[$days]) ? $days : 30;
    }

    private function render(string $template, string $title, string $current, array $data = []): void
    {
        $view = new View();
        $view->setLayout('layouts/admin');
        echo $view->render('admin/games/' . $template, array_merge([
            'pageTitle'   => $title,
            'currentPage' => $current,
            'ranges'      => self::RANGES,
            'source'      => GamesData::source(),
            'connected'   => GamesData::db() !== null,
            'problem'     => GamesData::problem(),
            'host'        => GamesData::host(),
        ], $data));
    }

    private function game(Request $request): string
    {
        $game = (string) $request->get('game', 'vyomastra');
        return isset(GamesData::GAMES[$game]) ? $game : 'vyomastra';
    }

    /* ------------------------------------------------------------------
     *  GET /admin/games — overview
     * ----------------------------------------------------------------*/
    public function index(Request $request, array $params): void
    {
        $days = $this->days($request);
        [$now, $before] = GamesData::db() ? GamesData::summary($days) : [[], []];
        $this->render('overview', 'Games — Overview', 'games-overview', [
            'days'      => $days,
            'now'       => $now,
            'before'    => $before,
            'allTime'   => GamesData::db() ? GamesData::allTime() : [],
            'daily'     => GamesData::db() ? GamesData::daily(max($days, 7)) : [],
            'platforms' => GamesData::db() ? GamesData::platforms($days) : ['platform' => [], 'player' => []],
            'levels'    => GamesData::db() ? GamesData::levels('vyomastra', $days) : [],
            'settings'  => GamesData::db() ? GamesData::settings() : GamesData::SETTING_DEFAULTS,
        ]);
    }

    /* ------------------------------------------------------------------
     *  GET /admin/games/list — all games
     * ----------------------------------------------------------------*/
    public function list(Request $request, array $params): void
    {
        $stats = [];
        if (GamesData::db()) {
            foreach (GamesData::GAMES as $id => $info) {
                $stats[$id] = ['levels' => GamesData::levels($id, 30), 'boards' => GamesData::boardSizes($id)];
            }
        }
        $this->render('list', 'All Games', 'games-list', [
            'stats'    => $stats,
            'allTime'  => GamesData::db() ? GamesData::allTime() : [],
            'settings' => GamesData::db() ? GamesData::settings() : GamesData::SETTING_DEFAULTS,
        ]);
    }

    /* ------------------------------------------------------------------
     *  GET /admin/games/analytics
     * ----------------------------------------------------------------*/
    public function analytics(Request $request, array $params): void
    {
        $days = $this->days($request);
        $game = $this->game($request);
        $connected = GamesData::db() !== null;
        [$now, $before] = $connected ? GamesData::summary($days) : [[], []];
        $this->render('analytics', 'Games — Analytics', 'games-analytics', [
            'days'      => $days,
            'game'      => $game,
            'now'       => $now,
            'before'    => $before,
            'daily'     => $connected ? GamesData::daily(max($days, 7)) : [],
            'platforms' => $connected ? GamesData::platforms($days) : ['platform' => [], 'player' => []],
            'logins'    => $connected ? GamesData::logins($days) : ['logins' => [], 'signups' => []],
            'pages'     => $connected ? GamesData::pages($days) : ['views' => [], 'clicks' => [], 'installs' => []],
            'levels'    => $connected ? GamesData::levels($game, $days) : [],
            'allTime'   => $connected ? GamesData::allTime() : [],
        ]);
    }

    /** GET /admin/games/analytics/export — the daily counters as CSV. */
    public function exportAnalytics(Request $request, array $params): void
    {
        $days = $this->days($request);
        $this->csv('games-analytics-' . gmdate('Y-m-d') . '.csv', ['day', 'game', 'counter', 'mission', 'detail', 'total'],
            array_map(static fn(array $r): array => [$r['day'], $r['game'], $r['name'], $r['level'], $r['detail'], $r['total']], GamesData::exportEvents($days)));
    }

    /* ------------------------------------------------------------------
     *  Players
     * ----------------------------------------------------------------*/
    public function players(Request $request, array $params): void
    {
        $search = trim((string) $request->get('q', ''));
        $sort = (string) $request->get('sort', 'newest');
        $filter = (string) $request->get('filter', '');
        $page = max(1, (int) $request->get('page', 1));
        $found = GamesData::db() ? GamesData::players($search, $sort, $page, 40, $filter) : ['total' => 0, 'rows' => []];
        $this->render('players', 'Games — Players', 'games-players', [
            'search' => $search,
            'sort'   => isset(GamesData::PLAYER_SORTS[$sort]) ? $sort : 'newest',
            'filter' => $filter,
            'page'   => $page,
            'found'  => $found,
            'allTime' => GamesData::db() ? GamesData::allTime() : [],
        ]);
    }

    public function player(Request $request, array $params): void
    {
        $player = GamesData::db() ? GamesData::player((int) ($params['id'] ?? 0)) : null;
        if (!$player) {
            Session::flash('error', 'That player was not found.');
            Response::redirect(url('admin/games/players'));
            return;
        }
        $this->render('player', '@' . ($player['username'] ?: 'player') . ' — Player', 'games-players', [
            'p'     => $player,
            'photo' => GamesData::photo((int) $player['id']),
        ]);
    }

    /** POST /admin/games/players/{id}/action — ban, unban, clear photo, remove scores, sign out, delete. */
    public function playerAction(Request $request, array $params): void
    {
        Csrf::check();
        $id = (int) ($params['id'] ?? 0);
        $player = GamesData::db() ? GamesData::player($id) : null;
        if (!$player) {
            Session::flash('error', 'That player was not found.');
            Response::redirect(url('admin/games/players'));
            return;
        }
        $back = url('admin/games/players/' . $id);
        $isAdmin = GamesData::isAdminUsername((string) $player['username']);
        try {
            switch ((string) $request->post('do', '')) {
                case 'ban':
                    if ($isAdmin) {
                        throw new \RuntimeException('An admin account cannot be banned.');
                    }
                    GamesData::setBanned($id, true);
                    Session::flash('success', '@' . $player['username'] . ' is banned: scores and photo removed, no new scores or photo.');
                    break;
                case 'unban':
                    GamesData::setBanned($id, false);
                    Session::flash('success', '@' . $player['username'] . ' can post scores again.');
                    break;
                case 'photo':
                    GamesData::clearPhoto($id);
                    Session::flash('success', 'Photo removed from the leaderboard.');
                    break;
                case 'scores':
                    $removed = GamesData::deleteScores($id, (string) $request->post('game', 'vyomastra'), (int) $request->post('level', 0));
                    Session::flash('success', $removed . ' score(s) removed.');
                    break;
                case 'signout':
                    Session::flash('success', GamesData::endSessions($id) . ' sign-in(s) ended. The player has to sign in again.');
                    break;
                case 'delete':
                    if ($isAdmin) {
                        throw new \RuntimeException('An admin account cannot be deleted here.');
                    }
                    if (strtolower(trim((string) $request->post('confirm', ''))) !== strtolower((string) $player['username'])) {
                        throw new \RuntimeException('Type the username to confirm deleting the account.');
                    }
                    GamesData::deletePlayer($id);
                    Session::flash('success', 'Account @' . $player['username'] . ' and its save and scores were deleted.');
                    $back = url('admin/games/players');
                    break;
                default:
                    throw new \RuntimeException('Unknown action.');
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect($back);
    }

    public function exportPlayers(Request $request, array $params): void
    {
        $rows = GamesData::exportPlayers();
        $head = $rows ? array_keys($rows[0]) : ['id', 'username', 'name', 'email', 'created_at'];
        $this->csv('games-players-' . gmdate('Y-m-d') . '.csv', $head, array_map(static function (array $r): array {
            foreach (['created_at', 'last_seen'] as $k) {
                if (isset($r[$k])) {
                    $r[$k] = (int) $r[$k] > 0 ? gmdate('Y-m-d H:i', (int) $r[$k]) : '';
                }
            }
            return array_values($r);
        }, $rows));
    }

    /* ------------------------------------------------------------------
     *  Leaderboards
     * ----------------------------------------------------------------*/
    public function leaderboards(Request $request, array $params): void
    {
        $game = $this->game($request);
        $level = max(1, min(GamesData::GAMES[$game]['levels'], (int) $request->get('level', 1)));
        $this->render('leaderboards', 'Games — Leaderboards', 'games-leaderboards', [
            'game'  => $game,
            'level' => $level,
            'board' => GamesData::db() ? GamesData::board($game, $level, 50) : [],
            'sizes' => GamesData::db() ? GamesData::boardSizes($game) : [],
        ]);
    }

    /** POST /admin/games/leaderboards/remove — one score off a board. */
    public function removeScore(Request $request, array $params): void
    {
        Csrf::check();
        $game = (string) $request->post('game', 'vyomastra');
        $level = (int) $request->post('level', 1);
        if (isset(GamesData::GAMES[$game]) && $level > 0) {
            $removed = GamesData::deleteScores((int) $request->post('user', 0), $game, $level);
            Session::flash('success', $removed ? 'Score removed from the board.' : 'Nothing was removed.');
        }
        Response::redirect(url('admin/games/leaderboards?game=' . rawurlencode($game) . '&level=' . $level));
    }

    /* ------------------------------------------------------------------
     *  Website settings (games.codingcush.com)
     * ----------------------------------------------------------------*/
    public function pages(Request $request, array $params): void
    {
        $this->render('pages', 'Games — Page Settings', 'games-pages', [
            'settings' => GamesData::db() ? GamesData::settings() : GamesData::SETTING_DEFAULTS,
            'traffic'  => GamesData::db() ? GamesData::pages(30) : ['views' => [], 'clicks' => [], 'installs' => []],
        ]);
    }

    public function savePages(Request $request, array $params): void
    {
        Csrf::check();
        $current = GamesData::settings()['site'];
        $admins = [];
        $ignored = [];
        foreach (preg_split('/[\s,]+/', strtolower((string) $request->post('extra_admins', ''))) ?: [] as $name) {
            $name = ltrim(trim($name), '@');
            if ($name === '' || $name === 'kushkrg') {
                continue;
            }
            if (preg_match('/^[a-z][a-z0-9_]{2,15}$/', $name) && GamesData::usernameExists($name)) {
                $admins[] = $name;
            } else {
                $ignored[] = $name;
            }
        }
        $link = trim((string) $request->post('notice_link', ''));
        if ($link !== '' && !preg_match('#^(https?://|/)[^\s<>"]*$#', $link)) {
            Session::flash('error', 'The notice link must start with https:// or /.');
            Response::redirect(url('admin/games/pages'));
            return;
        }
        try {
            GamesData::saveSetting('site', [
                'notice'              => mb_substr(trim((string) $request->post('notice', '')), 0, 180),
                'notice_link'         => mb_substr($link, 0, 300),
                'notice_tone'         => in_array($request->post('notice_tone'), ['info', 'good', 'warn'], true) ? (string) $request->post('notice_tone') : 'info',
                'maintenance'         => $request->post('maintenance') === '1',
                'maintenance_message' => mb_substr(trim((string) $request->post('maintenance_message', '')), 0, 240),
                'signups_open'        => $request->post('signups_open') === '1',
                'extra_admins'        => array_values(array_unique($admins)),
            ] + $current);
            Session::flash('success', 'Saved. The games site shows the change within a minute.'
                . ($ignored ? ' Not added as admins (no such player): ' . implode(', ', $ignored) . '.' : ''));
        } catch (\Throwable $e) {
            Session::flash('error', 'Could not save: ' . $e->getMessage());
        }
        Response::redirect(url('admin/games/pages'));
    }

    /* ------------------------------------------------------------------
     *  Game settings (live tuning, read by the game when it starts)
     * ----------------------------------------------------------------*/
    public function settings(Request $request, array $params): void
    {
        $game = $this->game($request);
        $this->render('settings', 'Games — Game Settings', 'games-settings', [
            'game'     => $game,
            'settings' => GamesData::db() ? GamesData::settings() : GamesData::SETTING_DEFAULTS,
        ]);
    }

    public function saveSettings(Request $request, array $params): void
    {
        Csrf::check();
        $game = (string) $request->post('game', 'vyomastra');
        if (!isset(GamesData::GAMES[$game])) {
            Response::redirect(url('admin/games/settings'));
            return;
        }
        $until = 0;
        $untilText = trim((string) $request->post('boost_until', ''));
        if ($untilText !== '') {
            // The form gives local time (India); store as a Unix time.
            $parsed = \DateTime::createFromFormat('Y-m-d\TH:i', $untilText, new \DateTimeZone('Asia/Kolkata'));
            $until = $parsed ? $parsed->getTimestamp() : 0;
        }
        $status = (string) $request->post('status', 'live');
        try {
            GamesData::saveSetting('game.' . $game, [
                'status'         => isset(GamesData::GAME_STATUSES[$status]) ? $status : 'live',
                'notice'         => mb_substr(trim((string) $request->post('notice', '')), 0, 140),
                'coin_boost'     => max(1.0, min(5.0, round((float) $request->post('coin_boost', 1), 2))),
                'boost_until'    => $until,
                'continue_cost'  => max(1, min(50, (int) $request->post('continue_cost', 2))),
                'continue_lives' => max(1, min(9, (int) $request->post('continue_lives', 3))),
                'scores_open'    => $request->post('scores_open') === '1',
            ]);
            Session::flash('success', 'Saved. Players get the new settings the next time they open the game.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Could not save: ' . $e->getMessage());
        }
        Response::redirect(url('admin/games/settings?game=' . rawurlencode($game)));
    }

    /* ------------------------------------------------------------------
     *  Errors, backups, data source
     * ----------------------------------------------------------------*/
    public function errors(Request $request, array $params): void
    {
        $days = $this->days($request);
        $this->render('errors', 'Games — Error Reports', 'games-errors', [
            'days'   => $days,
            'errors' => GamesData::db() ? GamesData::errors($days) : [],
        ]);
    }

    public function backups(Request $request, array $params): void
    {
        $this->render('backups', 'Games — Data & Backups', 'games-backups', [
            'backups' => GamesData::backups(),
            'size'    => GamesData::db() ? GamesData::databaseSize() : 0,
            'mysql'   => GamesData::usesMysql(),
            'allTime' => GamesData::db() ? GamesData::allTime() : [],
        ]);
    }

    public function backupNow(Request $request, array $params): void
    {
        Csrf::check();
        try {
            Session::flash('success', 'Backup made: ' . GamesData::backupNow());
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
        }
        Response::redirect(url('admin/games/backups'));
    }

    public function downloadBackup(Request $request, array $params): void
    {
        $path = GamesData::backupPath((string) ($params['name'] ?? ''));
        if ($path === null) {
            Response::notFound();
            return;
        }
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store');
        readfile($path);
        exit;
    }

    /** POST /admin/games/source — look at the live site's data or the staging copy's. */
    public function switchSource(Request $request, array $params): void
    {
        Csrf::check();
        GamesData::setSource((string) $request->post('source', 'live'));
        $back = (string) $request->post('back', '');
        Response::redirect(str_starts_with($back, '/admin/games') ? url(ltrim($back, '/')) : url('admin/games'));
    }

    private function csv(string $filename, array $head, array $rows): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fputcsv($out, $head);
        foreach ($rows as $row) {
            // A leading = + - @ would make a spreadsheet run the cell as a formula.
            fputcsv($out, array_map(static fn($v): string => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : (string) $v, $row));
        }
        fclose($out);
        exit;
    }
}
