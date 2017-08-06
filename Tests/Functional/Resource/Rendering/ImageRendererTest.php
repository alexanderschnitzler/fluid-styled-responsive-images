<?php

namespace Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering;

use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRenderer;
use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRendererConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\TimeTracker\NullTimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Class Schnitzler\FluidStyledResponsiveImages\Tests\Functional\Resource\Rendering\ImageRendererTest
 */
class ImageRendererTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/version',
        'typo3/sysext/workspaces',
        'typo3conf/ext/fluid_styled_responsive_images'
    ];

    /**
     * @var File
     */
    protected $file;

    /**
     * @return void
     */
    public function setUp()
    {
        $this->configurationToUseInTestInstance['GFX']['processor'] = getenv('PROCESSOR') ?: 'ImageMagick';
        $this->configurationToUseInTestInstance['GFX']['processor_path'] = getenv('PROCESSOR_PATH') ?: '/usr/local/';
        $this->configurationToUseInTestInstance['GFX']['processor_path_lzw'] = getenv('PROCESSOR_PATH_LZW') ?: '/usr/local/';

        parent::setUp();
        parent::setUpBackendUserFromFixture(1);

        /** @var ConnectionPool $connectionPool */
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $fixtureRootPath = ORIGINAL_ROOT . 'typo3conf/ext/fluid_styled_responsive_images/.Build/fixtures/';
        foreach (['pages', 'sys_file_reference'] as $table) {
            $connectionPool->getConnectionForTable($table)->truncate($table);
            $this->importDataSet($fixtureRootPath . $table . '.xml');
        }

        // Clean up processed files
        $connectionPool->getConnectionForTable('sys_file_processedfile')->truncate('sys_file_processedfile');
        foreach (glob(PATH_site . 'fileadmin/_processed_/*') as $file) {
            if (is_dir($file)) {
                GeneralUtility::rmdir($file, true);
            }
        }

        /** @var ResourceFactory $storage */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        /** @var Folder $folder */
        $folder = $resourceFactory->retrieveFileOrFolderObject('1:/');
        $file = $folder->createFile('guernica.jpg');
        $file->setContents(file_get_contents(ORIGINAL_ROOT . 'typo3conf/ext/fluid_styled_responsive_images/.Build/fixtures/guernica.jpg'));

        /** @var FileRepository $fileReposistory */
        $fileReposistory = GeneralUtility::makeInstance(FileRepository::class);
        $this->file = $fileReposistory->findByUid(1);
    }

    protected function setUpTSFE()
    {
        $GLOBALS['TT'] = new NullTimeTracker();

        /** @var TypoScriptFrontendController $TSFE */
        $TSFE = GeneralUtility::makeInstance(TypoScriptFrontendController::class, [], 1, 0);
        $TSFE->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $TSFE->initTemplate();
        $TSFE->getPageAndRootline();
        $TSFE->getConfigArray();

        $GLOBALS['TSFE'] = $TSFE;
    }

    public function testSetupWorksCorrectly()
    {
        static::assertInstanceOf(File::class, $this->file);
        static::assertSame('beb5e4faa5ada0f57407976ca75e8719e7dbf02d', $this->file->getSha1());
        static::assertSame(739459, $this->file->getSize());
        static::assertSame('image/jpeg', $this->file->getMimeType());

        $expectedDefaultCropArea = ['x' => 0.0, 'y' => 0.0, 'width' => 1.0, 'height' => 1.0];
        $actualDefaultCropArea = CropVariantCollection::create((string)$this->file->getProperty('crop'))->getCropArea()->asArray();
        static::assertSame($expectedDefaultCropArea, $actualDefaultCropArea);
    }

    public function testEnableSmallDefaultImageRendersSmallDefaultImage()
    {
        $configuration = ['enableSmallDefaultImage' => true];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        /** @var ImageRendererConfiguration $imageRendererConfiguration */
        $imageRendererConfiguration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        static::assertSame($configuration, $imageRendererConfiguration->getExtensionConfiguration());

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render($this->file, 1200, 1200);

        // If no rendering mode is enabled, width and height should be set
        // to the width and height of the processed image
        static::assertNotFalse(strpos($html, 'width="360"', 'Rendered width was not 360'));
        static::assertNotFalse(strpos($html, 'height="135"'), 'Rendered height was not 135');
    }

    public function testDisableSmallDefaultImageRendersOriginalImage()
    {
        $configuration = ['enableSmallDefaultImage' => false];
        $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fluid_styled_responsive_images'] = serialize($configuration);

        /** @var ImageRendererConfiguration $imageRendererConfiguration */
        $imageRendererConfiguration = GeneralUtility::makeInstance(ImageRendererConfiguration::class);
        static::assertSame($configuration, $imageRendererConfiguration->getExtensionConfiguration());

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render($this->file, 1200, 1200);

        // If no rendering mode is enabled, width and height should be set
        // to the width and height of the original image
        static::assertNotFalse(strpos($html, 'width="1200"'), 'Rendered width was not 1200');
        static::assertNotFalse(strpos($html, 'height="450"'), 'Rendered height was not 450');
    }

    public function testRenderingWithSrcSetConfiguration()
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

        static::assertNotFalse(strpos($html, '320w'), '320w must be rendered');
        static::assertNotFalse(strpos($html, '640w'), '640w must be rendered');
        static::assertNotFalse(strpos($html, '720w'), '720w must be rendered');
        static::assertNotFalse(strpos($html, '960w'), '960w must be rendered');

        // 1260 is bigger than the defined 1200 max width, therefore it must not be rendered
        static::assertFalse(strpos($html, '1260w'), '1260w must not be rendered');
        unset($html);

        // ---------------------------------------------------------------------------------------------------------------------

        $html = $imageRenderer->render($this->file, 1600, 1600);

        static::assertNotFalse(strpos($html, '320w'), '320w must be rendered');
        static::assertNotFalse(strpos($html, '640w'), '640w must be rendered');
        static::assertNotFalse(strpos($html, '720w'), '720w must be rendered');
        static::assertNotFalse(strpos($html, '960w'), '960w must be rendered');

        // 1260 is smaller than the defined 1600 max width, therefore it must not be rendered
        static::assertNotFalse(strpos($html, '1260w'), '1260w must be rendered');
    }

    public function testRenderingWithCropVariantCollectionConfiguration()
    {
        /** @var FileRepository $repository */
        $repository = GeneralUtility::makeInstance(FileRepository::class);
        $fileReferences = $repository->findByRelation('tt_content', 'image', 1);
        static::assertCount(1, $fileReferences);

        /** @var FileReference $fileReference */
        $fileReference = reset($fileReferences);
        static::assertInstanceOf(FileReference::class, $fileReference);

        $cropVariantCollection = CropVariantCollection::create((string)$fileReference->getProperty('crop'));
        static::assertNotEmpty($cropVariantCollection->asArray());

        $defaultCropArea = $cropVariantCollection->getCropArea();
        static::assertFalse($defaultCropArea->isEmpty());
        static::assertSame(0.0, $defaultCropArea->asArray()['x']);
        static::assertSame(0.0, $defaultCropArea->asArray()['y']);
        static::assertSame(0.5, $defaultCropArea->asArray()['width']);
        static::assertSame(0.5, $defaultCropArea->asArray()['height']);

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            $fileReference->getProperty('width'), // 3200
            $fileReference->getProperty('height') // 1200
        );

        static::assertNotFalse(strpos($html, 'width="3200"'), 'width="3200" must be rendered');
        static::assertNotFalse(strpos($html, 'height="1200"'), 'height="1200" must be rendered');

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            1600,
            1200
        );

        static::assertNotFalse(strpos($html, 'width="1600"'), 'width="1600" must be rendered');
        static::assertNotFalse(strpos($html, 'height="600"'), 'height="600" must be rendered');
    }

    public function testRenderingWithSrcSetAndCropVariantCollectionConfiguration()
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
        static::assertCount(1, $fileReferences);

        /** @var FileReference $fileReference */
        $fileReference = reset($fileReferences);
        static::assertInstanceOf(FileReference::class, $fileReference);

        $cropVariantCollection = CropVariantCollection::create((string)$fileReference->getProperty('crop'));
        static::assertNotEmpty($cropVariantCollection->asArray());

        $defaultCropArea = $cropVariantCollection->getCropArea();
        static::assertFalse($defaultCropArea->isEmpty());
        static::assertSame(0.0, $defaultCropArea->asArray()['x']);
        static::assertSame(0.0, $defaultCropArea->asArray()['y']);
        static::assertSame(0.5, $defaultCropArea->asArray()['width']);
        static::assertSame(0.5, $defaultCropArea->asArray()['height']);

        // ---------------------------------------------------------------------------------------------------------------------

        /** @var ImageRenderer $imageRenderer */
        $imageRenderer = GeneralUtility::makeInstance(ImageRenderer::class);
        $html = $imageRenderer->render(
            $fileReference,
            $fileReference->getProperty('width'), // 3200
            $fileReference->getProperty('height') // 1200
        );

        static::assertNotFalse(strpos($html, '320w'), '320w must be rendered');
        static::assertNotFalse(strpos($html, '640w'), '640w must be rendered');
        static::assertNotFalse(strpos($html, '720w'), '720w must be rendered');
        static::assertNotFalse(strpos($html, '960w'), '960w must be rendered');
        static::assertNotFalse(strpos($html, '1260w'), '1260w must be rendered');
    }
}
