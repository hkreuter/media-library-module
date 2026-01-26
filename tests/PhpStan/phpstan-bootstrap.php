<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

use OxidEsales\Eshop\Core\Language;
use OxidEsales\MediaLibrary\Language\Core\LanguageExtension_parent;
use OxidEsales\Eshop\Core\ViewConfig;
use OxidEsales\MediaLibrary\Transition\Core\ViewConfig_parent;

class_alias(
    Language::class,
    LanguageExtension_parent::class
);

class_alias(
    ViewConfig::class,
    ViewConfig_parent::class
);
