<?php
/**
 * Small view helpers for Admin → Games (guarded so several views can include this file).
 */
use App\Models\GamesData;

if (!function_exists('gm_trend')) {
    /** "▲ 12% vs previous 30 days" for a KPI tile. */
    function gm_trend(int|float $now, int|float $before, int $days): string
    {
        if ($before == 0) {
            return $now > 0 ? '<span class="pa-kpi__trend pa-up">new</span>' : '<span class="pa-kpi__trend pa-flat">—</span>';
        }
        $pct = (int) round(($now - $before) / $before * 100);
        $cls = $pct > 0 ? 'pa-up' : ($pct < 0 ? 'pa-down' : 'pa-flat');
        return '<span class="pa-kpi__trend ' . $cls . '">' . ($pct > 0 ? '▲ ' : ($pct < 0 ? '▼ ' : '')) . abs($pct) . '% vs previous ' . ($days === 1 ? 'day' : $days . ' days') . '</span>';
    }

    /** A list of labelled bars (label => number), largest first. */
    function gm_bars(array $items, array $labels = [], string $empty = 'Nothing yet for this period.'): string
    {
        $items = array_filter($items, static fn($n) => (int) $n > 0);
        if (!$items) {
            return '<p class="gm-empty">' . h($empty) . '</p>';
        }
        $total = array_sum($items);
        $max = max($items);
        $html = '<ul class="pa-list">';
        foreach ($items as $key => $n) {
            $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', (string) $key));
            $html .= '<li><span>' . h($label) . '</span><b>' . number_format((int) $n) . ' <small>' . round($n / $total * 100) . '%</small></b>'
                . '<span class="bar"><span style="width:' . round($n / $max * 100, 1) . '%"></span></span></li>';
        }
        return $html . '</ul>';
    }

    function gm_when(int $time): string
    {
        if ($time <= 0) {
            return '—';
        }
        $ago = time() - $time;
        if ($ago < 3600) { return max(1, (int) floor($ago / 60)) . ' min ago'; }
        if ($ago < 86400) { return (int) floor($ago / 3600) . ' h ago'; }
        if ($ago < 86400 * 30) { return (int) floor($ago / 86400) . ' days ago'; }
        return date('j M Y', $time);
    }

    function gm_date(int $time): string
    {
        return $time > 0 ? date('j M Y, H:i', $time) : '—';
    }

    function gm_bytes(int $bytes): string
    {
        if ($bytes >= 1048576) { return round($bytes / 1048576, 1) . ' MB'; }
        if ($bytes >= 1024) { return round($bytes / 1024) . ' KB'; }
        return $bytes . ' B';
    }

    /** Pretty names for counter details. */
    function gm_labels(): array
    {
        return [
            'web' => 'Browser', 'app' => 'Installed app', 'win' => 'Windows', 'mac' => 'macOS', 'other' => 'Other', 'unknown' => 'Older game version',
            'guest' => 'Guests (no account)', 'member' => 'Signed in',
            'google' => 'Google', 'facebook' => 'Facebook', 'email' => 'Email & password',
            'google_new' => 'Google (new account)', 'facebook_new' => 'Facebook (new account)', 'email_new' => 'Email (new account)',
            'home' => 'Home page', 'vyomastra' => 'Vyomastra page', 'privacy' => 'Privacy policy', 'vyomastra_play' => 'Play page', 'play' => 'Play page',
            'ship' => 'Ship destroyed', 'cargo' => 'Cargo lost', 'line' => 'Defence line broke',
        ];
    }
}
