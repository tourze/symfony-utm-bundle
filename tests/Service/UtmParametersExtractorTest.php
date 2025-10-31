<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Service\UtmParametersExtractor;

/**
 * @internal
 */
#[CoversClass(UtmParametersExtractor::class)]
#[RunTestsInSeparateProcesses]
final class UtmParametersExtractorTest extends AbstractIntegrationTestCase
{
    private UtmParametersExtractor $extractor;

    protected function onSetUp(): void
    {
        $this->extractor = self::getService(UtmParametersExtractor::class);
    }

    public function testExtractWithStandardParametersReturnsPopulatedDto(): void
    {
        // Arrange
        $request = new Request([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring_sale',
            'utm_term' => 'running shoes',
            'utm_content' => 'banner_1',
            'other_param' => 'ignored',
        ]);

        // Act
        $result = $this->extractor->extract($request);

        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertSame('google', $result->getSource());
        $this->assertSame('cpc', $result->getMedium());
        $this->assertSame('spring_sale', $result->getCampaign());
        $this->assertSame('running shoes', $result->getTerm());
        $this->assertSame('banner_1', $result->getContent());
        $this->assertEmpty($result->getAdditionalParameters());
    }

    public function testExtractWithNoParametersReturnsEmptyDto(): void
    {
        // Arrange
        $request = new Request();

        // Act
        $result = $this->extractor->extract($request);

        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertNull($result->getSource());
        $this->assertNull($result->getMedium());
        $this->assertNull($result->getCampaign());
        $this->assertNull($result->getTerm());
        $this->assertNull($result->getContent());
        $this->assertEmpty($result->getAdditionalParameters());
        $this->assertFalse($result->hasAnyParameter());
    }

    public function testHasUtmParametersWithStandardParameterReturnsTrue(): void
    {
        // Arrange
        $request = new Request([
            'utm_source' => 'google',
            'other_param' => 'value',
        ]);

        // Act
        $result = $this->extractor->hasUtmParameters($request);

        // Assert
        $this->assertTrue($result);
    }

    public function testHasUtmParametersWithNoUtmParametersReturnsFalse(): void
    {
        // Arrange
        $request = new Request([
            'other_param' => 'value',
        ]);

        // Act
        $result = $this->extractor->hasUtmParameters($request);

        // Assert
        $this->assertFalse($result);
    }

    public function testHasUtmParametersWithEmptyRequestReturnsFalse(): void
    {
        // Arrange
        $request = new Request();

        // Act
        $result = $this->extractor->hasUtmParameters($request);

        // Assert
        $this->assertFalse($result);
    }
}
