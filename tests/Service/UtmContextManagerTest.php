<?php

namespace Tourze\UtmBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Service\UtmContextManager;
use Tourze\UtmBundle\Service\UtmSessionManager;

/**
 * @internal
 */
#[CoversClass(UtmContextManager::class)]
#[RunTestsInSeparateProcesses]
final class UtmContextManagerTest extends AbstractIntegrationTestCase
{
    /** @var MockObject&UtmSessionManager */
    private MockObject $sessionManager;

    /** @var MockObject&UtmStorageStrategyInterface */
    private MockObject $storageStrategy;

    private UtmContextManager $contextManager;

    private string $testSessionId = 'test_session_123';

    protected function onSetUp(): void
    {
        $this->sessionManager = $this->createMock(UtmSessionManager::class);
        $this->storageStrategy = $this->createMock(UtmStorageStrategyInterface::class);

        // 只设置可以替换的服务到容器
        self::getContainer()->set(UtmSessionManager::class, $this->sessionManager);
        self::getContainer()->set(UtmStorageStrategyInterface::class, $this->storageStrategy);

        // 从容器获取 UtmContextManager 实例（它会自动注入依赖）
        $this->contextManager = self::getService(UtmContextManager::class);
    }

    public function testGetCurrentParametersWithParametersInStorageReturnsParameters(): void
    {
        // Arrange
        $parameters = new UtmParameter();
        $parameters->setSource('google');
        $parameters->setMedium('cpc');

        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn($parameters)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null)
        ;

        // Act
        $result = $this->contextManager->getCurrentParameters();

        // Assert
        $this->assertSame($parameters, $result);
        $this->assertIsString($this->testSessionId);
        $this->assertSame('test_session_123', $this->testSessionId);
    }

    public function testGetCurrentParametersWithNoParametersInStorageReturnsNull(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null)
        ;

        // Act
        $result = $this->contextManager->getCurrentParameters();

        // Assert
        $this->assertNull($result);
    }

    public function testGetCurrentSessionWithSessionAvailableReturnsSession(): void
    {
        // Arrange
        $session = new UtmSession();

        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        // Act
        $result = $this->contextManager->getCurrentSession();

        // Assert
        $this->assertSame($session, $result);
    }

    public function testGetCurrentSessionWithNoSessionReturnsNull(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null)
        ;

        // Act
        $result = $this->contextManager->getCurrentSession();

        // Assert
        $this->assertNull($result);
    }

    public function testHasUtmContextWithParametersReturnsTrue(): void
    {
        // Arrange
        $parameters = new UtmParameter();

        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn($parameters)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null)
        ;

        // Act
        $result = $this->contextManager->hasUtmContext();

        // Assert
        $this->assertTrue($result);
    }

    public function testHasUtmContextWithSessionReturnsTrue(): void
    {
        // Arrange
        $session = new UtmSession();

        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session)
        ;

        // Act
        $result = $this->contextManager->hasUtmContext();

        // Assert
        $this->assertTrue($result);
    }

    public function testHasUtmContextWithNoContextReturnsFalse(): void
    {
        // Arrange
        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null)
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn(null)
        ;

        // Act
        $result = $this->contextManager->hasUtmContext();

        // Assert
        $this->assertFalse($result);
    }

    public function testResetResetsState(): void
    {
        // Arrange
        $parameters = new UtmParameter();

        $this->storageStrategy->expects($this->exactly(2))
            ->method('retrieve')
            ->willReturn($parameters)
        ;

        $this->sessionManager->expects($this->exactly(2))
            ->method('getSession')
            ->willReturn(null)
        ;

        // First, get the parameters to initialize the context
        $this->contextManager->getCurrentParameters();

        // Act
        $this->contextManager->reset();

        // Assert - After reset, retrieve should be called again
        $this->contextManager->getCurrentParameters();
    }

    public function testGetCurrentParametersWithSessionHavingParametersRecoversParametersFromSession(): void
    {
        // Arrange
        $parameters = new UtmParameter();
        $parameters->setSource('google');

        $session = new UtmSession();
        $sessionReflection = new \ReflectionClass(UtmSession::class);
        $parametersProperty = $sessionReflection->getProperty('parameters');
        $parametersProperty->setAccessible(true);
        $parametersProperty->setValue($session, $parameters);

        $this->storageStrategy->expects($this->once())
            ->method('retrieve')
            ->willReturn(null) // 存储中没有参数
        ;

        $this->sessionManager->expects($this->once())
            ->method('getSession')
            ->willReturn($session) // 但会话中有参数
        ;

        // Act
        $result = $this->contextManager->getCurrentParameters();

        // Assert
        $this->assertSame($parameters, $result);
    }
}
