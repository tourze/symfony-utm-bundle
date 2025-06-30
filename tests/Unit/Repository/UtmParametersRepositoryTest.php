<?php

namespace Tourze\UtmBundle\Tests\Unit\Repository;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Repository\UtmParametersRepository;

class UtmParametersRepositoryTest extends TestCase
{
    public function testRepositoryExists(): void
    {
        $this->assertTrue(class_exists(UtmParametersRepository::class));
    }
}