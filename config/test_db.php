<?php

$db = require __DIR__ . '/db.php';

$host = getenv('MYSQL_HOST') ?: 'localhost';
$testDbName = getenv('MYSQL_TEST_DATABASE');
if ($testDbName === false || $testDbName === '') {
    $base = getenv('MYSQL_DATABASE') ?: 'yii2basic';
    $testDbName = $base . '_test';
}

$db['dsn'] = 'mysql:host=' . $host . ';dbname=' . $testDbName;

return $db;
