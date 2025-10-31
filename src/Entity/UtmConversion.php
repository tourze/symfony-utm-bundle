<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\UtmBundle\Repository\UtmConversionRepository;

/**
 * 表示一个转化事件，关联UTM参数
 */
#[ORM\Entity(repositoryClass: UtmConversionRepository::class)]
#[ORM\Table(name: 'utm_conversion', options: ['comment' => 'UTM转化事件表'])]
class UtmConversion implements \Stringable
{
    use CreateTimeAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(name: 'event_name', type: Types::STRING, length: 255, nullable: false, options: ['comment' => '转化事件名称'])]
    #[Assert\NotBlank(message: '事件名称不能为空')]
    #[Assert\Length(max: 255, maxMessage: '事件名称长度不能超过255个字符')]
    private string $eventName;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识符'])]
    #[Assert\Length(max: 255, maxMessage: '用户标识符长度不能超过255个字符')]
    private ?string $userIdentifier = null;

    /**
     * 关联的UTM参数
     */
    #[ORM\ManyToOne(targetEntity: UtmParameter::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'parameters_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmParameter $parameters = null;

    /**
     * 关联的UTM会话
     */
    #[ORM\ManyToOne(targetEntity: UtmSession::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmSession $session = null;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['default' => 0, 'comment' => '转化价值'])]
    #[Assert\GreaterThanOrEqual(value: 0, message: '转化价值不能为负数')]
    private float $value = 0.0;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '转化元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组类型')]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): void
    {
        $this->eventName = $eventName;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getParameters(): ?UtmParameter
    {
        return $this->parameters;
    }

    public function setParameters(?UtmParameter $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getSession(): ?UtmSession
    {
        return $this->session;
    }

    public function setSession(?UtmSession $session): void
    {
        $this->session = $session;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function __toString(): string
    {
        return sprintf(
            'Conversion[%s:%s:%s]',
            $this->eventName,
            $this->userIdentifier ?? 'anonymous',
            $this->value
        );
    }
}
