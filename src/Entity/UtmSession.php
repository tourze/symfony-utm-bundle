<?php

namespace Tourze\UtmBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\UtmBundle\Repository\UtmSessionRepository;

/**
 * 表示一个用户会话，包含UTM参数和会话信息
 */
#[ORM\Entity(repositoryClass: UtmSessionRepository::class)]
#[ORM\Table(name: 'utm_session', options: ['comment' => 'UTM会话表'])]
class UtmSession implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: false, options: ['comment' => '用户会话ID'])]
    #[Assert\NotBlank(message: '会话ID不能为空')]
    #[Assert\Length(max: 255, maxMessage: '会话ID长度不能超过255个字符')]
    private string $sessionId;

    /**
     * UTM参数引用
     */
    #[ORM\ManyToOne(targetEntity: UtmParameter::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'parameters_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?UtmParameter $parameters = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '用户标识符'])]
    #[Assert\Length(max: 255, maxMessage: '用户标识符长度不能超过255个字符')]
    private ?string $userIdentifier = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true, options: ['comment' => '客户端IP地址'])]
    #[Assert\Length(max: 45, maxMessage: 'IP地址长度不能超过45个字符')]
    #[Assert\Ip(message: '请输入有效的IP地址')]
    private ?string $clientIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理字符串'])]
    #[Assert\Length(max: 65535, maxMessage: '用户代理字符串过长')]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '会话过期时间'])]
    #[Assert\DateTime(message: '请输入有效的日期时间')]
    private ?\DateTimeInterface $expiresAt = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '额外会话元数据'])]
    #[Assert\Type(type: 'array', message: '元数据必须是数组类型')]
    private array $metadata = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getParameters(): ?UtmParameter
    {
        return $this->parameters;
    }

    public function setParameters(?UtmParameter $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function setUserIdentifier(?string $userIdentifier): void
    {
        $this->userIdentifier = $userIdentifier;
    }

    public function getClientIp(): ?string
    {
        return $this->clientIp;
    }

    public function setClientIp(?string $clientIp): void
    {
        $this->clientIp = $clientIp;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
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

    public function isExpired(): bool
    {
        if (null === $this->expiresAt) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            'Session[%s:%s]',
            $this->sessionId,
            $this->userIdentifier ?? 'anonymous'
        );
    }
}
