<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Traits\CreateTimeAware;
use Tourze\UtmBundle\Repository\UtmConversionRepository;

/**
 * 表示一个转化事件，关联UTM参数
 */
#[ORM\Entity(repositoryClass: UtmConversionRepository::class)]
#[ORM\Table(name: 'utm_conversion', options: ['comment' => 'UTM转化事件表'])]
#[ORM\Index(name: 'utm_conversion_idx_event_name', columns: ['event_name'])]
#[ORM\Index(name: 'utm_conversion_idx_user_identifier', columns: ['user_identifier'])]
class UtmConversion implements Stringable
{
    use CreateTimeAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(name: 'event_name', type: Types::STRING, length: 255, nullable: false, options: ['comment' => '转化事件名称'])]
    private string $eventName;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识符'])]
    private ?string $userIdentifier = null;

    /**
     * 关联的UTM参数
     */
    #[ORM\ManyToOne(targetEntity: UtmParameters::class)]
    #[ORM\JoinColumn(name: 'parameters_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmParameters $parameters = null;

    /**
     * 关联的UTM会话
     */
    #[ORM\ManyToOne(targetEntity: UtmSession::class)]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmSession $session = null;

    #[ORM\Column(type: Types::FLOAT, nullable: false, options: ['default' => 0, 'comment' => '转化价值'])]
    private float $value = 0.0;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '转化元数据'])]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function setEventName(string $eventName): self
    {
        $this->eventName = $eventName;
        return $this;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    public function getParameters(): ?UtmParameters
    {
        return $this->parameters;
    }

    public function setParameters(?UtmParameters $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function getSession(): ?UtmSession
    {
        return $this->session;
    }

    public function setSession(?UtmSession $session): self
    {
        $this->session = $session;
        return $this;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function setValue(float $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function addMetadata(string $key, mixed $value): self
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            "Conversion[%s:%s:%s]",
            $this->eventName,
            $this->userIdentifier ?? 'anonymous',
            $this->value
        );
    }
}
