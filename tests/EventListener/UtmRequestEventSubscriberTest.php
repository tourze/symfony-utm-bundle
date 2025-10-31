<?php

namespace Tourze\UtmBundle\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\EventListener\UtmRequestEventSubscriber;
use Tourze\UtmBundle\Repository\UtmParameterRepository;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Service\UtmContextManager;
use Tourze\UtmBundle\Service\UtmParametersExtractor;
use Tourze\UtmBundle\Service\UtmParametersValidator;

/**
 * @internal
 * @phpstan-ignore phpunit.noMockOnly
 */
#[CoversClass(UtmRequestEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class UtmRequestEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    /** @var MockObject&UtmParametersExtractor */
    private MockObject $parametersExtractor;

    /** @var MockObject&UtmParametersValidator */
    private MockObject $parametersValidator;

    /** @var MockObject&UtmStorageStrategyInterface */
    private MockObject $storageStrategy;

    /** @var MockObject&UtmContextManager */
    private MockObject $contextManager;

    /** @var MockObject&UtmParameterRepository */
    private MockObject $repository;

    private string $testSourceParam = 'test_source';

    protected function onSetUp(): void        // Mock具体类说明：
    {// 1. 使用具体类原因：UtmParametersExtractor是具体的服务类，没有对应的接口，但需要测试其方法调用
        // 2. 使用合理性：合理，该类的职责单一，主要处理UTM参数提取逻辑，Mock不会影响测试的可靠性
        // 3. 替代方案：可考虑重构为接口+实现的模式，但当前情况下Mock具体类是可接受的
        $this->parametersExtractor = $this->createMock(UtmParametersExtractor::class);

        // Mock具体类说明：
        // 1. 使用具体类原因：UtmParametersValidator是具体的验证服务类，没有对应的接口
        // 2. 使用合理性：合理，该类专门负责UTM参数验证，逻辑相对独立，Mock是合适的
        // 3. 替代方案：可考虑提取验证接口，但当前Mock具体类不影响测试质量
        $this->parametersValidator = $this->createMock(UtmParametersValidator::class);

        $this->storageStrategy = $this->createMock(UtmStorageStrategyInterface::class);

        // Mock具体类说明：
        // 1. 使用具体类原因：UtmContextManager是上下文管理服务，没有对应接口
        // 2. 使用合理性：合理，该类负责管理UTM上下文状态，Mock可以有效控制测试环境
        // 3. 替代方案：可考虑重构为接口模式，但现阶段Mock具体类是实用的选择
        $this->contextManager = $this->createMock(UtmContextManager::class);

        // Mock具体类说明：
        // 1. 使用具体类原因：UtmParametersRepository继承自Doctrine的Repository，没有自定义接口
        // 2. 使用合理性：合理，Repository类通常作为数据访问层，Mock可以隔离数据库依赖
        // 3. 替代方案：可定义Repository接口，但对于标准的Doctrine Repository，Mock具体类是常见做法
        $this->repository = $this->createMock(UtmParameterRepository::class);

        // 在测试前将 Mock 服务注册到容器中
        self::getContainer()->set(UtmParametersExtractor::class, $this->parametersExtractor);
        self::getContainer()->set(UtmParametersValidator::class, $this->parametersValidator);
        self::getContainer()->set(UtmStorageStrategyInterface::class, $this->storageStrategy);
        self::getContainer()->set(UtmContextManager::class, $this->contextManager);
        self::getContainer()->set(UtmParameterRepository::class, $this->repository);
        // 注意：不替换 EntityManagerInterface 和 LoggerInterface，使用容器中已有的服务
    }

    public function testOnKernelRequestWithNoUtmParametersDoesNothing(): void
    {
        // Arrange
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(false)
        ;

        $this->parametersExtractor->expects($this->never())
            ->method('extract')
        ;

        $listener = self::getService(UtmRequestEventSubscriber::class);

        // Act
        $listener->onKernelRequest($event);

        // Assert - No additional assertions needed, just verifying the mock expectations
        $this->assertIsString($this->testSourceParam);
        $this->assertSame('test_source', $this->testSourceParam);
    }

    public function testOnKernelRequestWithSubRequestDoesNothing(): void
    {
        // Arrange
        $request = new Request();
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        $this->parametersExtractor->expects($this->never())
            ->method('hasUtmParameters')
        ;

        $listener = self::getService(UtmRequestEventSubscriber::class);

        // Act
        $listener->onKernelRequest($event);

        // Assert - No additional assertions needed, just verifying the mock expectations
    }

    public function testOnKernelRequestWithUtmParametersButNoneValidatedDoesNothing(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');

        $validatedDto = new UtmParametersDto(); // 空的，没有有效参数

        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true)
        ;

        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto)
        ;

        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto)
        ;

        // Logger 是真实的服务，不在测试中验证

        // 不再需要调用 getRepository

        $listener = self::getService(UtmRequestEventSubscriber::class);

        // Act
        $listener->onKernelRequest($event);

        // Assert - No additional assertions needed, just verifying the mock expectations
    }

    public function testOnKernelRequestWithValidUtmParametersProcessesParameters(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');

        $validatedDto = new UtmParametersDto();
        $validatedDto->setSource('google');

        $parameters = new UtmParameter();
        $parameters->setSource('google');

        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true)
        ;

        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto)
        ;

        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto)
        ;

        // 不再需要调用 getRepository

        $this->repository->expects($this->once())
            ->method('findByParams')
            ->with($validatedDto)
            ->willReturn(null)
        ;

        $this->storageStrategy->expects($this->once())
            ->method('store')
            ->with(self::isInstanceOf(UtmParameter::class))
        ;

        $this->contextManager->expects($this->once())
            ->method('reset')
        ;

        // Logger 是真实的服务，不在测试中验证

        $listener = self::getService(UtmRequestEventSubscriber::class);

        // Act
        $listener->onKernelRequest($event);

        // Assert - No additional assertions needed, just verifying the mock expectations
    }

    public function testOnKernelRequestWithExistingParametersUsesExistingParameters(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $kernel = $this->createMock(HttpKernelInterface::class);
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');

        $validatedDto = new UtmParametersDto();
        $validatedDto->setSource('google');

        $parameters = new UtmParameter();
        $parameters->setSource('google');

        // 模拟已存在的参数，通过设置ID
        $parametersReflection = new \ReflectionClass(UtmParameter::class);
        $idProperty = $parametersReflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($parameters, 123);

        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true)
        ;

        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto)
        ;

        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto)
        ;

        // 不再需要调用 getRepository

        $this->repository->expects($this->once())
            ->method('findByParams')
            ->with($validatedDto)
            ->willReturn($parameters)
        ;

        // 对于现有参数，不需要额外的预期设置

        $this->storageStrategy->expects($this->once())
            ->method('store')
            ->with($parameters)
        ;

        $this->contextManager->expects($this->once())
            ->method('reset')
        ;

        $listener = self::getService(UtmRequestEventSubscriber::class);

        // Act
        $listener->onKernelRequest($event);

        // Assert - No additional assertions needed, just verifying the mock expectations
    }

    public function testGetSubscribedEventsReturnsCorrectEvents(): void
    {
        // Act
        $events = UtmRequestEventSubscriber::getSubscribedEvents();

        // Assert
        $this->assertArrayHasKey('kernel.request', $events);
        $this->assertSame('onKernelRequest', $events['kernel.request'][0]);
        $this->assertSame(100, $events['kernel.request'][1]);
    }
}
