<?php

namespace Tourze\UtmBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Event\UtmConversionEvent;

/**
 * @internal
 */
#[CoversClass(UtmConversionEvent::class)]
final class UtmConversionEventTest extends AbstractEventTestCase
{
    public function testConstructorSetsConversion(): void
    {
        $conversion = new UtmConversion();
        $event = new UtmConversionEvent($conversion);

        $this->assertSame($conversion, $event->getConversion());
    }

    public function testGetConversionReturnsConversion(): void
    {
        $conversion = new UtmConversion();
        $event = new UtmConversionEvent($conversion);

        $result = $event->getConversion();

        $this->assertSame($conversion, $result);
    }

    public function testEventConstantHasCorrectValue(): void
    {
        $this->assertEquals('utm.conversion', UtmConversionEvent::NAME);
    }
}
