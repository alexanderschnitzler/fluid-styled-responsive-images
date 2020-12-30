<?php
declare(strict_types=1);

namespace Schnitzler\FluidStyledResponsiveImages\Tests\Unit\Resource\Rendering;

use PHPUnit\Framework\MockObject\MockObject;
use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRenderer;
use Schnitzler\FluidStyledResponsiveImages\Resource\Rendering\ImageRendererConfiguration;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class ImageRendererTest
 */
class ImageRendererTest extends UnitTestCase
{

    /**
     * @var MockObject|ImageRendererConfiguration
     */
    protected $imageRendererConfiguration;

    /**
     * @var MockObject|ImageRenderer
     */
    protected $imageRenderer;

    /**
     * @var MockObject|File
     */
    protected $file;

    /**
     * @var array<int,MockObject>
     */
    protected array $processedFiles = [];

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpProcessedFiles();

        $this->file = $this->getMockBuilder(File::class)
            ->onlyMethods(['getProperty', 'process'])
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->file
            ->method('getProperty')
            ->willReturnCallback(function ($in): string {
                switch ($in) {
                    case 'title':
                        $out =  'title';
                        break;
                    case 'alternative':
                        $out =  'alt';
                        break;
                    default:
                        $out = '';
                        break;
                }

                return $out;
            });

        $this->imageRendererConfiguration = $this->getMockBuilder(ImageRendererConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->imageRendererConfiguration
            ->method('getAbsRefPrefix')
            ->willReturn('');

        $this->imageRendererConfiguration
            ->method('getGenericTagAttributes')
            ->willReturn([]);

        $this->imageRenderer = $this->getMockBuilder(ImageRenderer::class)
            ->onlyMethods(['getConfiguration'])
            ->getMock();

        $this->imageRenderer
            ->method('getConfiguration')
            ->willReturn($this->imageRendererConfiguration);
    }

    public function setUpProcessedFiles(): void
    {
        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->onlyMethods(['getPublicUrl', 'getProperty'])
            ->disableOriginalConstructor()
            ->getMock();

        $processedFile
            ->method('getPublicUrl')
            ->willReturn('image.jpg');

        $processedFile
            ->method('getProperty')
            ->willReturn(100);

        $this->processedFiles[0] = $processedFile;

        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->onlyMethods(['getPublicUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $processedFile
            ->method('getPublicUrl')
            ->willReturn('image360.jpg');

        $this->processedFiles[1] = $processedFile;

        $processedFile = $this->getMockBuilder(ProcessedFile::class)
            ->onlyMethods(['getPublicUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $processedFile
            ->method('getPublicUrl')
            ->willReturn('image720.jpg');

        $this->processedFiles[2] = $processedFile;
    }

    public function testWithSrcSetAndWithoutSourceCollection(): void
    {
        $this->file
            ->expects(self::at(1))
            ->method('process')
            ->willReturn($this->processedFiles[0]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getSourceCollection')
            ->willReturn([]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getLayoutKey')
            ->willReturn('srcset');

        $result = $this->imageRenderer->render(
            $this->file,
            '1000',
            '1000',
            []
        );

        self::assertEquals(
            '<img src="image.jpg" alt="alt" title="title" />',
            $result,
            'sizes-attribute is omitted when no sizes are given'
        );
    }

    public function testWithSrcSetAndSourceCollection(): void
    {
        $this->file
            ->expects(self::at(1))
            ->method('process')
            ->willReturn($this->processedFiles[1]);

        $this->file
            ->expects(self::at(2))
            ->method('process')
            ->willReturn($this->processedFiles[2]);

        $this->file
            ->expects(self::at(3))
            ->method('process')
            ->willReturn($this->processedFiles[0]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getSourceCollection')
            ->willReturn([
                10 => [
                    'width' => '360m',
                    'srcset' => '360w',
                ],
                20 => [
                    'width' => '720m',
                    'srcset' => '720w',
                    'sizes' => '(min-width: 360px) 720px',
                ]
            ]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getLayoutKey')
            ->willReturn('srcset');

        self::assertEquals(
            '<img src="image.jpg" alt="alt" title="title" srcset="image360.jpg 360w, image720.jpg 720w" sizes="(min-width: 360px) 720px" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

    public function testWithDataAndWithoutSourceCollection(): void
    {
        $this->file
            ->expects(self::at(1))
            ->method('process')
            ->willReturn($this->processedFiles[0]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getSourceCollection')
            ->willReturn([]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getLayoutKey')
            ->willReturn('data');

        self::assertEquals(
            '<img src="image.jpg" alt="alt" title="title" />',
            $this->imageRenderer->render(
                $this->file,
                '1000',
                '1000',
                []
            )
        );
    }

    public function testWithDataAndSourceCollection(): void
    {
        $this->file
            ->expects(self::at(1))
            ->method('process')
            ->willReturn($this->processedFiles[1]);

        $this->file
            ->expects(self::at(2))
            ->method('process')
            ->willReturn($this->processedFiles[2]);

        $this->file
            ->expects(self::at(3))
            ->method('process')
            ->willReturn($this->processedFiles[0]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getSourceCollection')
            ->willReturn([
                10 => [
                    'width' => '360m',
                    'dataKey' => 'small',
                ],
                20 => [
                    'width' => '720m',
                    'dataKey' => 'small-retina',
                ]
            ]);

        $this->imageRendererConfiguration
            ->expects(self::once())
            ->method('getLayoutKey')
            ->willReturn('data');

        self::assertEquals(
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
