<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Service\UtmContextManager;
use Tourze\UtmBundle\Service\UtmSessionManager;

class UtmContextManagerTest extends TestCase
{
    private UtmSessionManager $sessionManager;
    private UtmStorageStrategyInterface $storageStrategy;
    private LoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->sessionManager = $this->createMock(UtmSessionManager::class);
        $this->storageStrategy = $this->createMock(UtmStorageStrategyInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }
    
    public function testGetCurrentParameters_withParametersInStorage_returnsParameters(): void
    {
        // Arrange
        $parameters = new UtmParameters();
        $parameters->setSource('google')
            ->setMedium('cpc');
        
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn($parameters);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->getCurrentParameters();
        
        // Assert
        $this->assertSame($parameters, $result);
    }
    
    public function testGetCurrentParameters_withNoParametersInStorage_returnsNull(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->getCurrentParameters();
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testGetCurrentSession_withSessionAvailable_returnsSession(): void
    {
        // Arrange
        $session = new UtmSession();
        
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->getCurrentSession();
        
        // Assert
        $this->assertSame($session, $result);
    }
    
    public function testGetCurrentSession_withNoSession_returnsNull(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->getCurrentSession();
        
        // Assert
        $this->assertNull($result);
    }
    
    public function testHasUtmContext_withParameters_returnsTrue(): void
    {
        // Arrange
        $parameters = new UtmParameters();
        
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn($parameters);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->hasUtmContext();
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testHasUtmContext_withSession_returnsTrue(): void
    {
        // Arrange
        $session = new UtmSession();
        
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->hasUtmContext();
        
        // Assert
        $this->assertTrue($result);
    }
    
    public function testHasUtmContext_withNoContext_returnsFalse(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null);
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->hasUtmContext();
        
        // Assert
        $this->assertFalse($result);
    }
    
    public function testReset_resetsState(): void
    {
        // Arrange
        $parameters = new UtmParameters();
        
        $this->storageStrategy->expects($this->exactly(2))
            ->method('retrieve')
            ->willReturn($parameters);
        
        $this->sessionManager->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn(null);
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // First, get the parameters to initialize the context
        $contextManager->getCurrentParameters();
        
        // Act
        $contextManager->reset();
        
        // Assert - After reset, retrieve should be called again
        $contextManager->getCurrentParameters();
    }
    
    public function testGetCurrentParameters_withSessionHavingParameters_recoversParametersFromSession(): void
    {
        // Arrange
        $parameters = new UtmParameters();
        $parameters->setSource('google');
        
        $session = new UtmSession();
        $sessionReflection = new \ReflectionClass(UtmSession::class);
        $parametersProperty = $sessionReflection->getProperty('parameters');
        $parametersProperty->setAccessible(true);
        $parametersProperty->setValue($session, $parameters);
        
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null); // 存储中没有参数
        
        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session); // 但会话中有参数
        
        // 在 PHPUnit 10 中，我们不再使用 withConsecutive
        // 改为使用 callback 来验证调用
        $this->logger->expects($this->atLeastOnce())
            ->method('debug')
            ->with(
                $this->callback(function ($message) {
                    return $message === '从会话中恢复了UTM参数' || $message === '初始化UTM上下文';
                }),
                $this->anything()
            );
        
        $contextManager = new UtmContextManager($this->sessionManager, $this->storageStrategy, $this->logger);
        
        // Act
        $result = $contextManager->getCurrentParameters();
        
        // Assert
        $this->assertSame($parameters, $result);
    }
} 