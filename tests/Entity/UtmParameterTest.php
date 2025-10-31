<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\UtmBundle\Entity\UtmParameter;

/**
 * @internal
 */
#[CoversClass(UtmParameter::class)]
final class UtmParameterTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new UtmParameter();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'additionalParameters' => ['additionalParameters', ['key' => 'value']],
        ];
    }

    public function testAddAdditionalParameter(): void
    {
        $parameters = new UtmParameter();

        // Test adding a single parameter
        $parameters->addAdditionalParameter('custom1', 'value1');
        $this->assertSame(['custom1' => 'value1'], $parameters->getAdditionalParameters());

        // Test adding another parameter
        $parameters->addAdditionalParameter('custom2', 'value2');
        $this->assertSame([
            'custom1' => 'value1',
            'custom2' => 'value2',
        ], $parameters->getAdditionalParameters());

        // Test overwriting an existing parameter
        $parameters->addAdditionalParameter('custom1', 'new_value');
        $this->assertSame([
            'custom1' => 'new_value',
            'custom2' => 'value2',
        ], $parameters->getAdditionalParameters());
    }

    public function testToString(): void
    {
        $parameters = new UtmParameter();

        // Test with empty values
        $this->assertSame('UTM[-:-:-]', (string) $parameters);

        // Test with partial values
        $parameters->setSource('google');
        $this->assertSame('UTM[google:-:-]', (string) $parameters);

        $parameters->setMedium('cpc');
        $this->assertSame('UTM[google:cpc:-]', (string) $parameters);

        // Test with all values
        $parameters->setCampaign('spring_sale');
        $this->assertSame('UTM[google:cpc:spring_sale]', (string) $parameters);
    }

    public function testCreateTime(): void
    {
        $parameters = new UtmParameter();

        // createTime should be initialized by the CreateTimeColumn attribute handler
        // For testing purposes, we just verify the method exists and returns a ?DateTimeImmutable
        $createTimeReflection = new \ReflectionMethod(UtmParameter::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame('?DateTimeImmutable', (string) $createTimeReflection->getReturnType());
    }
}
