<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

// This is acceptance bootstrap
use OxidEsales\Facts\Facts;
use OxidEsales\Codeception\Module\FixturesHelper;
use Symfony\Component\Filesystem\Path;

require_once Path::join((new Facts())->getShopRootPath(), 'source', 'bootstrap.php');

$helper = new FixturesHelper();
$helper->loadRuntimeFixtures(__DIR__ . '/../Support/Data/fixtures.php');
