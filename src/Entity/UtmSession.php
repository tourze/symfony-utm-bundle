<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Stringable;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\UtmBundle\Repository\UtmSessionRepository;

/**
 * 表示一个用户会话，包含UTM参数和会话信息
 */
#[ORM\Entity(repositoryClass: UtmSessionRepository::class)]
#[ORM\Table(name: 'utm_session', options: ['comment' => 'UTM会话表'])]
#[ORM\Index(name: 'utm_session_idx_session_id', columns: ['session_id'])]
#[ORM\Index(name: 'utm_session_idx_user_identifier', columns: ['user_identifier'])]
class UtmSession implements Stringable
{
    use TimestampableAware;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * 用户会话ID
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '用户会话ID'])]
    private string $sessionId;

    /**
     * UTM参数引用
     */
    #[ORM\ManyToOne(targetEntity: UtmParameters::class)]
    #[ORM\JoinColumn(name: 'parameters_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmParameters $parameters = null;

    /**
     * 用户标识符（可为空，未登录用户）
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识符'])]
    private ?string $userIdentifier = null;

    /**
     * 客户端IP地址
     */
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '客户端IP地址'])]
    private ?string $clientIp = null;

    /**
     * 用户代理字符串
     */
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理字符串'])]
    private ?string $userAgent = null;

    /**
     * 创建时间
     */
    #[CreateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false, options: ['comment' => '创建时间'])]
    private \DateTimeInterface $createTime;

    /**
     * 更新时间
     */
    #[UpdateTimeColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]/**
     * 会话过期时间
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '会话过期时间'])]
    private ?\DateTimeInterface $expiresAt = null;

    /**
     * 额外会话元数据
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '额外会话元数据'])]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;
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

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): self
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): self
    {
        $this->clientIp = $clientIp;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
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

    public function isExpired(): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf(
            "Session[%s:%s]",
            $this->sessionId,
            $this->userIdentifier ?? 'anonymous'
        );
    }
}
