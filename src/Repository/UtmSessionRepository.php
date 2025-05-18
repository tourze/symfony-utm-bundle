<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * UTM会话仓库
 *
 * @method UtmSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtmSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtmSession[]    findAll()
 * @method UtmSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtmSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtmSession::class);
    }

    /**
     * 根据会话ID查找会话
     */
    public function findBySessionId(string $sessionId): ?UtmSession
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    /**
     * 查找用户的有效会话
     */
    public function findActiveByUserIdentifier(string $userIdentifier): array
    {
        $qb = $this->createQueryBuilder('s');
        return $qb
            ->where('s.userIdentifier = :userIdentifier')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('s.expiresAt'),
                $qb->expr()->gt('s.expiresAt', ':now')
            ))
            ->setParameter('userIdentifier', $userIdentifier)
            ->setParameter('now', new \DateTime())
            ->orderBy('s.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 创建新的会话
     */
    public function createSession(
        string $sessionId,
        ?UtmParameters $parameters = null,
        ?string $userIdentifier = null,
        ?string $clientIp = null,
        ?string $userAgent = null,
        ?\DateTimeInterface $expiresAt = null
    ): UtmSession {
        $session = new UtmSession();
        $session->setSessionId($sessionId)
            ->setParameters($parameters)
            ->setUserIdentifier($userIdentifier)
            ->setClientIp($clientIp)
            ->setUserAgent($userAgent)
            ->setExpiresAt($expiresAt);

        return $session;
    }

    /**
     * 清理过期会话
     */
    public function cleanExpiredSessions(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return $qb->delete(UtmSession::class, 's')
            ->where('s.expiresAt IS NOT NULL')
            ->andWhere('s.expiresAt < :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }
}
