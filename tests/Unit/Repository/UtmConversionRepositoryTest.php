<?php

namespace Tourze\UtmBundle\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Repository\UtmConversionRepository;

class UtmConversionRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(UtmConversionRepository::class));
    }
}