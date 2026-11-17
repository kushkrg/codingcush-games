<?php
// Bridge for /typing-test/ — renders the typing test through the main layout,
// so the header, footer, menus, ads and analytics come live from the site,
// exactly like the pages under /tools/. The page body lives in
// views/frontend/tools/typing-test.php.
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\SEO;
use App\Core\Session;
use App\Core\View;
use App\Models\Setting;

Session::start();

$siteName = Setting::get('site_name', 'CodingCush');
$description = 'Free online typing test with live WPM, accuracy, a speed graph and missed-key analysis. Beginner to advanced levels plus a code typing test. No sign-up.';
$websiteSchema = SEO::websiteSchema();
unset($websiteSchema['@context']);
$graph = [
    $websiteSchema,
    [
        '@type'  => 'Organization',
        'name'   => $siteName,
        'url'    => url('/'),
        'logo'   => 'https://codingcush.com/uploads/media/original/img_6a09eac8e0dde9.06355244_3af3f487.png',
        'sameAs' => ['https://www.youtube.com/@CodingCush'],
    ],
    [
        '@type'               => 'WebApplication',
        'name'                => 'CodingCush Typing Test',
        'url'                 => 'https://codingcush.com/typing-test/',
        'applicationCategory' => 'EducationalApplication',
        'operatingSystem'     => 'Any',
        'offers'              => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
        'description'         => $description,
    ],
];

$seo = new SEO();
$seo->setTitle('Typing Test Online (Free) — Check Your WPM Speed & Accuracy — CodingCush', false)
    ->setDescription($description)
    ->setCanonical('https://codingcush.com/typing-test/')
    ->setImage('https://codingcush.com/typing-test/og-typing-test.png')
    ->setStructuredData([
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ]);

$view = new View();
$view->setLayout('layouts/main');
echo $view->render('frontend/tools/typing-test', ['seo' => $seo]);
