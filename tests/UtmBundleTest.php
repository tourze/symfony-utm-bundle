<?php

declare(strict_types=1);

namespace Tourze\UtmBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\UtmBundle\UtmBundle;

/**
 * @internal
 */
#[CoversClass(UtmBundle::class)]
#[RunTestsInSeparateProcesses]
final class UtmBundleTest extends AbstractBundleTestCase
{
}
