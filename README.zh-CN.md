# Symfony UTM Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![License](https://img.shields.io/packagist/l/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/symfony-utm-bundle.svg?style=flat-square)](https://codecov.io/gh/tourze/symfony-utm-bundle)

一个用于跟踪 UTM（Urchin 跟踪模块）参数和转化事件的综合性 Symfony Bundle。该 Bundle 为 Web 应用程序提供自动 UTM 参数检测、会话管理和转化跟踪功能。

## 目录

- [功能特性](#功能特性)
- [依赖要求](#依赖要求)
- [安装](#安装)
- [快速开始](#快速开始)
- [配置](#配置)
- [使用方法](#使用方法)
- [高级用法](#高级用法)
- [EasyAdmin 集成](#easyadmin-集成)
- [事件](#事件)
- [存储策略](#存储策略)
- [API 参考](#api-参考)
- [测试](#测试)
- [贡献指南](#贡献指南)
- [许可证](#许可证)

## 功能特性

- **自动 UTM 参数检测**：自动从传入请求中提取 UTM 参数
- **会话管理**：管理具有可配置生命周期和过期时间的 UTM 会话
- **转化跟踪**：跟踪带有 UTM 归因的转化事件
- **多种存储策略**：支持数据库和基于会话的存储
- **EasyAdmin 集成**：预构建的管理控制器用于管理 UTM 数据
- **灵活配置**：支持自定义 UTM 参数和验证规则
- **事件驱动架构**：为 UTM 转化和会话分发事件
- **参数验证**：内置验证，具有 XSS 保护和长度限制
- **用户归因**：自动用户识别和匿名会话跟踪

## 依赖要求

- PHP 8.1 或更高版本
- Symfony 6.4 或更高版本
- Doctrine ORM 3.0 或更高版本
- EasyAdmin Bundle 4.0 或更高版本

## 安装

通过 Composer 安装 Bundle：

```bash
composer require tourze/symfony-utm-bundle
```

## 快速开始

### 1. 启用 Bundle

将 Bundle 添加到您的 `config/bundles.php` 文件：

```php
return [
    // ...
    Tourze\UtmBundle\UtmBundle::class => ['all' => true],
];
```

### 2. 配置数据库

运行以下命令设置数据库表：

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 3. 基本使用

Bundle 会自动跟踪来自 URL 的 UTM 参数，例如：

```text
https://example.com/landing-page?utm_source=google&utm_medium=cpc&utm_campaign=summer_sale
```

在控制器中跟踪转化：

```php
use Tourze\UtmBundle\Service\UtmConversionTracker;

class OrderController extends AbstractController
{
    public function __construct(
        private UtmConversionTracker $conversionTracker
    ) {}

    public function complete(): Response
    {
        // 跟踪订单完成转化
        $this->conversionTracker->track('order_complete', 99.99, [
            'order_id' => 12345,
            'items_count' => 3
        ]);

        return $this->render('order/complete.html.twig');
    }
}
```

## 配置

在 `config/packages/utm.yaml` 创建配置文件：

```yaml
utm:
  # 存储策略：'database' 或 'session'
  storage_strategy: 'database'
  
  # 会话配置
  session:
    lifetime: 3600  # 会话生命周期（秒）
    
  # 参数验证
  validation:
    max_length: 255     # 最大参数长度
    sanitize: true      # 启用 XSS 保护
    
  # 要跟踪的附加 UTM 参数
  additional_parameters:
    - utm_id
    - utm_custom
```

## 使用方法

### 手动 UTM 参数提取

```php
use Tourze\UtmBundle\Service\UtmParametersExtractor;
use Symfony\Component\HttpFoundation\Request;

class MyController extends AbstractController
{
    public function __construct(
        private UtmParametersExtractor $extractor
    ) {}

    public function landing(Request $request): Response
    {
        $utmParams = $this->extractor->extract($request);
        
        if ($utmParams->hasAnyParameter()) {
            $source = $utmParams->getSource();
            $medium = $utmParams->getMedium();
            $campaign = $utmParams->getCampaign();
            
            // 使用 UTM 数据进行分析
        }

        return $this->render('landing.html.twig');
    }
}
```

### 会话管理

```php
use Tourze\UtmBundle\Service\UtmSessionManager;

class AnalyticsController extends AbstractController
{
    public function __construct(
        private UtmSessionManager $sessionManager
    ) {}

    public function dashboard(): Response
    {
        $currentSession = $this->sessionManager->getCurrentSession();
        
        if ($currentSession) {
            $parameters = $currentSession->getParameters();
            $userIdentifier = $currentSession->getUserIdentifier();
            $metadata = $currentSession->getMetadata();
        }

        return $this->render('analytics/dashboard.html.twig');
    }
}
```

## 高级用法

### 自定义存储策略

实现您自己的存储策略：

```php
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Dto\UtmParametersDto;

class RedisStorageStrategy implements UtmStorageStrategyInterface
{
    public function store(UtmParametersDto $parameters, string $sessionId): UtmSession
    {
        // 自定义 Redis 实现
    }

    public function retrieve(string $sessionId): ?UtmSession
    {
        // 自定义 Redis 检索
    }
}
```

### 事件监听器

监听 UTM 事件：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\UtmBundle\Event\UtmConversionEvent;

class UtmAnalyticsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            UtmConversionEvent::NAME => 'onConversion',
        ];
    }

    public function onConversion(UtmConversionEvent $event): void
    {
        $conversion = $event->getConversion();
        $eventName = $conversion->getEventName();
        $value = $conversion->getValue();
        
        // 将数据发送到分析平台
    }
}
```

## EasyAdmin 集成

Bundle 提供用于管理 UTM 数据的预构建 EasyAdmin 控制器。添加到您的仪表板：

```php
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Tourze\UtmBundle\Controller\Admin\UtmParametersCrudController;
use Tourze\UtmBundle\Controller\Admin\UtmSessionCrudController;
use Tourze\UtmBundle\Controller\Admin\UtmConversionCrudController;

class DashboardController extends AbstractDashboardController
{
    public function configureMenuItems(): iterable
    {
        yield MenuItem::section('UTM 分析');
        yield MenuItem::linkToCrud('UTM 参数', 'fas fa-link', UtmParameters::class)
            ->setController(UtmParametersCrudController::class);
        yield MenuItem::linkToCrud('UTM 会话', 'fas fa-users', UtmSession::class)
            ->setController(UtmSessionCrudController::class);
        yield MenuItem::linkToCrud('UTM 转化', 'fas fa-chart-line', UtmConversion::class)
            ->setController(UtmConversionCrudController::class);
    }
}
```

## 事件

Bundle 分发以下事件：

- `UtmConversionEvent::NAME`：当跟踪到转化时分发

## 存储策略

### 数据库策略（默认）

使用 Doctrine ORM 将 UTM 数据存储在数据库表中。

### 会话策略

将 UTM 数据存储在 PHP 会话中，适用于无需持久存储的简单设置。

## API 参考

### UtmConversionTracker

- `track(string $eventName, float $value = 0.0, array $metadata = []): UtmConversion`

### UtmParametersExtractor

- `extract(Request $request): UtmParametersDto`

### UtmSessionManager

- `getCurrentSession(): ?UtmSession`
- `createSession(UtmParametersDto $parameters): UtmSession`

### UtmContextManager

- `getCurrentParameters(): ?UtmParameters`
- `getCurrentSession(): ?UtmSession`

## 测试

运行测试套件：

```bash
vendor/bin/phpunit packages/symfony-utm-bundle/tests
```

运行静态分析：

```bash
vendor/bin/phpstan analyse packages/symfony-utm-bundle
```

## 贡献指南

1. Fork 仓库
2. 创建功能分支
3. 进行更改
4. 为新功能添加测试
5. 确保所有测试通过
6. 提交 Pull Request

请确保您的代码遵循 PSR-12 编码标准并包含适当的测试。

## 许可证

此 Bundle 基于 MIT 许可证发布。查看 [LICENSE](LICENSE) 文件了解详情。