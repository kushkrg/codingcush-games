<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h(($title ?? 'Dashboard') . ' - Admin') ?></title>
    <link rel="icon" href="<?= url('favicon.ico') ?>?v=20261020" sizes="any">
    <link rel="icon" type="image/svg+xml" href="<?= url('favicon.svg') ?>?v=20261020">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= url('favicon-16x16.png') ?>?v=20261020">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= url('favicon-32x32.png') ?>?v=20261020">
    <link rel="icon" type="image/png" sizes="48x48" href="<?= url('favicon-48x48.png') ?>?v=20261020">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= url('apple-touch-icon.png') ?>?v=20261020">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>?v=20261025a">
    <link rel="stylesheet" href="<?= url('assets/css/store-admin.css') ?>?v=20261018a">
    <link rel="stylesheet" href="<?= url('assets/css/plugins-admin.css') ?>?v=20261103">
    <link rel="stylesheet" href="<?= url('assets/css/games-admin.css') ?>?v=20261117">
    <link rel="stylesheet" href="<?= url('assets/css/students-admin.css') ?>?v=20261030a">
</head>
<body class="admin-layout">

    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= url('/') ?>" class="sidebar-logo">
                <img src="<?= url('assets/img/logo.svg') ?>?v=20261020" alt="CodingCush" class="sidebar-logo-img" width="150" height="34" style="height:34px;width:auto;max-width:168px;display:block">
                <span class="sidebar-admin-tag">Admin</span>
            </a>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav-list">
                <li class="nav-section">Overview</li>
                <li class="nav-item">
                    <a href="<?= url('admin') ?>" class="nav-link<?= ($currentPage ?? '') === 'dashboard' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?= url('admin/analytics') ?>" class="nav-link<?= ($currentPage ?? '') === 'analytics' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3v18h18"></path>
                            <path d="M7 15l4-5 3 3 5-7"></path>
                        </svg>
                        <span>Analytics</span>
                    </a>
                </li>
                <?php $contentActive = in_array((string) ($currentPage ?? ''), ['posts', 'pages', 'media', 'categories', 'tags', 'menus', 'comments'], true); ?>
                <li class="nav-item content-group<?= $contentActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link content-toggle" aria-expanded="<?= $contentActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Content</span>
                        <svg class="content-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <ul class="content-sub">
                        <li>
                    <a href="<?= url('admin/posts') ?>" class="nav-link<?= ($currentPage ?? '') === 'posts' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"></path>
                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                        </svg>
                        <span>Posts</span>
                    </a>
                        </li>
                        <?php if (\App\Core\Auth::can('pages.manage')): ?>
                        <li>
                    <a href="<?= url('admin/pages') ?>" class="nav-link<?= ($currentPage ?? '') === 'pages' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span>Pages</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('tools.use')): ?>
                        <li>
                    <a href="<?= url('admin/tools') ?>" class="nav-link<?= ($currentPage ?? '') === 'tools' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                        </svg>
                        <span>Tools</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <li>
                    <a href="<?= url('admin/media') ?>" class="nav-link<?= ($currentPage ?? '') === 'media' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                            <polyline points="21 15 16 10 5 21"></polyline>
                        </svg>
                        <span>Media</span>
                    </a>
                        </li>
                        <?php if (\App\Core\Auth::can('categories.manage')): ?>
                        <li>
                    <a href="<?= url('admin/categories') ?>" class="nav-link<?= ($currentPage ?? '') === 'categories' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Categories</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('tags.manage')): ?>
                        <li>
                    <a href="<?= url('admin/tags') ?>" class="nav-link<?= ($currentPage ?? '') === 'tags' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path>
                            <line x1="7" y1="7" x2="7.01" y2="7"></line>
                        </svg>
                        <span>Tags</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('menus.manage')): ?>
                        <li>
                    <a href="<?= url('admin/menus') ?>" class="nav-link<?= ($currentPage ?? '') === 'menus' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                        <span>Menus</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('comments.moderate')): ?>
                        <li>
                    <a href="<?= url('admin/comments') ?>" class="nav-link<?= ($currentPage ?? '') === 'comments' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Comments</span>
                        <?php
                        $pendingCount = \App\Models\Comment::pendingCount();
                        if ($pendingCount > 0): ?>
                            <span class="nav-badge"><?= (int) $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php if (\App\Core\Auth::can('store.manage')): ?>
                <li class="nav-section">Store</li>
                <?php
                $storeActive = str_starts_with((string) ($currentPage ?? ''), 'store-');
                $storeBadge = 0;
                try {
                    if (class_exists(\App\Models\StoreOrder::class) && method_exists(\App\Models\StoreOrder::class, 'pendingCount')) {
                        $storeBadge += (int) \App\Models\StoreOrder::pendingCount();
                    }
                    if (class_exists(\App\Models\StoreReview::class) && method_exists(\App\Models\StoreReview::class, 'pendingCount')) {
                        $storeBadge += (int) \App\Models\StoreReview::pendingCount();
                    }
                } catch (\Throwable $e) {
                    $storeBadge = 0; // Store tables not created yet — never break the layout.
                }
                ?>
                <li class="nav-item store-group<?= $storeActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link store-toggle" aria-expanded="<?= $storeActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <path d="M16 10a4 4 0 0 1-8 0"></path>
                        </svg>
                        <span>My Store</span>
                        <?php if ($storeBadge > 0): ?>
                            <span class="nav-badge"><?= (int) $storeBadge ?></span>
                        <?php endif; ?>
                        <svg class="store-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <ul class="store-sub">
                        <li><a href="<?= url('admin/store') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-overview' ? ' active' : '' ?>"><span>Overview</span></a></li>
                        <li><a href="<?= url('admin/store/products') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-products' ? ' active' : '' ?>"><span>Products</span></a></li>
                        <li><a href="<?= url('admin/store/orders') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-orders' ? ' active' : '' ?>"><span>Orders</span></a></li>
                        <li><a href="<?= url('admin/store/reviews') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-reviews' ? ' active' : '' ?>"><span>Reviews</span></a></li>
                        <li><a href="<?= url('admin/store/payments') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-payments' ? ' active' : '' ?>"><span>Payments</span></a></li>
                        <li><a href="<?= url('admin/store/coupons') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-coupons' ? ' active' : '' ?>"><span>Coupons</span></a></li>
                        <li><a href="<?= url('admin/store/settings') ?>" class="nav-link<?= ($currentPage ?? '') === 'store-settings' ? ' active' : '' ?>"><span>Settings</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('plugins.manage')): ?>
                <li class="nav-section">WordPress</li>
                <?php $pluginsActive = str_starts_with((string) ($currentPage ?? ''), 'plugins'); ?>
                <li class="nav-item plugins-group<?= $pluginsActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link plugins-toggle" aria-expanded="<?= $pluginsActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6M15 2v6"></path><path d="M6 8h12v4a6 6 0 0 1-12 0z"></path><path d="M12 18v4"></path></svg>
                        <span>Plugins</span>
                        <svg class="plugins-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="plugins-sub">
                        <li><a href="<?= url('admin/plugins') ?>" class="nav-link<?= ($currentPage ?? '') === 'plugins-overview' ? ' active' : '' ?>"><span>Overview &amp; stats</span></a></li>
                        <li><a href="<?= url('admin/plugins/list') ?>" class="nav-link<?= ($currentPage ?? '') === 'plugins-list' ? ' active' : '' ?>"><span>All plugins</span></a></li>
                        <li><a href="<?= url('admin/plugins/create') ?>" class="nav-link<?= ($currentPage ?? '') === 'plugins-create' ? ' active' : '' ?>"><span>Add new</span></a></li>
                        <li><a href="<?= url('plugins') ?>" class="nav-link" target="_blank" rel="noopener"><span>View /plugins ↗</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('games.manage')): ?>
                <li class="nav-section">Games</li>
                <?php
                $gamesActive = str_starts_with((string) ($currentPage ?? ''), 'games-');
                $gamesBadge = 0;
                try {
                    // Errors reported today by players' browsers, shown as a badge.
                    if (class_exists(\App\Models\GamesData::class) && \App\Models\GamesData::db() !== null) {
                        foreach (\App\Models\GamesData::errors(1) as $gmError) { $gamesBadge += (int) $gmError['total']; }
                    }
                } catch (\Throwable $e) {
                    $gamesBadge = 0; // games database unavailable — never break the layout.
                }
                ?>
                <li class="nav-item games-group<?= $gamesActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link games-toggle" aria-expanded="<?= $gamesActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="6"></rect><line x1="7" y1="10" x2="7" y2="14"></line><line x1="5" y1="12" x2="9" y2="12"></line><circle cx="15.5" cy="11" r="1"></circle><circle cx="18" cy="13.5" r="1"></circle></svg>
                        <span>Games</span>
                        <?php if ($gamesBadge > 0): ?>
                            <span class="nav-badge" title="Errors reported today"><?= (int) $gamesBadge ?></span>
                        <?php endif; ?>
                        <svg class="games-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </button>
                    <ul class="games-sub">
                        <li><a href="<?= url('admin/games') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-overview' ? ' active' : '' ?>"><span>Overview</span></a></li>
                        <li><a href="<?= url('admin/games/list') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-list' ? ' active' : '' ?>"><span>All Games</span></a></li>
                        <li><a href="<?= url('admin/games/analytics') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-analytics' ? ' active' : '' ?>"><span>Analytics</span></a></li>
                        <li><a href="<?= url('admin/games/players') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-players' ? ' active' : '' ?>"><span>Players</span></a></li>
                        <li><a href="<?= url('admin/games/leaderboards') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-leaderboards' ? ' active' : '' ?>"><span>Leaderboards</span></a></li>
                        <li><a href="<?= url('admin/games/pages') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-pages' ? ' active' : '' ?>"><span>Page Settings</span></a></li>
                        <li><a href="<?= url('admin/games/settings') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-settings' ? ' active' : '' ?>"><span>Game Settings</span></a></li>
                        <li><a href="<?= url('admin/games/errors') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-errors' ? ' active' : '' ?>"><span>Error Reports</span></a></li>
                        <li><a href="<?= url('admin/games/backups') ?>" class="nav-link<?= ($currentPage ?? '') === 'games-backups' ? ' active' : '' ?>"><span>Data &amp; Backups</span></a></li>
                        <li><a href="https://games.codingcush.com/" class="nav-link" target="_blank" rel="noopener"><span>View games site ↗</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('students.manage')): ?>
                <li class="nav-section">Students</li>
                <?php $studentsActive = str_starts_with((string) ($currentPage ?? ''), 'students'); ?>
                <?php
                $studentBadge = 0;
                try {
                    if (class_exists(\App\Models\Student::class) && method_exists(\App\Models\Student::class, 'stats')) {
                        $stuStats = \App\Models\Student::stats();
                        $studentBadge = (int) ($stuStats['pending_apps'] ?? 0) + (int) ($stuStats['pending_subs'] ?? 0);
                    }
                } catch (\Throwable $e) {
                    $studentBadge = 0; // Student tables not created yet — never break the layout.
                }
                ?>
                <li class="nav-item students-group<?= $studentsActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link students-toggle" aria-expanded="<?= $studentsActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9L12 4 2 9l10 5 10-5z"></path><path d="M6 11.5V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.5"></path><line x1="22" y1="9" x2="22" y2="15"></line></svg>
                        <span>Students</span>
                        <?php if ($studentBadge > 0): ?>
                            <span class="nav-badge"><?= (int) $studentBadge ?></span>
                        <?php endif; ?>
                        <svg class="students-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <ul class="students-sub">
                        <li><a href="<?= url('admin/students') ?>" class="nav-link<?= ($currentPage ?? '') === 'students' ? ' active' : '' ?>"><span>Students</span></a></li>
                        <li><a href="<?= url('admin/students/webflow') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-webflow' ? ' active' : '' ?>"><span>Webflow Course</span></a></li>
                        <li><a href="<?= url('admin/students/courses') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-courses' ? ' active' : '' ?>"><span>Courses</span></a></li>
                        <li><a href="<?= url('admin/students/syllabus') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-syllabus' ? ' active' : '' ?>"><span>Syllabus</span></a></li>
                        <li><a href="<?= url('admin/students/lessons') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-lessons' ? ' active' : '' ?>"><span>Lessons</span></a></li>
                        <li><a href="<?= url('admin/students/assignments') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-assignments' ? ' active' : '' ?>"><span>Assignments</span></a></li>
                        <li><a href="<?= url('admin/students/submissions') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-submissions' ? ' active' : '' ?>"><span>Submissions</span></a></li>
                        <li><a href="<?= url('admin/students/applications') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-applications' ? ' active' : '' ?>"><span>Applications</span></a></li>
                        <li><a href="<?= url('admin/students/questions') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-questions' ? ' active' : '' ?>"><span>Exam Questions</span></a></li>
                        <li><a href="<?= url('admin/students/certificates') ?>" class="nav-link<?= ($currentPage ?? '') === 'students-certificates' ? ' active' : '' ?>"><span>Certificates</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('subscribers.manage') || \App\Core\Auth::can('messages.manage')): ?>
                <li class="nav-section">Audience</li>
                <?php if (\App\Core\Auth::can('subscribers.manage')): ?>
                <li class="nav-item">
                    <a href="<?= url('admin/subscribers') ?>" class="nav-link<?= ($currentPage ?? '') === 'subscribers' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                        <span>Subscribers</span>
                        <?php
                        $subCount = \App\Models\Subscriber::activeCount();
                        if ($subCount > 0): ?>
                            <span class="nav-badge"><?= (int) $subCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('messages.manage')): ?>
                <li class="nav-item">
                    <a href="<?= url('admin/messages') ?>" class="nav-link<?= ($currentPage ?? '') === 'messages' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                        <span>Messages</span>
                        <?php
                        $unreadMsgCount = \App\Models\Message::unreadCount();
                        if ($unreadMsgCount > 0): ?>
                            <span class="nav-badge"><?= (int) $unreadMsgCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                <?php if (\App\Core\Auth::can('ads.manage') || \App\Core\Auth::can('settings.manage') || \App\Core\Auth::can('users.manage') || \App\Core\Auth::can('redirects.manage') || \App\Core\Auth::can('permissions.manage')): ?>
                <li class="nav-section">Manage</li>
                <?php if (\App\Core\Auth::can('ads.manage')): ?>
                <li class="nav-item">
                    <a href="<?= url('admin/ads') ?>" class="nav-link<?= ($currentPage ?? '') === 'ads' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>
                        <span>Ads</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php $settingsActive = in_array((string) ($currentPage ?? ''), ['settings', 'users', 'redirects', 'permissions'], true); $settingsTab = ($currentPage ?? '') === 'settings' ? (string) ($_GET['tab'] ?? 'general') : ''; ?>
                <?php if (\App\Core\Auth::can('settings.manage') || \App\Core\Auth::can('users.manage') || \App\Core\Auth::can('redirects.manage') || \App\Core\Auth::can('permissions.manage')): ?>
                <li class="nav-item settings-group<?= $settingsActive ? ' open' : '' ?>">
                    <button type="button" class="nav-link settings-toggle" aria-expanded="<?= $settingsActive ? 'true' : 'false' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        <span>Settings</span>
                        <svg class="settings-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>
                    <ul class="settings-sub">
                        <?php if (\App\Core\Auth::can('settings.manage') || \App\Core\Auth::can('users.manage')): ?>
                        <?php if (\App\Core\Auth::can('settings.manage')): ?>
                        <li><a href="<?= url('admin/settings?tab=general') ?>" class="nav-link<?= $settingsTab === 'general' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg><span>General</span></a></li>
                        <li><a href="<?= url('admin/settings?tab=social') ?>" class="nav-link<?= $settingsTab === 'social' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg><span>Social</span></a></li>
                        <li><a href="<?= url('admin/settings?tab=seo') ?>" class="nav-link<?= $settingsTab === 'seo' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><span>SEO</span></a></li>
                        <li><a href="<?= url('admin/settings?tab=advanced') ?>" class="nav-link<?= $settingsTab === 'advanced' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg><span>Advanced</span></a></li>
                        <li><a href="<?= url('admin/settings?tab=security') ?>" class="nav-link<?= $settingsTab === 'security' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><span>Security</span></a></li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('users.manage')): ?>
                        <li>
                    <a href="<?= url('admin/users') ?>" class="nav-link<?= ($currentPage ?? '') === 'users' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                        <span>Users</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('redirects.manage')): ?>
                        <li>
                    <a href="<?= url('admin/redirects') ?>" class="nav-link<?= ($currentPage ?? '') === 'redirects' ? ' active' : '' ?>">
                        <svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 14 20 9 15 4"></polyline>
                            <path d="M4 20v-7a4 4 0 0 1 4-4h12"></path>
                        </svg>
                        <span>Redirects</span>
                    </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\App\Core\Auth::can('permissions.manage')): ?>
                        <li><a href="<?= url('admin/permissions') ?>" class="nav-link<?= ($currentPage ?? '') === 'permissions' ? ' active' : '' ?>"><svg class="nav-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><span>Permissions</span></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="user-avatar">
                    <?= strtoupper(mb_substr(h(\App\Core\Session::get('user_name', 'U')), 0, 1)) ?>
                </div>
                <div class="user-info">
                    <span class="user-name"><?= h(\App\Core\Session::get('user_name', 'User')) ?></span>
                    <span class="user-role"><?= h(ucfirst(str_replace('_', ' ', \App\Core\Session::get('user_role', '')))) ?></span>
                </div>
            </div>
            <form method="POST" action="<?= url('admin/logout') ?>" class="logout-form">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="logout-btn" title="Sign out">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">

        <!-- Top Header Bar -->
        <header class="content-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="page-title"><?= h($pageTitle ?? $title ?? 'Dashboard') ?></h1>
            </div>
            <div class="header-right">
                <a href="<?= url('/') ?>" class="btn-view-site" target="_blank" title="View Website">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>
                        <polyline points="15 3 21 3 21 9"/>
                        <line x1="10" y1="14" x2="21" y2="3"/>
                    </svg>
                    <span>View Website</span>
                </a>
                <span class="header-user"><?= h(\App\Core\Session::get('user_name', '')) ?></span>
            </div>
        </header>

        <!-- Flash Messages -->
        <?php $flashSuccess = \App\Core\Session::getFlash('success'); ?>
        <?php $flashError = \App\Core\Session::getFlash('error'); ?>

        <?php if ($flashSuccess): ?>
        <div class="flash-message flash-success">
            <span class="flash-text"><?= h($flashSuccess) ?></span>
            <button type="button" class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
        <div class="flash-message flash-error">
            <span class="flash-text"><?= h($flashError) ?></span>
            <button type="button" class="flash-close" onclick="this.parentElement.remove()">&times;</button>
        </div>
        <?php endif; ?>

        <!-- Page Content -->
        <div class="content-body">
            <?= $content ?>
        </div>

    </main>

    <script src="<?= url('assets/js/admin.js') ?>"></script>
    <script>
    // Settings sidebar group: collapsible; open by default (same behaviour
    // as the Content group), state persisted per browser.
    (function () {
        var group = document.querySelector('.settings-group');
        if (!group) return;
        var toggle = group.querySelector('.settings-toggle');
        var onSettingsPage = group.querySelector('.settings-sub .nav-link.active') !== null;
        if (onSettingsPage) {
            group.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            try {
                if (window.localStorage.getItem('settingsGroupOpen') === '0') {
                    group.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    group.classList.add('open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            } catch (e) { /* storage unavailable — server-rendered state stands */ }
        }
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('settingsGroupOpen', open ? '1' : '0'); } catch (e) {}
            if (open) {
                setTimeout(function () {
                    group.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }, 60);
            }
        });
    })();

    // Content sidebar group: collapsible; open by default, state persisted per browser.
    (function () {
        var group = document.querySelector('.content-group');
        if (!group) return;
        var toggle = group.querySelector('.content-toggle');
        var onContentPage = group.querySelector('.content-sub .nav-link.active') !== null;
        if (onContentPage) {
            group.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
        } else {
            try {
                if (window.localStorage.getItem('contentGroupOpen') === '0') {
                    group.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                } else {
                    group.classList.add('open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            } catch (e) { /* storage unavailable — server-rendered state stands */ }
        }
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('contentGroupOpen', open ? '1' : '0'); } catch (e) {}
        });
    })();

    // Plugins sidebar group: collapsible, state persisted per browser.
    (function () {
        var group = document.querySelector('.plugins-group');
        if (!group) return;
        var toggle = group.querySelector('.plugins-toggle');
        var onPage = group.querySelector('.plugins-sub .nav-link.active') !== null;
        try {
            if (!onPage && window.localStorage.getItem('pluginsGroupOpen') === '1') {
                group.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } catch (e) {}
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('pluginsGroupOpen', open ? '1' : '0'); } catch (e) {}
        });
    })();

    // Games sidebar group: collapsible, state persisted per browser.
    (function () {
        var group = document.querySelector('.games-group');
        if (!group) return;
        var toggle = group.querySelector('.games-toggle');
        var onPage = group.querySelector('.games-sub .nav-link.active') !== null;
        try {
            if (!onPage && window.localStorage.getItem('gamesGroupOpen') === '1') {
                group.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } catch (e) {}
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('gamesGroupOpen', open ? '1' : '0'); } catch (e) {}
        });
    })();

    // My Store sidebar group: collapsible, state persisted per browser.
    (function () {
        var group = document.querySelector('.store-group');
        if (!group) return;
        var toggle = group.querySelector('.store-toggle');
        var onStorePage = group.querySelector('.store-sub .nav-link.active') !== null;
        try {
            if (!onStorePage && window.localStorage.getItem('storeGroupOpen') === '1') {
                group.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } catch (e) { /* storage unavailable — server-rendered state stands */ }
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('storeGroupOpen', open ? '1' : '0'); } catch (e) {}
        });
    })();

    // Students sidebar group: collapsible, state persisted per browser.
    (function () {
        var group = document.querySelector('.students-group');
        if (!group) return;
        var toggle = group.querySelector('.students-toggle');
        var onStudentsPage = group.querySelector('.students-sub .nav-link.active') !== null;
        try {
            if (!onStudentsPage && window.localStorage.getItem('studentsGroupOpen') === '1') {
                group.classList.add('open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        } catch (e) { /* storage unavailable — server-rendered state stands */ }
        toggle.addEventListener('click', function () {
            var open = group.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            try { window.localStorage.setItem('studentsGroupOpen', open ? '1' : '0'); } catch (e) {}
        });
    })();
    </script>
</body>
</html>