<?php

namespace Tourze\UtmBundle\Service\Storage;

use Tourze\UtmBundle\Entity\UtmParameters;

/**
 * UTM存储策略接口
 */
interface UtmStorageStrategyInterface
{
    /**
     * 存储UTM参数
     */
    public function store(UtmParameters $parameters): void;

    /**
     * 检索存储的UTM参数
     */
    public function retrieve(): ?UtmParameters;

    /**
     * 清除存储的UTM参数
     */
    public function clear(): void;
} 