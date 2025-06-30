<?php

namespace Tourze\UtmBundle\Tests\Unit\Event;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Event\UtmConversionEvent;

class UtmConversionEventTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $conversion = $this->createMock(UtmConversion::class);
        $event = new UtmConversionEvent($conversion);

        $this->assertSame($conversion, $event->getConversion());
    }

    public function testEventName(): void
    {
        $this->assertEquals('utm.conversion', UtmConversionEvent::NAME);
    }
}