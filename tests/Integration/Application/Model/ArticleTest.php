<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\MediaLibrary\Tests\Integration\Application\Model;

use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\LocaleMapper\Service\LocaleContextInterface;
use OxidEsales\MediaLibrary\Application\Model\Article;
use OxidEsales\MediaLibrary\Media\Repository\MediaRepository;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Article::class)]
final class ArticleTest extends IntegrationTestCase
{
    public function testReturnsAltTextWhenTranslationExists(): void
    {
        $mediaId = uniqid();
        $altText = uniqid();
        $locale = 'en_US';

        $articleMock = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $articleMock->_aMediaIds = [1 => $mediaId];

        $mediaRepositoryMock = $this->createMock(MediaRepository::class);
        $mediaRepositoryMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn($altText);

        $localeContextStub = $this->getLocaleContextStub($locale);

        $articleMock->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepositoryMock],
                [LocaleContextInterface::class, $localeContextStub]
            ]);

        $result = $articleMock->getPictureAltText(1, $locale);
        $this->assertSame($altText, $result);
    }

    public function testReturnsNullWhenNoTranslationExists(): void
    {
        $mediaId = uniqid();
        $locale = 'en_US';

        $articleMock = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $articleMock->_aMediaIds = [1 => $mediaId];

        $mediaRepositoryMock = $this->createMock(MediaRepository::class);
        $mediaRepositoryMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn(null);

        $localeContextStub = $this->getLocaleContextStub($locale);

        $articleMock->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepositoryMock],
                [LocaleContextInterface::class, $localeContextStub]
            ]);

        $result = $articleMock->getPictureAltText(1, $locale);
        $this->assertNull($result);
    }

    public function testReturnsNullWhenNoMediaIdExists(): void
    {
        $locale = 'en_US';

        $articleMock = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $articleMock->_aMediaIds = [];

        $localeContextStub = $this->getLocaleContextStub($locale);

        $articleMock->method('getService')
            ->willReturnMap([
                [LocaleContextInterface::class, $localeContextStub]
            ]);

        $result = $articleMock->getPictureAltText(1, $locale);
        $this->assertNull($result);
    }

    public function testUsesDefaultLocaleWhenNoneProvided(): void
    {
        $mediaId = uniqid();
        $altText = uniqid();
        $locale = 'de_DE';

        $articleMock = $this->getMockBuilder(Article::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $articleMock->_aMediaIds = [1 => $mediaId];

        $mediaRepositoryMock = $this->createMock(MediaRepository::class);
        $mediaRepositoryMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn($altText);

        $localeContextStub = $this->getLocaleContextStub($locale);

        $articleMock->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepositoryMock],
                [LocaleContextInterface::class, $localeContextStub]
            ]);

        $result = $articleMock->getPictureAltText(1);
        $this->assertSame($altText, $result);
    }

    private function getLocaleContextStub(string $localeId)
    {
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);
        return $localeContextStub;
    }
}
