<?php
namespace Schnitzler\FluidStyledResponsiveImages\Resource\Rendering;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\TagBuilder;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class ImageRendererTest
 * @package Schnitzler\FluidStyledResponsiveImages\Resource\Rendering
 */
class ImageRendererTest extends UnitTestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected $typoScriptFrontendController = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|ImageRenderer
     */
    protected $imageRenderer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AccessibleObjectInterface|File
     */
    protected $file;

    /**
     * @var TagBuilder
     */
    protected $tagBuilder;

    /**
     * @var array
     */
    protected $processedFiles = [];

    /**
     * @return void
     */
    public function setUp()
    {
        $this->setUpProcessedFiles();

        $this->tagBuilder = GeneralUtility::makeInstance(TagBuilder::class);

        $this->file = $this->getAccessibleMock(
            File::class,
            ['getProperty', 'process'],
            [],
            '',
            false
        );

        $this->file
            ->expects($this->any())
            ->method('getProperty')
            ->will($this->returnCallback(function ($in) {
                switch ($in) {
                    case 'title':
                        return 'title';
                        break;
                    case 'alternative':
                        return 'alt';
                        break;
                    default:
                        return '';
                        break;
                }
            }));

        $this->typoScriptFrontendController = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            [],
            [],
            '',
            false
        );

        $this->imageRenderer = $this->getAccessibleMock(
            ImageRenderer::class,
            ['getConfiguration', 'getTypoScriptFrontendController'],
            [],
            '',
            false
        );

        $this->imageRenderer
            ->expects($this->any())
            ->method('getTypoScriptFrontendController')
            ->will($this->returnValue($this->typoScriptFrontendController));

        $this->imageRenderer->_set('tagBuilder', $this->tagBuilder);
    }

    /**
     * @return void
     */
    public function setUpProcessedFiles()
    {
        $processedFile = $this->getMock(
            ProcessedFile::class,
            ['getPublicUrl'],
            [],
            '',
            false
        );

        $processedFile
            ->expects($this->any())
            ->method('getPublicUrl')
            ->will($this->returnValue('image.jpg'));

        $this->processedFiles[0] = $processedFile;

        $processedFile = $this->getMock(
            ProcessedFile::class,
            ['getPublicUrl'],
            [],
            '',
            false
        );

        $processedFile
            ->expects($this->any())
            ->method('getPublicUrl')
            ->will($this->returnValue('image360.jpg'));

        $this->processedFiles[1] = $processedFile;

        $processedFile = $this->getMock(
            ProcessedFile::class,
            ['getPublicUrl'],
            [],
            '',
            false
        );

        $processedFile
            ->expects($this->any())
            ->method('getPublicUrl')
            ->will($this->returnValue('image720.jpg'));

        $this->processedFiles[2] = $processedFile;
    }

    /**
     * @return void
     */
    public function testWithSrcSetAndWithoutSourceCollection()
    {
        $this->file
            ->expects($this->at(1))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[0]));

        $this->imageRenderer->_set(
            'settings',
            [
                'layoutKey' => 'srcset',
                'sourceCollection' => [],
            ]
        );

        $this->assertEquals(
            '<img src="image.jpg" alt="alt" title="title" sizes="" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

    /**
     * @return void
     */
    public function testWithSrcSetAndSourceCollection()
    {
        $this->file
            ->expects($this->at(1))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[1]));

        $this->file
            ->expects($this->at(2))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[2]));

        $this->file
            ->expects($this->at(3))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[0]));

        $this->imageRenderer->_set(
            'settings',
            [
                'layoutKey' => 'srcset',
                'sourceCollection' => [
                    10 => [
                        'width' => '360m',
                        'srcset' => '360w',
                    ],
                    20 => [
                        'width' => '720m',
                        'srcset' => '720w',
                        'sizes' => '(min-width: 360px) 720px',
                    ]
                ],
            ]
        );

        $this->assertEquals(
            '<img src="image.jpg" alt="alt" title="title" srcset="image360.jpg 360w, image720.jpg 720w" sizes="(min-width: 360px) 720px" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

    /**
     * @return void
     */
    public function testWithDataAndWithoutSourceCollection()
    {
        $this->file
            ->expects($this->at(1))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[0]));

        $this->imageRenderer->_set(
            'settings',
            [
                'layoutKey' => 'data',
                'sourceCollection' => [],
            ]
        );

        $this->assertEquals(
            '<img src="image.jpg" alt="alt" title="title" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

    /**
     * @return void
     */
    public function testWithDataAndSourceCollection()
    {
        $this->file
            ->expects($this->at(1))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[1]));

        $this->file
            ->expects($this->at(2))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[2]));

        $this->file
            ->expects($this->at(3))
            ->method('process')
            ->will($this->returnValue($this->processedFiles[0]));

        $this->imageRenderer->_set(
            'settings',
            [
                'layoutKey' => 'data',
                'sourceCollection' => [
                    10 => [
                        'width' => '360m',
                        'dataKey' => 'small',
                    ],
                    20 => [
                        'width' => '720m',
                        'dataKey' => 'small-retina',
                    ]
                ],
            ]
        );

        $this->assertEquals(
            '<img src="image.jpg" alt="alt" title="title" data-small="image360.jpg" data-small-retina="image720.jpg" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

}
