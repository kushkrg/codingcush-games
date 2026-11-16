<?php
/**
 * Admin → Games → Page Settings (games.codingcush.com). Vars: $settings, $traffic, + _top vars
 */
use App\Core\Csrf;

require __DIR__ . '/_helpers.php';
include __DIR__ . '/_top.php';
if (!$connected) { return; }
$s = $settings['site'];
$labels = gm_labels();
$pagesList = [
    ['home', 'Home page', '/'],
    ['vyomastra', 'Vyomastra page', '/vyomastra/'],
    ['vyomastra_play', 'Play page (the game)', '/vyomastra/play/'],
    ['privacy', 'Privacy policy', '/privacy/'],
];
?>
<form method="POST" action="<?= url('admin/games/pages') ?>" class="store-form gm-form">
    <?= Csrf::field() ?>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Notice bar</h3><span class="pa-help">Shown at the top of every page and on the game's main menu</span></div>
        <div class="store-grid-2">
            <div class="store-field store-field-wide">
                <label for="g-notice">Message <span class="pa-counter" data-count="g-notice" data-max="180"></span></label>
                <input id="g-notice" type="text" name="notice" maxlength="180" value="<?= h($s['notice']) ?>" placeholder="Weekend event: double coins till Sunday!">
                <small>Leave empty to hide the bar.</small>
            </div>
            <div class="store-field">
                <label for="g-link">Link (optional)</label>
                <input id="g-link" type="text" name="notice_link" maxlength="300" value="<?= h($s['notice_link']) ?>" placeholder="/vyomastra/play/  or  https://…">
            </div>
            <div class="store-field">
                <label for="g-tone">Colour</label>
                <select id="g-tone" name="notice_tone">
                    <?php foreach (['info' => 'Blue (news)', 'good' => 'Green (event, reward)', 'warn' => 'Yellow (warning)'] as $k => $label): ?><option value="<?= $k ?>"<?= $s['notice_tone'] === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="gm-preview"><span>Preview</span><div class="gm-notice gm-notice--<?= h($s['notice_tone']) ?>" id="g-preview"><?= h($s['notice'] ?: 'Your message appears here') ?></div></div>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Maintenance</h3></div>
        <label class="gm-switch"><input type="checkbox" name="maintenance" value="1"<?= $s['maintenance'] ? ' checked' : '' ?>><span></span> Maintenance mode</label>
        <p class="pa-note">Shows a yellow bar on every page and a message in the game instead of the notice. The games keep working; use it while you update things.</p>
        <div class="store-field" style="margin-top:10px">
            <label for="g-mm">Maintenance message</label>
            <input id="g-mm" type="text" name="maintenance_message" maxlength="240" value="<?= h($s['maintenance_message']) ?>" placeholder="We are updating the games. Your progress is safe.">
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Accounts</h3></div>
        <label class="gm-switch"><input type="checkbox" name="signups_open" value="1"<?= $s['signups_open'] ? ' checked' : '' ?>><span></span> Allow new accounts</label>
        <p class="pa-note">Turn off to stop new sign-ups (for example during spam). Existing players can still sign in, and anyone can play as a guest.</p>
        <div class="store-field" style="margin-top:10px">
            <label for="g-admins">Extra game admins</label>
            <input id="g-admins" type="text" name="extra_admins" value="<?= h(implode(', ', (array) $s['extra_admins'])) ?>" placeholder="username1, username2">
            <small>Usernames that get every mission unlocked in the game, like @kushkrg (always an admin).</small>
        </div>
    </div>

    <div class="gm-savebar">
        <button class="store-btn" type="submit">Save page settings</button>
        <?php if (!empty($s['_updated_at'])): ?><span class="gm-muted">Last saved <?= h(gm_when((int) $s['_updated_at'])) ?></span><?php endif; ?>
    </div>
</form>

<div class="dashboard-section">
    <div class="section-header"><h3 class="section-title">Pages on <?= h(parse_url($host, PHP_URL_HOST)) ?> — last 30 days</h3></div>
    <table class="data-table">
        <thead><tr><th>Page</th><th class="pa-num">Visits</th><th class="pa-num">Play clicks</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($pagesList as [$key, $label, $path]): ?>
            <tr><td><?= h($label) ?> <small class="gm-muted"><?= h($path) ?></small></td><td class="pa-num"><?= number_format($traffic['views'][$key] ?? 0) ?></td><td class="pa-num"><?= number_format($traffic['clicks'][$key] ?? 0) ?></td><td class="pa-num"><a href="<?= h($host . $path) ?>" target="_blank" rel="noopener">Open ↗</a></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p class="pa-note">Page titles, text and SEO are part of the site's code (GitHub <code>kushkrg/codingcush-games</code>, file <code>_src/build_site.py</code>) so search engines always see them; the settings above change things that need to switch on and off quickly.</p>
</div>
<script>
(function () {
    var input = document.getElementById('g-notice'), tone = document.getElementById('g-tone'), box = document.getElementById('g-preview');
    function show() { box.textContent = input.value || 'Your message appears here'; box.className = 'gm-notice gm-notice--' + tone.value; }
    input.addEventListener('input', show); tone.addEventListener('change', show);
    document.querySelectorAll('[data-count]').forEach(function (c) {
        var f = document.getElementById(c.getAttribute('data-count')), max = +c.getAttribute('data-max');
        function n() { c.textContent = f.value.length + '/' + max; c.classList.toggle('over', f.value.length > max); }
        f.addEventListener('input', n); n();
    });
})();
</script>
