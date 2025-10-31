<?php

namespace Tourze\UtmBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\UtmBundle\Dto\UtmParametersDto;

/**
 * UTM参数提取服务
 *
 * 负责从HTTP请求中提取标准和自定义UTM参数
 */
#[WithMonologChannel(channel: 'utm')]
class UtmParametersExtractor
{
    /**
     * @var array<string>
     */
    private readonly array $allowedParameters;

    /**
     * @param array<string> $allowedParameters
     * @param array<string> $customParameters
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        array $allowedParameters = [],
        private readonly array $customParameters = [],
    ) {
        $this->allowedParameters = [] !== $allowedParameters
            ? $allowedParameters
            : ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
    }

    /**
     * 从请求中提取UTM参数
     */
    public function extract(Request $request): UtmParametersDto
    {
        /** @var array<string, mixed> $parameters */
        $parameters = [];

        // 从GET参数中提取
        foreach ($this->allowedParameters as $param) {
            if ($request->query->has($param)) {
                $parameters[$param] = $request->query->get($param);
            }
        }

        // 提取自定义参数
        foreach ($this->customParameters as $param) {
            if ($request->query->has($param)) {
                $parameters[$param] = $request->query->get($param);
            }
        }

        // 创建DTO对象
        $utmDto = UtmParametersDto::fromArray($parameters);

        // 如果有UTM参数，记录日志
        if ($utmDto->hasAnyParameter()) {
            $this->logger->debug('提取UTM参数', [
                'parameters' => $utmDto->toArray(),
                'url' => $request->getUri(),
            ]);
        }

        return $utmDto;
    }

    /**
     * 检查请求中是否包含UTM参数
     */
    public function hasUtmParameters(Request $request): bool
    {
        foreach ($this->allowedParameters as $param) {
            if ($request->query->has($param)) {
                return true;
            }
        }

        foreach ($this->customParameters as $param) {
            if ($request->query->has($param)) {
                return true;
            }
        }

        return false;
    }
}
