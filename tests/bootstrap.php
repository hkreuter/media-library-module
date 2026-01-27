<?php

/**
 * Bootstrap for module integration tests.
 * Sets up the shop context when running tests from the module directory.
 *
 * @phpcs:disable PSR1.Files.SideEffects
 * Bootstrap files inherently mix constant definitions (define/const) with
 * side effects (require, autoloader registration, etc). This is the standard
 * pattern for test bootstraps - their purpose IS to execute setup code.
 */

declare(strict_types=1);

// The shop root is two directories up from the module directory
// /var/www/ARCHIVE/media-library-module -> /var/www
$shopRoot = dirname(__DIR__, 3);

// Override the project root detection
define('INSTALLATION_ROOT_PATH', $shopRoot);
const VENDOR_PATH = INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
define('OX_BASE_PATH', INSTALLATION_ROOT_PATH . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR);

// Load shop autoloader
require VENDOR_PATH . 'autoload.php';

// Load module autoloader
require dirname(__DIR__) . '/vendor/autoload.php';

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
