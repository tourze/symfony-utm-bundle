<?php

namespace Tourze\UtmBundle\Service\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Repository\UtmParametersRepository;
use Tourze\UtmBundle\Repository\UtmSessionRepository;

/**
 * 数据库存储策略
 * 
 * 将UTM参数持久化到数据库
 */
class DatabaseStorageStrategy implements UtmStorageStrategyInterface
{
    private readonly string $sessionKey;
    private readonly int $lifetime;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        ?string $sessionKey = null,
        ?int $lifetime = null
    ) {
        $this->sessionKey = $sessionKey ?? 'utm_session_id';
        $this->lifetime = $lifetime ?? 2592000; // 默认30天（单位：秒）
    }

    /**
     * 在数据库中存储UTM参数
     */
    public function store(UtmParameters $parameters): void
    {
        // 获取当前请求和会话
        $request = $this->requestStack->getCurrentRequest();
        $session = $this->requestStack->getSession();
        
        if (null === $request || null === $session) {
            $this->logger->warning('无法存储UTM参数：缺少请求或会话');
            return;
        }
        
        // 通过仓库获取实例
        /** @var UtmParametersRepository $parametersRepository */
        $parametersRepository = $this->entityManager->getRepository(UtmParameters::class);
        /** @var UtmSessionRepository $sessionRepository */
        $sessionRepository = $this->entityManager->getRepository(UtmSession::class);
        
        // 保存UTM参数
        $this->entityManager->persist($parameters);
        
        // 创建或更新会话
        $sessionId = $session->getId();
        $utmSession = $sessionRepository->findBySessionId($sessionId);
        
        if (null === $utmSession) {
            // 创建新会话
            $utmSession = $sessionRepository->createSession(
                $sessionId,
                $parameters,
                null, // 用户标识符稍后由身份验证监听器设置
                $request->getClientIp(),
                $request->headers->get('User-Agent'),
                new \DateTime(sprintf('+%d seconds', $this->lifetime))
            );
        } else {
            // 更新现有会话
            $utmSession->setParameters($parameters)
                ->setExpiresAt(new \DateTime(sprintf('+%d seconds', $this->lifetime)));
        }
        
        // 存储会话实体
        $this->entityManager->persist($utmSession);
        $this->entityManager->flush();
        
        // 在用户会话中记录UTM会话ID
        $session->set($this->sessionKey, $utmSession->getId());
        
        $this->logger->debug('UTM参数已存储到数据库', [
            'parameters_id' => $parameters->getId(),
            'session_id' => $utmSession->getId(),
            'utm_source' => $parameters->getSource(),
            'utm_medium' => $parameters->getMedium(),
            'utm_campaign' => $parameters->getCampaign(),
        ]);
    }

    /**
     * 从数据库中检索UTM参数
     */
    public function retrieve(): ?UtmParameters
    {
        $session = $this->requestStack->getSession();
        
        if (null === $session || !$session->has($this->sessionKey)) {
            return null;
        }
        
        $utmSessionId = $session->get($this->sessionKey);
        
        /** @var UtmSessionRepository $sessionRepository */
        $sessionRepository = $this->entityManager->getRepository(UtmSession::class);
        
        $utmSession = $sessionRepository->find($utmSessionId);
        
        if (null === $utmSession) {
            $this->logger->warning('无法找到UTM会话', [
                'utm_session_id' => $utmSessionId,
            ]);
            $this->clear();
            return null;
        }
        
        // 检查会话是否过期
        if ($utmSession->isExpired()) {
            $this->logger->debug('UTM会话已过期', [
                'utm_session_id' => $utmSessionId,
                'expires_at' => $utmSession->getExpiresAt()->format('Y-m-d H:i:s'),
            ]);
            $this->clear();
            return null;
        }
        
        $parameters = $utmSession->getParameters();
        
        if (null === $parameters) {
            $this->logger->warning('UTM会话没有关联的参数', [
                'utm_session_id' => $utmSessionId,
            ]);
            return null;
        }
        
        $this->logger->debug('从数据库中检索到UTM参数', [
            'parameters_id' => $parameters->getId(),
            'session_id' => $utmSession->getId(),
            'utm_source' => $parameters->getSource(),
            'utm_medium' => $parameters->getMedium(),
            'utm_campaign' => $parameters->getCampaign(),
        ]);
        
        return $parameters;
    }

    /**
     * 清除UTM会话关联
     */
    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        
        if (null === $session) {
            return;
        }
        
        if ($session->has($this->sessionKey)) {
            $utmSessionId = $session->get($this->sessionKey);
            $session->remove($this->sessionKey);
            
            $this->logger->debug('已清除UTM会话关联', [
                'utm_session_id' => $utmSessionId,
            ]);
        }
    }
} 