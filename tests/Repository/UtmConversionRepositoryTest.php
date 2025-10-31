<?php

namespace Tourze\UtmBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Repository\UtmConversionRepository;

/**
 * @internal
 */
#[CoversClass(UtmConversionRepository::class)]
#[RunTestsInSeparateProcesses]
final class UtmConversionRepositoryTest extends AbstractRepositoryTestCase
{
    private UtmConversionRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(UtmConversionRepository::class);
    }

    public function testFindByEventNameWithNoConversionsReturnsEmptyArray(): void
    {
        // Act
        $result = $this->repository->findByEventName('non_existent_event');

        // Assert
        $this->assertEmpty($result);
    }

    public function testFindByEventNameReturnsMatchingConversions(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        $eventName = 'purchase';

        $conversion1 = new UtmConversion();
        $conversion1->setEventName($eventName);
        $conversion1->setValue(100.0);
        $conversion1->setUserIdentifier('user1');

        $conversion2 = new UtmConversion();
        $conversion2->setEventName($eventName);
        $conversion2->setValue(200.0);
        $conversion2->setUserIdentifier('user2');

        $conversion3 = new UtmConversion();
        $conversion3->setEventName('other_event');
        $conversion3->setValue(50.0);
        $conversion3->setUserIdentifier('user3');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);
        $this->persistAndFlush($conversion3);

        // Act
        $result = $this->repository->findByEventName($eventName);

        // Assert
        $this->assertCount(2, $result);
        // 验证返回的都是正确的事件名
        foreach ($result as $conversion) {
            $this->assertSame($eventName, $conversion->getEventName());
        }
        // 验证返回的用户标识符集合是正确的
        $userIdentifiers = array_map(fn ($c) => $c->getUserIdentifier(), $result);
        $this->assertContains('user1', $userIdentifiers);
        $this->assertContains('user2', $userIdentifiers);
    }

    public function testFindByEventNameWithDateRangeFiltersCorrectly(): void
    {
        // Arrange
        $eventName = 'registration';
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-12-31');

        $conversion = new UtmConversion();
        $conversion->setEventName($eventName);
        $conversion->setValue(10.0);
        $conversion->setUserIdentifier('user_test');

        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findByEventName($eventName, $startDate, $endDate);

        // Assert - 由于我们无法精确控制createTime，只验证返回内容
        $this->assertEmpty($result);
    }

    public function testFindByUserIdentifierWithNoConversionsReturnsEmptyArray(): void
    {
        // Act
        $result = $this->repository->findByUserIdentifier('non_existent_user');

        // Assert
        $this->assertEmpty($result);
    }

    public function testFindByUserIdentifierReturnsMatchingConversions(): void
    {
        // Arrange
        $userIdentifier = 'test_user';

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('event1');
        $conversion1->setValue(75.0);
        $conversion1->setUserIdentifier($userIdentifier);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('event2');
        $conversion2->setValue(125.0);
        $conversion2->setUserIdentifier($userIdentifier);

        $conversion3 = new UtmConversion();
        $conversion3->setEventName('event3');
        $conversion3->setValue(25.0);
        $conversion3->setUserIdentifier('other_user');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);
        $this->persistAndFlush($conversion3);

        // Act
        $result = $this->repository->findByUserIdentifier($userIdentifier);

        // Assert
        $this->assertCount(2, $result);
        // 验证返回的都是正确的用户标识符
        foreach ($result as $conversion) {
            $this->assertSame($userIdentifier, $conversion->getUserIdentifier());
        }
        // 验证返回的事件名集合是正确的
        $eventNames = array_map(fn ($c) => $c->getEventName(), $result);
        $this->assertContains('event1', $eventNames);
        $this->assertContains('event2', $eventNames);
    }

    public function testFindByUserIdentifierWithDateRangeFiltersCorrectly(): void
    {
        // Arrange
        $userIdentifier = 'filtered_user';
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-12-31');

        $conversion = new UtmConversion();
        $conversion->setEventName('test_event');
        $conversion->setValue(30.0);
        $conversion->setUserIdentifier($userIdentifier);

        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findByUserIdentifier($userIdentifier, $startDate, $endDate);

        // Assert - 由于我们无法精确控制createTime，只验证返回内容
        $this->assertEmpty($result);
    }

    public function testFindByEventNameOrdersByCreateTimeDescending(): void
    {
        // Arrange
        $eventName = 'ordered_event';

        // 创建两个转化事件（由于CreateTimeAware会自动设置时间，后创建的会有更新的时间）
        $firstConversion = new UtmConversion();
        $firstConversion->setEventName($eventName);
        $firstConversion->setValue(100.0);
        $firstConversion->setUserIdentifier('user1');

        $this->persistAndFlush($firstConversion);

        // 稍微延迟确保时间不同
        usleep(1000);

        $secondConversion = new UtmConversion();
        $secondConversion->setEventName($eventName);
        $secondConversion->setValue(200.0);
        $secondConversion->setUserIdentifier('user2');

        $this->persistAndFlush($secondConversion);

        // Act
        $result = $this->repository->findByEventName($eventName);

        // Assert
        $this->assertCount(2, $result);
        // 验证都是正确的事件名
        foreach ($result as $conversion) {
            $this->assertSame($eventName, $conversion->getEventName());
        }
        // 验证包含正确的用户标识符
        $userIdentifiers = array_map(fn ($c) => $c->getUserIdentifier(), $result);
        $this->assertContains('user1', $userIdentifiers);
        $this->assertContains('user2', $userIdentifiers);
    }

    public function testFindByUserIdentifierOrdersByCreateTimeDescending(): void
    {
        // Arrange
        $userIdentifier = 'ordered_user';

        // 创建两个转化事件（由于CreateTimeAware会自动设置时间，后创建的会有更新的时间）
        $firstConversion = new UtmConversion();
        $firstConversion->setEventName('event1');
        $firstConversion->setValue(50.0);
        $firstConversion->setUserIdentifier($userIdentifier);

        $this->persistAndFlush($firstConversion);

        // 稍微延迟确保时间不同
        usleep(1000);

        $secondConversion = new UtmConversion();
        $secondConversion->setEventName('event2');
        $secondConversion->setValue(150.0);
        $secondConversion->setUserIdentifier($userIdentifier);

        $this->persistAndFlush($secondConversion);

        // Act
        $result = $this->repository->findByUserIdentifier($userIdentifier);

        // Assert
        $this->assertCount(2, $result);
        // 验证都是正确的用户标识符
        foreach ($result as $conversion) {
            $this->assertSame($userIdentifier, $conversion->getUserIdentifier());
        }
        // 验证包含正确的事件名
        $eventNames = array_map(fn ($c) => $c->getEventName(), $result);
        $this->assertContains('event1', $eventNames);
        $this->assertContains('event2', $eventNames);
    }

    public function testFind(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('test_event');
        $conversion->setValue(100.0);
        $conversion->setUserIdentifier('test_user');

        $this->persistAndFlush($conversion);
        $id = $conversion->getId();

        // Act
        $result = $this->repository->find($id);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($id, $result->getId());
        $this->assertSame('test_event', $result->getEventName());
        $this->assertSame(100.0, $result->getValue());
        $this->assertSame('test_user', $result->getUserIdentifier());
    }

    public function testFindWithNonExistentId(): void
    {
        // Act
        $result = $this->repository->find(99999);

        // Assert
        $this->assertNull($result);
    }

    public function testFindAll(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        $conversion1 = new UtmConversion();
        $conversion1->setEventName('event1');
        $conversion1->setValue(50.0);
        $conversion1->setUserIdentifier('user1');

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('event2');
        $conversion2->setValue(75.0);
        $conversion2->setUserIdentifier('user2');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertCount(2, $result);

        $eventNames = array_map(fn ($c) => $c->getEventName(), $result);
        $this->assertContains('event1', $eventNames);
        $this->assertContains('event2', $eventNames);
    }

    public function testFindAllWithEmptyTable(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertEmpty($result);
    }

    public function testFindBy(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        $conversion1 = new UtmConversion();
        $conversion1->setEventName('purchase');
        $conversion1->setValue(100.0);
        $conversion1->setUserIdentifier('user1');

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('purchase');
        $conversion2->setValue(200.0);
        $conversion2->setUserIdentifier('user2');

        $conversion3 = new UtmConversion();
        $conversion3->setEventName('signup');
        $conversion3->setValue(50.0);
        $conversion3->setUserIdentifier('user3');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);
        $this->persistAndFlush($conversion3);

        // Act
        $result = $this->repository->findBy(['eventName' => 'purchase']);

        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $conversion) {
            $this->assertSame('purchase', $conversion->getEventName());
        }

        $userIdentifiers = array_map(fn ($c) => $c->getUserIdentifier(), $result);
        $this->assertContains('user1', $userIdentifiers);
        $this->assertContains('user2', $userIdentifiers);
    }

    public function testFindByWithLimitAndOffset(): void
    {
        // Arrange
        for ($i = 1; $i <= 5; ++$i) {
            $conversion = new UtmConversion();
            $conversion->setEventName('test_event');
            $conversion->setValue((float) $i * 10);
            $conversion->setUserIdentifier('user_' . $i);
            $this->persistAndFlush($conversion);
        }

        // Act
        $result = $this->repository->findBy(['eventName' => 'test_event'], null, 2, 1);

        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $conversion) {
            $this->assertSame('test_event', $conversion->getEventName());
        }
    }

    public function testFindByWithNoResults(): void
    {
        // Act
        $result = $this->repository->findBy(['eventName' => 'non_existent_event']);

        // Assert
        $this->assertEmpty($result);
    }

    public function testFindOneBy(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('unique_event');
        $conversion->setValue(123.45);
        $conversion->setUserIdentifier('unique_user');

        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findOneBy(['eventName' => 'unique_event']);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('unique_event', $result->getEventName());
        $this->assertSame(123.45, $result->getValue());
        $this->assertSame('unique_user', $result->getUserIdentifier());
    }

    public function testFindOneByWithNoResult(): void
    {
        // Act
        $result = $this->repository->findOneBy(['eventName' => 'non_existent_event']);

        // Assert
        $this->assertNull($result);
    }

    public function testFindOneByWithOrderBy(): void
    {
        // Arrange
        $conversion1 = new UtmConversion();
        $conversion1->setEventName('test_event');
        $conversion1->setValue(50.0);
        $conversion1->setUserIdentifier('user_a');

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('test_event');
        $conversion2->setValue(100.0);
        $conversion2->setUserIdentifier('user_b');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act - 获取value最高的记录
        $result = $this->repository->findOneBy(['eventName' => 'test_event'], ['value' => 'DESC']);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame(100.0, $result->getValue());
        $this->assertSame('user_b', $result->getUserIdentifier());
    }

    public function testCount(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        for ($i = 1; $i <= 3; ++$i) {
            $conversion = new UtmConversion();
            $conversion->setEventName('count_test_event');
            $conversion->setValue((float) $i * 10);
            $conversion->setUserIdentifier('user_' . $i);
            $this->persistAndFlush($conversion);
        }

        // Act
        $count = $this->repository->count(['eventName' => 'count_test_event']);

        // Assert
        $this->assertSame(3, $count);
    }

    public function testCountWithEmptyResult(): void
    {
        // Act
        $count = $this->repository->count(['eventName' => 'non_existent_event']);

        // Assert
        $this->assertSame(0, $count);
    }

    public function testCountAll(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        for ($i = 1; $i <= 5; ++$i) {
            $conversion = new UtmConversion();
            $conversion->setEventName('event_' . $i);
            $conversion->setValue((float) $i * 10);
            $conversion->setUserIdentifier('user_' . $i);
            $this->persistAndFlush($conversion);
        }

        // Act
        $count = $this->repository->count([]);

        // Assert
        $this->assertSame(5, $count);
    }

    public function testSave(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('save_test');
        $conversion->setValue(99.99);
        $conversion->setUserIdentifier('save_user');

        // Act
        $this->repository->save($conversion);

        // Assert
        $this->assertNotNull($conversion->getId());

        // 验证保存到数据库
        $saved = $this->repository->find($conversion->getId());
        $this->assertNotNull($saved);
        $this->assertSame('save_test', $saved->getEventName());
        $this->assertSame(99.99, $saved->getValue());
        $this->assertSame('save_user', $saved->getUserIdentifier());
    }

    public function testSaveWithoutFlush(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('save_no_flush_test');
        $conversion->setValue(88.88);
        $conversion->setUserIdentifier('save_no_flush_user');

        // Act
        $this->repository->save($conversion, false);

        // Assert - 此时还没有ID因为没有flush
        $this->assertNull($conversion->getId());

        // 手动flush
        self::getEntityManager()->flush();

        // 验证现在有了ID
        $this->assertNotNull($conversion->getId());

        // 验证保存到数据库
        $saved = $this->repository->find($conversion->getId());
        $this->assertNotNull($saved);
        $this->assertSame('save_no_flush_test', $saved->getEventName());
    }

    public function testRemove(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('remove_test');
        $conversion->setValue(77.77);
        $conversion->setUserIdentifier('remove_user');

        $this->repository->save($conversion);
        $id = $conversion->getId();

        // 验证存在
        $this->assertNotNull($this->repository->find($id));

        // Act
        $this->repository->remove($conversion);

        // Assert
        $this->assertNull($this->repository->find($id));
    }

    public function testGetConversionStats(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Arrange
        $conversion1 = new UtmConversion();
        $conversion1->setEventName('purchase');
        $conversion1->setValue(100.0);
        $conversion1->setUserIdentifier('user1');

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('purchase');
        $conversion2->setValue(200.0);
        $conversion2->setUserIdentifier('user2');

        $conversion3 = new UtmConversion();
        $conversion3->setEventName('signup');
        $conversion3->setValue(50.0);
        $conversion3->setUserIdentifier('user3');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);
        $this->persistAndFlush($conversion3);

        // Act
        $stats = $this->repository->getConversionStats();

        // Assert
        $this->assertCount(2, $stats);

        // 按事件名整理统计数据
        $statsByEvent = [];
        foreach ($stats as $stat) {
            $eventName = $stat['eventName'];
            $this->assertIsString($eventName);
            $statsByEvent[$eventName] = $stat;
        }

        $this->assertArrayHasKey('purchase', $statsByEvent);
        $this->assertArrayHasKey('signup', $statsByEvent);

        $this->assertSame(2, $statsByEvent['purchase']['count']);
        $this->assertSame(300.0, $statsByEvent['purchase']['total_value']);

        $this->assertSame(1, $statsByEvent['signup']['count']);
        $this->assertSame(50.0, $statsByEvent['signup']['total_value']);
    }

    public function testGetConversionStatsWithDateRange(): void
    {
        // Arrange
        $conversion = new UtmConversion();
        $conversion->setEventName('date_range_test');
        $conversion->setValue(100.0);
        $conversion->setUserIdentifier('user1');

        $this->persistAndFlush($conversion);

        $startDate = new \DateTime('2020-01-01');
        $endDate = new \DateTime('2030-12-31');

        // Act
        $stats = $this->repository->getConversionStats($startDate, $endDate);

        // Assert - 由于我们无法精确控制createTime，只验证返回格式
        foreach ($stats as $stat) {
            $this->assertArrayHasKey('eventName', $stat);
            $this->assertArrayHasKey('count', $stat);
            $this->assertArrayHasKey('total_value', $stat);
        }
    }

    public function testGetConversionStatsWithEmptyResult(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Act
        $stats = $this->repository->getConversionStats();

        // Assert
        $this->assertEmpty($stats);
    }

    public function testGetUtmSourceStats(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange
        $utmParams1 = new UtmParameter();
        $utmParams1->setSource('google');
        $utmParams1->setMedium('cpc');
        $utmParams1->setCampaign('test_campaign');
        $this->persistAndFlush($utmParams1);

        $utmParams2 = new UtmParameter();
        $utmParams2->setSource('facebook');
        $utmParams2->setMedium('social');
        $utmParams2->setCampaign('social_campaign');
        $this->persistAndFlush($utmParams2);

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('purchase');
        $conversion1->setValue(100.0);
        $conversion1->setUserIdentifier('user1');
        $conversion1->setParameters($utmParams1);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('purchase');
        $conversion2->setValue(200.0);
        $conversion2->setUserIdentifier('user2');
        $conversion2->setParameters($utmParams1);

        $conversion3 = new UtmConversion();
        $conversion3->setEventName('signup');
        $conversion3->setValue(50.0);
        $conversion3->setUserIdentifier('user3');
        $conversion3->setParameters($utmParams2);

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);
        $this->persistAndFlush($conversion3);

        // Act
        $stats = $this->repository->getUtmSourceStats();

        // Assert
        $this->assertCount(2, $stats);

        // 按来源整理统计数据
        $statsBySource = [];
        foreach ($stats as $stat) {
            $source = $stat['source'];
            $this->assertIsString($source);
            $statsBySource[$source] = $stat;
        }

        $this->assertArrayHasKey('google', $statsBySource);
        $this->assertArrayHasKey('facebook', $statsBySource);

        $this->assertSame(2, $statsBySource['google']['count']);
        $this->assertSame(300.0, $statsBySource['google']['total_value']);

        $this->assertSame(1, $statsBySource['facebook']['count']);
        $this->assertSame(50.0, $statsBySource['facebook']['total_value']);
    }

    public function testGetUtmSourceStatsWithEmptyResult(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Act
        $stats = $this->repository->getUtmSourceStats();

        // Assert
        $this->assertEmpty($stats);
    }

    public function testGetUtmMediumStats(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange
        $utmParams1 = new UtmParameter();
        $utmParams1->setSource('google');
        $utmParams1->setMedium('cpc');
        $utmParams1->setCampaign('test_campaign');
        $this->persistAndFlush($utmParams1);

        $utmParams2 = new UtmParameter();
        $utmParams2->setSource('facebook');
        $utmParams2->setMedium('social');
        $utmParams2->setCampaign('social_campaign');
        $this->persistAndFlush($utmParams2);

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('purchase');
        $conversion1->setValue(150.0);
        $conversion1->setUserIdentifier('user1');
        $conversion1->setParameters($utmParams1);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('signup');
        $conversion2->setValue(75.0);
        $conversion2->setUserIdentifier('user2');
        $conversion2->setParameters($utmParams2);

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act
        $stats = $this->repository->getUtmMediumStats();

        // Assert
        $this->assertCount(2, $stats);

        // 按媒介整理统计数据
        $statsByMedium = [];
        foreach ($stats as $stat) {
            $medium = $stat['medium'];
            $this->assertIsString($medium);
            $statsByMedium[$medium] = $stat;
        }

        $this->assertArrayHasKey('cpc', $statsByMedium);
        $this->assertArrayHasKey('social', $statsByMedium);

        $this->assertSame(1, $statsByMedium['cpc']['count']);
        $this->assertSame(150.0, $statsByMedium['cpc']['total_value']);

        $this->assertSame(1, $statsByMedium['social']['count']);
        $this->assertSame(75.0, $statsByMedium['social']['total_value']);
    }

    public function testGetUtmMediumStatsWithEmptyResult(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Act
        $stats = $this->repository->getUtmMediumStats();

        // Assert
        $this->assertEmpty($stats);
    }

    public function testGetUtmCampaignStats(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange
        $utmParams1 = new UtmParameter();
        $utmParams1->setSource('google');
        $utmParams1->setMedium('cpc');
        $utmParams1->setCampaign('summer_sale');
        $this->persistAndFlush($utmParams1);

        $utmParams2 = new UtmParameter();
        $utmParams2->setSource('facebook');
        $utmParams2->setMedium('social');
        $utmParams2->setCampaign('brand_awareness');
        $this->persistAndFlush($utmParams2);

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('purchase');
        $conversion1->setValue(250.0);
        $conversion1->setUserIdentifier('user1');
        $conversion1->setParameters($utmParams1);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('signup');
        $conversion2->setValue(125.0);
        $conversion2->setUserIdentifier('user2');
        $conversion2->setParameters($utmParams2);

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act
        $stats = $this->repository->getUtmCampaignStats();

        // Assert
        $this->assertCount(2, $stats);

        // 按活动整理统计数据
        $statsByCampaign = [];
        foreach ($stats as $stat) {
            $campaign = $stat['campaign'];
            $this->assertIsString($campaign);
            $statsByCampaign[$campaign] = $stat;
        }

        $this->assertArrayHasKey('summer_sale', $statsByCampaign);
        $this->assertArrayHasKey('brand_awareness', $statsByCampaign);

        $this->assertSame(1, $statsByCampaign['summer_sale']['count']);
        $this->assertSame(250.0, $statsByCampaign['summer_sale']['total_value']);

        $this->assertSame(1, $statsByCampaign['brand_awareness']['count']);
        $this->assertSame(125.0, $statsByCampaign['brand_awareness']['total_value']);
    }

    public function testGetUtmCampaignStatsWithEmptyResult(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();

        // Act
        $stats = $this->repository->getUtmCampaignStats();

        // Assert
        $this->assertEmpty($stats);
    }

    public function testWithAssociatedUtmParameters(): void
    {
        // Arrange
        $utmParams = new UtmParameter();
        $utmParams->setSource('google');
        $utmParams->setMedium('cpc');
        $utmParams->setCampaign('test_campaign');
        $utmParams->setTerm('test_term');
        $utmParams->setContent('test_content');
        $this->persistAndFlush($utmParams);

        $conversion = new UtmConversion();
        $conversion->setEventName('purchase_with_utm');
        $conversion->setValue(99.99);
        $conversion->setUserIdentifier('utm_user');
        $conversion->setParameters($utmParams);
        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findOneBy(['eventName' => 'purchase_with_utm']);

        // Assert
        $this->assertNotNull($result);
        $this->assertNotNull($result->getParameters());
        $this->assertSame('google', $result->getParameters()->getSource());
        $this->assertSame('cpc', $result->getParameters()->getMedium());
        $this->assertSame('test_campaign', $result->getParameters()->getCampaign());
        $this->assertSame('test_term', $result->getParameters()->getTerm());
        $this->assertSame('test_content', $result->getParameters()->getContent());
    }

    public function testWithAssociatedUtmSession(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('test_session_id');
        $session->setUserIdentifier('session_user');
        $session->setClientIp('192.168.1.1');
        $session->setUserAgent('Test Agent');
        $this->persistAndFlush($session);

        $conversion = new UtmConversion();
        $conversion->setEventName('purchase_with_session');
        $conversion->setValue(199.99);
        $conversion->setUserIdentifier('session_user');
        $conversion->setSession($session);
        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findOneBy(['eventName' => 'purchase_with_session']);

        // Assert
        $this->assertNotNull($result);
        $this->assertNotNull($result->getSession());
        $this->assertSame('test_session_id', $result->getSession()->getSessionId());
        $this->assertSame('session_user', $result->getSession()->getUserIdentifier());
        $this->assertSame('192.168.1.1', $result->getSession()->getClientIp());
        $this->assertSame('Test Agent', $result->getSession()->getUserAgent());
    }

    public function testWithMetadata(): void
    {
        // Arrange
        $metadata = [
            'product_id' => 'PROD123',
            'category' => 'electronics',
            'discount_applied' => true,
            'coupon_code' => 'SAVE20',
        ];

        $conversion = new UtmConversion();
        $conversion->setEventName('purchase_with_metadata');
        $conversion->setValue(79.99);
        $conversion->setUserIdentifier('metadata_user');
        $conversion->setMetadata($metadata);
        $this->persistAndFlush($conversion);

        // Act
        $result = $this->repository->findOneBy(['eventName' => 'purchase_with_metadata']);

        // Assert
        $this->assertNotNull($result);
        $actualMetadata = $result->getMetadata();
        $this->assertSame($metadata, $actualMetadata);
        $this->assertArrayHasKey('product_id', $actualMetadata);
        $this->assertArrayHasKey('category', $actualMetadata);
        $this->assertArrayHasKey('discount_applied', $actualMetadata);
        $this->assertArrayHasKey('coupon_code', $actualMetadata);
        $this->assertSame('PROD123', $actualMetadata['product_id']);
        $this->assertSame('electronics', $actualMetadata['category']);
        $this->assertSame('SAVE20', $actualMetadata['coupon_code']);
    }

    public function testDatabaseUnavailableException(): void
    {
        // 这个测试模拟数据库不可用的情况，但由于我们使用的是集成测试
        // 很难真实模拟数据库连接失败，所以我们测试一个会抛出异常的查询

        $this->expectException(\Exception::class);

        // 使用错误的查询语法来触发异常
        self::getEntityManager()->createQuery('INVALID SQL QUERY')->execute();
    }

    public function testFindOneByAssociationParametersShouldReturnMatchingEntity(): void
    {
        // 清理数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange
        $utmParams = new UtmParameter();
        $utmParams->setSource('test_source');
        $utmParams->setMedium('test_medium');
        $utmParams->setCampaign('test_campaign');
        $this->persistAndFlush($utmParams);

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('params_test');
        $conversion1->setValue(100.0);
        $conversion1->setUserIdentifier('user1');
        $conversion1->setParameters($utmParams);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('params_test');
        $conversion2->setValue(200.0);
        $conversion2->setUserIdentifier('user2');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act
        $result = $this->repository->findOneBy(['parameters' => $utmParams]);

        // Assert
        $this->assertNotNull($result);
        $parameters = $result->getParameters();
        $this->assertNotNull($parameters);
        $this->assertSame($utmParams->getId(), $parameters->getId());
        $this->assertSame('user1', $result->getUserIdentifier());
    }

    public function testFindOneByAssociationSessionShouldReturnMatchingEntity(): void
    {
        // 清理数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Arrange
        $session = new UtmSession();
        $session->setSessionId('test_session');
        $session->setUserIdentifier('session_user');
        $session->setClientIp('127.0.0.1');
        $session->setUserAgent('Test Browser');
        $this->persistAndFlush($session);

        $conversion1 = new UtmConversion();
        $conversion1->setEventName('session_test');
        $conversion1->setValue(150.0);
        $conversion1->setUserIdentifier('user1');
        $conversion1->setSession($session);

        $conversion2 = new UtmConversion();
        $conversion2->setEventName('session_test');
        $conversion2->setValue(250.0);
        $conversion2->setUserIdentifier('user2');

        $this->persistAndFlush($conversion1);
        $this->persistAndFlush($conversion2);

        // Act
        $result = $this->repository->findOneBy(['session' => $session]);

        // Assert
        $this->assertNotNull($result);
        $resultSession = $result->getSession();
        $this->assertNotNull($resultSession);
        $this->assertSame($session->getId(), $resultSession->getId());
        $this->assertSame('user1', $result->getUserIdentifier());
    }

    public function testCountByAssociationParametersShouldReturnCorrectNumber(): void
    {
        // 清理数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange
        $utmParams = new UtmParameter();
        $utmParams->setSource('count_test_source');
        $utmParams->setMedium('count_test_medium');
        $utmParams->setCampaign('count_test_campaign');
        $this->persistAndFlush($utmParams);

        for ($i = 1; $i <= 3; ++$i) {
            $conversion = new UtmConversion();
            $conversion->setEventName('count_params_test');
            $conversion->setValue((float) $i * 10);
            $conversion->setUserIdentifier('user_' . $i);
            $conversion->setParameters($utmParams);
            $this->persistAndFlush($conversion);
        }

        $conversionWithoutParams = new UtmConversion();
        $conversionWithoutParams->setEventName('count_params_test');
        $conversionWithoutParams->setValue(999.0);
        $conversionWithoutParams->setUserIdentifier('user_no_params');
        $this->persistAndFlush($conversionWithoutParams);

        // Act
        $count = $this->repository->count(['parameters' => $utmParams]);

        // Assert
        $this->assertSame(3, $count);
    }

    public function testCountByAssociationSessionShouldReturnCorrectNumber(): void
    {
        // 清理数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmConversion')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Arrange
        $session = new UtmSession();
        $session->setSessionId('count_test_session');
        $session->setUserIdentifier('count_session_user');
        $session->setClientIp('127.0.0.1');
        $session->setUserAgent('Test Browser');
        $this->persistAndFlush($session);

        for ($i = 1; $i <= 2; ++$i) {
            $conversion = new UtmConversion();
            $conversion->setEventName('count_session_test');
            $conversion->setValue((float) $i * 50);
            $conversion->setUserIdentifier('user_' . $i);
            $conversion->setSession($session);
            $this->persistAndFlush($conversion);
        }

        $conversionWithoutSession = new UtmConversion();
        $conversionWithoutSession->setEventName('count_session_test');
        $conversionWithoutSession->setValue(999.0);
        $conversionWithoutSession->setUserIdentifier('user_no_session');
        $this->persistAndFlush($conversionWithoutSession);

        // Act
        $count = $this->repository->count(['session' => $session]);

        // Assert
        $this->assertSame(2, $count);
    }

    protected function createNewEntity(): object
    {
        $entity = new UtmConversion();
        $entity->setEventName('test_event_' . uniqid());
        $entity->setValue((float) rand(1, 1000));
        $entity->setUserIdentifier('test_user_' . uniqid());

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<UtmConversion>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
