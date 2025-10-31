<?php

namespace Tourze\UtmBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\UtmBundle\Entity\UtmConversion;

/**
 * UTM转化事件
 *
 * 当记录UTM转化时触发
 */
class UtmConversionEvent extends Event
{
    /**
     * 事件名称
     */
    public const NAME = 'utm.conversion';

    public function __construct(
        private readonly UtmConversion $conversion,
    ) {
    }

    /**
     * 获取转化实体
     */
    public function getConversion(): UtmConversion
    {
        return $this->conversion;
    }
}
