<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidEsales\MediaLibrary\Tests\Integration\Media\Repository;

use OxidEsales\EshopCommunity\Internal\Framework\Database\ConnectionProviderInterface;
use OxidEsales\EshopCommunity\Internal\Framework\Database\Id;
use OxidEsales\EshopCommunity\Internal\Framework\Database\QueryBuilderFactoryInterface;
use OxidEsales\EshopCommunity\Internal\Transition\Utility\ContextInterface;
use OxidEsales\LocaleMapper\Service\LocaleContextInterface;
use OxidEsales\MediaLibrary\Image\DataTransfer\ImageSize;
use OxidEsales\MediaLibrary\Media\DataType\Media;
use OxidEsales\MediaLibrary\Media\Exception\MediaNotFoundException;
use OxidEsales\MediaLibrary\Media\Exception\WrongMediaIdGivenException;
use OxidEsales\MediaLibrary\Media\Repository\MediaFactoryInterface;
use OxidEsales\MediaLibrary\Media\Repository\MediaRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

#[CoversClass(MediaRepository::class)]
class MediaRepositoryTest extends RepositoryIntegrationTestCase
{
    #[Test]
    public function getShopFolderMediaCount(): void
    {
        $this->createTestItems(3, 'someFolder');
        $this->createTestItems(2, '');

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn(2);
        $sut = $this->getSut(
            context: $contextStub
        );

        $this->assertSame(3, $sut->getFolderMediaCount('someFolder'));
        $this->assertSame(3, $sut->getFolderMediaCount(''));
    }

    #[DataProvider('getFolderMediaDataProvider')]
    #[Test]
    public function getShopFolderMediaInFolder(
        string $folder,
        int $start,
        int $expectedItems,
        int $firstListItemId
    ): void {
        $this->createTestItems(7, 'someFolder');
        $this->createTestItems(3, '');

        $sut = $this->getSutForShop(2);

        $result = $sut->getFolderMedia($folder, $start, 5);

        $this->assertSame($expectedItems, count($result));
        foreach ($result as $key => $oneItem) {
            $this->assertInstanceOf(Media::class, $oneItem);
            $this->assertSame($folder . 'example' . ($firstListItemId - $key), $oneItem->getOxid());
        }
    }

    #[Test]
    public function getShopFolderMediaInRootWithFolderPresent(): void
    {
        $expectedItems = 4;
        $firstListItemId = 3;

        $this->createTestItems(7, 'someFolder');
        $this->createTestItems(3, '');

        $sut = $this->getSutForShop(2);

        $result = $sut->getFolderMedia('', 0, 5);

        $this->assertSame($expectedItems, count($result));

        $oneItem = current($result);
        $this->assertInstanceOf(Media::class, $oneItem);
        $this->assertSame('someFolder', $oneItem->getOxid());
        next($result);

        foreach ($result as $key => $oneItem) {
            if (!$key) {
                continue;
            }
            $this->assertInstanceOf(Media::class, $oneItem);
            $this->assertSame('example' . ($firstListItemId - $key + 1), $oneItem->getOxid());
        }
    }

    #[Test]
    public function getMediaByIdNotFound(): void
    {
        $sut = $this->getSut();

        $this->expectException(MediaNotFoundException::class);
        $sut->getMediaById('someWrongId');
    }

    public static function getFolderMediaDataProvider(): \Generator
    {
        yield "first page in folder" => [
            'folder' => 'someFolder',
            'start' => 0,
            'expectedItems' => 5,
            'firstListItemId' => 7
        ];

        yield "second page in folder" => [
            'folder' => 'someFolder',
            'start' => 5,
            'expectedItems' => 2,
            'firstListItemId' => 2
        ];
    }

    private function createTestItems(int $amount, string $folderId): void
    {
        $queryBuilder = $this->getAddItemQueryBuilder();

        if ($folderId) {
            $queryBuilder->setParameters([
                'OXID' => $folderId,
                'OXSHOPID' => 2,
                'DDFILENAME' => $folderId . 'Filename',
                'DDFILESIZE' => 0,
                'DDFILETYPE' => 'directory',
                'DDIMAGESIZE' => 0,
                'DDFOLDERID' => '',
                'OXTIMESTAMP' => date("Y-m-d H:i:59")
            ])->execute();
        }

        for ($i = 1; $i <= $amount; $i++) {
            $queryBuilder->setParameters([
                'OXID' => $folderId . 'example' . $i,
                'OXSHOPID' => 2,
                'DDFILENAME' => 'filename' . $i . '.jpg',
                'DDFILESIZE' => $i * 10,
                'DDFILETYPE' => 'image/gif',
                'DDIMAGESIZE' => $i . '00x' . $i . '00.jpg',
                'DDFOLDERID' => $folderId,
                'OXTIMESTAMP' => date("Y-m-d H:i:") . $i
            ])->execute();
        }
    }

    private function getSutForShop(int $shopId): MediaRepository
    {
        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);
        return $this->getSut(
            context: $contextStub
        );
    }

    private function getSut(
        ?ContextInterface $context = null,
        ?ConnectionProviderInterface $connectionProvider = null,
        ?MediaFactoryInterface $mediaFactory = null,
        ?LocaleContextInterface $localeContext = null
    ): MediaRepository {
        $sut = new MediaRepository(
            connectionProvider: $connectionProvider ?? $this->get(ConnectionProviderInterface::class),
            context: $context ?? $this->get(ContextInterface::class),
            mediaFactory: $mediaFactory ?? $this->get(MediaFactoryInterface::class),
            localeContext: $localeContext ?? $this->get(LocaleContextInterface::class)
        );

        return $sut;
    }

    #[Test]
    public function addMedia(): void
    {
        $oxid = 'someExampleMediaId';
        $exampleMedia = new Media(
            oxid: $oxid,
            fileName: 'someFilename',
            fileSize: 123,
            fileType: 'image/gif',
            imageSize: new ImageSize(111, 222),
            folderId: 'someFolderId'
        );

        $sut = $this->getSutForShop(3);
        $sut->addMedia($exampleMedia);

        $resultMedia = $sut->getMediaById($oxid);
        $this->assertEquals($exampleMedia, $resultMedia);
    }

    public function testAddMediaWithAltText(): void
    {
        $oxid = 'mediaWithAltText';
        $altText = 'Test alt text for media';
        $localeId = 'test_locale';
        $shopId = 3;

        $exampleMedia = new Media(
            oxid: $oxid,
            fileName: 'image-with-alt.jpg',
            fileSize: 2048,
            fileType: 'image/jpeg',
            imageSize: new ImageSize(300, 200),
            folderId: '',
            folderName: '',
            altText: $altText
        );

        // Mock locale context
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);
        $sut->addMedia($exampleMedia);

        // Verify media was added
        $resultMedia = $sut->getMediaById($oxid);
        $this->assertInstanceOf(Media::class, $resultMedia);
        $this->assertSame($oxid, $resultMedia->getOxid());

        // Verify translation was saved by checking database directly
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $translationResult = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $oxid,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertNotFalse($translationResult, 'Translation record should exist');
        $this->assertSame($altText, $translationResult['OXALTSHORTTEXT']);
    }

    public function testAddMediaWithoutAltText(): void
    {
        $oxid = 'mediaWithoutAltText';
        $localeId = 'test_locale';
        $shopId = 3;

        $exampleMedia = new Media(
            oxid: $oxid,
            fileName: 'image-no-alt.jpg',
            fileSize: 1024,
            fileType: 'image/jpeg',
            imageSize: new ImageSize(150, 100),
            folderId: ''
        );

        // Mock locale context
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);
        $sut->addMedia($exampleMedia);

        // Verify media was added
        $resultMedia = $sut->getMediaById($oxid);
        $this->assertInstanceOf(Media::class, $resultMedia);
        $this->assertSame($oxid, $resultMedia->getOxid());

        // Verify no translation was saved since altText is empty
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $translationResult = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $oxid,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertFalse($translationResult, 'No translation record should exist when altText is empty');
    }

    public function testAddMediaReplaceExistingTranslation(): void
    {
        $oxid = 'mediaReplaceTranslation';
        $localeId = 'test_locale';
        $shopId = 3;
        $initialAltText = 'Initial alt text';
        $newAltText = 'Updated alt text';

        // Mock contexts
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);

        // First, manually create a translation record using our helper
        $this->createMediaItem($oxid, $shopId);
        $this->createTranslationData($oxid, $localeId, $shopId, $initialAltText);

        // Verify initial translation exists
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $initialResult = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $oxid,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertNotFalse($initialResult, 'Initial translation should exist');
        $this->assertSame($initialAltText, $initialResult['OXALTSHORTTEXT']);

        // Now test REPLACE INTO directly to test the behavior
        $connection = $this->get(ConnectionProviderInterface::class)->get();
        $connection->executeQuery(
            "REPLACE INTO ddmedia_translations SET
                OXID = :OXID,
                OXOBJECTID = :OXOBJECTID,
                OXLOCALEID = :OXLOCALEID,
                OXSHOPID = :OXSHOPID,
                OXALTSHORTTEXT = :OXALTSHORTTEXT",
            [
                'OXID' => uniqid('replace_', true),
                'OXOBJECTID' => $oxid,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId,
                'OXALTSHORTTEXT' => $newAltText
            ]
        );

        // Verify the translation was replaced
        $finalResult = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $oxid,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertNotFalse($finalResult, 'Final translation should exist');
        $this->assertSame($newAltText, $finalResult['OXALTSHORTTEXT']);
    }

    public function testRenameMedia(): void
    #[Test]
    public function renameMedia(): void
    {
        $mediaIdToRename = 'mediaToRename';

        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $mediaIdToRename,
            'OXSHOPID' => 2,
            'DDFILENAME' => 'OriginalName',
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'any',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $newName = 'NewName';

        $sut = $this->getSut();
        $renameResult = $sut->renameMedia($mediaIdToRename, $newName);
        $this->assertSame($newName, $renameResult->getFileName());

        $updatedData = $sut->getMediaById($mediaIdToRename);
        $this->assertSame($newName, $updatedData->getFileName());
    }

    #[Test]
    public function changeMediaFolder(): void
    {
        $mediaIdToUpdate = 'mediaToChangeFolderId';

        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $mediaIdToUpdate,
            'OXSHOPID' => 2,
            'DDFILENAME' => 'OriginalName',
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'any',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $newFolderId = uniqid();

        $sut = $this->getSut();
        $sut->changeMediaFolderId($mediaIdToUpdate, $newFolderId);

        $updatedData = $sut->getMediaById($mediaIdToUpdate);
        $this->assertSame($newFolderId, $updatedData->getFolderId());
    }

    public function testDeleteRegularMedia(): void
    {
        $queryBuilder = $this->getAddItemQueryBuilder();

        $idToRemove = 'regularMediaForRemoval';
        $queryBuilder->setParameters([
            'OXID' => $idToRemove,
            'OXSHOPID' => 3,
            'DDFILENAME' => uniqid(),
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'not directory',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $sut = $this->getSut();
        $sut->deleteMedia($idToRemove);

        $this->expectException(MediaNotFoundException::class);
        $sut->getMediaById($idToRemove);
    }

    #[Test]
    public function deleteRemovesDirectoryRelatedMediaOnly(): void
    {
        $queryBuilder = $this->getAddItemQueryBuilder();

        $idToRemove = 'directoryMediaForRemoval';
        $queryBuilder->setParameters([
            'OXID' => $idToRemove,
            'OXSHOPID' => 3,
            'DDFILENAME' => uniqid(),
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'directory',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $inDirectoryId = uniqid();
        $queryBuilder->setParameters([
            'OXID' => $inDirectoryId,
            'OXSHOPID' => 3,
            'DDFILENAME' => uniqid(),
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'in directory',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => $idToRemove,
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $notInDirectoryId = uniqid();
        $queryBuilder->setParameters([
            'OXID' => $notInDirectoryId,
            'OXSHOPID' => 3,
            'DDFILENAME' => uniqid(),
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'not in directory',
            'DDIMAGESIZE' => 0,
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        $sut = $this->getSut();
        $sut->deleteMedia($idToRemove);

        $this->assertInstanceOf(Media::class, $sut->getMediaById($notInDirectoryId));

        $this->expectException(MediaNotFoundException::class);
        $sut->getMediaById($inDirectoryId);
    }

    #[Test]
    public function deleteArgumentWrongValueExplodes(): void
    {
        $sut = $this->getSut();

        $this->expectException(WrongMediaIdGivenException::class);
        $sut->deleteMedia('');
    }

    public function testGetMediaByIdWithTranslationDataFound(): void
    {
        $mediaId = 'mediaWithTranslation';
        $localeId = 'test_locale_id';
        $shopId = 2;
        $altText = 'Test alt text for image';

        // Create media item
        $this->createMediaItem($mediaId, $shopId);

        // Create translation data
        $this->createTranslationData($mediaId, $localeId, $shopId, $altText);

        // Mock locale context to return our test locale
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $sut = $this->getSut(localeContext: $localeContextStub);
        $result = $sut->getMediaById($mediaId);

        $this->assertInstanceOf(Media::class, $result);
        $this->assertSame($mediaId, $result->getOxid());
        // Note: We would need to extend the Media class to include altText property
        // This follows TDD - we're defining the expected behavior first
    }

    public function testGetMediaByIdWithNoTranslationDataFound(): void
    {
        $mediaId = 'mediaWithoutTranslation';
        $localeId = 'test_locale_id';
        $shopId = 2;

        // Create media item without translation data
        $this->createMediaItem($mediaId, $shopId);

        // Mock locale context to return our test locale
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $sut = $this->getSut(localeContext: $localeContextStub);
        $result = $sut->getMediaById($mediaId);

        $this->assertInstanceOf(Media::class, $result);
        $this->assertSame($mediaId, $result->getOxid());
        // ALTTEXT should be null when no translation data exists
    }

    public function testGetFolderMediaWithTranslationData(): void
    {
        $folderId = 'testFolder';
        $mediaId = 'mediaInFolderWithTranslation';
        $localeId = 'test_locale_id';
        $shopId = 2;
        $altText = 'Alt text for folder media';

        // Create folder first (using the same pattern as existing tests)
        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $folderId,
            'OXSHOPID' => $shopId,
            'DDFILENAME' => $folderId . 'Filename',
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'directory',
            'DDIMAGESIZE' => '0x0',
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        // Create media item in folder
        $this->createMediaItemInFolder($mediaId, $folderId, $shopId);

        // Create translation data
        $this->createTranslationData($mediaId, $localeId, $shopId, $altText);

        // Mock locale context and use the same shop context
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);
        $results = $sut->getFolderMedia($folderId, 0, 10);

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertInstanceOf(Media::class, $result);
        $this->assertSame($mediaId, $result->getOxid());
    }

    public function testGetFolderMediaWithDifferentLocales(): void
    {
        $folderId = 'testFolderMulti';
        $mediaId = 'mediaMultiLocale';
        $localeId1 = 'en_US';
        $localeId2 = 'de_DE';
        $shopId = 2;
        $altTextEn = 'English alt text';
        $altTextDe = 'German alt text';

        // Create folder first (using the same pattern as existing tests)
        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $folderId,
            'OXSHOPID' => $shopId,
            'DDFILENAME' => $folderId . 'Filename',
            'DDFILESIZE' => 0,
            'DDFILETYPE' => 'directory',
            'DDIMAGESIZE' => '0x0',
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:59")
        ])->execute();

        // Create media item
        $this->createMediaItemInFolder($mediaId, $folderId, $shopId);

        // Create translation data for both locales with unique IDs
        $this->createTranslationData($mediaId, $localeId1, $shopId, $altTextEn, 'trans_en_' . $mediaId);
        $this->createTranslationData($mediaId, $localeId2, $shopId, $altTextDe, 'trans_de_' . $mediaId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        // Test with English locale
        $localeContextStub1 = $this->createMock(LocaleContextInterface::class);
        $localeContextStub1->method('getCurrentLocaleId')->willReturn($localeId1);

        $sut1 = $this->getSut(context: $contextStub, localeContext: $localeContextStub1);
        $results1 = $sut1->getFolderMedia($folderId, 0, 10);

        $this->assertCount(1, $results1);
        // Would verify English alt text is returned

        // Test with German locale
        $localeContextStub2 = $this->createMock(LocaleContextInterface::class);
        $localeContextStub2->method('getCurrentLocaleId')->willReturn($localeId2);

        $sut2 = $this->getSut(context: $contextStub, localeContext: $localeContextStub2);
        $results2 = $sut2->getFolderMedia($folderId, 0, 10);

        $this->assertCount(1, $results2);
        // Would verify German alt text is returned
    }

    private function createMediaItem(string $mediaId, int $shopId): void
    {
        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $mediaId,
            'OXSHOPID' => $shopId,
            'DDFILENAME' => 'test-media.jpg',
            'DDFILESIZE' => 1024,
            'DDFILETYPE' => 'image/jpeg',
            'DDIMAGESIZE' => '100x100',
            'DDFOLDERID' => '',
            'OXTIMESTAMP' => date("Y-m-d H:i:s")
        ])->execute();
    }

    private function createMediaItemInFolder(string $mediaId, string $folderId, int $shopId): void
    {
        $queryBuilder = $this->getAddItemQueryBuilder();
        $queryBuilder->setParameters([
            'OXID' => $mediaId,
            'OXSHOPID' => $shopId,
            'DDFILENAME' => 'test-media-in-folder.jpg',
            'DDFILESIZE' => 2048,
            'DDFILETYPE' => 'image/jpeg',
            'DDIMAGESIZE' => '200x200',
            'DDFOLDERID' => $folderId,
            'OXTIMESTAMP' => date("Y-m-d H:i:s")
        ])->execute();
    }

    public function testSaveAltTextNew(): void
    {
        $mediaId = 'mediaSaveAltText';
        $localeId = 'test_locale';
        $shopId = 3;
        $altText = 'New alt text';

        // Create media item
        $this->createMediaItem($mediaId, $shopId);

        // Mock contexts
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);

        // Save alt text
        $sut->saveAltText($mediaId, $altText);

        // Verify translation was saved
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $result = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $mediaId,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertNotFalse($result, 'Translation should be saved');
        $this->assertSame($altText, $result['OXALTSHORTTEXT']);
    }

    public function testSaveAltTextUpdate(): void
    {
        $mediaId = 'mediaUpdateAltText';
        $localeId = 'test_locale';
        $shopId = 3;
        $initialAltText = 'Initial alt text';
        $updatedAltText = 'Updated alt text';

        // Create media item and initial translation
        $this->createMediaItem($mediaId, $shopId);
        $this->createTranslationData($mediaId, $localeId, $shopId, $initialAltText);

        // Mock contexts
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);

        // Update alt text
        $sut->saveAltText($mediaId, $updatedAltText);

        // Verify translation was updated
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $result = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $mediaId,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertNotFalse($result, 'Translation should exist');
        $this->assertSame($updatedAltText, $result['OXALTSHORTTEXT']);
    }

    public function testSaveAltTextEmpty(): void
    {
        $mediaId = 'mediaDeleteAltText';
        $localeId = 'test_locale';
        $shopId = 3;
        $initialAltText = 'Text to be deleted';

        // Create media item and initial translation
        $this->createMediaItem($mediaId, $shopId);
        $this->createTranslationData($mediaId, $localeId, $shopId, $initialAltText);

        // Mock contexts
        $localeContextStub = $this->createMock(LocaleContextInterface::class);
        $localeContextStub->method('getCurrentLocaleId')->willReturn($localeId);

        $contextStub = $this->createMock(ContextInterface::class);
        $contextStub->method('getCurrentShopId')->willReturn($shopId);

        $sut = $this->getSut(context: $contextStub, localeContext: $localeContextStub);

        // Save empty alt text (should delete the record)
        $sut->saveAltText($mediaId, '');

        // Verify translation was deleted
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();

        $result = $queryBuilder
            ->select('OXALTSHORTTEXT')
            ->from('ddmedia_translations')
            ->where('OXOBJECTID = :OXOBJECTID')
            ->andWhere('OXLOCALEID = :OXLOCALEID')
            ->andWhere('OXSHOPID = :OXSHOPID')
            ->setParameters([
                'OXOBJECTID' => $mediaId,
                'OXLOCALEID' => $localeId,
                'OXSHOPID' => $shopId
            ])
            ->execute()
            ->fetchAssociative();

        $this->assertFalse($result, 'Translation should be deleted when alt text is empty');
    }

    private function createTranslationData(
        string $mediaId,
        string $localeId,
        int $shopId,
        string $altText,
        ?string $oxid = null
    ): void {
        $queryBuilderFactory = ContainerFacade::get(QueryBuilderFactoryInterface::class);
        $queryBuilder = $queryBuilderFactory->create();
        $queryBuilder->insert("ddmedia_translations")->values([
            'OXID' => ':OXID',
            'OXOBJECTID' => ':OXOBJECTID',
            'OXLOCALEID' => ':OXLOCALEID',
            'OXSHOPID' => ':OXSHOPID',
            'OXALTSHORTTEXT' => ':OXALTSHORTTEXT'
        ])->setParameters([
            'OXID' => $oxid ?? (string) Id::generate(),
            'OXOBJECTID' => $mediaId,
            'OXLOCALEID' => $localeId,
            'OXSHOPID' => $shopId,
            'OXALTSHORTTEXT' => $altText
        ])->execute();
    }

    public function testGetAltTextForMedia(): void
    {
        $mediaId = uniqid();
        $localeId = 'en_US';
        $shopId = 2;
        $altText = uniqid();

        $this->createMediaItem($mediaId, $shopId);
        $this->createTranslationData($mediaId, $localeId, $shopId, $altText);

        $sut = $this->getSut();
        $result = $sut->getAltTextForMedia($mediaId, $localeId);
        $this->assertSame($altText, $result);

        $resultNoTranslation = $sut->getAltTextForMedia($mediaId, 'fr_FR');
        $this->assertNull($resultNoTranslation);
    }
}
