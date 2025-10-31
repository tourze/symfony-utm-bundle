<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Entity\UtmParameter;

/**
 * UTM参数仓库
 *
 * @extends ServiceEntityRepository<UtmParameter>
 */
#[AsRepository(entityClass: UtmParameter::class)]
class UtmParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtmParameter::class);
    }

    /**
     * 根据UTM参数DTO查找参数记录
     */
    public function findByParams(UtmParametersDto $utmParamsDto): ?UtmParameter
    {
        $criteria = array_filter([
            'source' => $utmParamsDto->getSource(),
            'medium' => $utmParamsDto->getMedium(),
            'campaign' => $utmParamsDto->getCampaign(),
            'term' => $utmParamsDto->getTerm(),
            'content' => $utmParamsDto->getContent(),
        ], static fn ($value): bool => null !== $value && '' !== $value);

        if ([] === $criteria) {
            return null;
        }

        return $this->findOneBy($criteria);
    }

    public function save(UtmParameter $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UtmParameter $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
