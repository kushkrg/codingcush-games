<?php
/** Mission table. Needs $levels (from GamesData::levels). */
$labels = gm_labels();
?>
<table class="data-table gm-levels">
    <thead><tr><th>Mission</th><th class="pa-num">Played</th><th class="pa-num">Won</th><th class="pa-num">Lost</th><th>Win rate</th><th class="pa-num">Continues</th><th>Stars (won runs)</th><th>Why runs were lost</th></tr></thead>
    <tbody>
    <?php foreach ($levels as $l): $stars = array_sum($l['stars']); ?>
        <tr>
            <td><b><?= (int) $l['level'] ?>.</b> <?= h($l['name']) ?></td>
            <td class="pa-num"><?= number_format($l['starts']) ?></td>
            <td class="pa-num"><?= number_format($l['wins']) ?></td>
            <td class="pa-num"><?= number_format($l['fails']) ?></td>
            <td><div class="gm-meter <?= $l['starts'] && $l['win_rate'] < 35 ? 'low' : '' ?>"><span style="width:<?= min(100, (float) $l['win_rate']) ?>%"></span></div><small><?= $l['starts'] ? $l['win_rate'] . '%' : '—' ?></small></td>
            <td class="pa-num"><?= number_format($l['continues']) ?></td>
            <td><?php if ($stars): ?><span class="gm-stars">★★★ <?= round($l['stars'][3] / $stars * 100) ?>% · ★★ <?= round($l['stars'][2] / $stars * 100) ?>% · ★ <?= round($l['stars'][1] / $stars * 100) ?>%</span><?php else: ?>—<?php endif; ?></td>
            <td><?php if ($l['fails']): $parts = []; foreach (array_slice($l['causes'], 0, 3, true) as $c => $n) { $parts[] = h($labels[$c] ?? ucfirst($c)) . ' ' . round($n / $l['fails'] * 100) . '%'; } ?><?= implode(' · ', $parts) ?><?= $l['boss_fails'] ? ' <small class="gm-muted">(' . round($l['boss_fails'] / $l['fails'] * 100) . '% at the boss)</small>' : '' ?><?php else: ?>—<?php endif; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p class="pa-note">A low win rate (red) means the mission may be too hard. Stars are as the game awards them (★★★ is the best).</p>
