<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\Entity\UtmSession;

/**
 * UTM转化事件仓库
 *
 * @method UtmConversion|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtmConversion|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtmConversion[]    findAll()
 * @method UtmConversion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtmConversionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtmConversion::class);
    }

    /**
     * 创建新的转化事件记录
     */
    public function createConversion(
        string $eventName,
        ?UtmParameters $parameters = null,
        ?UtmSession $session = null,
        ?string $userIdentifier = null,
        float $value = 0.0,
        array $metadata = []
    ): UtmConversion {
        $conversion = new UtmConversion();
        $conversion->setEventName($eventName)
            ->setParameters($parameters)
            ->setSession($session)
            ->setUserIdentifier($userIdentifier)
            ->setValue($value)
            ->setMetadata($metadata);

        return $conversion;
    }

    /**
     * 根据事件名称查找转化事件
     */
    public function findByEventName(string $eventName, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.eventName = :eventName')
            ->setParameter('eventName', $eventName);

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 查找用户的转化事件
     */
    public function findByUserIdentifier(string $userIdentifier, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.userIdentifier = :userIdentifier')
            ->setParameter('userIdentifier', $userIdentifier);

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * 获取转化事件统计信息
     */
    public function getConversionStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c.eventName, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->groupBy('c.eventName');

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM来源转化统计
     */
    public function getUtmSourceStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p.source, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->from(UtmConversion::class, 'c')
            ->join('c.parameters', 'p')
            ->where('p.source IS NOT NULL')
            ->groupBy('p.source');

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM媒介转化统计
     */
    public function getUtmMediumStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p.medium, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->from(UtmConversion::class, 'c')
            ->join('c.parameters', 'p')
            ->where('p.medium IS NOT NULL')
            ->groupBy('p.medium');

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取UTM活动转化统计
     */
    public function getUtmCampaignStats(?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('p.campaign, COUNT(c.id) as count, SUM(c.value) as total_value')
            ->from(UtmConversion::class, 'c')
            ->join('c.parameters', 'p')
            ->where('p.campaign IS NOT NULL')
            ->groupBy('p.campaign');

        if (null !== $startDate) {
            $qb->andWhere('c.createTime >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if (null !== $endDate) {
            $qb->andWhere('c.createTime <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }
}
