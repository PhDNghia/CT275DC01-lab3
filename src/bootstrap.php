<?php

spl_autoload_register(function ($class) {
    $prefix = 'CT275\\Labs\\';
    $base_dir = __DIR__ . '/classes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once __DIR__ . '/functions.php';

try {
    $host = 'localhost';
    $port = '5432';
    $dbname = 'ct275_lab3';
    $user = 'postgres';
    $password = '1029384756';

    $PDO = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password);
    $PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $PDO->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    exit('Cannot connect to database: ' . $e->getMessage());
}
