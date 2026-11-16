<?php
/**
 * Admin → Games → Players. Vars: $search, $sort, $filter, $page, $found, $allTime, + _top vars
 */
require __DIR__ . '/_helpers.php';
$actions = '<a class="store-btn store-btn-ghost" href="' . url('admin/games/players/export') . '">⬇ Export CSV</a>';
include __DIR__ . '/_top.php';
if (!$connected) { return; }
$labels = gm_labels();
$pages = max(1, (int) ceil($found['total'] / 40));
$link = static fn(array $change): string => url('admin/games/players?' . http_build_query(array_filter(array_merge(['q' => $search, 'sort' => $sort, 'filter' => $filter, 'page' => $page], $change), static fn($v) => $v !== '' && $v !== null)));
$filters = ['' => 'Everyone', 'google' => 'Joined with Google', 'facebook' => 'Joined with Facebook', 'email' => 'Joined with email', 'banned' => 'Banned'];
?>
<form class="gm-filter" method="GET" action="<?= url('admin/games/players') ?>">
    <input type="search" name="q" value="<?= h($search) ?>" placeholder="Search username, name or email" aria-label="Search players">
    <select name="filter" aria-label="Show"><?php foreach ($filters as $k => $label): ?><option value="<?= h($k) ?>"<?= $filter === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select>
    <select name="sort" aria-label="Order"><option value="newest"<?= $sort === 'newest' ? ' selected' : '' ?>>Newest first</option><option value="active"<?= $sort === 'active' ? ' selected' : '' ?>>Recently active</option><option value="oldest"<?= $sort === 'oldest' ? ' selected' : '' ?>>Oldest first</option><option value="name"<?= $sort === 'name' ? ' selected' : '' ?>>Username A–Z</option></select>
    <button class="store-btn" type="submit">Show</button>
    <span class="gm-muted"><?= number_format($found['total']) ?> of <?= number_format((int) ($allTime['players'] ?? 0)) ?> players</span>
</form>

<div class="dashboard-section">
<?php if ($found['rows']): ?>
    <table class="data-table">
        <thead><tr><th>Player</th><th>Email</th><th>Joined</th><th>Joined with</th><th>Last active</th><th class="pa-num">Levels on board</th><th class="pa-num">Score total</th><th>Cloud save</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($found['rows'] as $r): ?>
            <tr>
                <td><a href="<?= url('admin/games/players/' . (int) $r['id']) ?>"><b>@<?= h($r['username'] ?: '—') ?></b></a><small class="gm-block"><?= h($r['name']) ?><?= (int) $r['has_photo'] ? ' · 📷' : '' ?></small><?= (int) $r['banned'] ? ' <span class="pa-pill gm-pill--hidden">Banned</span>' : '' ?></td>
                <td><?= h($r['email']) ?></td>
                <td title="<?= h(gm_date((int) $r['created_at'])) ?>"><?= h(date('j M Y', (int) $r['created_at'])) ?></td>
                <td><?= h($labels[$r['signup_via']] ?? ($r['signup_via'] ?: '—')) ?></td>
                <td><?= h(gm_when((int) $r['last_seen'])) ?></td>
                <td class="pa-num"><?= (int) $r['levels_scored'] ?></td>
                <td class="pa-num"><?= number_format((int) $r['score_total']) ?></td>
                <td><?= $r['saved_at'] ? h(gm_when((int) $r['saved_at'])) : '—' ?></td>
                <td class="pa-num"><a href="<?= url('admin/games/players/' . (int) $r['id']) ?>">Open →</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
        <nav class="gm-pager" aria-label="Pages">
            <?php if ($page > 1): ?><a href="<?= h($link(['page' => $page - 1])) ?>">← Previous</a><?php endif; ?>
            <span>Page <?= $page ?> of <?= $pages ?></span>
            <?php if ($page < $pages): ?><a href="<?= h($link(['page' => $page + 1])) ?>">Next →</a><?php endif; ?>
        </nav>
    <?php endif; ?>
<?php else: ?>
    <p class="gm-empty"><?= $search !== '' || $filter !== '' ? 'No player matches.' : 'No accounts yet. Guests can play without one.' ?></p>
<?php endif; ?>
</div>
