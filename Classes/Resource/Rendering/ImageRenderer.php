<?php
namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class ImageRenderer
 * @package Schnitzler\FluidStyledResponsiveImages\Resource\Rendering
 */
class ImageRenderer implements FileRendererInterface
{

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var TagBuilder
     */
    protected $tagBuilder;

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
    protected $settings;

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
     * @return ImageRenderer
     */
    public function __construct()
    {
        $this->settings = [];
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);

        $this->getConfiguration();
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

        if ($file instanceof FileReference) {
            $originalFile = $file->getOriginalFile();
        } else {
            $originalFile = $file;
        }

        try {
            $defaultProcessConfiguration = [];
            $defaultProcessConfiguration['width'] = (int)$width;
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
        foreach ($this->settings['sourceCollection'] as $configuration) {
            try {
                if (!is_array($configuration)) {
                    throw new \RuntimeException();
                }

                if (isset($configuration['sizes'])) {
                    $this->sizes[] = trim($configuration['sizes'], ' ,');
                }

                if ((int)$configuration['width'] > (int)$this->defaultWidth) {
                    throw new \RuntimeException();
                }

                $localProcessingConfiguration = $defaultProcessConfiguration;
                $localProcessingConfiguration['width'] = $configuration['width'];

                $processedFile = $originalFile->process(
                    ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
                    $localProcessingConfiguration
                );

                $url = $this->getTypoScriptFrontendController()->absRefPrefix . $processedFile->getPublicUrl();

                $this->data['data-' . $configuration['dataKey']] = $url;
                $this->srcset[] = $url . rtrim(' ' . $configuration['srcset'] ?: '');
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
    protected function buildImageTag($src, $alt = '', $title = '') {
        $this->tagBuilder->reset();
        $this->tagBuilder->setTagName('img');
        $this->tagBuilder->addAttribute('src', $src);
        $this->tagBuilder->addAttribute('alt', $alt);
        $this->tagBuilder->addAttribute('title', $title);

        switch ($this->settings['layoutKey']) {
            case 'srcset':
                if (!empty($this->srcset)) {
                    $this->tagBuilder->addAttribute('srcset', implode(', ', $this->srcset));
                }

                $this->tagBuilder->addAttribute('sizes', implode(', ', $this->sizes));
                break;
            case 'data':
                if (!empty($this->data)) {
                    foreach ($this->data as $key => $value) {
                        $this->tagBuilder->addAttribute($key, $value);
                    }
                }
                break;
            default:
                $this->tagBuilder->addAttributes([
                    'width' => (int)$this->defaultWidth,
                    'height' => (int)$this->defaultHeight,
                ]);
                break;
        }

        return $this->tagBuilder->render();
    }

    /**
     * @return ContentObjectRenderer
     */
    protected function getTypoScriptSetup()
    {
        if (!$GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
            return [];
        }

        if (!$GLOBALS['TSFE']->tmpl instanceof TemplateService) {
            return [];
        }

        return $GLOBALS['TSFE']->tmpl->setup;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController() {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return void
     */
    protected function getConfiguration()
    {
        $configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($this->getTypoScriptSetup());

        $settings = ObjectAccess::getPropertyPath(
            $configuration,
            'tt_content.textmedia.settings.responsive_image_rendering'
        );
        $settings = is_array($settings) ? $settings : [];

        $this->settings['layoutKey'] =
            (isset($settings['layoutKey']))
                ? $settings['layoutKey']
                : 'default';

        $this->settings['sourceCollection'] =
            (isset($settings['sourceCollection']) && is_array($settings['sourceCollection']))
                ? $settings['sourceCollection']
                : [];
    }
}
