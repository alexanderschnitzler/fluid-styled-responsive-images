<?php
namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;

/**
 * Class ImageRenderer
 * @package Schnitzler\FluidStyledResponsiveImages\Resource\Rendering
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
    protected function getConfiguration()
    {
        if (!static::$configuration instanceof ImageRendererConfiguration) {
            static::$configuration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        }

        return static::$configuration;
    }

    /**
     * @return TagBuilder
     */
    protected function getTagBuilder()
    {
        if (!static::$tagBuilder instanceof TagBuilder) {
            static::$tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);
        }

        return static::$tagBuilder;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return 5;
    }

    /**
     * @param FileInterface $file
     * @return bool
     */
    public function canRender(FileInterface $file)
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
        array $options = array(),
        $usedPathsRelativeToCurrentScript = false
    ) {
        $this->defaultWidth = $width;
        $this->defaultHeight = $height;

        if (is_callable([$file, 'getOriginalFile'])) {
            /** @var FileReference $file */
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        try {
            $defaultProcessConfiguration = [];
            $defaultProcessConfiguration['width'] = '360m';
            $defaultProcessConfiguration['crop'] = $file->getProperty('crop');
        } catch (\InvalidArgumentException $e) {
            $defaultProcessConfiguration['crop'] = '';
        }

        $this->processSourceCollection($originalFile, $defaultProcessConfiguration);

        $src = $originalFile->process(
            ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
            $defaultProcessConfiguration
        )->getPublicUrl();

        try {
            $alt = $file->getProperty('alternative');
        } catch (\InvalidArgumentException $e) {
            $alt = '';
        }

        try {
            $title = $file->getProperty('title');
        } catch (\InvalidArgumentException $e) {
            $title = '';
        }

        return $this->buildImageTag($src, $alt, $title);
    }

    /**
     * @param File $originalFile
     * @param array $defaultProcessConfiguration
     */
    protected function processSourceCollection(File $originalFile, array $defaultProcessConfiguration)
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

                $this->data['data-' . $sourceCollection['dataKey']] = $url;
                $this->srcset[] = $url . rtrim(' ' . $sourceCollection['srcset'] ?: '');
            } catch (\Exception $ignoredException) {
                continue;
            }
        }
    }

    /**
     * @param string $src
     * @param string $alt
     * @param string $title
     *
     * @return string
     */
    protected function buildImageTag($src, $alt = '', $title = '')
    {
        $tagBuilder = $this->getTagBuilder();
        $configuration = $this->getConfiguration();

        $tagBuilder->reset();
        $tagBuilder->setTagName('img');
        $tagBuilder->addAttribute('src', $src);
        $tagBuilder->addAttribute('alt', $alt);
        $tagBuilder->addAttribute('title', $title);

        switch ($configuration->getLayoutKey()) {
            case 'srcset':
                if (!empty($this->srcset)) {
                    $tagBuilder->addAttribute('srcset', implode(', ', $this->srcset));
                }

                $tagBuilder->addAttribute('sizes', implode(', ', $this->sizes));
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
                    'width' => (int)$this->defaultWidth,
                    'height' => (int)$this->defaultHeight,
                ]);
                break;
        }

        return $tagBuilder->render();
    }

}
