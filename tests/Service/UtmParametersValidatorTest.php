<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Service\UtmParametersValidator;

class UtmParametersValidatorTest extends TestCase
{
    private LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    public function testValidate_withValidParameters_returnsUnchangedDto(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('google')
            ->setMedium('cpc')
            ->setCampaign('spring_sale')
            ->setTerm('running shoes')
            ->setContent('banner_1');
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertSame('google', $result->getSource());
        $this->assertSame('cpc', $result->getMedium());
        $this->assertSame('spring_sale', $result->getCampaign());
        $this->assertSame('running shoes', $result->getTerm());
        $this->assertSame('banner_1', $result->getContent());
        $this->assertEmpty($result->getAdditionalParameters());
    }
    
    public function testValidate_withEmptyDto_returnsEmptyDto(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertFalse($result->hasAnyParameter());
    }
    
    public function testValidate_withTooLongValue_truncatesValue(): void
    {
        // Arrange
        $maxLength = 10;
        $longValue = str_repeat('a', 20); // 20 characters, exceeds maxLength
        
        $dto = new UtmParametersDto();
        $dto->setSource($longValue);
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('UTM参数值过长，已截断', $this->anything());
        
        $validator = new UtmParametersValidator($this->logger, $maxLength);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame(str_repeat('a', $maxLength), $result->getSource());
    }
    
    public function testValidate_withXssRiskCharacters_sanitizesValue(): void
    {
        // Arrange
        $maliciousValue = '<script>alert("XSS")</script>';
        $expectedValue = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';
        
        $dto = new UtmParametersDto();
        $dto->setSource($maliciousValue);
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame($expectedValue, $result->getSource());
    }
    
    public function testValidate_withSanitizeDisabled_doesNotSanitizeValue(): void
    {
        // Arrange
        $maliciousValue = '<script>alert("XSS")</script>';
        
        $dto = new UtmParametersDto();
        $dto->setSource($maliciousValue);
        
        $validator = new UtmParametersValidator($this->logger, null, false);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame($maliciousValue, $result->getSource());
    }
    
    public function testValidate_withZeroStringValue_preservesValue(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('0');
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame('0', $result->getSource());
    }
    
    public function testValidate_withLeadingAndTrailingWhitespace_trimsValue(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('  google  ');
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame('google', $result->getSource());
    }
    
    public function testValidate_withAdditionalParameters_validatesAdditionalParameters(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setAdditionalParameters([
            'custom1' => '<script>alert("XSS")</script>',
            'custom2' => str_repeat('a', 300), // 超长值
        ]);
        
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('UTM附加参数值过长，已截断', $this->anything());
        
        $validator = new UtmParametersValidator($this->logger, 255);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $additionalParams = $result->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $additionalParams['custom1']);
        $this->assertSame(255, strlen($additionalParams['custom2']));
        $this->assertSame(str_repeat('a', 255), $additionalParams['custom2']);
    }
    
    public function testValidate_withNonStringValue_convertsToString(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource(123); // 整数值
        
        $validator = new UtmParametersValidator($this->logger);
        
        // Act
        $result = $validator->validate($dto);
        
        // Assert
        $this->assertSame('123', $result->getSource());
    }
} 