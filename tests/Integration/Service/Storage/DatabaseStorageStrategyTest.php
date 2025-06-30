<?php

namespace Tourze\UtmBundle\Tests\Integration\Service\Storage;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Service\Storage\DatabaseStorageStrategy;

class DatabaseStorageStrategyTest extends TestCase
{
    public function testStrategyExists(): void
    {
        $this->assertTrue(class_exists(DatabaseStorageStrategy::class));
    }
}