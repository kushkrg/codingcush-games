<?php
// Copy this file to config.php and fill in the database details from hPanel > Databases.
// config.php is blocked from the web by .htaccess; never share it.
return [
    'dsn' => 'mysql:host=localhost;dbname=YOUR_DATABASE_NAME;charset=utf8mb4',
    'user' => 'YOUR_DATABASE_USER',
    'password' => 'YOUR_DATABASE_PASSWORD',
];
