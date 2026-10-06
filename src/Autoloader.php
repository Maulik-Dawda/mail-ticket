<?php

// Check if Composer vendor autoloader exists
$vendorPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];

$loaded = false;
foreach ($vendorPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $loaded = true;
        break;
    }
}

// Fallback PSR-4 autoloader if composer vendor directory is missing on server
if (!$loaded) {
    spl_autoload_register(function ($class) {
        $prefix = 'MailTicket\\';
        $baseDir = __DIR__ . '/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    });
}
