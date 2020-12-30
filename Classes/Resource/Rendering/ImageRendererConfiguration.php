<?php
declare(strict_types=1);

namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class ImageRendererConfiguration
 */
class ImageRendererConfiguration
{
    /**
     * @var array<string,mixed>
     */
    protected array $extensionConfiguration = [];

    protected TypoScriptService $typoScriptService;

    /**
     * @var array<string,mixed>
     */
    protected array $settings = [];

    /**
     * @var array<string>
     */
    protected array $genericTagAttributes = [
        'class',
        'dir',
        'id',
        'lang',
        'style',
        'accesskey',
        'tabindex',
        'onclick',
    ];

    public function __construct()
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'])) {
            $extensionConfiguration = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'],
                ['allowed_classes' => false]
            );

            if (!is_array($extensionConfiguration)) {
                $extensionConfiguration = [
                    'enableSmallDefaultImage' => true,
                ];
            }

            $extensionConfiguration = filter_var_array(
                $extensionConfiguration,
                [
                    'enableSmallDefaultImage' => FILTER_VALIDATE_BOOLEAN
                ],
                false
            );

            $this->extensionConfiguration = is_array($extensionConfiguration)
                ? $extensionConfiguration
                : []
            ;
        }

        $this->settings = [];
        $this->typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);

        $configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($this->getTypoScriptSetup());

        $settings = ObjectAccess::getPropertyPath(
            $configuration,
            'tt_content.textmedia.settings.responsive_image_rendering'
        );
        $settings = is_array($settings) ? $settings : [];

        $this->settings['layoutKey'] = $settings['layoutKey'] ?? 'default';

        $this->settings['sourceCollection'] =
            (isset($settings['sourceCollection']) && is_array($settings['sourceCollection']))
                ? $settings['sourceCollection']
                : [];
    }

    /**
     * @return string
     */
    public function getAbsRefPrefix(): string
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
    public function getLayoutKey(): string
    {
        return (string)$this->settings['layoutKey'];
    }

    /**
     * @return array<string,mixed>
     */
    public function getSourceCollection(): array
    {
        $sourceCollection = $this->settings['sourceCollection'];
        return is_array($sourceCollection) ? $sourceCollection : [];
    }

    /**
     * @return array<string,mixed>
     */
    protected function getTypoScriptSetup(): array
    {
        if ($this->getTypoScriptFrontendController() === null) {
            return [];
        }

        if ($this->getTypoScriptFrontendController()->tmpl === null) {
            return [];
        }

        return $this->getTypoScriptFrontendController()->tmpl->setup;
    }

    /**
     * @return array<string>
     */
    public function getGenericTagAttributes(): array
    {
        return $this->genericTagAttributes;
    }

    /**
     * @return array<string,mixed>
     */
    public function getExtensionConfiguration(): array
    {
        return $this->extensionConfiguration;
    }

    /**
     * @return TypoScriptFrontendController|null
     */
    protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
    {
        return $GLOBALS['TSFE'] ?? null;
    }
}
