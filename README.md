# Symfony UTM Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/packagist/php-v/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![License](https://img.shields.io/packagist/l/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Latest Version](https://img.shields.io/packagist/v/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/symfony-utm-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/symfony-utm-bundle)  
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/symfony-utm-bundle.svg?style=flat-square)](https://codecov.io/gh/tourze/symfony-utm-bundle)

A comprehensive Symfony bundle for tracking UTM (Urchin Tracking Module) parameters  
and conversion events. This bundle provides automatic UTM parameter detection,  
session management, and conversion tracking for web applications.

## Table of Contents

- [Features](#features)
- [Dependencies](#dependencies)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Usage](#usage)
- [Advanced Usage](#advanced-usage)
- [EasyAdmin Integration](#easyadmin-integration)
- [Events](#events)
- [Storage Strategies](#storage-strategies)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

## Features

- **Automatic UTM Parameter Detection**: Automatically extracts UTM parameters from incoming requests
- **Session Management**: Manages UTM sessions with configurable lifetime and expiration
- **Conversion Tracking**: Track conversion events with UTM attribution
- **Multiple Storage Strategies**: Support for database and session-based storage
- **EasyAdmin Integration**: Pre-built admin controllers for managing UTM data
- **Flexible Configuration**: Support for custom UTM parameters and validation rules
- **Event-Driven Architecture**: Dispatches events for UTM conversions and sessions
- **Parameter Validation**: Built-in validation with XSS protection and length limits
- **User Attribution**: Automatic user identification and anonymous session tracking

## Dependencies

- PHP 8.1 or higher
- Symfony 6.4 or higher
- Doctrine ORM 3.0 or higher
- EasyAdmin Bundle 4.0 or higher

## Installation

Install the bundle via Composer:

```bash
composer require tourze/symfony-utm-bundle
```

## Quick Start

### 1. Enable the Bundle

Add the bundle to your `config/bundles.php` file:

```php
return [
    // ...
    Tourze\UtmBundle\UtmBundle::class => ['all' => true],
];
```

### 2. Configure Database

Run the following commands to set up the database tables:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

### 3. Basic Usage

The bundle automatically tracks UTM parameters from URLs like:

```text
https://example.com/landing-page?utm_source=google&utm_medium=cpc&utm_campaign=summer_sale
```

Track conversions in your controllers:

```php
use Tourze\UtmBundle\Service\UtmConversionTracker;

class OrderController extends AbstractController
{
    public function __construct(
        private UtmConversionTracker $conversionTracker
    ) {}

    public function complete(): Response
    {
        // Track order completion conversion
        $this->conversionTracker->track('order_complete', 99.99, [
            'order_id' => 12345,
            'items_count' => 3
        ]);

        return $this->render('order/complete.html.twig');
    }
}
```

## Configuration

Create a configuration file at `config/packages/utm.yaml`:

```yaml
utm:
  # Storage strategy: 'database' or 'session'
  storage_strategy: 'database'
  
  # Session configuration
  session:
    lifetime: 3600  # Session lifetime in seconds
    
  # Parameter validation
  validation:
    max_length: 255     # Maximum parameter length
    sanitize: true      # Enable XSS protection
    
  # Additional UTM parameters to track
  additional_parameters:
    - utm_id
    - utm_custom
```

## Usage

### Manual UTM Parameter Extraction

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
            
            // Use UTM data for analytics
        }

        return $this->render('landing.html.twig');
    }
}
```

### Session Management

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

## Advanced Usage

### Custom Storage Strategy

Implement your own storage strategy:

```php
use Tourze\UtmBundle\Service\Storage\UtmStorageStrategyInterface;
use Tourze\UtmBundle\Entity\UtmSession;
use Tourze\UtmBundle\Dto\UtmParametersDto;

class RedisStorageStrategy implements UtmStorageStrategyInterface
{
    public function store(UtmParametersDto $parameters, string $sessionId): UtmSession
    {
        // Custom Redis implementation
    }

    public function retrieve(string $sessionId): ?UtmSession
    {
        // Custom Redis retrieval
    }
}
```

### Event Listeners

Listen to UTM events:

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
        
        // Send data to analytics platform
    }
}
```

## EasyAdmin Integration

The bundle provides pre-built EasyAdmin controllers for managing UTM data. Add to your dashboard:

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
        yield MenuItem::section('UTM Analytics');
        yield MenuItem::linkToCrud('UTM Parameters', 'fas fa-link', UtmParameters::class)
            ->setController(UtmParametersCrudController::class);
        yield MenuItem::linkToCrud('UTM Sessions', 'fas fa-users', UtmSession::class)
            ->setController(UtmSessionCrudController::class);
        yield MenuItem::linkToCrud('UTM Conversions', 'fas fa-chart-line', UtmConversion::class)
            ->setController(UtmConversionCrudController::class);
    }
}
```

## Events

The bundle dispatches the following events:

- `UtmConversionEvent::NAME`: Dispatched when a conversion is tracked

## Storage Strategies

### Database Strategy (Default)

Stores UTM data in database tables using Doctrine ORM.

### Session Strategy

Stores UTM data in PHP sessions for simpler setups without persistent storage.

## API Reference

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

## Testing

Run the test suite:

```bash
vendor/bin/phpunit packages/symfony-utm-bundle/tests
```

Run static analysis:

```bash
vendor/bin/phpstan analyse packages/symfony-utm-bundle
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass
6. Submit a pull request

Please ensure your code follows PSR-12 coding standards and includes appropriate tests.

## License

This bundle is released under the MIT License. See [LICENSE](LICENSE) file for details.