<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\UtmBundle\Entity\UtmConversion;

/**
 * UTM转化事件仓库
 *
 * @extends ServiceEntityRepository<UtmConversion>
 */
#[AsRepository(entityClass: UtmConversion::class)]
class UtmConversionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtmConversion::class);
    }

    /**
     * 根据事件名称查找转化事件
     *
     * @return array<UtmConversion>
     */
    public function findByEventName(string $eventName, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.eventName = :eventName')
            ->setParameter('eventName', $eventName)
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<UtmConversion> */
        return $qb->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找用户的转化事件
     *
     * @return array<UtmConversion>
     */
    public function findByUserIdentifier(string $userIdentifier, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.userIdentifier = :userIdentifier')
            ->setParameter('userIdentifier', $userIdentifier)
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<UtmConversion> */
        return $qb->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取转化事件统计信息
     *
     * @return array<int, array<string, mixed>>
     */
    public function getConversionStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.eventName, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->groupBy('c.eventName')
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<int, array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM来源转化统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmSourceStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('p.source, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->join('c.parameters', 'p')
            ->where('p.source IS NOT NULL')
            ->groupBy('p.source')
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<int, array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM媒介转化统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmMediumStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('p.medium, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->join('c.parameters', 'p')
            ->where('p.medium IS NOT NULL')
            ->groupBy('p.medium')
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<int, array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM活动转化统计
     *
     * @return array<int, array<string, mixed>>
     */
    public function getUtmCampaignStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('p.campaign, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->join('c.parameters', 'p')
            ->where('p.campaign IS NOT NULL')
            ->groupBy('p.campaign')
        ;

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate)
            ;
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate)
            ;
        }

        /** @var array<int, array<string, mixed>> */
        return $qb->getQuery()->getResult();
    }

    public function save(UtmConversion $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UtmConversion $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
