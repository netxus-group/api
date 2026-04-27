<?php

/**
 * CodeIgniter 4 - Front Controller
 * Netxus API
 */

// Valid PHP Version?
$minPhpVersion = '8.1';
if (version_compare(PHP_VERSION, $minPhpVersion, '<')) {
    $message = sprintf(
        'Your PHP version must be %s or higher to run CodeIgniter. Current version: %s',
        $minPhpVersion,
        PHP_VERSION
    );
    header('HTTP/1.1 503 Service Unavailable', true, 503);
    echo $message;
    exit(1);
}

if (getenv('CI_ENVIRONMENT') === false && ! isset($_SERVER['CI_ENVIRONMENT'])) {
    putenv('CI_ENVIRONMENT=production');
    $_ENV['CI_ENVIRONMENT'] = 'production';
    $_SERVER['CI_ENVIRONMENT'] = 'production';
}

ini_set('expose_php', '0');
if (function_exists('header_remove')) {
    header_remove('X-Powered-By');
}

// Path to the front controller
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);

// Ensure the current directory is pointing to the front controller's directory
if (getcwd() . DIRECTORY_SEPARATOR !== FCPATH) {
    chdir(FCPATH);
}

// Load Paths config
$pathsConfig = FCPATH . '../app/Config/Paths.php';

require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();

// Location of the framework bootstrap file
require rtrim($paths->systemDirectory, '\\/ ') . DIRECTORY_SEPARATOR . 'Boot.php';

exit(\CodeIgniter\Boot::bootWeb($paths));
