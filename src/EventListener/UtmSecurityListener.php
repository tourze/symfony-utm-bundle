<?php

namespace Tourze\UtmBundle\EventListener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Tourze\UtmBundle\Service\UtmSessionManager;

/**
 * UTM安全监听器
 * 
 * 监听用户登录事件，关联用户与UTM会话
 */
class UtmSecurityListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly UtmSessionManager $sessionManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 处理登录成功事件
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        // 获取用户标识符
        $userIdentifier = null;
        if (method_exists($user, 'getUserIdentifier')) {
            $userIdentifier = $user->getUserIdentifier();
        } elseif (method_exists($user, '__toString')) {
            // 处理可以转换为字符串的用户对象
            $userIdentifier = (string) $user;
        } elseif (is_string($user)) {
            $userIdentifier = $user;
        }
        
        if (null === $userIdentifier) {
            $this->logger->warning('无法确定用户标识符，无法关联UTM会话');
            return;
        }
        
        // 关联用户与UTM会话
        $this->sessionManager->associateUser($userIdentifier);
        
        $this->logger->info('用户登录，关联到UTM会话', [
            'user_identifier' => $userIdentifier,
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }
}
