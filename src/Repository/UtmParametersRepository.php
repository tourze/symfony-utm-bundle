<?php

namespace Tourze\UtmBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Entity\UtmParameters;

/**
 * UTM参数仓库
 *
 * @method UtmParameters|null find($id, $lockMode = null, $lockVersion = null)
 * @method UtmParameters|null findOneBy(array $criteria, array $orderBy = null)
 * @method UtmParameters[]    findAll()
 * @method UtmParameters[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtmParametersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UtmParameters::class);
    }

    /**
     * 根据UTM参数DTO查找或创建参数记录
     */
    public function findOrCreateByParams(UtmParametersDto $utmParamsDto): UtmParameters
    {
        $criteria = array_filter([
            'source' => $utmParamsDto->getSource(),
            'medium' => $utmParamsDto->getMedium(),
            'campaign' => $utmParamsDto->getCampaign(),
            'term' => $utmParamsDto->getTerm(),
            'content' => $utmParamsDto->getContent(),
        ]);

        if (empty($criteria)) {
            return new UtmParameters();
        }

        $parameters = $this->findOneBy($criteria);

        if (null === $parameters) {
            $parameters = new UtmParameters();
            $parameters
                ->setSource($utmParamsDto->getSource())
                ->setMedium($utmParamsDto->getMedium())
                ->setCampaign($utmParamsDto->getCampaign())
                ->setTerm($utmParamsDto->getTerm())
                ->setContent($utmParamsDto->getContent());

            // 处理额外的UTM参数
            $additionalParams = $utmParamsDto->getAdditionalParameters();
            if (!empty($additionalParams)) {
                $parameters->setAdditionalParameters($additionalParams);
            }
        }

        return $parameters;
    }
}
