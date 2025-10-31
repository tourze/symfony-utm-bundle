<?php

namespace Tourze\UtmBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Repository\UtmParameterRepository;

/**
 * @internal
 */
#[CoversClass(UtmParameterRepository::class)]
#[RunTestsInSeparateProcesses]
final class UtmParameterRepositoryTest extends AbstractRepositoryTestCase
{
    private UtmParameterRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getService(UtmParameterRepository::class);
        self::assertInstanceOf(UtmParameterRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testFindByParamsWithAllParameters(): void
    {
        // 创建测试数据
        $parameters = new UtmParameter();
        $parameters->setSource('google');
        $parameters->setMedium('cpc');
        $parameters->setCampaign('summer_sale');
        $parameters->setTerm('shoes');
        $parameters->setContent('link1');

        self::getEntityManager()->persist($parameters);
        self::getEntityManager()->flush();

        // 创建DTO
        $dto = new UtmParametersDto();
        $dto->setSource('google');
        $dto->setMedium('cpc');
        $dto->setCampaign('summer_sale');
        $dto->setTerm('shoes');
        $dto->setContent('link1');

        // 测试查找
        $result = $this->repository->findByParams($dto);

        $this->assertNotNull($result);
        $this->assertEquals('google', $result->getSource());
        $this->assertEquals('cpc', $result->getMedium());
        $this->assertEquals('summer_sale', $result->getCampaign());
        $this->assertEquals('shoes', $result->getTerm());
        $this->assertEquals('link1', $result->getContent());
    }

    public function testFindByParamsWithPartialParameters(): void
    {
        // 创建测试数据（使用不会与fixtures冲突的组合）
        $parameters = new UtmParameter();
        $parameters->setSource('test_source');
        $parameters->setMedium('test_medium');

        self::getEntityManager()->persist($parameters);
        self::getEntityManager()->flush();

        // 创建DTO（只包含部分参数）
        $dto = new UtmParametersDto();
        $dto->setSource('test_source');
        $dto->setMedium('test_medium');

        // 测试查找
        $result = $this->repository->findByParams($dto);

        $this->assertNotNull($result);
        $this->assertEquals('test_source', $result->getSource());
        $this->assertEquals('test_medium', $result->getMedium());
        $this->assertNull($result->getCampaign());
        $this->assertNull($result->getTerm());
        $this->assertNull($result->getContent());
    }

    public function testFindByParamsWithNoMatch(): void
    {
        // 创建不匹配的测试数据
        $parameters = new UtmParameter();
        $parameters->setSource('twitter');
        $parameters->setMedium('social');

        self::getEntityManager()->persist($parameters);
        self::getEntityManager()->flush();

        // 创建不匹配的DTO（使用完全不存在的参数组合）
        $dto = new UtmParametersDto();
        $dto->setSource('nonexistent');
        $dto->setMedium('unknown');

        // 测试查找
        $result = $this->repository->findByParams($dto);

        $this->assertNull($result);
    }

    public function testFindByParamsWithEmptyDto(): void
    {
        // 创建空的DTO
        $dto = new UtmParametersDto();

        // 测试查找
        $result = $this->repository->findByParams($dto);

        $this->assertNull($result);
    }

    public function testFindByParamsWithNullValues(): void
    {
        // 创建测试数据
        $parameters = new UtmParameter();
        $parameters->setSource('email');
        $parameters->setMedium('newsletter');

        self::getEntityManager()->persist($parameters);
        self::getEntityManager()->flush();

        // 创建包含null值的DTO
        $dto = new UtmParametersDto();
        $dto->setSource('email');
        $dto->setMedium('newsletter');
        $dto->setCampaign(null);
        $dto->setTerm(null);
        $dto->setContent(null);

        // 测试查找
        $result = $this->repository->findByParams($dto);

        $this->assertNotNull($result);
        $this->assertEquals('email', $result->getSource());
        $this->assertEquals('newsletter', $result->getMedium());
    }

    public function testFindByParamsWithMultipleRecords(): void
    {
        // 创建第一条记录
        $parameters1 = new UtmParameter();
        $parameters1->setSource('google');
        $parameters1->setMedium('cpc');
        $parameters1->setCampaign('spring');

        // 创建第二条记录（相同的source和medium，不同的campaign）
        $parameters2 = new UtmParameter();
        $parameters2->setSource('google');
        $parameters2->setMedium('cpc');
        $parameters2->setCampaign('summer');

        self::getEntityManager()->persist($parameters1);
        self::getEntityManager()->persist($parameters2);
        self::getEntityManager()->flush();

        // 查找第一条记录
        $dto1 = new UtmParametersDto();
        $dto1->setSource('google');
        $dto1->setMedium('cpc');
        $dto1->setCampaign('spring');

        $result1 = $this->repository->findByParams($dto1);
        $this->assertNotNull($result1);
        $this->assertEquals('spring', $result1->getCampaign());

        // 查找第二条记录
        $dto2 = new UtmParametersDto();
        $dto2->setSource('google');
        $dto2->setMedium('cpc');
        $dto2->setCampaign('summer');

        $result2 = $this->repository->findByParams($dto2);
        $this->assertNotNull($result2);
        $this->assertEquals('summer', $result2->getCampaign());
    }

    public function testFindOneByWithOrderByShouldReturnFirstOrderedEntity(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters1 = new UtmParameter();
        $parameters1->setSource('test');
        $parameters1->setMedium('medium1');
        $parameters1->setCampaign('z_campaign');

        $parameters2 = new UtmParameter();
        $parameters2->setSource('test');
        $parameters2->setMedium('medium2');
        $parameters2->setCampaign('a_campaign');

        $em = self::getEntityManager();
        $em->persist($parameters1);
        $em->persist($parameters2);
        $em->flush();

        // 测试按升序排序后取第一个
        $result = $this->repository->findOneBy(['source' => 'test'], ['campaign' => 'ASC']);

        $this->assertNotNull($result);
        $this->assertEquals('a_campaign', $result->getCampaign());

        // 测试按降序排序后取第一个
        $result = $this->repository->findOneBy(['source' => 'test'], ['campaign' => 'DESC']);

        $this->assertNotNull($result);
        $this->assertEquals('z_campaign', $result->getCampaign());
    }

    public function testSaveMethodShouldPersistEntity(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters = new UtmParameter();
        $parameters->setSource('save_test');
        $parameters->setMedium('save_medium');
        $parameters->setCampaign('save_campaign');

        // 测试保存实体
        $this->repository->save($parameters);

        $this->assertNotNull($parameters->getId());

        // 验证实体已保存到数据库
        $found = $this->repository->find($parameters->getId());
        $this->assertNotNull($found);
        $this->assertEquals('save_test', $found->getSource());
        $this->assertEquals('save_medium', $found->getMedium());
        $this->assertEquals('save_campaign', $found->getCampaign());
    }

    public function testSaveMethodWithFlushParameterShouldWork(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters = new UtmParameter();
        $parameters->setSource('test_flush_param');
        $parameters->setMedium('test_medium');

        // 测试带flush参数的保存
        $this->repository->save($parameters, true);

        // 验证实体已保存
        $this->assertNotNull($parameters->getId());
        $found = $this->repository->findBy(['source' => 'test_flush_param']);
        $this->assertCount(1, $found);
        $this->assertEquals('test_medium', $found[0]->getMedium());
    }

    public function testRemoveMethodShouldDeleteEntity(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters = new UtmParameter();
        $parameters->setSource('remove_test');
        $parameters->setMedium('remove_medium');

        $em = self::getEntityManager();
        $em->persist($parameters);
        $em->flush();

        $id = $parameters->getId();
        $this->assertNotNull($id);

        // 验证实体存在
        $found = $this->repository->find($id);
        $this->assertNotNull($found);

        // 测试删除实体
        $this->repository->remove($parameters);

        // 验证实体已删除
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testRemoveMethodWithFlushParameterShouldWork(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters = new UtmParameter();
        $parameters->setSource('test_remove');
        $parameters->setMedium('test_medium');

        $em->persist($parameters);
        $em->flush();

        $id = $parameters->getId();
        $this->assertNotNull($id);

        // 验证实体存在
        $found = $this->repository->find($id);
        $this->assertNotNull($found);

        // 测试删除方法（默认flush=true）
        $this->repository->remove($parameters);

        // 验证实体已被删除
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testFindByWithNullFieldValueShouldMatchNullRecords(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters1 = new UtmParameter();
        $parameters1->setSource('test');
        $parameters1->setMedium('medium1'); // campaign为null

        $parameters2 = new UtmParameter();
        $parameters2->setSource('test');
        $parameters2->setMedium('medium2');
        $parameters2->setCampaign('not_null');

        $em = self::getEntityManager();
        $em->persist($parameters1);
        $em->persist($parameters2);
        $em->flush();

        // 测试查找campaign为null的记录
        $results = $this->repository->findBy(['source' => 'test', 'campaign' => null]);

        $this->assertCount(1, $results);
        $this->assertEquals('medium1', $results[0]->getMedium());
        $this->assertNull($results[0]->getCampaign());
    }

    public function testFindOneByWithNullFieldValueShouldMatchNullRecord(): void
    {
        // 清理现有数据
        $em = self::getEntityManager();
        $connection = $em->getConnection();
        $connection->executeStatement('DELETE FROM utm_parameters');
        $em->clear();

        $parameters = new UtmParameter();
        $parameters->setSource('null_test');
        $parameters->setMedium('null_medium');
        $parameters->setTerm(null);  // 显式设置为null
        $parameters->setContent(null); // 显式设置为null

        $em = self::getEntityManager();
        $em->persist($parameters);
        $em->flush();

        // 测试通过null值查找
        $result = $this->repository->findOneBy([
            'source' => 'null_test',
            'term' => null,
        ]);

        $this->assertNotNull($result);
        $this->assertEquals('null_test', $result->getSource());
        $this->assertEquals('null_medium', $result->getMedium());
        $this->assertNull($result->getTerm());
        $this->assertNull($result->getContent());
    }

    /**
     * @return UtmParameter
     */
    protected function createNewEntity(): object
    {
        $entity = new UtmParameter();
        $entity->setSource('test_source_' . uniqid());
        $entity->setMedium('test_medium_' . uniqid());
        $entity->setCampaign('test_campaign_' . uniqid());
        $entity->setTerm('test_term_' . uniqid());
        $entity->setContent('test_content_' . uniqid());

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<UtmParameter>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
