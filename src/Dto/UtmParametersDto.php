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
     */
    private array $additionalParameters = [];

    /**
     * 从请求参数数组创建DTO
     */
    public static function fromArray(array $parameters): self
    {
        $dto = new self();

        // 设置标准UTM参数
        $dto->source = $parameters['utm_source'] ?? null;
        $dto->medium = $parameters['utm_medium'] ?? null;
        $dto->campaign = $parameters['utm_campaign'] ?? null;
        $dto->term = $parameters['utm_term'] ?? null;
        $dto->content = $parameters['utm_content'] ?? null;

        // 处理附加参数
        foreach ($parameters as $key => $value) {
            if (strpos($key, 'utm_') === 0 && !in_array($key, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'])) {
                $dto->additionalParameters[substr($key, 4)] = $value; // 去掉'utm_'前缀
            }
        }

        return $dto;
    }

    /**
     * 检查是否有任何UTM参数设置
     */
    public function hasAnyParameter(): bool
    {
        return $this->source !== null
            || $this->medium !== null
            || $this->campaign !== null
            || $this->term !== null
            || $this->content !== null
            || !empty($this->additionalParameters);
    }

    /**
     * 转换成数组
     */
    public function toArray(): array
    {
        $result = [];

        // 添加标准参数
        if ($this->source !== null) {
            $result['utm_source'] = $this->source;
        }

        if ($this->medium !== null) {
            $result['utm_medium'] = $this->medium;
        }

        if ($this->campaign !== null) {
            $result['utm_campaign'] = $this->campaign;
        }

        if ($this->term !== null) {
            $result['utm_term'] = $this->term;
        }

        if ($this->content !== null) {
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

    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getMedium(): ?string
    {
        return $this->medium;
    }

    public function setMedium(?string $medium): self
    {
        $this->medium = $medium;
        return $this;
    }

    public function getCampaign(): ?string
    {
        return $this->campaign;
    }

    public function setCampaign(?string $campaign): self
    {
        $this->campaign = $campaign;
        return $this;
    }

    public function getTerm(): ?string
    {
        return $this->term;
    }

    public function setTerm(?string $term): self
    {
        $this->term = $term;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getAdditionalParameters(): array
    {
        return $this->additionalParameters;
    }

    public function setAdditionalParameters(array $additionalParameters): self
    {
        $this->additionalParameters = $additionalParameters;
        return $this;
    }

    public function addAdditionalParameter(string $key, mixed $value): self
    {
        $this->additionalParameters[$key] = $value;
        return $this;
    }
}
