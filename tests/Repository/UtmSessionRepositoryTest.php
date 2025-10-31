<?php

namespace Tourze\UtmBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Repository\UtmSessionRepository;

/**
 * @internal
 */
#[CoversClass(UtmSessionRepository::class)]
#[RunTestsInSeparateProcesses]
final class UtmSessionRepositoryTest extends AbstractRepositoryTestCase
{
    private UtmSessionRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getService(UtmSessionRepository::class);
        self::assertInstanceOf(UtmSessionRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testFindBySessionId(): void
    {
        // 创建测试数据
        $session = new UtmSession();
        $session->setSessionId('test_session_123');
        $session->setUserIdentifier('user_456');
        $session->setClientIp('192.168.1.1');
        $session->setUserAgent('Mozilla/5.0 TestAgent');

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // 测试查找
        $result = $this->repository->findBySessionId('test_session_123');

        $this->assertNotNull($result);
        $this->assertEquals('test_session_123', $result->getSessionId());
        $this->assertEquals('user_456', $result->getUserIdentifier());
        $this->assertEquals('192.168.1.1', $result->getClientIp());
        $this->assertEquals('Mozilla/5.0 TestAgent', $result->getUserAgent());
    }

    public function testFindBySessionIdNotFound(): void
    {
        // 创建一个会话
        $session = new UtmSession();
        $session->setSessionId('existing_session');

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // 测试查找不存在的会话
        $result = $this->repository->findBySessionId('non_existing_session');

        $this->assertNull($result);
    }

    public function testFindActiveByUserIdentifier(): void
    {
        $now = new \DateTimeImmutable();
        $futureTime = $now->add(new \DateInterval('PT1H')); // 1小时后
        $pastTime = $now->sub(new \DateInterval('PT1H'));   // 1小时前

        // 创建活跃会话（未过期）
        $activeSession1 = new UtmSession();
        $activeSession1->setSessionId('active_session_1');
        $activeSession1->setUserIdentifier('user_123');
        $activeSession1->setExpiresAt($futureTime);

        // 创建活跃会话（无过期时间）
        $activeSession2 = new UtmSession();
        $activeSession2->setSessionId('active_session_2');
        $activeSession2->setUserIdentifier('user_123');

        // 创建过期会话
        $expiredSession = new UtmSession();
        $expiredSession->setSessionId('expired_session');
        $expiredSession->setUserIdentifier('user_123');
        $expiredSession->setExpiresAt($pastTime);

        // 创建其他用户的会话
        $otherUserSession = new UtmSession();
        $otherUserSession->setSessionId('other_user_session');
        $otherUserSession->setUserIdentifier('user_456');
        $otherUserSession->setExpiresAt($futureTime);

        self::getEntityManager()->persist($activeSession1);
        self::getEntityManager()->persist($activeSession2);
        self::getEntityManager()->persist($expiredSession);
        self::getEntityManager()->persist($otherUserSession);
        self::getEntityManager()->flush();

        // 测试查找
        $results = $this->repository->findActiveByUserIdentifier('user_123');

        $this->assertCount(2, $results);

        $sessionIds = array_map(function (UtmSession $session) {
            return $session->getSessionId();
        }, $results);

        $this->assertContains('active_session_1', $sessionIds);
        $this->assertContains('active_session_2', $sessionIds);
        $this->assertNotContains('expired_session', $sessionIds);
        $this->assertNotContains('other_user_session', $sessionIds);
    }

    public function testFindActiveByUserIdentifierNoResults(): void
    {
        // 创建过期会话
        $expiredSession = new UtmSession();
        $expiredSession->setSessionId('expired_session');
        $expiredSession->setUserIdentifier('user_123');
        $expiredSession->setExpiresAt(new \DateTimeImmutable('yesterday'));

        self::getEntityManager()->persist($expiredSession);
        self::getEntityManager()->flush();

        // 测试查找不存在用户的活跃会话
        $results = $this->repository->findActiveByUserIdentifier('non_existing_user');
        $this->assertEmpty($results);

        // 测试查找只有过期会话的用户
        $results = $this->repository->findActiveByUserIdentifier('user_123');
        $this->assertEmpty($results);
    }

    public function testCleanExpiredSessions(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        $now = new \DateTimeImmutable();
        $futureTime = $now->add(new \DateInterval('PT1H')); // 1小时后
        $pastTime = $now->sub(new \DateInterval('PT1H'));   // 1小时前

        // 创建过期会话
        $expiredSession1 = new UtmSession();
        $expiredSession1->setSessionId('expired_session_1');
        $expiredSession1->setExpiresAt($pastTime);

        $expiredSession2 = new UtmSession();
        $expiredSession2->setSessionId('expired_session_2');
        $expiredSession2->setExpiresAt($pastTime);

        // 创建未过期会话
        $activeSession = new UtmSession();
        $activeSession->setSessionId('active_session');
        $activeSession->setExpiresAt($futureTime);

        // 创建永不过期的会话（expiresAt为null）
        $neverExpiresSession = new UtmSession();
        $neverExpiresSession->setSessionId('never_expires_session');

        self::getEntityManager()->persist($expiredSession1);
        self::getEntityManager()->persist($expiredSession2);
        self::getEntityManager()->persist($activeSession);
        self::getEntityManager()->persist($neverExpiresSession);
        self::getEntityManager()->flush();

        // 执行清理
        $deletedCount = $this->repository->cleanExpiredSessions();

        // 验证删除数量
        $this->assertEquals(2, $deletedCount);

        // 验证剩余会话
        $remainingSessions = $this->repository->findAll();
        $this->assertCount(2, $remainingSessions);

        $remainingSessionIds = array_map(function (UtmSession $session) {
            return $session->getSessionId();
        }, $remainingSessions);

        $this->assertContains('active_session', $remainingSessionIds);
        $this->assertContains('never_expires_session', $remainingSessionIds);
        $this->assertNotContains('expired_session_1', $remainingSessionIds);
        $this->assertNotContains('expired_session_2', $remainingSessionIds);
    }

    public function testCleanExpiredSessionsNoExpiredSessions(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        $futureTime = new \DateTimeImmutable('+1 hour');

        // 创建未过期会话
        $activeSession = new UtmSession();
        $activeSession->setSessionId('active_session');
        $activeSession->setExpiresAt($futureTime);

        // 创建永不过期的会话
        $neverExpiresSession = new UtmSession();
        $neverExpiresSession->setSessionId('never_expires_session');

        self::getEntityManager()->persist($activeSession);
        self::getEntityManager()->persist($neverExpiresSession);
        self::getEntityManager()->flush();

        // 执行清理
        $deletedCount = $this->repository->cleanExpiredSessions();

        // 验证没有删除任何记录
        $this->assertEquals(0, $deletedCount);

        // 验证所有会话都还在
        $remainingSessions = $this->repository->findAll();
        $this->assertCount(2, $remainingSessions);
    }

    public function testFindBySessionIdWithUtmParameters(): void
    {
        // 创建UTM参数
        $utmParameters = new UtmParameter();
        $utmParameters->setSource('google');
        $utmParameters->setMedium('cpc');
        $utmParameters->setCampaign('test_campaign');

        self::getEntityManager()->persist($utmParameters);

        // 创建会话并关联UTM参数
        $session = new UtmSession();
        $session->setSessionId('session_with_utm');
        $session->setParameters($utmParameters);
        $session->setUserIdentifier('user_with_utm');

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // 测试查找
        $result = $this->repository->findBySessionId('session_with_utm');

        $this->assertNotNull($result);
        $this->assertEquals('session_with_utm', $result->getSessionId());
        $this->assertNotNull($result->getParameters());
        $this->assertEquals('google', $result->getParameters()->getSource());
        $this->assertEquals('cpc', $result->getParameters()->getMedium());
        $this->assertEquals('test_campaign', $result->getParameters()->getCampaign());
    }

    public function testFindActiveByUserIdentifierWithMetadata(): void
    {
        // 创建带有元数据的活跃会话
        $session = new UtmSession();
        $session->setSessionId('session_with_metadata');
        $session->setUserIdentifier('user_metadata');
        $session->setMetadata([
            'browser' => 'Chrome',
            'os' => 'Windows',
            'referrer' => 'https://example.com',
        ]);

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // 测试查找
        $results = $this->repository->findActiveByUserIdentifier('user_metadata');

        $this->assertCount(1, $results);
        $result = $results[0];
        $this->assertEquals('session_with_metadata', $result->getSessionId());
        $this->assertEquals([
            'browser' => 'Chrome',
            'os' => 'Windows',
            'referrer' => 'https://example.com',
        ], $result->getMetadata());
    }

    public function testFind(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('test_session_find');
        $session->setUserIdentifier('test_user_find');
        $session->setClientIp('192.168.1.100');
        $session->setUserAgent('Test User Agent');

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();
        $id = $session->getId();

        // Act
        $result = $this->repository->find($id);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame($id, $result->getId());
        $this->assertSame('test_session_find', $result->getSessionId());
        $this->assertSame('test_user_find', $result->getUserIdentifier());
        $this->assertSame('192.168.1.100', $result->getClientIp());
        $this->assertSame('Test User Agent', $result->getUserAgent());
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
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Arrange
        $session1 = new UtmSession();
        $session1->setSessionId('session_1');
        $session1->setUserIdentifier('user_1');
        $session1->setClientIp('192.168.1.1');

        $session2 = new UtmSession();
        $session2->setSessionId('session_2');
        $session2->setUserIdentifier('user_2');
        $session2->setClientIp('192.168.1.2');

        self::getEntityManager()->persist($session1);
        self::getEntityManager()->persist($session2);
        self::getEntityManager()->flush();

        // Act
        $result = $this->repository->findAll();

        // Assert
        $this->assertCount(2, $result);
        // 验证所有结果都是UtmSession实例
        foreach ($result as $session) {
            $this->assertInstanceOf(UtmSession::class, $session);
        }

        $sessionIds = array_map(fn ($s) => $s->getSessionId(), $result);
        $this->assertContains('session_1', $sessionIds);
        $this->assertContains('session_2', $sessionIds);
    }

    public function testFindAllWithEmptyTable(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Act
        $result = $this->repository->findAll();

        // Assert
        // 验证返回结果是数组
        $this->assertEmpty($result);
    }

    public function testFindBy(): void
    {
        // Arrange
        $session1 = new UtmSession();
        $session1->setSessionId('user_session_1');
        $session1->setUserIdentifier('common_user');
        $session1->setClientIp('192.168.1.10');

        $session2 = new UtmSession();
        $session2->setSessionId('user_session_2');
        $session2->setUserIdentifier('common_user');
        $session2->setClientIp('192.168.1.11');

        $session3 = new UtmSession();
        $session3->setSessionId('other_session');
        $session3->setUserIdentifier('other_user');
        $session3->setClientIp('192.168.1.12');

        self::getEntityManager()->persist($session1);
        self::getEntityManager()->persist($session2);
        self::getEntityManager()->persist($session3);
        self::getEntityManager()->flush();

        // Act
        $result = $this->repository->findBy(['userIdentifier' => 'common_user']);

        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $session) {
            $this->assertSame('common_user', $session->getUserIdentifier());
        }

        $sessionIds = array_map(fn ($s) => $s->getSessionId(), $result);
        $this->assertContains('user_session_1', $sessionIds);
        $this->assertContains('user_session_2', $sessionIds);
    }

    public function testFindByWithLimitAndOffset(): void
    {
        // Arrange
        for ($i = 1; $i <= 5; ++$i) {
            $session = new UtmSession();
            $session->setSessionId('session_' . $i);
            $session->setUserIdentifier('batch_user');
            $session->setClientIp('192.168.1.' . $i);
            self::getEntityManager()->persist($session);
        }
        self::getEntityManager()->flush();

        // Act
        $result = $this->repository->findBy(['userIdentifier' => 'batch_user'], null, 2, 1);

        // Assert
        $this->assertCount(2, $result);
        foreach ($result as $session) {
            $this->assertSame('batch_user', $session->getUserIdentifier());
        }
    }

    public function testFindByWithNoResults(): void
    {
        // Act
        $result = $this->repository->findBy(['userIdentifier' => 'non_existent_user']);

        // Assert
        // 验证返回结果是数组
        $this->assertEmpty($result);
    }

    public function testFindOneBy(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('unique_session');
        $session->setUserIdentifier('unique_user');
        $session->setClientIp('10.0.0.1');
        $session->setUserAgent('Unique Agent');

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // Act
        $result = $this->repository->findOneBy(['sessionId' => 'unique_session']);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('unique_session', $result->getSessionId());
        $this->assertSame('unique_user', $result->getUserIdentifier());
        $this->assertSame('10.0.0.1', $result->getClientIp());
        $this->assertSame('Unique Agent', $result->getUserAgent());
    }

    public function testFindOneByWithNoResult(): void
    {
        // Act
        $result = $this->repository->findOneBy(['sessionId' => 'non_existent_session']);

        // Assert
        $this->assertNull($result);
    }

    public function testFindOneByWithOrderBy(): void
    {
        // Arrange
        $session1 = new UtmSession();
        $session1->setSessionId('session_1');
        $session1->setUserIdentifier('order_test_user');
        $session1->setClientIp('192.168.1.1');

        $session2 = new UtmSession();
        $session2->setSessionId('session_2');
        $session2->setUserIdentifier('order_test_user');
        $session2->setClientIp('192.168.1.2');

        self::getEntityManager()->persist($session1);
        self::getEntityManager()->persist($session2);
        self::getEntityManager()->flush();

        // Act - 获取sessionId最大的记录
        $result = $this->repository->findOneBy(['userIdentifier' => 'order_test_user'], ['sessionId' => 'DESC']);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('session_2', $result->getSessionId());
        $this->assertSame('192.168.1.2', $result->getClientIp());
    }

    public function testCount(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Arrange
        for ($i = 1; $i <= 3; ++$i) {
            $session = new UtmSession();
            $session->setSessionId('count_session_' . $i);
            $session->setUserIdentifier('count_user');
            $session->setClientIp('192.168.1.' . $i);
            self::getEntityManager()->persist($session);
        }
        self::getEntityManager()->flush();

        // Act
        $count = $this->repository->count(['userIdentifier' => 'count_user']);

        // Assert
        $this->assertSame(3, $count);
    }

    public function testCountWithEmptyResult(): void
    {
        // Act
        $count = $this->repository->count(['userIdentifier' => 'non_existent_user']);

        // Assert
        $this->assertSame(0, $count);
    }

    public function testCountAll(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        // Arrange
        for ($i = 1; $i <= 4; ++$i) {
            $session = new UtmSession();
            $session->setSessionId('total_session_' . $i);
            $session->setUserIdentifier('user_' . $i);
            $session->setClientIp('10.0.0.' . $i);
            self::getEntityManager()->persist($session);
        }
        self::getEntityManager()->flush();

        // Act
        $count = $this->repository->count([]);

        // Assert
        $this->assertSame(4, $count);
    }

    public function testSave(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('save_test_session');
        $session->setUserIdentifier('save_test_user');
        $session->setClientIp('172.16.0.1');
        $session->setUserAgent('Save Test Agent');

        // Act
        $this->repository->save($session);

        // Assert
        $this->assertNotNull($session->getId());

        // 验证保存到数据库
        $saved = $this->repository->find($session->getId());
        $this->assertNotNull($saved);
        $this->assertSame('save_test_session', $saved->getSessionId());
        $this->assertSame('save_test_user', $saved->getUserIdentifier());
        $this->assertSame('172.16.0.1', $saved->getClientIp());
        $this->assertSame('Save Test Agent', $saved->getUserAgent());
    }

    public function testSaveWithoutFlush(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('save_no_flush_session');
        $session->setUserIdentifier('save_no_flush_user');
        $session->setClientIp('172.16.0.2');

        // Act
        $this->repository->save($session, false);

        // Assert - 此时还没有ID因为没有flush
        $this->assertNull($session->getId());

        // 手动flush
        self::getEntityManager()->flush();

        // 验证现在有了ID
        $this->assertNotNull($session->getId());

        // 验证保存到数据库
        $saved = $this->repository->find($session->getId());
        $this->assertNotNull($saved);
        $this->assertSame('save_no_flush_session', $saved->getSessionId());
    }

    public function testRemove(): void
    {
        // Arrange
        $session = new UtmSession();
        $session->setSessionId('remove_test_session');
        $session->setUserIdentifier('remove_test_user');
        $session->setClientIp('172.16.0.3');

        $this->repository->save($session);
        $id = $session->getId();

        // 验证存在
        $this->assertNotNull($this->repository->find($id));

        // Act
        $this->repository->remove($session);

        // Assert
        $this->assertNull($this->repository->find($id));
    }

    public function testFindBySessionIdWithComplexAssociations(): void
    {
        // Arrange
        $utmParameters = new UtmParameter();
        $utmParameters->setSource('complex_source');
        $utmParameters->setMedium('complex_medium');
        $utmParameters->setCampaign('complex_campaign');
        $utmParameters->setTerm('complex_term');
        $utmParameters->setContent('complex_content');
        self::getEntityManager()->persist($utmParameters);

        $session = new UtmSession();
        $session->setSessionId('complex_session');
        $session->setUserIdentifier('complex_user');
        $session->setClientIp('203.0.113.1');
        $session->setUserAgent('Complex Test Agent');
        $session->setParameters($utmParameters);
        $session->setMetadata([
            'device_type' => 'desktop',
            'screen_resolution' => '1920x1080',
            'language' => 'zh-CN',
        ]);
        $session->setExpiresAt(new \DateTimeImmutable('+2 hours'));

        self::getEntityManager()->persist($session);
        self::getEntityManager()->flush();

        // Act
        $result = $this->repository->findBySessionId('complex_session');

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('complex_session', $result->getSessionId());
        $this->assertSame('complex_user', $result->getUserIdentifier());
        $this->assertSame('203.0.113.1', $result->getClientIp());
        $this->assertSame('Complex Test Agent', $result->getUserAgent());

        // 验证UTM参数关联
        $this->assertNotNull($result->getParameters());
        $this->assertSame('complex_source', $result->getParameters()->getSource());
        $this->assertSame('complex_medium', $result->getParameters()->getMedium());
        $this->assertSame('complex_campaign', $result->getParameters()->getCampaign());
        $this->assertSame('complex_term', $result->getParameters()->getTerm());
        $this->assertSame('complex_content', $result->getParameters()->getContent());

        // 验证元数据
        $metadata = $result->getMetadata();
        $this->assertSame('desktop', $metadata['device_type']);
        $this->assertSame('1920x1080', $metadata['screen_resolution']);
        $this->assertSame('zh-CN', $metadata['language']);

        // 验证过期时间
        $this->assertNotNull($result->getExpiresAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getExpiresAt());
    }

    public function testCleanExpiredSessionsWithMixedExpirationStates(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();

        $now = new \DateTimeImmutable();
        $pastTime1 = $now->sub(new \DateInterval('PT2H')); // 2小时前
        $pastTime2 = $now->sub(new \DateInterval('PT1H')); // 1小时前
        $futureTime = $now->add(new \DateInterval('PT1H')); // 1小时后

        // 创建多种过期状态的会话
        $expiredSession1 = new UtmSession();
        $expiredSession1->setSessionId('expired_session_1');
        $expiredSession1->setExpiresAt($pastTime1);

        $expiredSession2 = new UtmSession();
        $expiredSession2->setSessionId('expired_session_2');
        $expiredSession2->setExpiresAt($pastTime2);

        $activeSessionWithExpiry = new UtmSession();
        $activeSessionWithExpiry->setSessionId('active_session_with_expiry');
        $activeSessionWithExpiry->setExpiresAt($futureTime);

        $neverExpiresSession1 = new UtmSession();
        $neverExpiresSession1->setSessionId('never_expires_session_1');

        $neverExpiresSession2 = new UtmSession();
        $neverExpiresSession2->setSessionId('never_expires_session_2');

        self::getEntityManager()->persist($expiredSession1);
        self::getEntityManager()->persist($expiredSession2);
        self::getEntityManager()->persist($activeSessionWithExpiry);
        self::getEntityManager()->persist($neverExpiresSession1);
        self::getEntityManager()->persist($neverExpiresSession2);
        self::getEntityManager()->flush();

        // Act
        $deletedCount = $this->repository->cleanExpiredSessions();

        // Assert
        $this->assertSame(2, $deletedCount);

        // 验证剩余会话
        $remainingSessions = $this->repository->findAll();
        $this->assertCount(3, $remainingSessions);

        $remainingSessionIds = array_map(function (UtmSession $session) {
            return $session->getSessionId();
        }, $remainingSessions);

        $this->assertContains('active_session_with_expiry', $remainingSessionIds);
        $this->assertContains('never_expires_session_1', $remainingSessionIds);
        $this->assertContains('never_expires_session_2', $remainingSessionIds);
        $this->assertNotContains('expired_session_1', $remainingSessionIds);
        $this->assertNotContains('expired_session_2', $remainingSessionIds);
    }

    public function testFindActiveByUserIdentifierWithComplexExpirationLogic(): void
    {
        $now = new \DateTimeImmutable();
        $pastTime = $now->sub(new \DateInterval('PT30M')); // 30分钟前
        $futureTime1 = $now->add(new \DateInterval('PT30M')); // 30分钟后
        $futureTime2 = $now->add(new \DateInterval('PT1H')); // 1小时后

        // 创建各种会话状态
        $activeSessionNoExpiry = new UtmSession();
        $activeSessionNoExpiry->setSessionId('active_no_expiry');
        $activeSessionNoExpiry->setUserIdentifier('complex_user');

        $activeSessionWithExpiry1 = new UtmSession();
        $activeSessionWithExpiry1->setSessionId('active_with_expiry_1');
        $activeSessionWithExpiry1->setUserIdentifier('complex_user');
        $activeSessionWithExpiry1->setExpiresAt($futureTime1);

        $activeSessionWithExpiry2 = new UtmSession();
        $activeSessionWithExpiry2->setSessionId('active_with_expiry_2');
        $activeSessionWithExpiry2->setUserIdentifier('complex_user');
        $activeSessionWithExpiry2->setExpiresAt($futureTime2);

        $expiredSession = new UtmSession();
        $expiredSession->setSessionId('expired_session');
        $expiredSession->setUserIdentifier('complex_user');
        $expiredSession->setExpiresAt($pastTime);

        $otherUserActiveSession = new UtmSession();
        $otherUserActiveSession->setSessionId('other_user_active');
        $otherUserActiveSession->setUserIdentifier('other_user');
        $otherUserActiveSession->setExpiresAt($futureTime1);

        self::getEntityManager()->persist($activeSessionNoExpiry);
        self::getEntityManager()->persist($activeSessionWithExpiry1);
        self::getEntityManager()->persist($activeSessionWithExpiry2);
        self::getEntityManager()->persist($expiredSession);
        self::getEntityManager()->persist($otherUserActiveSession);
        self::getEntityManager()->flush();

        // Act
        $results = $this->repository->findActiveByUserIdentifier('complex_user');

        // Assert
        $this->assertCount(3, $results);

        $sessionIds = array_map(function (UtmSession $session) {
            return $session->getSessionId();
        }, $results);

        $this->assertContains('active_no_expiry', $sessionIds);
        $this->assertContains('active_with_expiry_1', $sessionIds);
        $this->assertContains('active_with_expiry_2', $sessionIds);
        $this->assertNotContains('expired_session', $sessionIds);
        $this->assertNotContains('other_user_active', $sessionIds);

        // 验证所有返回的会话都属于正确的用户
        foreach ($results as $result) {
            $this->assertSame('complex_user', $result->getUserIdentifier());
        }
    }

    public function testDatabaseUnavailableException(): void
    {
        // 这个测试模拟数据库不可用的情况，但由于我们使用的是集成测试
        // 很难真实模拟数据库连接失败，所以我们测试一个会抛出异常的查询

        $this->expectException(\Exception::class);

        // 使用错误的查询语法来触发异常
        self::getEntityManager()->createQuery('INVALID SQL QUERY')->execute();
    }

    public function testCountByAssociationParametersShouldReturnCorrectNumber(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange - 创建UTM参数
        $utmParameters1 = new UtmParameter();
        $utmParameters1->setSource('google');
        $utmParameters1->setMedium('cpc');
        $utmParameters1->setCampaign('test_campaign_1');
        self::getEntityManager()->persist($utmParameters1);

        $utmParameters2 = new UtmParameter();
        $utmParameters2->setSource('facebook');
        $utmParameters2->setMedium('social');
        $utmParameters2->setCampaign('test_campaign_2');
        self::getEntityManager()->persist($utmParameters2);

        // 创建带关联参数的会话
        $sessionWithParams1 = new UtmSession();
        $sessionWithParams1->setSessionId('session_with_params_1');
        $sessionWithParams1->setUserIdentifier('user_with_params');
        $sessionWithParams1->setParameters($utmParameters1);

        $sessionWithParams2 = new UtmSession();
        $sessionWithParams2->setSessionId('session_with_params_2');
        $sessionWithParams2->setUserIdentifier('user_with_params');
        $sessionWithParams2->setParameters($utmParameters1);

        $sessionWithDifferentParams = new UtmSession();
        $sessionWithDifferentParams->setSessionId('session_with_different_params');
        $sessionWithDifferentParams->setUserIdentifier('user_with_params');
        $sessionWithDifferentParams->setParameters($utmParameters2);

        $sessionWithoutParams = new UtmSession();
        $sessionWithoutParams->setSessionId('session_without_params');
        $sessionWithoutParams->setUserIdentifier('user_without_params');

        self::getEntityManager()->persist($sessionWithParams1);
        self::getEntityManager()->persist($sessionWithParams2);
        self::getEntityManager()->persist($sessionWithDifferentParams);
        self::getEntityManager()->persist($sessionWithoutParams);
        self::getEntityManager()->flush();

        // Act & Assert - 计算带特定参数的会话数量
        $countWithParams1 = $this->repository->count(['parameters' => $utmParameters1]);
        $this->assertSame(2, $countWithParams1);

        $countWithParams2 = $this->repository->count(['parameters' => $utmParameters2]);
        $this->assertSame(1, $countWithParams2);

        // Act & Assert - 计算所有带参数的会话（非null）
        $qb = $this->repository->createQueryBuilder('s');
        $qb->select('COUNT(s.id)')
            ->where('s.parameters IS NOT NULL')
        ;
        $countWithAnyParams = $qb->getQuery()->getSingleScalarResult();
        $this->assertSame(3, $countWithAnyParams);
    }

    public function testFindOneByAssociationParametersShouldReturnMatchingEntity(): void
    {
        // 清理现有数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmSession')->execute();
        self::getEntityManager()->createQuery('DELETE FROM Tourze\UtmBundle\Entity\UtmParameter')->execute();

        // Arrange - 创建UTM参数
        $utmParameters = new UtmParameter();
        $utmParameters->setSource('youtube');
        $utmParameters->setMedium('video');
        $utmParameters->setCampaign('findone_association_test');
        self::getEntityManager()->persist($utmParameters);

        // 创建关联的会话
        $sessionWithParams = new UtmSession();
        $sessionWithParams->setSessionId('findone_association_session');
        $sessionWithParams->setUserIdentifier('findone_association_user');
        $sessionWithParams->setParameters($utmParameters);

        $sessionWithoutParams = new UtmSession();
        $sessionWithoutParams->setSessionId('findone_no_association_session');
        $sessionWithoutParams->setUserIdentifier('findone_no_association_user');

        self::getEntityManager()->persist($sessionWithParams);
        self::getEntityManager()->persist($sessionWithoutParams);
        self::getEntityManager()->flush();

        // Act - 查找带特定参数的会话
        $result = $this->repository->findOneBy(['parameters' => $utmParameters]);

        // Assert
        $this->assertNotNull($result);
        $this->assertSame('findone_association_session', $result->getSessionId());
        $this->assertNotNull($result->getParameters());
        $this->assertSame($utmParameters->getId(), $result->getParameters()->getId());
        $this->assertSame('youtube', $result->getParameters()->getSource());

        // Act - 查找不存在的参数关联
        $nonExistentParams = new UtmParameter();
        $nonExistentParams->setSource('non_existent');
        self::getEntityManager()->persist($nonExistentParams);
        self::getEntityManager()->flush();

        $noResult = $this->repository->findOneBy(['parameters' => $nonExistentParams]);
        $this->assertNull($noResult);
    }

    protected function createNewEntity(): object
    {
        $entity = new UtmSession();
        $entity->setSessionId('test_session_' . uniqid());
        $entity->setUserIdentifier('test_user_' . uniqid());
        $entity->setClientIp('192.168.1.' . rand(1, 254));
        $entity->setUserAgent('Test User Agent ' . uniqid());

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<UtmSession>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
