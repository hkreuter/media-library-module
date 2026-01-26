<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\MediaLibrary\Tests\Integration;

use Doctrine\DBAL\Connection;
use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionFactoryInterface;

class IntegrationTestCase extends \OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase
{
    /**
     * Override to use the test container's connection for transaction isolation.
     * This ensures that data inserted via $this->get() services is visible within the transaction.
     */
    public function getDbConnection(): Connection
    {
        return $this->get(ConnectionFactoryInterface::class)->create();
    }
}
