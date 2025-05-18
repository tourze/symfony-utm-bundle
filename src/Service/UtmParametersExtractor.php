<?php

namespace Tourze\UtmBundle\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Tourze\UtmBundle\Dto\UtmParametersDto;

/**
 * UTM参数提取服务
 *
 * 负责从HTTP请求中提取标准和自定义UTM参数
 */
class UtmParametersExtractor
{
    private readonly array $allowedParameters;
    private readonly array $customParameters;

    public function __construct(
        private readonly LoggerInterface $logger,
        array $allowedParameters = [],
        array $customParameters = []
    )
    {
        $this->allowedParameters = !empty($allowedParameters)
            ? $allowedParameters
            : ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

        $this->customParameters = $customParameters;
    }

    /**
     * 从请求中提取UTM参数
     */
    public function extract(Request $request): UtmParametersDto
    {
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
