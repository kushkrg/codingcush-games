<?php
/**
 * Admin → Games → Leaderboards. Vars: $game, $level, $board, $sizes, + _top vars
 */
use App\Core\Csrf;
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
include __DIR__ . '/_top.php';
if (!$connected) { return; }
$g = GamesData::GAMES[$game];
?>
<nav class="pa-tabs gm-levtabs" aria-label="Missions">
    <?php for ($l = 1; $l <= $g['levels']; $l++): ?>
        <a href="<?= url('admin/games/leaderboards?game=' . $game . '&level=' . $l) ?>" class="<?= $l === $level ? 'active' : '' ?>"><?= $l ?>. <?= h($g['level_names'][$l - 1]) ?> <small>(<?= (int) ($sizes[$l]['n'] ?? 0) ?>)</small></a>
    <?php endfor; ?>
</nav>
<div class="dashboard-section">
    <div class="section-header">
        <h3 class="section-title">Top 50 — mission <?= $level ?>, <?= h($g['level_names'][$level - 1]) ?></h3>
        <a href="<?= h($host) ?>/#leaderboard" target="_blank" rel="noopener">Public board ↗</a>
    </div>
    <?php if ($board): ?>
    <table class="data-table">
        <thead><tr><th class="pa-num">#</th><th>Pilot</th><th>Username</th><th>Ship</th><th class="pa-num">Score</th><th>Set</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($board as $i => $r): ?>
            <tr>
                <td class="pa-num"><b><?= $i + 1 ?></b></td>
                <td><?= h($r['name']) ?><?= (int) $r['has_photo'] ? ' 📷' : '' ?></td>
                <td><?php if ($r['username']): ?><a href="<?= url('admin/games/players/' . (int) $r['user_id']) ?>">@<?= h($r['username']) ?></a><?php else: ?>—<?php endif; ?></td>
                <td><?= h(ucfirst((string) $r['ship'])) ?></td>
                <td class="pa-num"><b><?= number_format((int) $r['score']) ?></b></td>
                <td><?= h(gm_when((int) $r['updated_at'])) ?></td>
                <td class="pa-num">
                    <form method="POST" action="<?= url('admin/games/leaderboards/remove') ?>" onsubmit="return confirm('Remove this score from the board?')">
                        <?= Csrf::field() ?><input type="hidden" name="game" value="<?= h($game) ?>"><input type="hidden" name="level" value="<?= $level ?>"><input type="hidden" name="user" value="<?= (int) $r['user_id'] ?>">
                        <button class="store-btn store-btn-sm store-btn-ghost" type="submit">Remove</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="pa-note">Removing a score takes it off the board at once. The player keeps their game progress; a new run can put them back. To stop a cheater for good, ban them from their player page.</p>
    <?php else: ?><p class="gm-empty">No scores on this mission yet.</p><?php endif; ?>
</div>
