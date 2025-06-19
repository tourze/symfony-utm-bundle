<?php

namespace Tourze\UtmBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Entity\UtmParameters;
use Tourze\UtmBundle\EventListener\UtmRequestListener;
use Tourze\UtmBundle\Repository\UtmParametersRepository;
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Service\UtmContextManager;
use Tourze\UtmBundle\Service\UtmParametersExtractor;
use Tourze\UtmBundle\Service\UtmParametersValidator;

class UtmRequestListenerTest extends TestCase
{
    private UtmParametersExtractor $parametersExtractor;
    private UtmParametersValidator $parametersValidator;
    private UtmStorageStrategyInterface $storageStrategy;
    private UtmContextManager $contextManager;
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;
    private HttpKernelInterface $kernel;
    private UtmParametersRepository $repository;
    
    protected function setUp(): void
    {
        $this->parametersExtractor = $this->createMock(UtmParametersExtractor::class);
        $this->parametersValidator = $this->createMock(UtmParametersValidator::class);
        $this->storageStrategy = $this->createMock(UtmStorageStrategyInterface::class);
        $this->contextManager = $this->createMock(UtmContextManager::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->repository = $this->createMock(UtmParametersRepository::class);
    }
    
    public function testOnKernelRequest_withNoUtmParameters_doesNothing(): void
    {
        // Arrange
        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(false);
        
        $this->parametersExtractor->expects($this->never())
            ->method('extract');
        
        $listener = new UtmRequestListener(
            $this->parametersExtractor,
            $this->parametersValidator,
            $this->storageStrategy,
            $this->contextManager,
            $this->entityManager,
            $this->logger
        );
        
        // Act
        $listener->onKernelRequest($event);
        
        // Assert - No additional assertions needed, just verifying the mock expectations
    }
    
    public function testOnKernelRequest_withSubRequest_doesNothing(): void
    {
        // Arrange
        $request = new Request();
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::SUB_REQUEST);
        
        $this->parametersExtractor->expects($this->never())
            ->method('hasUtmParameters');
        
        $listener = new UtmRequestListener(
            $this->parametersExtractor,
            $this->parametersValidator,
            $this->storageStrategy,
            $this->contextManager,
            $this->entityManager,
            $this->logger
        );
        
        // Act
        $listener->onKernelRequest($event);
        
        // Assert - No additional assertions needed, just verifying the mock expectations
    }
    
    public function testOnKernelRequest_withUtmParametersButNoneValidated_doesNothing(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');
        
        $validatedDto = new UtmParametersDto(); // 空的，没有有效参数
        
        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true);
        
        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto);
        
        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto);
        
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('无有效的UTM参数，跳过处理');
        
        $this->entityManager->expects($this->never())
            ->method('getRepository');
        
        $listener = new UtmRequestListener(
            $this->parametersExtractor,
            $this->parametersValidator,
            $this->storageStrategy,
            $this->contextManager,
            $this->entityManager,
            $this->logger
        );
        
        // Act
        $listener->onKernelRequest($event);
        
        // Assert - No additional assertions needed, just verifying the mock expectations
    }
    
    public function testOnKernelRequest_withValidUtmParameters_processesParameters(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');
        
        $validatedDto = new UtmParametersDto();
        $validatedDto->setSource('google');
        
        $parameters = new UtmParameters();
        $parameters->setSource('google');
        
        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true);
        
        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto);
        
        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
        
        $this->repository->expects($this->once())
            ->method('findOrCreateByParams')
            ->with($validatedDto)
            ->willReturn($parameters);
        
        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($parameters);
        
        $this->entityManager->expects($this->once())
            ->method('flush');
        
        $this->storageStrategy->expects($this->once())
            ->method('store')
            ->with($parameters);
        
        $this->contextManager->expects($this->once())
            ->method('reset');
        
        $this->logger->expects($this->once())
            ->method('info')
            ->with('处理了UTM参数', $this->anything());
        
        $listener = new UtmRequestListener(
            $this->parametersExtractor,
            $this->parametersValidator,
            $this->storageStrategy,
            $this->contextManager,
            $this->entityManager,
            $this->logger
        );
        
        // Act
        $listener->onKernelRequest($event);
        
        // Assert - No additional assertions needed, just verifying the mock expectations
    }
    
    public function testOnKernelRequest_withExistingParameters_usesExistingParameters(): void
    {
        // Arrange
        $request = new Request(['utm_source' => 'google']);
        $event = new RequestEvent($this->kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        
        $rawDto = new UtmParametersDto();
        $rawDto->setSource('google');
        
        $validatedDto = new UtmParametersDto();
        $validatedDto->setSource('google');
        
        $parameters = new UtmParameters();
        $parameters->setSource('google');
        
        // 模拟已存在的参数，通过设置ID
        $parametersReflection = new \ReflectionClass(UtmParameters::class);
        $idProperty = $parametersReflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($parameters, 123);
        
        $this->parametersExtractor->expects($this->once())
            ->method('hasUtmParameters')
            ->with($request)
            ->willReturn(true);
        
        $this->parametersExtractor->expects($this->once())
            ->method('extract')
            ->with($request)
            ->willReturn($rawDto);
        
        $this->parametersValidator->expects($this->once())
            ->method('validate')
            ->with($rawDto)
            ->willReturn($validatedDto);
        
        $this->entityManager->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repository);
        
        $this->repository->expects($this->once())
            ->method('findOrCreateByParams')
            ->with($validatedDto)
            ->willReturn($parameters);
        
        // 对于现有参数，不应该调用persist和flush
        $this->entityManager->expects($this->never())
            ->method('persist');
        
        $this->entityManager->expects($this->never())
            ->method('flush');
        
        $this->storageStrategy->expects($this->once())
            ->method('store')
            ->with($parameters);
        
        $this->contextManager->expects($this->once())
            ->method('reset');
        
        $listener = new UtmRequestListener(
            $this->parametersExtractor,
            $this->parametersValidator,
            $this->storageStrategy,
            $this->contextManager,
            $this->entityManager,
            $this->logger
        );
        
        // Act
        $listener->onKernelRequest($event);
        
        // Assert - No additional assertions needed, just verifying the mock expectations
    }
    
    public function testGetSubscribedEvents_returnsCorrectEvents(): void
    {
        // Act
        $events = UtmRequestListener::getSubscribedEvents();
        
        // Assert
        $this->assertArrayHasKey('kernel.request', $events);
        $this->assertSame('onKernelRequest', $events['kernel.request'][0]);
        $this->assertSame(100, $events['kernel.request'][1]);
    }
} 