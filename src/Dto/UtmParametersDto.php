<?php

namespace Tourze\UtmBundle\Dto;

/**
 * UTM参数数据传输对象
 *
 * 用于在服务之间传递UTM参数数据
 */
class UtmParametersDto
{
    /**
     * 流量来源
     */
    private ?string $source = null;

    /**
     * 营销媒介
     */
    private ?string $medium = null;

    /**
     * 营销活动名称
     */
    private ?string $campaign = null;

    /**
     * 付费关键词
     */
    private ?string $term = null;

    /**
     * 内容标识
     */
    private ?string $content = null;

    /**
     * 自定义UTM参数
     * @var array<string, mixed>
     */
    private array $additionalParameters = [];

    /**
     * 从请求参数数组创建DTO
     * @param array<string, mixed> $parameters
     */
    public static function fromArray(array $parameters): self
    {
        $dto = new self();

        self::setStandardParameters($dto, $parameters);
        self::setAdditionalParametersFromArray($dto, $parameters);

        return $dto;
    }

    /**
     * 设置标准UTM参数
     * @param array<string, mixed> $parameters
     */
    private static function setStandardParameters(self $dto, array $parameters): void
    {
        $dto->source = self::extractStringParameter($parameters, 'utm_source');
        $dto->medium = self::extractStringParameter($parameters, 'utm_medium');
        $dto->campaign = self::extractStringParameter($parameters, 'utm_campaign');
        $dto->term = self::extractStringParameter($parameters, 'utm_term');
        $dto->content = self::extractStringParameter($parameters, 'utm_content');
    }

    /**
     * 设置附加参数
     * @param array<string, mixed> $parameters
     */
    private static function setAdditionalParametersFromArray(self $dto, array $parameters): void
    {
        $standardKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'utm_') && !in_array($key, $standardKeys, true)) {
                $dto->additionalParameters[substr($key, 4)] = $value;
            }
        }
    }

    /**
     * 从参数数组中提取字符串参数
     * @param array<string, mixed> $parameters
     */
    private static function extractStringParameter(array $parameters, string $key): ?string
    {
        return isset($parameters[$key]) && is_string($parameters[$key]) ? $parameters[$key] : null;
    }

    /**
     * 检查是否有任何UTM参数设置
     */
    public function hasAnyParameter(): bool
    {
        return null !== $this->source
            || null !== $this->medium
            || null !== $this->campaign
            || null !== $this->term
            || null !== $this->content
            || [] !== $this->additionalParameters;
    }

    /**
     * 转换成数组
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        // 添加标准参数
        if (null !== $this->source) {
            $result['utm_source'] = $this->source;
        }

        if (null !== $this->medium) {
            $result['utm_medium'] = $this->medium;
        }

        if (null !== $this->campaign) {
            $result['utm_campaign'] = $this->campaign;
        }

        if (null !== $this->term) {
            $result['utm_term'] = $this->term;
        }

        if (null !== $this->content) {
            $result['utm_content'] = $this->content;
        }

        // 添加附加参数
        foreach ($this->additionalParameters as $key => $value) {
            $result['utm_' . $key] = $value;
        }

        return $result;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): void
    {
        $this->source = $source;
    }

    public function getMedium(): ?string
    {
        return $this->medium;
    }

    public function setMedium(?string $medium): void
    {
        $this->medium = $medium;
    }

    public function getCampaign(): ?string
    {
        return $this->campaign;
    }

    public function setCampaign(?string $campaign): void
    {
        $this->campaign = $campaign;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): void
    {
        $this->term = $term;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalParameters(): array
    {
        return $this->additionalParameters;
    }

    /**
     * @param array<string, mixed> $additionalParameters
     */
    public function setAdditionalParameters(array $additionalParameters): void
    {
        $this->additionalParameters = $additionalParameters;
    }

    public function addAdditionalParameter(string $key, mixed $value): void
    {
        $this->additionalParameters[$key] = $value;
    }
}
