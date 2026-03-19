<?php

$host = getenv('MYSQL_HOST') ?: 'localhost';
$dbname = getenv('MYSQL_DATABASE') ?: 'yii2basic';
$user = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD');
if ($password === false) {
    $password = '';
}

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=' . $host . ';dbname=' . $dbname,
    'username' => $user,
    'password' => $password,
    'charset' => 'utf8mb4',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
