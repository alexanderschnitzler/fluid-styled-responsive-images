<?php
declare(strict_types=1);

namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ImageRenderer
 * @package Schnitzler\FluidStyledResponsiveImages\Resource\Rendering
 * @see \Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering\ImageRendererTest
 */
class ImageRenderer implements FileRendererInterface
{
    /**
     * @var TagBuilder
     */
    static protected $tagBuilder;

    /**
     * @var ImageRendererConfiguration
     */
    static protected $configuration;

    /**
     * @var array
     */
    protected $possibleMimeTypes = [
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * @var array
     */
    protected $sizes = [];

    /**
     * @var array
     */
    protected $srcset = [];

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $defaultWidth;

    /**
     * @var string
     */
    protected $defaultHeight;

    /**
     * @return ImageRendererConfiguration
     */
    protected function getConfiguration(): ImageRendererConfiguration
    {
        if (!static::$configuration instanceof ImageRendererConfiguration) {
            static::$configuration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        }

        return static::$configuration;
    }

    /**
     * @return TagBuilder
     */
    protected function getTagBuilder(): TagBuilder
    {
        if (!static::$tagBuilder instanceof TagBuilder) {
            static::$tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        }

        return static::$tagBuilder;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 5;
    }

    /**
     * @param FileInterface $file
     * @return bool
     */
    public function canRender(FileInterface $file): bool
    {
        return TYPO3_MODE === 'FE' && in_array($file->getMimeType(), $this->possibleMimeTypes, true);
    }

    /**
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function render(
        FileInterface $file,
        $width,
        $height,
        array $options = [],
        $usedPathsRelativeToCurrentScript = false
    ): string {
        $this->reset();

        $this->defaultWidth = (string)$width;
        $this->defaultHeight = (string)$height;

        if (is_callable([$file, 'getOriginalFile'])) {
            /** @var FileReference $file */
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        $defaultProcessConfiguration = [];
        if ($this->getConfiguration()->getExtensionConfiguration()['enableSmallDefaultImage'] ?? false) {
            $defaultProcessConfiguration['width'] = '360m';
        } else {
            $defaultProcessConfiguration['width'] = $this->defaultWidth . 'm';
        }

        try {
            $cropVariantCollection = CropVariantCollection::create((string)$file->getProperty('crop'));
            $defaultCropArea = $cropVariantCollection->getCropArea();
            $defaultProcessConfiguration['crop'] = !$defaultCropArea->isEmpty()
                ? $defaultCropArea->makeAbsoluteBasedOnFile($file)
                : null;
        } catch (\Exception $e) {
            $defaultProcessConfiguration['crop'] = null;
        }

        $this->processSourceCollection($originalFile, $defaultProcessConfiguration);

        $processedFile = $originalFile->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            $defaultProcessConfiguration
        );

        $width = (int)$processedFile->getProperty('width');
        $height = (int)$processedFile->getProperty('height');

        return $this->buildImageTag((string)$processedFile->getPublicUrl(), $file, $width, $height, $options);
    }

    /**
     * @return void
     */
    protected function reset(): void
    {
        $this->sizes = [];
        $this->srcset = [];
        $this->data = [];
    }

    /**
     * @param File $originalFile
     * @param array $defaultProcessConfiguration
     */
    protected function processSourceCollection(File $originalFile, array $defaultProcessConfiguration): void
    {
        $configuration = $this->getConfiguration();

        foreach ($configuration->getSourceCollection() as $sourceCollection) {
            try {
                if (!is_array($sourceCollection)) {
                    throw new \RuntimeException();
                }

                if (isset($sourceCollection['sizes'])) {
                    $this->sizes[] = trim($sourceCollection['sizes'], ' ,');
                }

                if ((int)$sourceCollection['width'] > (int)$this->defaultWidth) {
                    throw new \RuntimeException();
                }

                $localProcessingConfiguration = $defaultProcessConfiguration;
                $localProcessingConfiguration['width'] = $sourceCollection['width'];

                $processedFile = $originalFile->process(
                    ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                    $localProcessingConfiguration
                );

                $url = $configuration->getAbsRefPrefix() . $processedFile->getPublicUrl();

                $this->data['data-' . ($sourceCollection['dataKey'] ?? '')] = $url;
                $this->srcset[] = $url . rtrim(' ' . ($sourceCollection['srcset'] ?? ''));
            } catch (\Exception $ignoredException) {
                continue;
            }
        }
    }

    /**
     * @param string $src
     * @param FileInterface $file
     * @param int $width
     * @param int $height
     * @param array $options
     *
     * @return string
     */
    protected function buildImageTag(string $src, FileInterface $file, int $width, int $height, array $options): string
    {
        $tagBuilder = $this->getTagBuilder();
        $configuration = $this->getConfiguration();

        $tagBuilder->reset();
        $tagBuilder->setTagName('img');

        try {
            $alt = trim($file->getProperty('alternative'));

            if (empty($alt)) {
                throw new \LogicException;
            }
        } catch (\Exception $e) {
            $alt = isset($options['alt']) && !empty($options['alt']) ? $options['alt'] : '';
        }

        try {
            $title = trim($file->getProperty('title'));

            if (empty($title)) {
                throw new \LogicException;
            }
        } catch (\Exception $e) {
            $title = isset($options['title']) && !empty($options['title']) ? $options['title'] : '';
        }

        $tagBuilder->addAttribute('src', $src);
        $tagBuilder->addAttribute('alt', $alt);
        $tagBuilder->addAttribute('title', $title);

        switch ($configuration->getLayoutKey()) {
            case 'srcset':
                if (!empty($this->srcset)) {
                    $tagBuilder->addAttribute('srcset', implode(', ', $this->srcset));
                    if (!empty($this->sizes)) {
                        $tagBuilder->addAttribute('sizes', implode(', ', $this->sizes));
                    }
                }

                break;
            case 'data':
                if (!empty($this->data)) {
                    foreach ($this->data as $key => $value) {
                        $tagBuilder->addAttribute($key, $value);
                    }
                }
                break;
            default:
                $tagBuilder->addAttributes([
                    'width' => $width,
                    'height' => $height,
                ]);
                break;
        }

        if (isset($options['data']) && is_array($options['data'])) {
            foreach ($options['data'] as $dataAttributeKey => $dataAttributeValue) {
                $tagBuilder->addAttribute('data-' . $dataAttributeKey, $dataAttributeValue);
            }
        }

        foreach ($configuration->getGenericTagAttributes() as $attributeName) {
            if (isset($options[$attributeName]) && $options[$attributeName] !== '') {
                $tagBuilder->addAttribute($attributeName, $options[$attributeName]);
            }
        }

        if (isset($options['additionalAttributes']) && is_array($options['additionalAttributes'])) {
            $tagBuilder->addAttributes($options['additionalAttributes']);
        }

        return $tagBuilder->render();
    }
}
