<?php

namespace Tourze\UtmBundle\Tests\EventListener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Tourze\UtmBundle\EventListener\UtmSecurityListener;
use Tourze\UtmBundle\Service\UtmSessionManager;

class UtmSecurityListenerTest extends TestCase
{
    private UtmSessionManager|MockObject $sessionManager;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->sessionManager = $this->createMock(UtmSessionManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testOnLoginSuccess_withUserInterfaceUser_associatesUserIdentifier(): void
    {
        // Arrange
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('test_user');

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionManager->expects($this->once())
            ->method('associateUser')
            ->with('test_user');

        $this->logger->expects($this->once())
            ->method('info')
            ->with('用户登录，关联到UTM会话', $this->anything());

        $listener = new UtmSecurityListener($this->sessionManager, $this->logger);

        // Act
        $listener->onLoginSuccess($event);
    }

    public function testOnLoginSuccess_withEmptyUserIdentifier_logsWarning(): void
    {
        // Arrange
        $user = $this->createMock(UserInterface::class);
        $user->expects($this->once())
            ->method('getUserIdentifier')
            ->willReturn('');

        $event = $this->createMock(LoginSuccessEvent::class);
        $event->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->sessionManager->expects($this->never())
            ->method('associateUser');

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('无法确定用户标识符，无法关联UTM会话');

        $listener = new UtmSecurityListener($this->sessionManager, $this->logger);

        // Act
        $listener->onLoginSuccess($event);
    }


    public function testGetSubscribedEvents_returnsCorrectEvents(): void
    {
        // Act
        $events = UtmSecurityListener::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey(LoginSuccessEvent::class, $events);
        $this->assertSame('onLoginSuccess', $events[LoginSuccessEvent::class]);
    }
}
