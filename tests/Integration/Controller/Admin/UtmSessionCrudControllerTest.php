<?php

namespace Tourze\UtmBundle\Tests\Integration\Controller\Admin;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Controller\Admin\UtmSessionCrudController;
use Tourze\UtmBundle\Entity\UtmSession;

class UtmSessionCrudControllerTest extends TestCase
{
    public function testGetEntityFqcn(): void
    {
        $this->assertEquals(UtmSession::class, UtmSessionCrudController::getEntityFqcn());
    }
}