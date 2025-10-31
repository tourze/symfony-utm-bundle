<?php

namespace Tourze\UtmBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\UtmBundle\Exception\UtmSessionException;

/**
 * @internal
 */
#[CoversClass(UtmSessionException::class)]
final class UtmSessionExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $message = 'Test exception message';
        $exception = new UtmSessionException($message);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithCodeAndPrevious(): void
    {
        $message = 'Test exception message';
        $code = 500;
        $previous = new \Exception('Previous exception');

        $exception = new UtmSessionException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
