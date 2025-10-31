<?php

namespace Tourze\UtmBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Event\UtmConversionEvent;
use Tourze\UtmBundle\Repository\UtmConversionRepository;

/**
 * UTM转化跟踪服务
 *
 * 负责跟踪转化事件并关联UTM数据
 */
#[WithMonologChannel(channel: 'utm')]
class UtmConversionTracker
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UtmContextManager $contextManager,
        private readonly RequestStack $requestStack,
        private readonly ?TokenStorageInterface $tokenStorage,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UtmConversionRepository $utmConversionRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 跟踪转化事件
     *
     * @param string     $eventName 事件名称
     * @param float|null $value     转化价值
     * @param array<string, mixed> $metadata  额外元数据
     *
     * @return UtmConversion 创建的转化记录
     */
    public function trackConversion(string $eventName, ?float $value = null, array $metadata = []): UtmConversion
    {
        // 获取UTM上下文
        $parameters = $this->contextManager->getCurrentParameters();
        $session = $this->contextManager->getCurrentSession();

        // 获取用户标识符
        $userIdentifier = $this->getUserIdentifier();

        // 创建转化记录
        $conversion = new UtmConversion();
        $conversion->setEventName($eventName);
        $conversion->setParameters($parameters);
        $conversion->setSession($session);
        $conversion->setUserIdentifier($userIdentifier);
        $conversion->setValue($value ?? 0.0);
        $conversion->setMetadata($metadata);

        // 存储转化记录
        $this->entityManager->persist($conversion);
        $this->entityManager->flush();

        $this->logger->info('跟踪了转化事件', [
            'event_name' => $eventName,
            'value' => $value,
            'user_identifier' => $userIdentifier,
            'utm_source' => $parameters?->getSource(),
            'utm_medium' => $parameters?->getMedium(),
            'utm_campaign' => $parameters?->getCampaign(),
        ]);

        // 派发转化事件
        $event = new UtmConversionEvent($conversion);
        $this->eventDispatcher->dispatch($event, UtmConversionEvent::NAME);

        return $conversion;
    }

    /**
     * 获取当前用户标识符
     */
    private function getUserIdentifier(): string
    {
        // 首先检查UTM会话
        $userIdentifier = $this->getUserIdentifierFromUtmSession();
        if (null !== $userIdentifier) {
            return $userIdentifier;
        }

        // 然后检查安全令牌
        $userIdentifier = $this->getUserIdentifierFromSecurityToken();
        if (null !== $userIdentifier) {
            return $userIdentifier;
        }

        // 最后，使用会话ID作为匿名标识符
        return $this->generateAnonymousIdentifier();
    }

    /**
     * 从UTM会话获取用户标识符
     */
    private function getUserIdentifierFromUtmSession(): ?string
    {
        $session = $this->contextManager->getCurrentSession();

        return $session?->getUserIdentifier();
    }

    /**
     * 从安全令牌获取用户标识符
     */
    private function getUserIdentifierFromSecurityToken(): ?string
    {
        if (null === $this->tokenStorage) {
            return null;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();

        return ($user instanceof UserInterface) ? $user->getUserIdentifier() : null;
    }

    /**
     * 生成匿名用户标识符
     */
    private function generateAnonymousIdentifier(): string
    {
        $session = $this->requestStack->getSession();
        if ($session->isStarted()) {
            return 'anonymous_' . $session->getId();
        }

        return 'anonymous_unknown';
    }

    /**
     * 查找特定事件的转化
     *
     * @return array<UtmConversion>
     */
    public function findConversions(string $eventName, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->findByEventName($eventName, $startDate, $endDate);
    }

    /**
     * 获取用户的转化
     *
     * @return array<UtmConversion>
     */
    public function findUserConversions(string $userIdentifier, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->findByUserIdentifier($userIdentifier, $startDate, $endDate);
    }

    /**
     * 获取转化事件统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getConversionStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->getConversionStats($startDate, $endDate);
    }

    /**
     * 获取UTM来源统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmSourceStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->getUtmSourceStats($startDate, $endDate);
    }

    /**
     * 获取UTM媒介统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmMediumStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->getUtmMediumStats($startDate, $endDate);
    }

    /**
     * 获取UTM活动统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmCampaignStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        return $this->utmConversionRepository->getUtmCampaignStats($startDate, $endDate);
    }
}
