<?php

namespace Tourze\UtmBundle\Service\Storage;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Tourze\UtmBundle\Entity\UtmParameters;

/**
 * 会话存储策略
 *
 * 将UTM参数存储在用户会话中
 */
class SessionStorageStrategy implements UtmStorageStrategyInterface
{
    private readonly string $sessionKey;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger,
        ?string $sessionKey = null
    ) {
        $this->sessionKey = $sessionKey ?? 'utm_parameters';
    }

    /**
     * 在会话中存储UTM参数
     */
    public function store(UtmParameters $parameters): void
    {
        $session = $this->requestStack->getSession();
        
        // 序列化UTM参数为JSON
        $data = $this->serializer->serialize($parameters, 'json');
        
        $session->set($this->sessionKey, $data);
        
        $this->logger->debug('UTM参数已存储到会话', [
            'session_id' => $session->getId(),
            'parameters' => [
                'source' => $parameters->getSource(),
                'medium' => $parameters->getMedium(),
                'campaign' => $parameters->getCampaign(),
            ],
        ]);
    }

    /**
     * 从会话中检索UTM参数
     */
    public function retrieve(): ?UtmParameters
    {
        $session = $this->requestStack->getSession();
        
        if (!$session->has($this->sessionKey)) {
            return null;
        }
        
        $data = $session->get($this->sessionKey);
        
        try {
            $parameters = $this->serializer->deserialize($data, UtmParameters::class, 'json');
            
            $this->logger->debug('从会话中检索UTM参数', [
                'session_id' => $session->getId(),
                'parameters' => [
                    'source' => $parameters->getSource(),
                    'medium' => $parameters->getMedium(),
                    'campaign' => $parameters->getCampaign(),
                ],
            ]);
            
            return $parameters;
        } catch (\Throwable $e) {
            $this->logger->error('无法从会话中反序列化UTM参数', [
                'session_id' => $session->getId(),
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            
            // 清除损坏的数据
            $this->clear();
            
            return null;
        }
    }

    /**
     * 清除会话中的UTM参数
     */
    public function clear(): void
    {
        $session = $this->requestStack->getSession();
        
        if ($session->has($this->sessionKey)) {
            $session->remove($this->sessionKey);
            
            $this->logger->debug('已清除会话中的UTM参数', [
                'session_id' => $session->getId(),
            ]);
        }
    }
} 