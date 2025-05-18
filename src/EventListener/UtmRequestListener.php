<?php

namespace Tourze\UtmBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Repository\UtmParametersRepository;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Service\UtmContextManager;
use Tourze\UtmBundle\Service\UtmParametersExtractor;
use Tourze\UtmBundle\Service\UtmParametersValidator;

/**
 * UTM请求监听器
 *
 * 监听所有HTTP请求，检测UTM参数
 */
class UtmRequestListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly UtmParametersExtractor $parametersExtractor,
        private readonly UtmParametersValidator $parametersValidator,
        private readonly UtmStorageStrategyInterface $storageStrategy,
        private readonly UtmContextManager $contextManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * 处理请求事件
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // 只处理主请求
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // 检查请求中是否包含UTM参数
        if (!$this->parametersExtractor->hasUtmParameters($request)) {
            return;
        }

        // 提取UTM参数
        $utmParamsDto = $this->parametersExtractor->extract($request);

        // 验证UTM参数
        $validatedParamsDto = $this->parametersValidator->validate($utmParamsDto);

        if (!$validatedParamsDto->hasAnyParameter()) {
            $this->logger->debug('无有效的UTM参数，跳过处理');
            return;
        }

        // 创建/检索UTM参数实体
        /** @var UtmParametersRepository $repository */
        $repository = $this->entityManager->getRepository(UtmParameters::class);
        $parameters = $repository->findOrCreateByParams($validatedParamsDto);

        // 持久化参数（如果是新创建的）
        if (null === $parameters->getId()) {
            $this->entityManager->persist($parameters);
            $this->entityManager->flush();
        }

        // 存储到选定的策略
        $this->storageStrategy->store($parameters);

        // 重置上下文，以便下次获取时使用新值
        $this->contextManager->reset();

        $this->logger->info('处理了UTM参数', [
            'utm_source' => $parameters->getSource(),
            'utm_medium' => $parameters->getMedium(),
            'utm_campaign' => $parameters->getCampaign(),
            'request_uri' => $request->getRequestUri(),
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // 必须在早期运行，以便其他监听器可以访问UTM上下文
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }
}
