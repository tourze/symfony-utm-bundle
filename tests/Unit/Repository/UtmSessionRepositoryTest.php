<?php

namespace Tourze\UtmBundle\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Repository\UtmSessionRepository;

class UtmSessionRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(UtmSessionRepository::class));
    }
}