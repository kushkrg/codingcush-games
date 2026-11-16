<?php

declare(strict_types=1);

use App\Controllers\Frontend\HomeController;
use App\Controllers\Frontend\PostController;
use App\Controllers\Frontend\PageController;
use App\Controllers\Frontend\CategoryController;
use App\Controllers\Frontend\TagController;
use App\Controllers\Frontend\AuthorController;
use App\Controllers\Frontend\SearchController;
use App\Controllers\Frontend\ArchiveController;
use App\Controllers\Frontend\CommentController;
use App\Controllers\Frontend\FeedController;
use App\Controllers\Frontend\SitemapController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\PostController as AdminPostController;
use App\Controllers\Admin\PageController as AdminPageController;
use App\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Controllers\Admin\TagController as AdminTagController;
use App\Controllers\Admin\CommentController as AdminCommentController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\SettingController;
use App\Controllers\Admin\PermissionController;
use App\Controllers\Admin\RedirectController;
use App\Controllers\Admin\ToolController;
use App\Controllers\Admin\AdController;
use App\Controllers\Admin\MenuController;
use App\Controllers\Admin\MessageController as AdminMessageController;
use App\Controllers\Admin\SubscriberController as AdminSubscriberController;
use App\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Controllers\Admin\StoreController as AdminStoreController;
use App\Controllers\Api\StoreApiController;
use App\Controllers\Frontend\SubscribeController;
use App\Controllers\Frontend\StudentController;
use App\Controllers\Admin\StudentController as AdminStudentController;
use App\Controllers\Frontend\ContactController;
use App\Controllers\Frontend\PluginController;
use App\Controllers\Frontend\WebflowController;
use App\Controllers\Admin\WebflowAdminController;
use App\Controllers\Admin\PluginController as AdminPluginController;
use App\Controllers\Admin\GamesController as AdminGamesController;

/**
 * Route definitions for the News/Blog CMS.
 *
 * @var App\Core\Router $router
 */

// ============================================================
//  Frontend Routes
// ============================================================

// Home / Pagination
$router->get('/', [HomeController::class, 'index']);
$router->get('/page/{page}', [HomeController::class, 'index']);

// Single article (clean URL: /slug instead of /article/slug)

// Category listing
$router->get('/category/{slug}', [CategoryController::class, 'index']);
$router->get('/category/{slug}/page/{page}', [CategoryController::class, 'index']);

// Tag listing
$router->get('/tag/{slug}', [TagController::class, 'index']);
$router->get('/tag/{slug}/page/{page}', [TagController::class, 'index']);

// Author listing
$router->get('/author/{slug}', [AuthorController::class, 'index']);
$router->get('/author/{slug}/page/{page}', [AuthorController::class, 'index']);

// Search
$router->get('/search', [SearchController::class, 'index']);

// Archive
$router->get('/archive', [ArchiveController::class, 'index']);
$router->get('/archive/{year}', [ArchiveController::class, 'index']);
$router->get('/archive/{year}/{month}', [ArchiveController::class, 'index']);

// Comments
$router->post('/comments/store', [CommentController::class, 'store']);

// Newsletter subscription
$router->post('/newsletter/subscribe', [SubscribeController::class, 'store']);
$router->get('/newsletter/unsubscribe', [SubscribeController::class, 'unsubscribe']);

// Contact form
$router->get('/contact', [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'submit']);

// RSS feeds
$router->get('/feed', [FeedController::class, 'rss']);
$router->get('/feed/category/{slug}', [FeedController::class, 'category']);

// Sitemaps & robots
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
$router->get('/post-sitemap.xml', [SitemapController::class, 'posts']);
$router->get('/category-sitemap.xml', [SitemapController::class, 'categories']);
$router->get('/page-sitemap.xml', [SitemapController::class, 'pages']);
$router->get('/robots.txt', [SitemapController::class, 'robots']);

// ============================================================
//  Admin Routes
// ============================================================

// Authentication
$router->get('/admin/login', [AuthController::class, 'loginForm']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/admin', [DashboardController::class, 'index']);
$router->get('/admin/analytics', [AdminAnalyticsController::class, 'index']);

// Posts
$router->get('/admin/posts', [AdminPostController::class, 'index']);
$router->get('/admin/posts/create', [AdminPostController::class, 'create']);
$router->post('/admin/posts/store', [AdminPostController::class, 'store']);
$router->get('/admin/posts/{id}/edit', [AdminPostController::class, 'edit']);
$router->post('/admin/posts/{id}/update', [AdminPostController::class, 'update']);
$router->post('/admin/posts/{id}/delete', [AdminPostController::class, 'delete']);

// Pages
$router->get('/admin/pages', [AdminPageController::class, 'index']);
$router->get('/admin/pages/create', [AdminPageController::class, 'create']);
$router->post('/admin/pages/store', [AdminPageController::class, 'store']);
$router->get('/admin/pages/{id}/edit', [AdminPageController::class, 'edit']);
$router->post('/admin/pages/{id}/update', [AdminPageController::class, 'update']);
$router->post('/admin/pages/{id}/delete', [AdminPageController::class, 'delete']);

// Categories
$router->get('/admin/categories', [AdminCategoryController::class, 'index']);
$router->post('/admin/categories/store', [AdminCategoryController::class, 'store']);
$router->get('/admin/categories/{id}/edit', [AdminCategoryController::class, 'edit']);
$router->post('/admin/categories/{id}/update', [AdminCategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [AdminCategoryController::class, 'delete']);

// Tags
$router->get('/admin/tags', [AdminTagController::class, 'index']);
$router->post('/admin/tags/store', [AdminTagController::class, 'store']);
$router->post('/admin/tags/{id}/delete', [AdminTagController::class, 'delete']);

// Comments
$router->get('/admin/comments', [AdminCommentController::class, 'index']);
$router->post('/admin/comments/{id}/approve', [AdminCommentController::class, 'approve']);
$router->post('/admin/comments/{id}/spam', [AdminCommentController::class, 'spam']);
$router->post('/admin/comments/{id}/delete', [AdminCommentController::class, 'delete']);

// Media
$router->get('/admin/media', [MediaController::class, 'index']);
$router->get('/admin/media/json', [MediaController::class, 'json']);
$router->post('/admin/media/upload', [MediaController::class, 'upload']);
$router->post('/admin/media/{id}/delete', [MediaController::class, 'delete']);
$router->post('/admin/media/{id}/update', [MediaController::class, 'update']);

// Users
$router->get('/admin/users', [UserController::class, 'index']);
$router->get('/admin/users/create', [UserController::class, 'create']);
$router->post('/admin/users/store', [UserController::class, 'store']);
$router->get('/admin/users/{id}/edit', [UserController::class, 'edit']);
$router->post('/admin/users/{id}/update', [UserController::class, 'update']);
$router->post('/admin/users/{id}/delete', [UserController::class, 'delete']);

// Settings
$router->get('/admin/settings', [SettingController::class, 'index']);
$router->post('/admin/settings/save', [SettingController::class, 'save']);

// Role permissions (super admin only, enforced in controller)
$router->get('/admin/permissions', [PermissionController::class, 'index']);
$router->post('/admin/permissions/save', [PermissionController::class, 'save']);
$router->post('/admin/permissions/reset', [PermissionController::class, 'reset']);

// Redirects
$router->get('/admin/redirects', [RedirectController::class, 'index']);
$router->post('/admin/redirects/store', [RedirectController::class, 'store']);
$router->post('/admin/redirects/{id}/delete', [RedirectController::class, 'delete']);

// Tools
$router->get('/admin/tools', [ToolController::class, 'index']);
$router->post('/admin/tools/store', [ToolController::class, 'store']);
$router->post('/admin/tools/{id}/update', [ToolController::class, 'update']);
$router->post('/admin/tools/{id}/toggle', [ToolController::class, 'toggle']);
$router->post('/admin/tools/{id}/delete', [ToolController::class, 'delete']);

// Menus
$router->get('/admin/menus', [MenuController::class, 'index']);
$router->post('/admin/menus/store', [MenuController::class, 'store']);
$router->get('/admin/menus/{id}/edit', [MenuController::class, 'edit']);
$router->post('/admin/menus/{id}/update', [MenuController::class, 'update']);
$router->post('/admin/menus/{id}/delete', [MenuController::class, 'delete']);
$router->post('/admin/menus/reorder', [MenuController::class, 'reorder']);

// Ads
$router->get('/admin/ads', [AdController::class, 'index']);
$router->post('/admin/ads/save', [AdController::class, 'save']);

// Messages
$router->get('/admin/messages', [AdminMessageController::class, 'index']);
$router->get('/admin/messages/{id}', [AdminMessageController::class, 'show']);
$router->post('/admin/messages/{id}/reply', [AdminMessageController::class, 'reply']);
$router->post('/admin/messages/{id}/delete', [AdminMessageController::class, 'delete']);

// Subscribers
$router->get('/admin/subscribers', [AdminSubscriberController::class, 'index']);
$router->get('/admin/subscribers/compose', [AdminSubscriberController::class, 'compose']);
$router->post('/admin/subscribers/send', [AdminSubscriberController::class, 'send']);
$router->get('/admin/subscribers/email-config', [AdminSubscriberController::class, 'emailConfig']);
$router->post('/admin/subscribers/email-config/save', [AdminSubscriberController::class, 'saveEmailConfig']);
$router->post('/admin/subscribers/email-config/test', [AdminSubscriberController::class, 'testEmailConfig']);
$router->post('/admin/subscribers/popup-settings', [AdminSubscriberController::class, 'savePopupSettings']);
$router->get('/admin/subscribers/import', [AdminSubscriberController::class, 'import']);
$router->post('/admin/subscribers/import', [AdminSubscriberController::class, 'processImport']);
$router->get('/admin/subscribers/{id}/edit', [AdminSubscriberController::class, 'edit']);
$router->post('/admin/subscribers/{id}/update', [AdminSubscriberController::class, 'update']);
$router->post('/admin/subscribers/{id}/delete', [AdminSubscriberController::class, 'delete']);

// ============================================================
//  My Store — Admin (super admin only, enforced in controller)
// ============================================================

$router->get('/admin/store', [AdminStoreController::class, 'index']);

// Store products
$router->get('/admin/store/products', [AdminStoreController::class, 'products']);
$router->get('/admin/store/products/create', [AdminStoreController::class, 'productCreate']);
$router->post('/admin/store/products/store', [AdminStoreController::class, 'productStore']);
$router->get('/admin/store/products/{id}/edit', [AdminStoreController::class, 'productEdit']);
$router->post('/admin/store/products/{id}/update', [AdminStoreController::class, 'productUpdate']);
$router->post('/admin/store/products/{id}/delete', [AdminStoreController::class, 'productDelete']);

// Store orders
$router->get('/admin/store/orders', [AdminStoreController::class, 'orders']);
$router->get('/admin/store/orders/{id}', [AdminStoreController::class, 'orderShow']);
$router->post('/admin/store/orders/{id}/status', [AdminStoreController::class, 'orderUpdateStatus']);

// Store reviews
$router->get('/admin/store/reviews', [AdminStoreController::class, 'reviews']);
$router->post('/admin/store/reviews', [AdminStoreController::class, 'reviewStore']);
$router->post('/admin/store/reviews/{id}/approve', [AdminStoreController::class, 'reviewApprove']);
$router->post('/admin/store/reviews/{id}/delete', [AdminStoreController::class, 'reviewDelete']);

// Store payments (dynamic gateways)
$router->get('/admin/store/payments', [AdminStoreController::class, 'payments']);
$router->post('/admin/store/payments/save', [AdminStoreController::class, 'paymentsSave']);

// Store coupons
$router->get('/admin/store/coupons', [AdminStoreController::class, 'coupons']);
$router->post('/admin/store/coupons/store', [AdminStoreController::class, 'couponStore']);
$router->post('/admin/store/coupons/{id}/delete', [AdminStoreController::class, 'couponDelete']);

// Store settings
$router->get('/admin/store/settings', [AdminStoreController::class, 'settings']);
$router->post('/admin/store/settings/save', [AdminStoreController::class, 'settingsSave']);

// ============================================================
//  Store API (public JSON for shop.codingcush.com)
// ============================================================

$router->get('/api/store/products', [StoreApiController::class, 'products']);
$router->post('/api/store/order', [StoreApiController::class, 'order']);
$router->get('/api/store/reviews', [StoreApiController::class, 'reviews']);
$router->post('/api/store/subscribe', [StoreApiController::class, 'subscribe']);
$router->post('/api/store/review', [StoreApiController::class, 'submitReview']);
$router->post('/api/store/verify', [StoreApiController::class, 'verifyPayment']);
$router->get('/api/store/download', [StoreApiController::class, 'download']);


// ============================================================
//  Students (courses, assignments, certification exam)
// ============================================================

$router->get('/students', [StudentController::class, 'home']);
$router->get('/students/register', [StudentController::class, 'registerForm']);
$router->post('/students/register', [StudentController::class, 'register']);
$router->get('/students/login', [StudentController::class, 'loginForm']);
$router->post('/students/login', [StudentController::class, 'login']);
$router->post('/students/logout', [StudentController::class, 'logout']);
$router->get('/students/dashboard', [StudentController::class, 'dashboard']);
$router->get('/students/assignments', [StudentController::class, 'assignments']);
$router->post('/students/assignments/{id}/submit', [StudentController::class, 'submitAssignment']);
$router->get('/students/certification', [StudentController::class, 'certification']);
$router->post('/students/certification/apply', [StudentController::class, 'applyCertification']);
$router->post('/students/exam/start', [StudentController::class, 'startExam']);
$router->get('/students/exam/{id}', [StudentController::class, 'exam']);
$router->post('/students/exam/{id}/submit', [StudentController::class, 'submitExam']);
$router->get('/students/certificate/{code}', [StudentController::class, 'certificate']);
$router->get('/students/certificate/{code}/qr', [StudentController::class, 'certificateQr']);
$router->get('/students/leaderboard', [StudentController::class, 'leaderboard']);
$router->get('/students/syllabus', [StudentController::class, 'syllabus']);
$router->get('/students/syllabus/download/{id}', [StudentController::class, 'syllabusDownload']);
$router->get('/students/learn', [StudentController::class, 'learn']);
$router->get('/students/watch/{id}', [StudentController::class, 'watch']);
$router->post('/students/watch/{id}/watched', [StudentController::class, 'markWatched']);
$router->post('/students/watch/{id}/comment', [StudentController::class, 'commentPost']);
$router->post('/students/comments/{id}/delete', [StudentController::class, 'commentDelete']);
$router->post('/students/checkin', [StudentController::class, 'checkin']);
$router->post('/students/streak/recover', [StudentController::class, 'streakRecover']);
$router->post('/students/streak/drop', [StudentController::class, 'streakDrop']);
$router->get('/students/profile', [StudentController::class, 'profile']);
$router->post('/students/profile', [StudentController::class, 'profileUpdate']);
$router->post('/students/syllabus/track', [StudentController::class, 'syllabusTrack']);
$router->post('/students/syllabus/lead', [StudentController::class, 'syllabusLead']);
$router->get('/verify-certificate', [StudentController::class, 'verify']);

// Admin — Students module
$router->get('/admin/students', [AdminStudentController::class, 'index']);
$router->get('/admin/students/courses', [AdminStudentController::class, 'courses']);
$router->get('/admin/students/courses/{id}', [AdminStudentController::class, 'courseRoster']);
$router->post('/admin/students/courses/store', [AdminStudentController::class, 'courseStore']);
$router->post('/admin/students/courses/{id}/update', [AdminStudentController::class, 'courseUpdate']);
$router->post('/admin/students/courses/{id}/delete', [AdminStudentController::class, 'courseDelete']);
$router->get('/admin/students/syllabus', [AdminStudentController::class, 'syllabus']);
$router->get('/admin/students/lessons', [AdminStudentController::class, 'lessons']);
$router->post('/admin/students/lessons/store', [AdminStudentController::class, 'lessonStore']);
$router->post('/admin/students/lessons/import', [AdminStudentController::class, 'lessonImport']);
$router->post('/admin/students/lessons/{id}/update', [AdminStudentController::class, 'lessonUpdate']);
$router->post('/admin/students/lessons/{id}/delete', [AdminStudentController::class, 'lessonDelete']);
$router->post('/admin/students/comments/{id}/delete', [AdminStudentController::class, 'commentDeleteAdmin']);
$router->post('/admin/students/syllabus/store', [AdminStudentController::class, 'syllabusStore']);
$router->post('/admin/students/syllabus/{id}/update', [AdminStudentController::class, 'syllabusUpdate']);
$router->post('/admin/students/syllabus/{id}/delete', [AdminStudentController::class, 'syllabusDelete']);
$router->get('/admin/students/assignments', [AdminStudentController::class, 'assignments']);
$router->post('/admin/students/assignments/store', [AdminStudentController::class, 'taskStore']);
$router->post('/admin/students/assignments/{id}/update', [AdminStudentController::class, 'taskUpdate']);
$router->post('/admin/students/assignments/{id}/delete', [AdminStudentController::class, 'taskDelete']);
$router->get('/admin/students/submissions', [AdminStudentController::class, 'submissions']);
$router->post('/admin/students/submissions/{id}/review', [AdminStudentController::class, 'submissionReview']);
$router->get('/admin/students/applications', [AdminStudentController::class, 'applications']);
$router->post('/admin/students/applications/{id}/decide', [AdminStudentController::class, 'applicationDecide']);
$router->get('/admin/students/questions', [AdminStudentController::class, 'questions']);
$router->post('/admin/students/questions/store', [AdminStudentController::class, 'questionStore']);
$router->post('/admin/students/questions/{id}/update', [AdminStudentController::class, 'questionUpdate']);
$router->post('/admin/students/questions/{id}/delete', [AdminStudentController::class, 'questionDelete']);
$router->get('/admin/students/certificates', [AdminStudentController::class, 'certificates']);
$router->post('/admin/students/certificates/{id}/status', [AdminStudentController::class, 'certificateStatus']);
// Webflow course tracking (must be before /admin/students/{id})
$router->get('/admin/students/webflow', [WebflowAdminController::class, 'index']);
$router->get('/admin/students/webflow/export', [WebflowAdminController::class, 'export']);
$router->get('/admin/students/webflow/{id}', [WebflowAdminController::class, 'show']);
$router->get('/admin/students/{id}', [AdminStudentController::class, 'show']);
$router->post('/admin/students/{id}/status', [AdminStudentController::class, 'studentStatus']);
$router->post('/admin/students/{id}/enroll', [AdminStudentController::class, 'enrollStudent']);
$router->post('/admin/students/{id}/delete', [AdminStudentController::class, 'studentDelete']);

// ============================================================
//  WordPress plugins (public hub + admin module)
// ============================================================

// Webflow course cheat sheets
$router->get('/webflow', [WebflowController::class, 'index']);
$router->get('/webflow-sitemap.xml', [WebflowController::class, 'sitemap']);
$router->post('/webflow/join', [WebflowController::class, 'join']);
$router->post('/webflow/progress', [WebflowController::class, 'progress']);
$router->post('/webflow/leave', [WebflowController::class, 'leave']);
$router->get('/webflow/{key}', [WebflowController::class, 'show']);

$router->get('/plugins', [PluginController::class, 'index']);
$router->get('/plugin-sitemap.xml', [PluginController::class, 'sitemap']);
$router->get('/plugins/{slug}', [PluginController::class, 'show']);
$router->get('/plugins/{slug}/download', [PluginController::class, 'download']);
$router->get('/plugins/{slug}/go/{target}', [PluginController::class, 'go']);
$router->post('/plugins/{slug}/notify', [PluginController::class, 'notify']);

$router->get('/admin/plugins', [AdminPluginController::class, 'index']);
$router->get('/admin/plugins/list', [AdminPluginController::class, 'list']);
$router->get('/admin/plugins/create', [AdminPluginController::class, 'create']);
$router->post('/admin/plugins/store', [AdminPluginController::class, 'store']);
$router->post('/admin/plugins/sync', [AdminPluginController::class, 'sync']);
$router->get('/admin/plugins/export', [AdminPluginController::class, 'export']);
$router->get('/admin/plugins/{id}/edit', [AdminPluginController::class, 'edit']);
$router->post('/admin/plugins/{id}/update', [AdminPluginController::class, 'update']);
$router->post('/admin/plugins/{id}/delete', [AdminPluginController::class, 'delete']);
$router->get('/admin/plugins/{id}/stats', [AdminPluginController::class, 'stats']);

// ============================================================
//  Post / Page Catch-All (MUST be registered last)
//  Tries to resolve as a post first, then falls back to a static page.
// ============================================================

$router->get('/{slug}', [PostController::class, 'show']);

// ============================================================
//  CodingCush Games (games.codingcush.com) — admin module
// ============================================================
$router->get('/admin/games', [AdminGamesController::class, 'index']);
$router->get('/admin/games/list', [AdminGamesController::class, 'list']);
$router->get('/admin/games/analytics', [AdminGamesController::class, 'analytics']);
$router->get('/admin/games/analytics/export', [AdminGamesController::class, 'exportAnalytics']);
$router->get('/admin/games/players', [AdminGamesController::class, 'players']);
$router->get('/admin/games/players/export', [AdminGamesController::class, 'exportPlayers']);
$router->get('/admin/games/players/{id}', [AdminGamesController::class, 'player']);
$router->post('/admin/games/players/{id}/action', [AdminGamesController::class, 'playerAction']);
$router->get('/admin/games/leaderboards', [AdminGamesController::class, 'leaderboards']);
$router->post('/admin/games/leaderboards/remove', [AdminGamesController::class, 'removeScore']);
$router->get('/admin/games/pages', [AdminGamesController::class, 'pages']);
$router->post('/admin/games/pages', [AdminGamesController::class, 'savePages']);
$router->get('/admin/games/settings', [AdminGamesController::class, 'settings']);
$router->post('/admin/games/settings', [AdminGamesController::class, 'saveSettings']);
$router->get('/admin/games/errors', [AdminGamesController::class, 'errors']);
$router->get('/admin/games/backups', [AdminGamesController::class, 'backups']);
$router->post('/admin/games/backups', [AdminGamesController::class, 'backupNow']);
$router->get('/admin/games/backups/{name}', [AdminGamesController::class, 'downloadBackup']);
$router->post('/admin/games/source', [AdminGamesController::class, 'switchSource']);
