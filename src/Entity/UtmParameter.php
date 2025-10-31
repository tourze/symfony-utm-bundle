<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\UtmBundle\Repository\UtmParameterRepository;

/**
 * 存储标准UTM参数
 */
#[ORM\Entity(repositoryClass: UtmParameterRepository::class)]
#[ORM\Table(name: 'utm_parameters', options: ['comment' => 'UTM参数表'])]
#[ORM\Index(name: 'utm_parameters_idx_source_medium_campaign', columns: ['source', 'medium', 'campaign'])]
class UtmParameter implements \Stringable
{
    use CreateTimeAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '流量来源（如：google, facebook, newsletter）'])]
    #[Assert\Length(max: 255, maxMessage: '流量来源长度不能超过255个字符')]
    private ?string $source = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营销媒介（如：cpc, email, social）'])]
    #[Assert\Length(max: 255, maxMessage: '营销媒介长度不能超过255个字符')]
    private ?string $medium = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '营销活动名称'])]
    #[Assert\Length(max: 255, maxMessage: '营销活动名称长度不能超过255个字符')]
    private ?string $campaign = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '付费关键词'])]
    #[Assert\Length(max: 255, maxMessage: '付费关键词长度不能超过255个字符')]
    private ?string $term = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '区分相似内容/广告'])]
    #[Assert\Length(max: 255, maxMessage: '广告内容标识长度不能超过255个字符')]
    private ?string $content = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '存储非标准UTM参数'])]
    #[Assert\Type(type: 'array', message: '额外参数必须是数组类型')]
    private array $additionalParameters = [];

    public function getId(): ?int
    {
        return $this->id;
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

    public function __toString(): string
    {
        return sprintf(
            'UTM[%s:%s:%s]',
            $this->source ?? '-',
            $this->medium ?? '-',
            $this->campaign ?? '-'
        );
    }
}
