<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\MediaLibrary\Tests\Integration\Application\Model;

use OxidEsales\EshopCommunity\Tests\Integration\IntegrationTestCase;
use OxidEsales\LocaleMapper\Service\LocaleContextInterface;
use OxidEsales\MediaLibrary\Application\Model\ArticleExtension;
use OxidEsales\MediaLibrary\Media\Repository\MediaRepository;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ArticleExtension::class)]
final class ArticleExtensionTest extends IntegrationTestCase
{
    public function testReturnsAltTextWhenTranslationExists(): void
    {
        $mediaId = uniqid();
        $altText = uniqid();
        $locale = 'en_US';

        $article = $this->getMockBuilder(ArticleExtension::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $article->_aMediaIds = [1 => $mediaId];

        $mediaRepoMock = $this->createMock(MediaRepository::class);
        $mediaRepoMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn($altText);

        $article->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepoMock],
                [LocaleContextInterface::class, $this->getLocaleContextStub($locale)]
            ]);

        $result = $article->getPictureAltText(1, $locale);
        $this->assertSame($altText, $result);
    }

    public function testReturnsNullWhenNoTranslationExists(): void
    {
        $mediaId = uniqid();
        $locale = 'en_US';

        $article = $this->getMockBuilder(ArticleExtension::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $article->_aMediaIds = [1 => $mediaId];

        $mediaRepoMock = $this->createMock(MediaRepository::class);
        $mediaRepoMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn(null);

        $article->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepoMock],
                [LocaleContextInterface::class, $this->getLocaleContextStub($locale)]
            ]);

        $result = $article->getPictureAltText(1, $locale);
        $this->assertNull($result);
    }

    public function testReturnsNullWhenNoMediaIdExists(): void
    {
        $locale = 'en_US';

        $article = $this->getMockBuilder(ArticleExtension::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $article->_aMediaIds = [];

        $article->method('getService')
            ->willReturnMap([
                [LocaleContextInterface::class, $this->getLocaleContextStub($locale)]
            ]);

        $result = $article->getPictureAltText(1, $locale);
        $this->assertNull($result);
    }

    public function testUsesDefaultLocaleWhenNoneProvided(): void
    {
        $mediaId = uniqid();
        $altText = uniqid();
        $locale = 'de_DE';

        $article = $this->getMockBuilder(ArticleExtension::class)
            ->onlyMethods(['getService'])
            ->getMock();
        $article->_aMediaIds = [1 => $mediaId];

        $mediaRepoMock = $this->createMock(MediaRepository::class);
        $mediaRepoMock->method('getAltTextForMedia')
            ->with($mediaId, $locale)
            ->willReturn($altText);

        $article->method('getService')
            ->willReturnMap([
                [MediaRepository::class, $mediaRepoMock],
                [LocaleContextInterface::class, $this->getLocaleContextStub($locale)]
            ]);

        $result = $article->getPictureAltText(1);
        $this->assertSame($altText, $result);
    }

    private function getLocaleContextStub(string $localeId)
    {
        $stub = $this->createMock(LocaleContextInterface::class);
        $stub->method('getCurrentLocaleId')->willReturn($localeId);
        return $stub;
    }
}
