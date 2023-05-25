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
 * @see \Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering\ImageRendererTest
 */
class ImageRenderer implements FileRendererInterface
{
    protected static ?TagBuilder $tagBuilder = null;

    protected static ?ImageRendererConfiguration $configuration = null;

    /**
     * @var array<string>
     */
    protected array $possibleMimeTypes = [
        'image/jpg',
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * @var array<string>
     */
    protected array $sizes = [];

    /**
     * @var array<string>
     */
    protected array $srcset = [];

    /**
     * @var array<string,string>
     */
    protected array $data = [];

    protected ?string $defaultWidth = null;

    protected ?string $defaultHeight = null;

    /**
     * @return ImageRendererConfiguration
     */
    protected function getConfiguration(): ImageRendererConfiguration
    {
        if (static::$configuration === null) {
            static::$configuration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        }

        return static::$configuration;
    }

    /**
     * @return TagBuilder
     */
    protected function getTagBuilder(): TagBuilder
    {
        if (static::$tagBuilder === null) {
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
     * @param array<string,mixed> $options
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
        $enableSmallDefaultImage = (bool)($this->getConfiguration()->getExtensionConfiguration()['enableSmallDefaultImage'] ?? false);
        if ($enableSmallDefaultImage) {
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

    protected function reset(): void
    {
        $this->sizes = [];
        $this->srcset = [];
        $this->data = [];
    }

    /**
     * @param File $originalFile
     * @param array<string,mixed> $defaultProcessConfiguration
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

                $url = $processedFile->getPublicUrl();

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
     * @param array<string,string> $options
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
            $alt = trim((string)$file->getProperty('alternative'));

            if ($alt === '') {
                throw new \LogicException;
            }
        } catch (\Exception $e) {
            $alt = isset($options['alt']) && is_string($options['alt']) ? $options['alt'] : '';
        }

        try {
            $title = trim((string)$file->getProperty('title'));

            if ($title === '') {
                throw new \LogicException;
            }
        } catch (\Exception $e) {
            $title = isset($options['title']) && is_string($options['title']) ? $options['alt'] : '';
        }

        $tagBuilder->addAttribute('src', $src);
        $tagBuilder->addAttribute('alt', $alt);
        $tagBuilder->addAttribute('title', $title);

        switch ($configuration->getLayoutKey()) {
            case 'srcset':
                if (count($this->srcset) > 0) {
                    $tagBuilder->addAttribute('srcset', implode(', ', $this->srcset));
                    if (count($this->sizes) > 0) {
                        $tagBuilder->addAttribute('sizes', implode(', ', $this->sizes));
                    }
                }

                break;
            case 'data':
                foreach ($this->data as $key => $value) {
                    $tagBuilder->addAttribute($key, $value);
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
