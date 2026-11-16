<?php
/**
 * Admin → Games → Error reports. Vars: $days, $errors, + _top vars
 */
require __DIR__ . '/_helpers.php';
$baseUrl = url('admin/games/errors');
include __DIR__ . '/_top.php';
if (!$connected) { return; }
?>
<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Errors from players' browsers</h3><span class="pa-help">The same error on the same day is counted, not repeated</span></div>
    <?php if ($errors): ?>
    <table class="data-table gm-errors">
        <thead><tr><th>Day</th><th>Error</th><th>Where</th><th>Version</th><th class="pa-num">Times</th><th>Last seen</th></tr></thead>
        <tbody>
        <?php foreach ($errors as $e): ?>
            <tr><td><?= h($e['day']) ?></td><td><code><?= h($e['message']) ?></code></td><td><small><?= h($e['place']) ?></small></td><td><?= h($e['version'] ?: '—') ?></td><td class="pa-num"><b><?= number_format((int) $e['total']) ?></b></td><td><?= h(gm_when((int) $e['last_at'])) ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?><p class="gm-empty">No errors reported in this period. 🎉</p><?php endif; ?>
</div>
