<?php

namespace Tourze\UtmBundle\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Controller\Admin\UtmConversionCrudController;
use Tourze\UtmBundle\Entity\UtmConversion;

class UtmConversionCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(UtmConversion::class, UtmConversionCrudController::getEntityFqcn());
    }
}