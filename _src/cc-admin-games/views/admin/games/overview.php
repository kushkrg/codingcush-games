<?php
/**
 * Admin → Games → Overview.
 * Vars: $days, $ranges, $now, $before, $allTime, $daily, $platforms, $levels, $settings, + _top vars
 */
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
$baseUrl = url('admin/games');
$actions = '<a class="store-btn store-btn-ghost" href="' . url('admin/games/analytics?days=' . $days) . '">Full analytics →</a>';
include __DIR__ . '/_top.php';
if (!$connected) { return; }

$guestShare = ($now['guest_sessions'] + $now['member_sessions']) > 0 ? round($now['guest_sessions'] / ($now['guest_sessions'] + $now['member_sessions']) * 100) : 0;
$kpis = [
    ['Players (accounts)', number_format($allTime['players']), '<span class="pa-kpi__trend pa-flat">all time · ' . number_format($allTime['banned']) . ' banned</span>'],
    ['New sign-ups', number_format($now['signups']), gm_trend($now['signups'], $before['signups'], $days)],
    ['Game opened', number_format($now['sessions']), gm_trend($now['sessions'], $before['sessions'], $days)],
    ['Played as guest', $guestShare . '%', '<span class="pa-kpi__trend pa-flat">' . number_format($now['guest_sessions']) . ' guest · ' . number_format($now['member_sessions']) . ' signed in</span>'],
    ['Missions played', number_format($now['starts']), gm_trend($now['starts'], $before['starts'], $days)],
    ['Mission win rate', $now['win_rate'] . '%', '<span class="pa-kpi__trend pa-flat">' . number_format($now['wins']) . ' won · ' . number_format($now['fails']) . ' lost</span>'],
    ['Sign-ins', number_format($now['logins'] + $now['new_logins']), gm_trend($now['logins'] + $now['new_logins'], $before['logins'] + $before['new_logins'], $days)],
    ['Active players', number_format($now['active']), '<span class="pa-kpi__trend pa-flat">accounts used in this period</span>'],
    ['App installs', number_format($now['installs']), gm_trend($now['installs'], $before['installs'], $days)],
    ['Website visits', number_format($now['page_views']), gm_trend($now['page_views'], $before['page_views'], $days)],
    ['Play button clicks', number_format($now['play_clicks']), gm_trend($now['play_clicks'], $before['play_clicks'], $days)],
    ['Errors reported', number_format($now['errors']), $now['errors'] > 0 ? '<a class="pa-kpi__trend pa-down" href="' . url('admin/games/errors?days=' . $days) . '">see the reports →</a>' : '<span class="pa-kpi__trend pa-up">all clear</span>'],
];
$site = $settings['site'];
$vy = $settings['game.vyomastra'];
$boostOn = (float) $vy['coin_boost'] > 1 && ((int) $vy['boost_until'] === 0 || (int) $vy['boost_until'] > time());
?>
<div class="pa-kpis gm-kpis">
    <?php foreach ($kpis as [$label, $num, $trend]): ?>
        <div class="pa-kpi"><div class="pa-kpi__label"><?= h($label) ?></div><div class="pa-kpi__num"><?= h($num) ?></div><?= $trend ?></div>
    <?php endforeach; ?>
</div>

<div class="gm-status">
    <div class="gm-chip <?= $site['maintenance'] ? 'warn' : 'good' ?>"><b>Site</b> <?= $site['maintenance'] ? 'Maintenance on' : 'Online' ?></div>
    <div class="gm-chip <?= $site['notice'] !== '' ? 'info' : '' ?>"><b>Notice bar</b> <?= $site['notice'] !== '' ? h(mb_strimwidth($site['notice'], 0, 48, '…')) : 'off' ?></div>
    <div class="gm-chip <?= $site['signups_open'] ? 'good' : 'warn' ?>"><b>New accounts</b> <?= $site['signups_open'] ? 'open' : 'paused' ?></div>
    <div class="gm-chip <?= $vy['scores_open'] ? 'good' : 'warn' ?>"><b>Leaderboard</b> <?= $vy['scores_open'] ? 'open' : 'paused' ?></div>
    <div class="gm-chip <?= $boostOn ? 'info' : '' ?>"><b>Coin boost</b> <?= $boostOn ? 'x' . h((string) (float) $vy['coin_boost']) . ((int) $vy['boost_until'] ? ' till ' . date('j M H:i', (int) $vy['boost_until']) : '') : 'off' ?></div>
    <a class="gm-chip-link" href="<?= url('admin/games/pages') ?>">Page settings →</a>
    <a class="gm-chip-link" href="<?= url('admin/games/settings') ?>">Game settings →</a>
</div>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Every day — <?= h(strtolower($ranges[$days])) ?><?= $days < 7 ? ' (chart shows 7 days)' : '' ?></h3></div>
    <?php
    $chartRows = $daily;
    $chartSeries = [['sessions', 'Game opened', 'gm-s1', '#0f766e'], ['plays', 'Missions played', 'gm-s2', '#d97706'], ['signups', 'New sign-ups', 'gm-s3', '#4f46e5'], ['views', 'Website visits', 'gm-s4', '#db2777']];
    include VIEW_PATH . '/admin/plugins/_chart.php';
    ?>
    <p class="pa-note">Days are counted in UTC (they start at 5:30 am India time). Counts are anonymous: no account, device or IP address is stored with them.</p>
</div>

<div class="pa-cols">
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Where it is played</h3></div>
        <?= gm_bars($platforms['platform'], gm_labels()) ?>
    </div>
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Guests vs signed in</h3></div>
        <?= gm_bars($platforms['player'], gm_labels()) ?>
    </div>
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">All time</h3></div>
        <ul class="pa-list gm-facts">
            <li><span>Game opened</span><b><?= number_format($allTime['sessions']) ?></b></li>
            <li><span>Missions played</span><b><?= number_format($allTime['starts']) ?></b></li>
            <li><span>Cloud saves</span><b><?= number_format($allTime['saves']) ?></b></li>
            <li><span>Leaderboard entries</span><b><?= number_format($allTime['scores']) ?></b></li>
            <li><span>Players with a photo</span><b><?= number_format($allTime['with_photo']) ?></b></li>
            <li><span>Counting since</span><b><?= $allTime['first_day'] ? h(date('j M Y', strtotime($allTime['first_day']))) : '—' ?></b></li>
        </ul>
    </div>
</div>

<div class="dashboard-section">
    <div class="section-header">
        <h3 class="section-title">Vyomastra missions — <?= h(strtolower($ranges[$days])) ?></h3>
        <a href="<?= url('admin/games/analytics?days=' . $days) ?>">Details →</a>
    </div>
    <?php include __DIR__ . '/_levels.php'; ?>
</div>
