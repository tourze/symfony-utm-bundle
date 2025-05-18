<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;

class UtmConversionTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $conversion = new UtmConversion();
        
        // 初始化必需的属性
        $reflectionClass = new \ReflectionClass(UtmConversion::class);
        $eventNameProperty = $reflectionClass->getProperty('eventName');
        $eventNameProperty->setAccessible(true);
        $eventNameProperty->setValue($conversion, 'default_event');
        
        // Test eventName
        $this->assertSame('default_event', $conversion->getEventName());
        $conversion->setEventName('purchase');
        $this->assertSame('purchase', $conversion->getEventName());
        
        // Test userIdentifier
        $this->assertNull($conversion->getUserIdentifier());
        $conversion->setUserIdentifier('test_user');
        $this->assertSame('test_user', $conversion->getUserIdentifier());
        
        // Test parameters
        $this->assertNull($conversion->getParameters());
        $parameters = new UtmParameters();
        $conversion->setParameters($parameters);
        $this->assertSame($parameters, $conversion->getParameters());
        
        // Test session
        $this->assertNull($conversion->getSession());
        $session = new UtmSession();
        $conversion->setSession($session);
        $this->assertSame($session, $conversion->getSession());
        
        // Test value
        $this->assertSame(0.0, $conversion->getValue());
        $conversion->setValue(99.99);
        $this->assertSame(99.99, $conversion->getValue());
        
        // Test metadata
        $this->assertEmpty($conversion->getMetadata());
        $metadata = ['product_id' => '123'];
        $conversion->setMetadata($metadata);
        $this->assertSame($metadata, $conversion->getMetadata());
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
            'key2' => 'value2'
        ], $conversion->getMetadata());
        
        // Test overwriting an existing metadata item
        $conversion->addMetadata('key1', 'new_value');
        $this->assertSame([
            'key1' => 'new_value',
            'key2' => 'value2'
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
        // For testing purposes, we just verify the method exists and returns a DateTimeInterface
        $createTimeReflection = new \ReflectionMethod(UtmConversion::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame(\DateTimeInterface::class, $createTimeReflection->getReturnType()->getName());
    }
} 