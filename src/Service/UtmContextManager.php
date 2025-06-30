<?php

namespace Tourze\UtmBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;

/**
 * UTM上下文管理器
 *
 * 提供当前请求的UTM上下文信息
 */
class UtmContextManager
{
    private ?UtmParameters $currentParameters = null;
    private ?UtmSession $currentSession = null;
    private bool $initialized = false;

    public function __construct(
        private readonly UtmSessionManager $sessionManager,
        private readonly UtmStorageStrategyInterface $storageStrategy,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * 获取当前UTM参数
     */
    public function getCurrentParameters(): ?UtmParameters
    {
        $this->initializeContext();
        return $this->currentParameters;
    }

    /**
     * 获取当前UTM会话
     */
    public function getCurrentSession(): ?UtmSession
    {
        $this->initializeContext();
        return $this->currentSession;
    }

    /**
     * 检查当前请求是否有UTM上下文
     */
    public function hasUtmContext(): bool
    {
        $this->initializeContext();
        return null !== $this->currentParameters || null !== $this->currentSession;
    }

    /**
     * 初始化上下文
     *
     * 从存储中加载UTM参数和会话
     */
    private function initializeContext(): void
    {
        if ($this->initialized) {
            return;
        }

        // 从存储中检索参数
        $this->currentParameters = $this->storageStrategy->retrieve();

        // 从会话管理器中获取会话
        $this->currentSession = $this->sessionManager->getSession();

        // 处理不一致的状态（参数和会话之间的不一致）
        if (null !== $this->currentSession && null === $this->currentParameters) {
            $this->currentParameters = $this->currentSession->getParameters();
            
            $this->logger->debug('从会话中恢复了UTM参数', [
                'session_id' => $this->currentSession->getId(),
            ]);
        }

        $this->initialized = true;

        $this->logger->debug('初始化UTM上下文', [
            'has_parameters' => null !== $this->currentParameters,
            'has_session' => null !== $this->currentSession,
            'utm_source' => $this->currentParameters?->getSource(),
            'utm_medium' => $this->currentParameters?->getMedium(),
            'utm_campaign' => $this->currentParameters?->getCampaign(),
        ]);
    }

    /**
     * 清除当前上下文缓存
     *
     * 下次访问时将重新加载
     */
    public function reset(): void
    {
        $this->currentParameters = null;
        $this->currentSession = null;
        $this->initialized = false;
    }
}
