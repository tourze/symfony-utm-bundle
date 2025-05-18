<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Service\UtmParametersExtractor;

class UtmParametersExtractorTest extends TestCase
{
    private LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    public function testExtract_withStandardParameters_returnsPopulatedDto(): void
    {
        // Arrange
        $request = new Request([
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring_sale',
            'utm_term' => 'running shoes',
            'utm_content' => 'banner_1',
            'other_param' => 'ignored'
        ]);
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('提取UTM参数', $this->anything());
        
        $extractor = new UtmParametersExtractor($this->logger);
        
        // Act
        $result = $extractor->extract($request);
        
        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertSame('google', $result->getSource());
        $this->assertSame('cpc', $result->getMedium());
        $this->assertSame('spring_sale', $result->getCampaign());
        $this->assertSame('running shoes', $result->getTerm());
        $this->assertSame('banner_1', $result->getContent());
        $this->assertEmpty($result->getAdditionalParameters());
    }
    
    public function testExtract_withNoParameters_returnsEmptyDto(): void
    {
        // Arrange
        $request = new Request();
        
        $this->logger->expects($this->never())
            ->method('debug');
        
        $extractor = new UtmParametersExtractor($this->logger);
        
        // Act
        $result = $extractor->extract($request);
        
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
    
    public function testExtract_withCustomParameters_includesCustomParameters(): void
    {
        // Arrange
        $request = new Request([
            'utm_source' => 'facebook',
            'utm_custom1' => 'value1',
            'utm_custom2' => 'value2',
        ]);
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('提取UTM参数', $this->anything());
        
        $extractor = new UtmParametersExtractor(
            $this->logger,
            ['utm_source', 'utm_medium'], // 只允许这两个标准参数
            ['utm_custom1', 'utm_custom2'] // 允许这两个自定义参数
        );
        
        // Act
        $result = $extractor->extract($request);
        
        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertSame('facebook', $result->getSource());
        $this->assertNull($result->getMedium());
        $this->assertNull($result->getCampaign());
        $this->assertNull($result->getTerm());
        $this->assertNull($result->getContent());
        
        $additionalParams = $result->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('value1', $additionalParams['custom1']);
        $this->assertSame('value2', $additionalParams['custom2']);
    }
    
    public function testHasUtmParameters_withStandardParameter_returnsTrue(): void
    {
        // Arrange
        $request = new Request([
            'utm_source' => 'google',
            'other_param' => 'value'
        ]);
        
        $extractor = new UtmParametersExtractor($this->logger);
        
        // Act
        $result = $extractor->hasUtmParameters($request);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testHasUtmParameters_withCustomParameter_returnsTrue(): void
    {
        // Arrange
        $request = new Request([
            'utm_custom' => 'value',
            'other_param' => 'value'
        ]);
        
        $extractor = new UtmParametersExtractor(
            $this->logger,
            ['utm_source'], // 标准参数
            ['utm_custom'] // 自定义参数
        );
        
        // Act
        $result = $extractor->hasUtmParameters($request);
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testHasUtmParameters_withNoUtmParameters_returnsFalse(): void
    {
        // Arrange
        $request = new Request([
            'other_param' => 'value'
        ]);
        
        $extractor = new UtmParametersExtractor($this->logger);
        
        // Act
        $result = $extractor->hasUtmParameters($request);
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testHasUtmParameters_withEmptyRequest_returnsFalse(): void
    {
        // Arrange
        $request = new Request();
        
        $extractor = new UtmParametersExtractor($this->logger);
        
        // Act
        $result = $extractor->hasUtmParameters($request);
        
        // Assert
        $this->assertFalse($result);
    }
} 