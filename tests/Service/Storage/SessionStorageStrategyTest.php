<?php

namespace Tourze\UtmBundle\Tests\Service\Storage;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Service\Storage\SessionStorageStrategy;

class SessionStorageStrategyTest extends TestCase
{
    private RequestStack $requestStack;
    private SessionInterface $session;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->requestStack->expects($this->any())
            ->method('getSession')
            ->willReturn($this->session);
    }
    
    public function testStore_withValidParameters_storesInSession(): void
    {
        // Arrange
        $parameters = new UtmParameters();
        $parameters->setSource('google')
            ->setMedium('cpc')
            ->setCampaign('spring_sale');
        
        // 使用反射来设置 ID
        $reflectionClass = new \ReflectionClass(UtmParameters::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($parameters, 123);
        
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($parameters, 'json')
            ->willReturn('{"id":123,"source":"google","medium":"cpc","campaign":"spring_sale"}');
        
        $this->session->expects($this->once())
            ->method('set')
            ->with('utm_parameters', '{"id":123,"source":"google","medium":"cpc","campaign":"spring_sale"}');
        
        $this->session->expects($this->any())
            ->method('getId')
            ->willReturn('session123');
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('UTM参数已存储到会话', $this->anything());
        
        $strategy = new SessionStorageStrategy($this->requestStack, $this->serializer, $this->logger);
        
        // Act
        $strategy->store($parameters);
    }
    
    public function testRetrieve_withNoParametersInSession_returnsNull(): void
    {
        // Arrange
        $this->session->expects($this->once())
            ->method('has')
            ->with('utm_parameters')
            ->willReturn(false);
        
        $strategy = new SessionStorageStrategy($this->requestStack, $this->serializer, $this->logger);
        
        // Act
        $result = $strategy->retrieve();
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testRetrieve_withParametersInSession_returnsParameters(): void
    {
        // Arrange
        $json = '{"id":123,"source":"google","medium":"cpc","campaign":"spring_sale"}';
        $parameters = new UtmParameters();
        $parameters->setSource('google')
            ->setMedium('cpc')
            ->setCampaign('spring_sale');
        
        $reflectionClass = new \ReflectionClass(UtmParameters::class);
        $idProperty = $reflectionClass->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($parameters, 123);
        
        $this->session->expects($this->once())
            ->method('has')
            ->with('utm_parameters')
            ->willReturn(true);
        
        $this->session->expects($this->once())
            ->method('get')
            ->with('utm_parameters')
            ->willReturn($json);
        
        $this->session->expects($this->any())
            ->method('getId')
            ->willReturn('session123');
        
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with($json, UtmParameters::class, 'json')
            ->willReturn($parameters);
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('从会话中检索UTM参数', $this->anything());
        
        $strategy = new SessionStorageStrategy($this->requestStack, $this->serializer, $this->logger);
        
        // Act
        $result = $strategy->retrieve();
        
        // Assert
        $this->assertSame($parameters, $result);
    }
    
    public function testClear_removesParametersFromSession(): void
    {
        // Arrange
        $this->session->expects($this->once())
            ->method('has')
            ->with('utm_parameters')
            ->willReturn(true);
        
        $this->session->expects($this->once())
            ->method('remove')
            ->with('utm_parameters');
        
        $this->session->expects($this->any())
            ->method('getId')
            ->willReturn('session123');
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('已清除会话中的UTM参数', $this->anything());
        
        $strategy = new SessionStorageStrategy($this->requestStack, $this->serializer, $this->logger);
        
        // Act
        $strategy->clear();
    }
} 