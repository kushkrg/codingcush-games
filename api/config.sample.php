<?php
// Optional settings. Copy this file to config.php (same folder) and keep only what you need.
// config.php is blocked from the web by .htaccess and ignored by git; never share it.
return [
    // --- Sign in with Google / Facebook -------------------------------------------------
    // Redirect URI to register with both providers:  https://games.codingcush.com/api/oauth.php
    // Google:   console.cloud.google.com > APIs & Services > Credentials > OAuth client ID (Web application)
    // Facebook: developers.facebook.com > your app > Facebook Login > Settings (ask for the "email" permission)
    'google' => ['id' => 'YOUR_GOOGLE_CLIENT_ID', 'secret' => 'YOUR_GOOGLE_CLIENT_SECRET'],
    'facebook' => ['id' => 'YOUR_FACEBOOK_APP_ID', 'secret' => 'YOUR_FACEBOOK_APP_SECRET'],

    // --- Database (optional) ------------------------------------------------------------
    // Leave these out to keep using the built-in SQLite file. To use MySQL instead, run
    // schema.sql in phpMyAdmin and fill in the details from hPanel > Databases.
    // 'dsn' => 'mysql:host=localhost;dbname=YOUR_DATABASE_NAME;charset=utf8mb4',
    // 'user' => 'YOUR_DATABASE_USER',
    // 'password' => 'YOUR_DATABASE_PASSWORD',
];
