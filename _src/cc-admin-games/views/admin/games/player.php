<?php
/**
 * Admin → Games → one player. Vars: $p (GamesData::player), $photo, + _top vars
 */
use App\Core\Csrf;
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
$actions = '<a class="store-btn store-btn-ghost" href="' . url('admin/games/players') . '">← All players</a>';
include __DIR__ . '/_top.php';
$labels = gm_labels();
$save = $p['saves']['vyomastra']['data'] ?? [];
$stars = is_array($save['stars'] ?? null) ? array_sum(array_map('intval', $save['stars'])) : 0;
$isAdmin = GamesData::isAdminUsername((string) $p['username']);
$banned = (int) ($p['banned'] ?? 0) === 1;
$act = static function (string $do, string $label, string $class = 'store-btn store-btn-ghost', string $confirm = '', array $extra = []) use ($p): string {
    $html = '<form method="POST" action="' . url('admin/games/players/' . (int) $p['id'] . '/action') . '"' . ($confirm ? ' onsubmit="return confirm(' . h(json_encode($confirm)) . ')"' : '') . '>' . Csrf::field() . '<input type="hidden" name="do" value="' . h($do) . '">';
    foreach ($extra as $k => $v) { $html .= '<input type="hidden" name="' . h($k) . '" value="' . h((string) $v) . '">'; }
    return $html . '<button class="' . h($class) . '" type="submit">' . h($label) . '</button></form>';
};
?>
<div class="gm-player">
    <div class="dashboard-section gm-card">
        <div class="gm-who">
            <?php if ($photo): ?><img src="<?= h($photo) ?>" alt="" width="72" height="72"><?php else: ?><span class="gm-avatar"><?= h(mb_strtoupper(mb_substr((string) ($p['name'] ?: $p['username']), 0, 1))) ?></span><?php endif; ?>
            <div>
                <h2>@<?= h($p['username'] ?: '—') ?> <?= $isAdmin ? '<span class="pa-pill gm-pill--beta">Admin</span>' : '' ?><?= $banned ? ' <span class="pa-pill gm-pill--hidden">Banned</span>' : '' ?></h2>
                <p class="gm-muted"><?= h($p['name']) ?> · <?= h($p['email']) ?></p>
            </div>
        </div>
        <dl class="gm-dl">
            <div><dt>Joined</dt><dd><?= h(gm_date((int) $p['created_at'])) ?></dd></div>
            <div><dt>Joined with</dt><dd><?= h($labels[$p['signup_via'] ?? ''] ?? (($p['signup_via'] ?? '') ?: 'before tracking')) ?></dd></div>
            <div><dt>Last active</dt><dd><?= h(gm_when((int) ($p['last_seen'] ?? 0))) ?></dd></div>
            <div><dt>Signed in on</dt><dd><?= (int) $p['sessions'] ?> device(s)</dd></div>
            <?php if (!empty($save)): ?>
            <div><dt>Coins</dt><dd><?= number_format((int) ($save['coins'] ?? 0)) ?></dd></div>
            <div><dt>Diamonds</dt><dd><?= number_format((int) ($save['diamonds'] ?? 0)) ?></dd></div>
            <div><dt>Missions open</dt><dd><?= (int) ($save['unlocked_level'] ?? 1) ?> of 8</dd></div>
            <div><dt>Stars</dt><dd><?= $stars ?> of 24</dd></div>
            <div><dt>Ships</dt><dd><?= h(implode(', ', array_map('ucfirst', (array) ($save['owned_ships'] ?? ['arrow'])))) ?></dd></div>
            <div><dt>Runs played</dt><dd><?= number_format((int) ($save['runs_played'] ?? 0)) ?></dd></div>
            <div><dt>Enemies destroyed</dt><dd><?= number_format((int) ($save['enemies_destroyed'] ?? 0)) ?></dd></div>
            <div><dt>Medals</dt><dd><?= count((array) ($save['medals'] ?? [])) ?></dd></div>
            <div><dt>City / country</dt><dd><?= h(trim(($save['city'] ?? '') . ', ' . ($save['country'] ?? ''), ', ') ?: '—') ?></dd></div>
            <div><dt>Cloud save</dt><dd><?= h(gm_when((int) ($p['saves']['vyomastra']['updated_at'] ?? 0))) ?> · <?= h(gm_bytes((int) ($p['saves']['vyomastra']['bytes'] ?? 0))) ?></dd></div>
            <?php else: ?>
            <div><dt>Cloud save</dt><dd>none yet</dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Leaderboard scores</h3></div>
        <?php if ($p['scores']): ?>
        <table class="data-table">
            <thead><tr><th>Mission</th><th>Ship</th><th class="pa-num">Score</th><th class="pa-num">Rank</th><th>When</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($p['scores'] as $s): $g = GamesData::GAMES[$s['game']] ?? null; ?>
                <tr>
                    <td><b><?= (int) $s['level'] ?>.</b> <?= h($g['level_names'][(int) $s['level'] - 1] ?? '') ?></td>
                    <td><?= h(ucfirst((string) $s['ship'])) ?></td>
                    <td class="pa-num"><b><?= number_format((int) $s['score']) ?></b></td>
                    <td class="pa-num">#<?= (int) ($s['rank'] ?? 0) ?></td>
                    <td><?= h(gm_when((int) $s['updated_at'])) ?></td>
                    <td class="pa-num"><?= $act('scores', 'Remove', 'store-btn store-btn-sm store-btn-ghost', 'Remove this score from the leaderboard?', ['game' => $s['game'], 'level' => (int) $s['level']]) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?><p class="gm-empty">No scores on the leaderboard.</p><?php endif; ?>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Actions</h3></div>
        <div class="pa-row-actions gm-actions">
            <?php if ($banned): ?>
                <?= $act('unban', 'Unban') ?>
            <?php elseif (!$isAdmin): ?>
                <?= $act('ban', 'Ban (removes scores and photo)', 'store-btn store-btn-danger', 'Ban @' . $p['username'] . '? Their scores and photo are removed and they cannot post new ones. They can still play.') ?>
            <?php endif; ?>
            <?php if ($photo): ?><?= $act('photo', 'Remove photo', 'store-btn store-btn-ghost', 'Remove this player\'s photo?') ?><?php endif; ?>
            <?php if ($p['scores']): ?><?= $act('scores', 'Remove all scores', 'store-btn store-btn-ghost', 'Remove all of this player\'s scores?', ['level' => 0]) ?><?php endif; ?>
            <?php if ((int) $p['sessions'] > 0): ?><?= $act('signout', 'Sign out everywhere', 'store-btn store-btn-ghost', 'Sign this player out on every device?') ?><?php endif; ?>
        </div>
        <?php if (!$isAdmin): ?>
        <details class="gm-danger">
            <summary>Delete this account</summary>
            <form method="POST" action="<?= url('admin/games/players/' . (int) $p['id'] . '/action') ?>">
                <?= Csrf::field() ?><input type="hidden" name="do" value="delete">
                <p>Deletes the account, its cloud save and its scores. This cannot be undone (a copy stays in the daily backups for up to 14 days).</p>
                <label>Type <b><?= h($p['username']) ?></b> to confirm <input type="text" name="confirm" autocomplete="off" required></label>
                <button class="store-btn store-btn-danger" type="submit">Delete account</button>
            </form>
        </details>
        <?php endif; ?>
    </div>
</div>
