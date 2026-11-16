<?php
/**
 * Admin → Games → All games. Vars: $stats, $allTime, $settings, + _top vars
 */
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
include __DIR__ . '/_top.php';
?>
<div class="gm-games">
<?php foreach (GamesData::GAMES as $id => $g):
    $gs = $settings['game.' . $id] ?? [];
    $levels = $stats[$id]['levels'] ?? [];
    $boards = $stats[$id]['boards'] ?? [];
    $starts = array_sum(array_column($levels, 'starts'));
    $wins = array_sum(array_column($levels, 'wins'));
    $status = (string) ($gs['status'] ?? 'live');
?>
    <article class="gm-game">
        <img src="<?= h($host . $g['image']) ?>" alt="" width="480" height="270" loading="lazy">
        <div class="gm-game__body">
            <div class="gm-game__head">
                <h3><?= h($g['name']) ?></h3>
                <span class="pa-pill gm-pill--<?= h($status) ?>"><?= h(GamesData::GAME_STATUSES[$status] ?? $status) ?></span>
            </div>
            <p class="gm-muted"><?= h($g['genre']) ?> · <?= (int) $g['levels'] ?> missions · Godot 4 (web)</p>
            <dl class="gm-dl">
                <div><dt>Players (accounts)</dt><dd><?= number_format((int) ($allTime['players'] ?? 0)) ?></dd></div>
                <div><dt>Missions played (30 days)</dt><dd><?= number_format($starts) ?></dd></div>
                <div><dt>Win rate (30 days)</dt><dd><?= $starts ? round($wins / $starts * 100, 1) . '%' : '—' ?></dd></div>
                <div><dt>Leaderboard entries</dt><dd><?= number_format(array_sum(array_column($boards, 'n'))) ?></dd></div>
            </dl>
            <div class="gm-game__links">
                <a class="store-btn" href="<?= h($host . $g['play']) ?>" target="_blank" rel="noopener">▶ Play</a>
                <a class="store-btn store-btn-ghost" href="<?= h($host . $g['page']) ?>" target="_blank" rel="noopener">Game page ↗</a>
                <a class="store-btn store-btn-ghost" href="<?= url('admin/games/analytics?game=' . $id) ?>">Analytics</a>
                <a class="store-btn store-btn-ghost" href="<?= url('admin/games/leaderboards?game=' . $id) ?>">Leaderboards</a>
                <a class="store-btn store-btn-ghost" href="<?= url('admin/games/settings?game=' . $id) ?>">Game settings</a>
            </div>
        </div>
    </article>
<?php endforeach; ?>
</div>
<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Adding a game</h3></div>
    <p class="pa-note" style="margin-top:0">A new game is built in Godot and published with the games site (GitHub <code>kushkrg/codingcush-games</code>, deployed by Hostinger). Once it is live it appears here with its own analytics, leaderboards and settings.</p>
</div>
