<?php
namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class ImageRendererConfiguration
 * @package Schnitzler\FluidStyledResponsiveImages\Resource\Rendering
 */
class ImageRendererConfiguration
{
    /**
     * @var array
     */
    protected $extensionConfiguration;

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $genericTagAttributes = [
        'class',
        'dir',
        'id',
        'lang',
        'style',
        'accesskey',
        'tabindex',
        'onclick',
    ];

    /**
     * @return ImageRendererConfiguration
     */
    public function __construct()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'])) {
            $extensionConfiguration = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images']
            );

            if (!is_array($extensionConfiguration)) {
                $extensionConfiguration = [
                    'enableSmallDefaultImage' => true,
                ];
            }

            $this->extensionConfiguration = filter_var_array(
                $extensionConfiguration,
                [
                    'enableSmallDefaultImage' => FILTER_VALIDATE_BOOLEAN
                ],
                false
            );
        }

        $this->settings = [];
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);

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

    /**
     * @return string
     */
    public function getAbsRefPrefix()
    {
        $asbRefPrefix = '';
        if ($this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            $asbRefPrefix = $this->getTypoScriptFrontendController()->absRefPrefix;
        }

        return $asbRefPrefix;
    }

    /**
     * @return string
     */
    public function getLayoutKey()
    {
        return $this->settings['layoutKey'];
    }

    /**
     * @return array
     */
    public function getSourceCollection()
    {
        return $this->settings['sourceCollection'];
    }

    /**
     * @return array
     */
    protected function getTypoScriptSetup()
    {
        if (!$this->getTypoScriptFrontendController() instanceof TypoScriptFrontendController) {
            return [];
        }

        if (!$this->getTypoScriptFrontendController()->tmpl instanceof TemplateService) {
            return [];
        }

        return $this->getTypoScriptFrontendController()->tmpl->setup;
    }

    /**
     * @return array
     */
    public function getGenericTagAttributes()
    {
        return $this->genericTagAttributes;
    }

    /**
     * @return array
     */
    public function getExtensionConfiguration()
    {
        return $this->extensionConfiguration;
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
