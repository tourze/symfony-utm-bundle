<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * UTM会话仓库
 *
 * @extends ServiceEntityRepository<UtmSession>
 */
#[AsRepository(entityClass: UtmSession::class)]
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
     * 根据用户标识符查找活跃会话
     *
     * @return array<UtmSession>
     */
    public function findActiveByUserIdentifier(string $userIdentifier): array
    {
        /** @var array<UtmSession> */
        return $this->createQueryBuilder('s')
            ->where('s.userIdentifier = :userIdentifier')
            ->andWhere('s.expiresAt IS NULL OR s.expiresAt > :now')
            ->setParameter('userIdentifier', $userIdentifier)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 清理过期会话
     */
    public function cleanExpiredSessions(): int
    {
        $affectedRows = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expiresAt IS NOT NULL AND s.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute()
        ;
        assert(is_int($affectedRows));

        return $affectedRows;
    }

    public function save(UtmSession $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UtmSession $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
