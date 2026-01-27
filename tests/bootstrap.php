<?php

/**
 * Bootstrap for module tests.
 *
 * @phpcs:disable PSR1.Files.SideEffects
 */

declare(strict_types=1);

// Detect shop root - works for both:
// 1. Local ARCHIVE setup: /var/www/ARCHIVE/media-library-module/tests -> /var/www
// 2. Composer-installed: /var/www/vendor/oxid-esales/media-library-module/tests -> /var/www
// 3. GitHub Actions (symlinked): /var/www/tests -> /var/www

$possibleShopRoots = [
    dirname(__DIR__, 3),                    // ARCHIVE setup
    dirname(__DIR__, 4),                    // Composer vendor setup
    dirname(__DIR__),                       // Tests symlinked to shop root
    getenv('SHOP_ROOT_PATH') ?: null,       // Environment variable
];

$shopRoot = null;
foreach ($possibleShopRoots as $path) {
    if ($path && is_file($path . '/vendor/autoload.php') && is_file($path . '/source/bootstrap.php')) {
        $shopRoot = $path;
        break;
    }
}

if (!$shopRoot) {
    // Fallback: just use module autoloader for unit tests
    require dirname(__DIR__) . '/vendor/autoload.php';
    return;
}

// Override the project root detection
define('INSTALLATION_ROOT_PATH', $shopRoot);
const VENDOR_PATH = INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
define('OX_BASE_PATH', INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR);

// Load shop autoloader
require VENDOR_PATH . 'autoload.php';

// Load module autoloader if it exists separately
$moduleAutoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($moduleAutoloader) && realpath($moduleAutoloader) !== realpath(VENDOR_PATH . 'autoload.php')) {
    require $moduleAutoloader;
}

use OxidEsales\EshopCommunity\Core\Autoload\BackwardsCompatibilityAutoload;
use OxidEsales\EshopCommunity\Core\Autoload\ModuleAutoload;
use OxidEsales\EshopCommunity\Internal\Framework\Env\DotenvLoader;
use Symfony\Component\Filesystem\Path;

spl_autoload_register([BackwardsCompatibilityAutoload::class, 'autoload']);
spl_autoload_register([ModuleAutoload::class, 'autoload']);

require_once Path::join(OX_BASE_PATH, 'oxfunctions.php');
require_once Path::join(OX_BASE_PATH, 'overridablefunctions.php');

(new DotenvLoader(INSTALLATION_ROOT_PATH))->loadEnvironmentVariables();

date_default_timezone_set(getenv('OXID_DEFAULT_TIMEZONE') ?: 'Europe/Berlin');
