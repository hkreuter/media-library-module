<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OxidEsales\MediaLibrary\Tests\Integration;

use Generator;
use OxidEsales\MediaLibrary\Media\Facade\MediaFacadeInterface;
use OxidEsales\MediaLibrary\Media\Facade\FallbackMediaFacadeDecorator;
use OxidEsales\MediaLibrary\Compatibility\Facade\MediaIdByPathFacadeInterface;
use OxidEsales\MediaLibrary\Compatibility\Repository\PathMappingRepositoryInterface;
use OxidEsales\MediaLibrary\Compatibility\Factory\MediaFileInformationFactoryInterface;
use OxidEsales\MediaLibrary\Media\Settings\FallbackMediaSettingsInterface;
use OxidEsales\MediaLibrary\Media\Twig\MediaDataExtension;
use OxidEsales\MediaLibrary\Media\Twig\MediaDataLogicInterface;
use OxidEsales\EshopCommunity\Internal\Container\ContainerBuilderFactory;
use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class ServiceAvailabilityTest extends IntegrationTestCase
{
    private static $cachedContainer;
    private static $decorations;

    public static function setUpBeforeClass(): void
    {
        $containerBuilder = new \OxidEsales\EshopCommunity\Internal\Framework\DIContainer\ContainerBuilder(new \OxidEsales\EshopCommunity\Internal\Transition\Utility\BasicContext(), (new \OxidEsales\EshopCommunity\Internal\Transition\ShopEvents\ShopIdCalculator(new \OxidEsales\EshopCommunity\Core\UtilsServer()))->getShopId());
        $container = $containerBuilder->getContainer();
        foreach ($container->getDefinitions() as $id => $definition) {
            $definition->setPublic(true);
            if ($decorated = $definition->getDecoratedService()) {
                self::$decorations[reset($decorated)][] = $id;
            }
        }
        $container->compile(true);

        self::$cachedContainer = $container;
    }

    #[DataProvider('serviceAvailabilityDataProvider')]
    #[Test]
    public function servicesAvailable(string $serviceName): void
    {
        $service = self::$cachedContainer->get($serviceName);
        $this->assertInstanceOf($serviceName, $service);
    }

    #[DataProvider('serviceDecorationProvider')]
    #[Test]
    public function servicesDecorated(string $serviceName, array $expectedDecorations): void
    {
        $decorations = self::$decorations[$serviceName];
        foreach ($expectedDecorations as $oneExpectedDecoration) {
            $this->assertContains($oneExpectedDecoration, $decorations);
        }
    }

    public static function serviceDecorationProvider(): Generator
    {
        yield [MediaFacadeInterface::class, [
            FallbackMediaFacadeDecorator::class,
        ]];
    }

    //todo: list all services here
    public static function serviceAvailabilityDataProvider(): array
    {
        return [
            // Compatibility
            [MediaIdByPathFacadeInterface::class],
            [PathMappingRepositoryInterface::class],
            [MediaFileInformationFactoryInterface::class],

            // Media
            [MediaFacadeInterface::class],
            [FallbackMediaSettingsInterface::class],

            [MediaDataExtension::class],
            [MediaDataLogicInterface::class],
        ];
    }
}
