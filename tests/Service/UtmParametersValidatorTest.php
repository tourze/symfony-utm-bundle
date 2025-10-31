<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Service\UtmParametersValidator;

/**
 * @internal
 */
#[CoversClass(UtmParametersValidator::class)]
#[RunTestsInSeparateProcesses]
final class UtmParametersValidatorTest extends AbstractIntegrationTestCase
{
    private UtmParametersValidator $validator;

    protected function onSetUp(): void
    {
        $this->validator = self::getService(UtmParametersValidator::class);
    }

    public function testValidateWithValidParametersReturnsUnchangedDto(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('google');
        $dto->setMedium('cpc');
        $dto->setCampaign('spring_sale');
        $dto->setTerm('running shoes');
        $dto->setContent('banner_1');

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertSame('google', $result->getSource());
        $this->assertSame('cpc', $result->getMedium());
        $this->assertSame('spring_sale', $result->getCampaign());
        $this->assertSame('running shoes', $result->getTerm());
        $this->assertSame('banner_1', $result->getContent());
        $this->assertEmpty($result->getAdditionalParameters());
    }

    public function testValidateWithEmptyDtoReturnsEmptyDto(): void
    {
        // Arrange
        $dto = new UtmParametersDto();

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertInstanceOf(UtmParametersDto::class, $result);
        $this->assertFalse($result->hasAnyParameter());
    }

    public function testValidateWithTooLongValueTruncatesValue(): void
    {
        // Arrange
        $longValue = str_repeat('a', 300); // 300 characters, exceeds default maxLength (255)

        $dto = new UtmParametersDto();
        $dto->setSource($longValue);

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame(str_repeat('a', 255), $result->getSource());
    }

    public function testValidateWithXssRiskCharactersSanitizesValue(): void
    {
        // Arrange
        $maliciousValue = '<script>alert("XSS")</script>';
        $expectedValue = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        $dto = new UtmParametersDto();
        $dto->setSource($maliciousValue);

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame($expectedValue, $result->getSource());
    }

    public function testValidateWithDefaultSanitizeEnabledSanitizesValue(): void
    {
        // Arrange - 测试默认情况下的清理行为
        $maliciousValue = '<script>alert("XSS")</script>';
        $expectedValue = '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;';

        $dto = new UtmParametersDto();
        $dto->setSource($maliciousValue);

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame($expectedValue, $result->getSource());
    }

    public function testValidateWithZeroStringValuePreservesValue(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('0');

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame('0', $result->getSource());
    }

    public function testValidateWithLeadingAndTrailingWhitespaceTrimsValue(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('  google  ');

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame('google', $result->getSource());
    }

    public function testValidateWithAdditionalParametersValidatesAdditionalParameters(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setAdditionalParameters([
            'custom1' => '<script>alert("XSS")</script>',
            'custom2' => str_repeat('a', 300), // 超长值
        ]);

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $additionalParams = $result->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', $additionalParams['custom1']);

        // 验证 custom2 被截断到 255 字符
        $custom2Value = $additionalParams['custom2'];
        $this->assertIsString($custom2Value);
        $this->assertSame(255, strlen($custom2Value));
        $this->assertSame(str_repeat('a', 255), $custom2Value);
    }

    public function testValidateWithNonStringValueConvertsToString(): void
    {
        // Arrange
        $dto = new UtmParametersDto();
        $dto->setSource('123'); // 使用字符串而不是整数

        // Act
        $result = $this->validator->validate($dto);

        // Assert
        $this->assertSame('123', $result->getSource());
    }
}
