<?php

namespace Tourze\UtmBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Service\UtmConversionTracker;

class UtmConversionTrackerTest extends TestCase
{
    public function testServiceExists(): void
    {
        $this->assertTrue(class_exists(UtmConversionTracker::class));
    }
}