<?php
/**
 * Top of every Games page: which data is shown (live or staging) and any problem opening it.
 * Needs $source, $connected, $problem, $host. Optional: $ranges + $days + $baseUrl for range pills, $actions (HTML).
 */
use App\Core\Csrf;
use App\Models\GamesData;

$here = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/admin/games'), '#');
?>
<div class="gm-top">
    <form method="POST" action="<?= url('admin/games/source') ?>" class="gm-source" aria-label="Data source">
        <?= Csrf::field() ?>
        <input type="hidden" name="back" value="<?= h($here) ?>">
        <span>Data:</span>
        <?php foreach (GamesData::SOURCES as $key => $s): ?>
            <button type="submit" name="source" value="<?= h($key) ?>" class="<?= $source === $key ? 'active' : '' ?>"><?= h($s['label']) ?></button>
        <?php endforeach; ?>
        <a href="<?= h($host) ?>/" target="_blank" rel="noopener"><?= h(parse_url($host, PHP_URL_HOST)) ?> ↗</a>
    </form>
    <?php if (!empty($ranges) && isset($days, $baseUrl)): ?>
        <nav class="pa-range" aria-label="Date range">
            <?php foreach ($ranges as $d => $label): ?>
                <a href="<?= h($baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'days=' . $d) ?>" class="<?= $d === $days ? 'active' : '' ?>"><?= h($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
    <?php if (!empty($actions)): ?><div class="pa-actions"><?= $actions ?></div><?php endif; ?>
</div>
<?php if ($source === 'staging'): ?>
    <div class="pa-warn">You are looking at the <b>staging</b> copy (test data). Switch to <b>Live site</b> for real players.</div>
<?php endif; ?>
<?php if (!$connected): ?>
    <div class="pa-warn"><?= h($problem ?: 'The games database could not be opened.') ?></div>
<?php endif; ?>
