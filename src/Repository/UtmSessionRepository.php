<?php

namespace Tourze\UtmBundle\Repository;

use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
     * 根据会话ID查找UTM会话
     */
    public function findBySessionId(string $sessionId): ?UtmSession
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    /**
     * 创建新的UTM会话
     */
    public function createSession(string $sessionId, ?string $userIdentifier = null): UtmSession
    {
        $session = new UtmSession();
        $session->setSessionId($sessionId);

        if (!empty($userIdentifier)) {
            $session->setUserIdentifier($userIdentifier);
        }

        return $session;
    }

    /**
     * 根据用户标识符查找活跃会话
     *
     * @return UtmSession[]
     */
    public function findActiveByUserIdentifier(string $userIdentifier): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :userIdentifier')
            ->andWhere('s.expiresAt IS NULL OR s.expiresAt > :now')
            ->setParameter('userIdentifier', $userIdentifier)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * 清理过期会话
     */
    public function cleanExpiredSessions(): int
    {
        return $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt IS NOT NULL AND s.expiresAt < :now')
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}
