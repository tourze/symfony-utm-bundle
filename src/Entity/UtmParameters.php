<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\UtmBundle\Repository\UtmParametersRepository;

/**
 * 存储标准UTM参数
 */
#[ORM\Entity(repositoryClass: UtmParametersRepository::class)]
#[ORM\Table(name: 'utm_parameters', options: ['comment' => 'UTM参数表'])]
#[ORM\Index(name: 'utm_parameters_idx_source_medium_campaign', columns: ['source', 'medium', 'campaign'])]
class UtmParameters implements Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * utm_source: 流量来源（如：google, facebook, newsletter）
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '流量来源（如：google, facebook, newsletter）'])]
    private ?string $source = null;

    /**
     * utm_medium: 营销媒介（如：cpc, email, social）
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营销媒介（如：cpc, email, social）'])]
    private ?string $medium = null;

    /**
     * utm_campaign: 营销活动名称
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营销活动名称'])]
    private ?string $campaign = null;

    /**
     * utm_term: 付费关键词
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '付费关键词'])]
    private ?string $term = null;

    /**
     * utm_content: 区分相似内容/广告
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '区分相似内容/广告'])]
    private ?string $content = null;

    /**
     * 创建时间
     */
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, options: ['comment' => '创建时间'])]
    private \DateTimeInterface $createTime;

    /**
     * 存储非标准UTM参数
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '存储非标准UTM参数'])]
    private array $additionalParameters = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreateTime(): \DateTimeInterface
    {
        return $this->createTime;
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

    public function __toString(): string
    {
        return sprintf(
            "UTM[%s:%s:%s]",
            $this->source ?? '-',
            $this->medium ?? '-',
            $this->campaign ?? '-'
        );
    }
} 