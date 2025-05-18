<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;

class UtmSessionTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $session = new UtmSession();
        
        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmSession::class);
        $sessionIdProperty = $reflectionClass->getProperty('sessionId');
        $sessionIdProperty->setAccessible(true);
        $sessionIdProperty->setValue($session, 'default_session_id');
        
        // Test sessionId
        $this->assertSame('default_session_id', $session->getSessionId());
        $session->setSessionId('test_session_id');
        $this->assertSame('test_session_id', $session->getSessionId());
        
        // Test parameters
        $this->assertNull($session->getParameters());
        $parameters = new UtmParameters();
        $session->setParameters($parameters);
        $this->assertSame($parameters, $session->getParameters());
        
        // Test userIdentifier
        $this->assertNull($session->getUserIdentifier());
        $session->setUserIdentifier('test_user');
        $this->assertSame('test_user', $session->getUserIdentifier());
        
        // Test clientIp
        $this->assertNull($session->getClientIp());
        $session->setClientIp('127.0.0.1');
        $this->assertSame('127.0.0.1', $session->getClientIp());
        
        // Test userAgent
        $this->assertNull($session->getUserAgent());
        $session->setUserAgent('Mozilla/5.0');
        $this->assertSame('Mozilla/5.0', $session->getUserAgent());
        
        // Test expiresAt
        $this->assertNull($session->getExpiresAt());
        $expiresAt = new \DateTime('+30 days');
        $session->setExpiresAt($expiresAt);
        $this->assertSame($expiresAt, $session->getExpiresAt());
        
        // Test metadata
        $this->assertEmpty($session->getMetadata());
        $metadata = ['key' => 'value'];
        $session->setMetadata($metadata);
        $this->assertSame($metadata, $session->getMetadata());
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
            'key2' => 'value2'
        ], $session->getMetadata());
        
        // Test overwriting an existing metadata item
        $session->addMetadata('key1', 'new_value');
        $this->assertSame([
            'key1' => 'new_value',
            'key2' => 'value2'
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
        $futureDate = new \DateTime('+1 day');
        $session->setExpiresAt($futureDate);
        $this->assertFalse($session->isExpired());
        
        // 过去的过期时间应该返回true
        $pastDate = new \DateTime('-1 day');
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
        // For testing purposes, we just verify the method exists and returns a DateTimeInterface
        $createTimeReflection = new \ReflectionMethod(UtmSession::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame(\DateTimeInterface::class, $createTimeReflection->getReturnType()->getName());
    }
} 