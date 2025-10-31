<?php

namespace Tourze\UtmBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\UtmBundle\Exception\UtmParameterException;

/**
 * @internal
 */
#[CoversClass(UtmParameterException::class)]
final class UtmParameterExceptionTest extends AbstractExceptionTestCase
{
    public function testIsInstanceOfInvalidArgumentException(): void
    {
        $exception = new UtmParameterException('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    public function testCanCreateWithMessage(): void
    {
        $message = 'Unsupported parameter: test';
        $exception = new UtmParameterException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCanCreateWithMessageAndCode(): void
    {
        $message = 'Test message';
        $code = 400;
        $exception = new UtmParameterException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
    }

    public function testCanCreateWithPreviousException(): void
    {
        $previous = new \RuntimeException('Previous exception');
        $exception = new UtmParameterException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
