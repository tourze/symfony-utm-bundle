<?php

namespace Tourze\UtmBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Entity\UtmParameters;

class UtmParametersTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $parameters = new UtmParameters();
        
        // Test source
        $this->assertNull($parameters->getSource());
        $parameters->setSource('google');
        $this->assertSame('google', $parameters->getSource());
        
        // Test medium
        $this->assertNull($parameters->getMedium());
        $parameters->setMedium('cpc');
        $this->assertSame('cpc', $parameters->getMedium());
        
        // Test campaign
        $this->assertNull($parameters->getCampaign());
        $parameters->setCampaign('spring_sale');
        $this->assertSame('spring_sale', $parameters->getCampaign());
        
        // Test term
        $this->assertNull($parameters->getTerm());
        $parameters->setTerm('running shoes');
        $this->assertSame('running shoes', $parameters->getTerm());
        
        // Test content
        $this->assertNull($parameters->getContent());
        $parameters->setContent('banner_1');
        $this->assertSame('banner_1', $parameters->getContent());
        
        // Test additional parameters
        $this->assertEmpty($parameters->getAdditionalParameters());
        $additionalParams = ['custom1' => 'value1', 'custom2' => 'value2'];
        $parameters->setAdditionalParameters($additionalParams);
        $this->assertSame($additionalParams, $parameters->getAdditionalParameters());
    }
    
    public function testAddAdditionalParameter(): void
    {
        $parameters = new UtmParameters();
        
        // Test adding a single parameter
        $parameters->addAdditionalParameter('custom1', 'value1');
        $this->assertSame(['custom1' => 'value1'], $parameters->getAdditionalParameters());
        
        // Test adding another parameter
        $parameters->addAdditionalParameter('custom2', 'value2');
        $this->assertSame([
            'custom1' => 'value1',
            'custom2' => 'value2'
        ], $parameters->getAdditionalParameters());
        
        // Test overwriting an existing parameter
        $parameters->addAdditionalParameter('custom1', 'new_value');
        $this->assertSame([
            'custom1' => 'new_value',
            'custom2' => 'value2'
        ], $parameters->getAdditionalParameters());
    }
    
    public function testToString(): void
    {
        $parameters = new UtmParameters();
        
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
        $parameters = new UtmParameters();
        
        // createTime should be initialized by the CreateTimeColumn attribute handler
        // For testing purposes, we just verify the method exists and returns a DateTimeInterface
        $createTimeReflection = new \ReflectionMethod(UtmParameters::class, 'getCreateTime');
        $this->assertTrue($createTimeReflection->hasReturnType());
        $this->assertSame(\DateTimeInterface::class, $createTimeReflection->getReturnType()->getName());
    }
} 