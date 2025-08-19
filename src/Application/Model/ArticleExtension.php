<?php

namespace OxidEsales\MediaLibrary\Application\Model;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\LocaleMapper\Service\LocaleContextInterface;
use OxidEsales\MediaLibrary\Media\Repository\MediaRepository;

class ArticleExtension extends Article
{
    public function getPictureAltText(int $iIndex, string|null $locale = null): ?string
    {
        if (!$locale) {
            /** @var LocaleContextInterface $localeMapper */
            $localeMapper = $this->getService(LocaleContextInterface::class);
            $locale = $localeMapper->getCurrentLocaleId();
        }

        $mediaId = $this->getMediaIdForPictureIndex($iIndex);
        if (!$mediaId) {
            return null;
        }

        $mediaRepo = $this->getService(MediaRepository::class);
        $altText = $mediaRepo->getAltTextForMedia($mediaId, $locale);

        return $altText ?: null;
    }

    protected function getMediaIdForPictureIndex($iIndex)
    {
        if (isset($this->_aMediaIds[$iIndex])) {
            return $this->_aMediaIds[$iIndex];
        }

        if ($iIndex == 1) {
            return $this->getId();
        }
        return null;
    }
}
