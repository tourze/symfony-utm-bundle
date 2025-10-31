<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * @internal
 */
#[CoversClass(UtmConversion::class)]
final class UtmConversionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new UtmConversion();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'eventName' => ['eventName', 'test_value'],
            'value' => ['value', 123.45],
            'metadata' => ['metadata', ['key' => 'value']],
        ];
    }

    public function testAddMetadata(): void
    {
        $conversion = new UtmConversion();

        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmConversion::class);
        $eventNameProperty = $reflectionClass->getProperty('eventName');
        $eventNameProperty->setAccessible(true);
        $eventNameProperty->setValue($conversion, 'test_event');

        // Test adding a single metadata item
        $conversion->addMetadata('key1', 'value1');
        $this->assertSame(['key1' => 'value1'], $conversion->getMetadata());

        // Test adding another metadata item
        $conversion->addMetadata('key2', 'value2');
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $conversion->getMetadata());

        // Test overwriting an existing metadata item
        $conversion->addMetadata('key1', 'new_value');
        $this->assertSame([
            'key1' => 'new_value',
            'key2' => 'value2',
        ], $conversion->getMetadata());
    }

    public function testCreateTime(): void
    {
        $conversion = new UtmConversion();

        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmConversion::class);
        $eventNameProperty = $reflectionClass->getProperty('eventName');
        $eventNameProperty->setAccessible(true);
        $eventNameProperty->setValue($conversion, 'test_event');

        // createTime should be initialized by the CreateTimeColumn attribute handler
        // For testing purposes, we just verify the method exists and returns a ?DateTimeImmutable
        $createTimeReflection = new \ReflectionMethod(UtmConversion::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame('?DateTimeImmutable', (string) $createTimeReflection->getReturnType());
    }
}
