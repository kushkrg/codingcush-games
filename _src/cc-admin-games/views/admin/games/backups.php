<?php
/**
 * Admin → Games → Data & backups. Vars: $backups, $size, $mysql, $allTime, + _top vars
 */
use App\Core\Csrf;
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
include __DIR__ . '/_top.php';
?>
<div class="pa-cols">
    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Database</h3></div>
        <ul class="pa-list gm-facts">
            <li><span>Type</span><b><?= $mysql ? 'MySQL' : 'SQLite file' ?></b></li>
            <?php if (!$mysql): ?><li><span>Size</span><b><?= h(gm_bytes($size)) ?></b></li><li><span>Location</span><b><?= h(GamesData::SOURCES[$source]['data']) ?>/games.sqlite</b></li><?php endif; ?>
            <li><span>Players</span><b><?= number_format((int) ($allTime['players'] ?? 0)) ?></b></li>
            <li><span>Cloud saves</span><b><?= number_format((int) ($allTime['saves'] ?? 0)) ?></b></li>
        </ul>
        <p class="pa-note">The folder is outside the website, so it cannot be opened from a browser.</p>
    </div>
    <div class="dashboard-section" style="grid-column:span 2">
        <div class="section-header">
            <h3 class="section-title">Backups</h3>
            <?php if (!$mysql && $connected): ?>
            <form method="POST" action="<?= url('admin/games/backups') ?>"><?= Csrf::field() ?><button class="store-btn" type="submit">Back up now</button></form>
            <?php endif; ?>
        </div>
        <?php if ($mysql): ?>
            <p class="gm-empty">The games use MySQL, which Hostinger backs up (hPanel → Files → Backups).</p>
        <?php elseif ($backups): ?>
        <table class="data-table">
            <thead><tr><th>File</th><th class="pa-num">Size</th><th>Made</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($backups as $b): ?>
                <tr><td><code><?= h($b['name']) ?></code></td><td class="pa-num"><?= h(gm_bytes($b['bytes'])) ?></td><td><?= h(gm_when($b['made_at'])) ?></td><td class="pa-num"><a href="<?= url('admin/games/backups/' . rawurlencode($b['name'])) ?>">⬇ Download</a></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="pa-note">The games site makes one copy a day by itself and keeps 14 days. A backup holds players' emails: keep downloaded copies private.</p>
        <?php else: ?>
            <p class="gm-empty">No backups yet. The first daily copy is made the next time the games site is used.</p>
        <?php endif; ?>
    </div>
</div>
