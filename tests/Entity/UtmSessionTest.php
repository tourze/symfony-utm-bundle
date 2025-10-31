<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * @internal
 */
#[CoversClass(UtmSession::class)]
final class UtmSessionTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new UtmSession();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            'sessionId' => ['sessionId', 'test_value'],
            'metadata' => ['metadata', ['key' => 'value']],
        ];
    }

    public function testAddMetadata(): void
    {
        $session = new UtmSession();

        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmSession::class);
        $sessionIdProperty = $reflectionClass->getProperty('sessionId');
        $sessionIdProperty->setAccessible(true);
        $sessionIdProperty->setValue($session, 'test_session_id');

        // Test adding a single metadata item
        $session->addMetadata('key1', 'value1');
        $this->assertSame(['key1' => 'value1'], $session->getMetadata());

        // Test adding another metadata item
        $session->addMetadata('key2', 'value2');
        $this->assertSame([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $session->getMetadata());

        // Test overwriting an existing metadata item
        $session->addMetadata('key1', 'new_value');
        $this->assertSame([
            'key1' => 'new_value',
            'key2' => 'value2',
        ], $session->getMetadata());
    }

    public function testIsExpired(): void
    {
        $session = new UtmSession();

        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmSession::class);
        $sessionIdProperty = $reflectionClass->getProperty('sessionId');
        $sessionIdProperty->setAccessible(true);
        $sessionIdProperty->setValue($session, 'test_session_id');

        // 没有过期时间应该返回false
        $this->assertFalse($session->isExpired());

        // 未来的过期时间应该返回false
        $futureDate = new \DateTimeImmutable('+1 day');
        $session->setExpiresAt($futureDate);
        $this->assertFalse($session->isExpired());

        // 过去的过期时间应该返回true
        $pastDate = new \DateTimeImmutable('-1 day');
        $session->setExpiresAt($pastDate);
        $this->assertTrue($session->isExpired());
    }

    public function testCreateTime(): void
    {
        $session = new UtmSession();

        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmSession::class);
        $sessionIdProperty = $reflectionClass->getProperty('sessionId');
        $sessionIdProperty->setAccessible(true);
        $sessionIdProperty->setValue($session, 'test_session_id');

        // createTime should be initialized by the CreateTimeColumn attribute handler
        // For testing purposes, we just verify the method exists and returns a ?DateTimeImmutable
        $createTimeReflection = new \ReflectionMethod(UtmSession::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame('?DateTimeImmutable', (string) $createTimeReflection->getReturnType());
    }
}
