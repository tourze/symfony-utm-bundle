<?php

namespace Tourze\UtmBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Exception\UtmSessionException;
use Tourze\UtmBundle\Repository\UtmSessionRepository;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;

/**
 * UTM会话管理器
 *
 * 管理UTM会话生命周期，处理会话合并和过期
 */
#[WithMonologChannel(channel: 'utm')]
class UtmSessionManager
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly UtmStorageStrategyInterface $storageStrategy,
        private readonly UtmSessionRepository $utmSessionRepository,
        private readonly LoggerInterface $logger,
        private readonly int $sessionLifetime = 2592000, // 默认30天（单位：秒）
    ) {
    }

    /**
     * 创建新的UTM会话
     */
    public function createSession(UtmParameter $parameters): UtmSession
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();

        if (null === $request || !$session->isStarted()) {
            throw new UtmSessionException('无法创建UTM会话：缺少请求或会话');
        }

        // 创建新会话
        $utmSession = new UtmSession();
        $utmSession->setSessionId($session->getId());
        $utmSession->setParameters($parameters);
        $utmSession->setClientIp($request->getClientIp());
        $utmSession->setUserAgent($request->headers->get('User-Agent'));
        $utmSession->setExpiresAt(new \DateTimeImmutable(sprintf('+%d seconds', $this->sessionLifetime)));

        // 存储到数据库
        $this->entityManager->persist($utmSession);
        $this->entityManager->flush();

        $this->logger->debug('创建了新的UTM会话', [
            'session_id' => $utmSession->getId(),
            'expires_at' => $utmSession->getExpiresAt()?->format('Y-m-d H:i:s'),
            'utm_source' => $parameters->getSource(),
            'utm_medium' => $parameters->getMedium(),
            'utm_campaign' => $parameters->getCampaign(),
        ]);

        return $utmSession;
    }

    /**
     * 获取当前会话
     */
    public function getSession(): ?UtmSession
    {
        $parameters = $this->storageStrategy->retrieve();

        if (null === $parameters) {
            return null;
        }

        $session = $this->requestStack->getSession();

        if (!$session->isStarted()) {
            return null;
        }

        $utmSession = $this->utmSessionRepository->findBySessionId($session->getId());

        if (null === $utmSession) {
            // 会话在存储策略中存在，但数据库中不存在，创建一个新的
            $utmSession = $this->createSession($parameters);
        }

        // 更新会话过期时间
        if ($this->shouldRenewSession($utmSession)) {
            $utmSession->setExpiresAt(new \DateTimeImmutable(sprintf('+%d seconds', $this->sessionLifetime)));
            $this->entityManager->persist($utmSession);
            $this->entityManager->flush();

            $this->logger->debug('更新了UTM会话过期时间', [
                'session_id' => $utmSession->getId(),
                'expires_at' => $utmSession->getExpiresAt()?->format('Y-m-d H:i:s'),
            ]);
        }

        return $utmSession;
    }

    /**
     * 关联用户与UTM会话
     */
    public function associateUser(string $userIdentifier): void
    {
        $utmSession = $this->getSession();

        if (null === $utmSession) {
            // 没有现有会话，尝试查找用户的其他活动会话
            $activeSessions = $this->utmSessionRepository->findActiveByUserIdentifier($userIdentifier);

            if ([] === $activeSessions) {
                $this->logger->debug('没有要关联的UTM会话', [
                    'user_identifier' => $userIdentifier,
                ]);

                return;
            }

            // 使用最近的会话
            $utmSession = $activeSessions[0];
        }

        // 更新会话用户标识符
        $utmSession->setUserIdentifier($userIdentifier);
        $this->entityManager->persist($utmSession);
        $this->entityManager->flush();

        $this->logger->debug('用户已关联到UTM会话', [
            'session_id' => $utmSession->getId(),
            'user_identifier' => $userIdentifier,
        ]);
    }

    /**
     * 清理过期会话
     */
    public function cleanExpiredSessions(): int
    {
        $count = $this->utmSessionRepository->cleanExpiredSessions();

        $this->logger->info('清理了过期的UTM会话', [
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * 检查是否应该更新会话过期时间
     */
    private function shouldRenewSession(UtmSession $session): bool
    {
        if (null === $session->getExpiresAt()) {
            return true;
        }

        // 如果会话将在一半生命周期内过期，则更新它
        $halfLifetime = $this->sessionLifetime / 2;
        $halfLifetimeLater = new \DateTimeImmutable(sprintf('+%d seconds', $halfLifetime));

        return $session->getExpiresAt() < $halfLifetimeLater;
    }
}
