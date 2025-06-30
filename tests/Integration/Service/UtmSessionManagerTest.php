<?php

namespace Tourze\UtmBundle\Tests\Integration\Service;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Service\UtmSessionManager;

class UtmSessionManagerTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(UtmSessionManager::class));
    }
}