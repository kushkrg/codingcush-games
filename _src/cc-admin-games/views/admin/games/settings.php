<?php
/**
 * Admin → Games → Game Settings. Vars: $game, $settings, + _top vars
 */
use App\Core\Csrf;
use App\Models\GamesData;

require __DIR__ . '/_helpers.php';
include __DIR__ . '/_top.php';
if (!$connected) { return; }
$g = GamesData::GAMES[$game];
$s = $settings['game.' . $game];
$until = (int) $s['boost_until'];
$untilValue = $until > 0 ? (new DateTime('@' . $until))->setTimezone(new DateTimeZone('Asia/Kolkata'))->format('Y-m-d\TH:i') : '';
$boostLive = (float) $s['coin_boost'] > 1 && ($until === 0 || $until > time());
?>
<form method="POST" action="<?= url('admin/games/settings') ?>" class="store-form gm-form">
    <?= Csrf::field() ?>
    <input type="hidden" name="game" value="<?= h($game) ?>">

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title"><?= h($g['name']) ?></h3><span class="pa-help">Players get changes the next time they open the game</span></div>
        <div class="store-grid-2">
            <div class="store-field">
                <label for="s-status">Status</label>
                <select id="s-status" name="status"><?php foreach (GamesData::GAME_STATUSES as $k => $label): ?><option value="<?= $k ?>"<?= $s['status'] === $k ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?></select>
                <small>A label for you and for the site; the game stays playable.</small>
            </div>
            <div class="store-field">
                <label for="s-notice">Message on the game's main menu</label>
                <input id="s-notice" type="text" name="notice" maxlength="140" value="<?= h($s['notice']) ?>" placeholder="New: mission 8 is live!">
                <small>Shown above PLAY. Empty = the site notice bar's message (if any).</small>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Coin boost event</h3><?= $boostLive ? '<span class="pa-pill gm-pill--live">Running</span>' : '<span class="pa-pill gm-pill--hidden">Off</span>' ?></div>
        <div class="store-grid-2">
            <div class="store-field">
                <label for="s-boost">Coins multiplied by</label>
                <select id="s-boost" name="coin_boost">
                    <?php foreach ([1 => 'Off (x1)', '1.5' => 'x1.5', 2 => 'x2 — double coins', 3 => 'x3', 5 => 'x5'] as $k => $label): ?><option value="<?= h((string) $k) ?>"<?= abs((float) $s['coin_boost'] - (float) $k) < 0.01 ? ' selected' : '' ?>><?= h($label) ?></option><?php endforeach; ?>
                </select>
                <small>On top of the difficulty's own coin rate. The main menu shows "COIN BOOST" while it runs.</small>
            </div>
            <div class="store-field">
                <label for="s-until">Ends at (India time)</label>
                <input id="s-until" type="datetime-local" name="boost_until" value="<?= h($untilValue) ?>">
                <small>Empty = runs until you switch it off.</small>
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Continue with diamonds</h3></div>
        <div class="store-grid-2">
            <div class="store-field">
                <label for="s-cost">First continue costs (diamonds)</label>
                <input id="s-cost" type="number" name="continue_cost" min="1" max="50" value="<?= (int) $s['continue_cost'] ?>">
                <small>Each further continue in the same run doubles: <?= (int) $s['continue_cost'] ?>, <?= (int) $s['continue_cost'] * 2 ?>, <?= (int) $s['continue_cost'] * 4 ?>…</small>
            </div>
            <div class="store-field">
                <label for="s-lives">Lives given back</label>
                <input id="s-lives" type="number" name="continue_lives" min="1" max="9" value="<?= (int) $s['continue_lives'] ?>">
            </div>
        </div>
    </div>

    <div class="dashboard-section">
        <div class="section-header"><h3 class="section-title">Leaderboard</h3></div>
        <label class="gm-switch"><input type="checkbox" name="scores_open" value="1"<?= $s['scores_open'] ? ' checked' : '' ?>><span></span> Accept new scores</label>
        <p class="pa-note">Turn off to freeze the leaderboard (for example while you clean up cheated scores). Players keep their progress; their runs just do not post.</p>
    </div>

    <div class="gm-savebar">
        <button class="store-btn" type="submit">Save game settings</button>
        <?php if (!empty($s['_updated_at'])): ?><span class="gm-muted">Last saved <?= h(gm_when((int) $s['_updated_at'])) ?></span><?php endif; ?>
    </div>
</form>
