<?php
/**
 * Admin → Games → Analytics.
 * Vars: $days, $game, $now, $before, $daily, $platforms, $logins, $pages, $levels, $allTime, + _top vars
 */
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
$baseUrl = url('admin/games/analytics?game=' . $game);
$actions = '<a class="store-btn store-btn-ghost" href="' . url('admin/games/analytics/export?days=' . $days) . '">⬇ Export CSV</a>';
include __DIR__ . '/_top.php';
if (!$connected) { return; }
$labels = gm_labels();
$sessionsAll = max(1, $now['sessions']);
$kpis = [
    ['Game opened', $now['sessions'], $before['sessions']],
    ['…as a guest', $now['guest_sessions'], $before['guest_sessions']],
    ['…signed in', $now['member_sessions'], $before['member_sessions']],
    ['…as installed app', $now['app_sessions'], $before['app_sessions']],
    ['New accounts', $now['signups'], $before['signups']],
    ['Sign-ins (returning)', $now['logins'], $before['logins']],
    ['Missions played', $now['starts'], $before['starts']],
    ['Missions won', $now['wins'], $before['wins']],
    ['Continues bought', $now['continues'], $before['continues']],
    ['App installs', $now['installs'], $before['installs']],
    ['Website visits', $now['page_views'], $before['page_views']],
    ['Play clicks', $now['play_clicks'], $before['play_clicks']],
];
?>
<div class="pa-kpis gm-kpis">
    <?php foreach ($kpis as [$label, $n, $b]): ?>
        <div class="pa-kpi"><div class="pa-kpi__label"><?= h($label) ?></div><div class="pa-kpi__num"><?= number_format($n) ?></div><?= gm_trend($n, $b, $days) ?></div>
    <?php endforeach; ?>
</div>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Who opened the game</h3></div>
    <?php
    $chartRows = $daily;
    $chartSeries = [['sessions', 'All', 'gm-s1', '#0f766e'], ['guest', 'Guests', 'gm-s2', '#d97706'], ['member', 'Signed in', 'gm-s3', '#4f46e5']];
    include VIEW_PATH . '/admin/plugins/_chart.php';
    ?>
</div>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Accounts and play</h3></div>
    <?php
    $chartRows = $daily;
    $chartSeries = [['plays', 'Missions played', 'gm-s2', '#d97706'], ['wins', 'Missions won', 'gm-s1', '#0f766e'], ['signups', 'New accounts', 'gm-s3', '#4f46e5'], ['logins', 'Sign-ins', 'gm-s4', '#db2777']];
    include VIEW_PATH . '/admin/plugins/_chart.php';
    ?>
</div>

<div class="pa-cols">
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Where it is played</h3></div>
        <?= gm_bars($platforms['platform'], $labels) ?>
        <p class="pa-note">"Installed app" = added to the home screen or installed from the browser. Windows and Android builds are not published yet, so there are no downloads to count.</p>
    </div>
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">How players sign in</h3></div>
        <?= gm_bars($logins['logins'], $labels, 'No sign-ins in this period.') ?>
    </div>
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">New accounts by method</h3></div>
        <?= gm_bars($logins['signups'], $labels, 'No new accounts in this period.') ?>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">games.codingcush.com pages</h3><a href="<?= url('admin/games/pages') ?>">Page settings →</a></div>
    <?php if ($pages['views'] || $pages['clicks']): ?>
    <table class="data-table">
        <thead><tr><th>Page</th><th class="pa-num">Visits</th><th class="pa-num">Play clicks</th><th class="pa-num">Click rate</th><th class="pa-num">Installs</th></tr></thead>
        <tbody>
        <?php foreach (array_unique(array_merge(array_keys($pages['views']), array_keys($pages['clicks']), array_keys($pages['installs']))) as $p): $v = $pages['views'][$p] ?? 0; $c = $pages['clicks'][$p] ?? 0; ?>
            <tr><td><?= h($labels[$p] ?? $p) ?></td><td class="pa-num"><?= number_format($v) ?></td><td class="pa-num"><?= number_format($c) ?></td><td class="pa-num"><?= $v ? round($c / $v * 100, 1) . '%' : '—' ?></td><td class="pa-num"><?= number_format($pages['installs'][$p] ?? 0) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="pa-note">A visit is counted once per page per browser tab. Bots that do not run JavaScript are not counted.</p>
    <?php else: ?>
        <p class="gm-empty">No website visits counted yet. Counting started with this update.</p>
    <?php endif; ?>
</div>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title"><?= h(GamesData::GAMES[$game]['name']) ?> missions</h3></div>
    <?php include __DIR__ . '/_levels.php'; ?>
</div>
