<?php

namespace Tourze\UtmBundle\Tests\Unit\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Controller\Admin\UtmParametersCrudController;
use Tourze\UtmBundle\Entity\UtmParameters;

class UtmParametersCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(UtmParameters::class, UtmParametersCrudController::getEntityFqcn());
    }
}