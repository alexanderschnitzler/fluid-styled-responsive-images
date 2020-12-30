<?php
declare(strict_types=1);

namespace Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering;

use Psr\Log\NullLogger;
use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRenderer;
use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRendererConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering\ImageRendererTest
 */
class ImageRendererTest extends FunctionalTestCase
{
    /**
     * @var iterable<int,string>
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/workspaces',
        'typo3conf/ext/fluid_styled_responsive_images'
    ];

    protected ?File $file = null;

    public function setUp(): void
    {
        $this->configurationToUseInTestInstance['GFX']['processor'] = getenv('PROCESSOR') ?? 'ImageMagick';
        $this->configurationToUseInTestInstance['GFX']['processor_path'] = getenv('PROCESSOR_PATH') ?? '/usr/local/';
        $this->configurationToUseInTestInstance['GFX']['processor_path_lzw'] = getenv('PROCESSOR_PATH_LZW') ?? '/usr/local/';

        parent::setUp();
        parent::setUpBackendUserFromFixture(1);

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $fixtureRootPath = ORIGINAL_ROOT . 'typo3conf/ext/fluid_styled_responsive_images/.Build/fixtures/';
        foreach (['pages', 'sys_file', 'sys_file_reference', 'sys_file_metadata'] as $table) {
            $connectionPool->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        // Clean up processed files
        $connectionPool->getConnectionForTable('sys_file_processedfile')->truncate('sys_file_processedfile');
        $files = glob(Environment::getPublicPath() . '/fileadmin/_processed_/*');
        $files = is_array($files) ? $files : [];
        foreach ($files as $file) {
            if (is_dir($file)) {
                GeneralUtility::rmdir($file, true);
            }
        }

        copy(
            ORIGINAL_ROOT . 'typo3conf/ext/fluid_styled_responsive_images/.Build/fixtures/guernica.jpg',
            $this->instancePath . '/fileadmin/guernica.jpg'
        );

        /** @var FileRepository $fileReposistory */
        $fileReposistory = GeneralUtility::makeInstance(FileRepository::class);
        $this->file = $fileReposistory->findByUid(1);
    }

    protected function setUpTSFE(): void
    {
        $GLOBALS['TT'] = new TimeTracker(false);
        $GLOBALS['TYPO3_REQUEST'] = new ServerRequest(
            new Uri('')
        );
        $GLOBALS['TYPO3_REQUEST'] = $GLOBALS['TYPO3_REQUEST']->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);

        $site = new Site('default', 1, []);
        /** @var TypoScriptFrontendController $TSFE */
        $TSFE = new TypoScriptFrontendController(
            $context,
            $site,
            $site->getDefaultLanguage(),
            new PageArguments(1, '0', []),
            new FrontendUserAuthentication()
        );
        $TSFE->setLogger(new NullLogger());
        $TSFE->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $TSFE->getPageAndRootlineWithDomain(1, $GLOBALS['TYPO3_REQUEST']);
        $TSFE->getConfigArray();

        $GLOBALS['TSFE'] = $TSFE;
    }

    public function testSetupWorksCorrectly(): void
    {
        self::assertSame('beb5e4faa5ada0f57407976ca75e8719e7dbf02d', $this->file->getSha1());
        self::assertSame(739459, $this->file->getSize());
        self::assertSame('image/jpeg', $this->file->getMimeType());

        $expectedDefaultCropArea = ['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0];
        $actualDefaultCropArea = CropVariantCollection::create((string)$this->file->getProperty('crop'))->getCropArea()->asArray();
        self::assertSame($expectedDefaultCropArea, $actualDefaultCropArea);
    }

    public function testEnableSmallDefaultImageRendersSmallDefaultImage(): void
    {
        $configuration = ['enableSmallDefaultImage' => true];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        /** @var ImageRendererConfiguration $imageRendererConfiguration */
        $imageRendererConfiguration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        self::assertSame($configuration, $imageRendererConfiguration->getExtensionConfiguration());

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render($this->file, 1200, 1200);

        // If no rendering mode is enabled, width and height should be set
        // to the width and height of the processed image
        self::assertStringContainsString('width="360"', $html);
        self::assertStringContainsString('height="135"', $html, 'Rendered height was not 135');
    }

    public function testDisableSmallDefaultImageRendersOriginalImage(): void
    {
        $configuration = ['enableSmallDefaultImage' => false];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        /** @var ImageRendererConfiguration $imageRendererConfiguration */
        $imageRendererConfiguration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        self::assertSame($configuration, $imageRendererConfiguration->getExtensionConfiguration());

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render($this->file, 1200, 1200);

        // If no rendering mode is enabled, width and height should be set
        // to the width and height of the original image
        self::assertStringContainsString('width="1200"', $html, 'Rendered width was not 1200');
        self::assertStringContainsString('height="450"', $html, 'Rendered height was not 450');
    }

    public function testRenderingWithSrcSetConfiguration(): void
    {
        parent::setUpFrontendRootPage(
            1,
            [
                'EXT:fluid_styled_responsive_images/.Build/fixtures/typoscript/setup.ts',
                'EXT:fluid_styled_responsive_images/.Build/fixtures/typoscript/srcset.ts',
            ]
        );

        $this->setUpTSFE();

        $configuration = ['enableSmallDefaultImage' => false];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);

        // ---------------------------------------------------------------------------------------------------------------------

        $html = $imageRenderer->render($this->file, 1200, 1200);

        self::assertStringContainsString('320w', $html, '320w must be rendered');
        self::assertStringContainsString('640w', $html, '640w must be rendered');
        self::assertStringContainsString('720w', $html, '720w must be rendered');
        self::assertStringContainsString('960w', $html, '960w must be rendered');

        // 1260 is bigger than the defined 1200 max width, therefore it must not be rendered
        self::assertStringNotContainsString('1260w', $html, '1260w must not be rendered');
        unset($html);

        // ---------------------------------------------------------------------------------------------------------------------

        $html = $imageRenderer->render($this->file, 1600, 1600);

        self::assertStringContainsString('320w', $html, '320w must be rendered');
        self::assertStringContainsString('640w', $html, '640w must be rendered');
        self::assertStringContainsString('720w', $html, '720w must be rendered');
        self::assertStringContainsString('960w', $html, '960w must be rendered');

        // 1260 is smaller than the defined 1600 max width, therefore it must not be rendered
        self::assertStringContainsString('1260w', $html, '1260w must be rendered');
    }

    public function testRenderingWithCropVariantCollectionConfiguration(): void
    {
        /** @var FileRepository $repository */
        $repository = GeneralUtility::makeInstance(FileRepository::class);
        $fileReferences = $repository->findByRelation('tt_content', 'image', 1);
        self::assertCount(1, $fileReferences);

        $fileReference = reset($fileReferences);
        self::assertInstanceOf(FileReference::class, $fileReference);

        $cropVariantCollection = CropVariantCollection::create((string)$fileReference->getProperty('crop'));
        self::assertNotEmpty($cropVariantCollection->asArray());

        $defaultCropArea = $cropVariantCollection->getCropArea();
        self::assertFalse($defaultCropArea->isEmpty());
        self::assertSame(0.0, $defaultCropArea->asArray()['x']);
        self::assertSame(0.0, $defaultCropArea->asArray()['y']);
        self::assertSame(0.5, $defaultCropArea->asArray()['width']);
        self::assertSame(0.5, $defaultCropArea->asArray()['height']);

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            $fileReference->getProperty('width'), // 3200
            $fileReference->getProperty('height') // 1200
        );

        self::assertStringContainsString('width="3200"', $html, 'width="3200" must be rendered');
        self::assertStringContainsString('height="1200"', $html, 'height="1200" must be rendered');

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            1600,
            1200
        );

        self::assertStringContainsString('width="1600"', $html, 'width="1600" must be rendered');
        self::assertStringContainsString('height="600"', $html, 'height="600" must be rendered');
    }

    public function testRenderingWithSrcSetAndCropVariantCollectionConfiguration(): void
    {
        parent::setUpFrontendRootPage(
            1,
            [
                'EXT:fluid_styled_responsive_images/.Build/fixtures/typoscript/setup.ts',
                'EXT:fluid_styled_responsive_images/.Build/fixtures/typoscript/srcset.ts',
            ]
        );

        $this->setUpTSFE();

        $configuration = ['enableSmallDefaultImage' => false];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var FileRepository $repository */
        $repository = GeneralUtility::makeInstance(FileRepository::class);
        $fileReferences = $repository->findByRelation('tt_content', 'image', 1);
        self::assertCount(1, $fileReferences);

        $fileReference = reset($fileReferences);
        self::assertInstanceOf(FileReference::class, $fileReference);

        $cropVariantCollection = CropVariantCollection::create((string)$fileReference->getProperty('crop'));
        self::assertNotEmpty($cropVariantCollection->asArray());

        $defaultCropArea = $cropVariantCollection->getCropArea();
        self::assertFalse($defaultCropArea->isEmpty());
        self::assertSame(0.0, $defaultCropArea->asArray()['x']);
        self::assertSame(0.0, $defaultCropArea->asArray()['y']);
        self::assertSame(0.5, $defaultCropArea->asArray()['width']);
        self::assertSame(0.5, $defaultCropArea->asArray()['height']);

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            $fileReference->getProperty('width'), // 3200
            $fileReference->getProperty('height') // 1200
        );

        self::assertStringContainsString('320w', $html, '320w must be rendered');
        self::assertStringContainsString('640w', $html, '640w must be rendered');
        self::assertStringContainsString('720w', $html, '720w must be rendered');
        self::assertStringContainsString('960w', $html, '960w must be rendered');
        self::assertStringContainsString('1260w', $html, '1260w must be rendered');
    }
}
