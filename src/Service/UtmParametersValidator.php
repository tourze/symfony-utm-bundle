<?php

namespace Tourze\UtmBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\UtmBundle\Dto\UtmParametersDto;
use Tourze\UtmBundle\Exception\UtmParameterException;

/**
 * UTM参数验证服务
 *
 * 负责验证UTM参数，过滤无效值，标准化数据
 */
#[WithMonologChannel(channel: 'utm')]
class UtmParametersValidator
{
    private readonly int $maxLength;

    private readonly bool $sanitize;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?int $maxLength = null,
        ?bool $sanitize = null,
    ) {
        $this->maxLength = $maxLength ?? 255;
        $this->sanitize = $sanitize ?? true;
    }

    /**
     * 验证UTM参数
     *
     * @param UtmParametersDto $parametersDto 原始UTM参数
     *
     * @return UtmParametersDto 验证后的UTM参数
     */
    public function validate(UtmParametersDto $parametersDto): UtmParametersDto
    {
        if (!$parametersDto->hasAnyParameter()) {
            return new UtmParametersDto();
        }

        $validatedDto = new UtmParametersDto();
        $originalParameters = $parametersDto->toArray();
        /** @var array<string> $validKeys */
        $validKeys = [];

        // 验证标准UTM参数
        $validKeys = $this->validateStandardParameter($parametersDto->getSource(), 'source', $validatedDto, $validKeys);
        $validKeys = $this->validateStandardParameter($parametersDto->getMedium(), 'medium', $validatedDto, $validKeys);
        $validKeys = $this->validateStandardParameter($parametersDto->getCampaign(), 'campaign', $validatedDto, $validKeys);
        $validKeys = $this->validateStandardParameter($parametersDto->getTerm(), 'term', $validatedDto, $validKeys);
        $validKeys = $this->validateStandardParameter($parametersDto->getContent(), 'content', $validatedDto, $validKeys);

        // 验证附加参数
        $validKeys = $this->validateAdditionalParameters($parametersDto, $validatedDto, $validKeys);

        // 记录被过滤的参数
        $originalKeys = array_keys($originalParameters);
        $filteredKeys = array_diff($originalKeys, $validKeys);

        if ([] !== $filteredKeys) {
            $this->logger->info('某些UTM参数被过滤', [
                'original' => $originalKeys,
                'validated' => $validKeys,
                'filtered' => $filteredKeys,
            ]);
        }

        return $validatedDto;
    }

    /**
     * 验证单个标准UTM参数
     */
    /**
     * @param array<string> $validKeys
     * @return array<string>
     */
    private function validateStandardParameter(?string $value, string $parameterName, UtmParametersDto $validatedDto, array $validKeys): array
    {
        if (null === $value || '' === $value) {
            return $validKeys;
        }

        $validValue = $this->sanitizeAndTruncateValue($value, 'utm_' . $parameterName);
        $this->setParameterValue($validatedDto, $parameterName, $validValue);
        $validKeys[] = 'utm_' . $parameterName;

        return $validKeys;
    }

    /**
     * 验证附加UTM参数
     */
    /**
     * @param array<string> $validKeys
     * @return array<string>
     */
    private function validateAdditionalParameters(UtmParametersDto $parametersDto, UtmParametersDto $validatedDto, array $validKeys): array
    {
        /** @var array<string, mixed> $additionalParams */
        $additionalParams = [];
        foreach ($parametersDto->getAdditionalParameters() as $key => $value) {
            if (null === $value || '' === $value) {
                continue;
            }

            $validValue = $this->sanitizeAndTruncateValue($value, 'utm_' . $key);
            $additionalParams[$key] = $validValue;
            $validKeys[] = 'utm_' . $key;
        }

        if ([] !== $additionalParams) {
            $validatedDto->setAdditionalParameters($additionalParams);
        }

        return $validKeys;
    }

    /**
     * 标准化并截断参数值
     */
    private function sanitizeAndTruncateValue(mixed $value, string $parameterName): string
    {
        $sanitizedValue = $this->sanitizeValue($value);

        if (strlen($sanitizedValue) > $this->maxLength) {
            $this->logger->warning('UTM参数值过长，已截断', [
                'parameter' => $parameterName,
                'original_length' => strlen($sanitizedValue),
                'max_length' => $this->maxLength,
            ]);

            return substr($sanitizedValue, 0, $this->maxLength);
        }

        return $sanitizedValue;
    }

    /**
     * 设置参数值到DTO
     */
    private function setParameterValue(UtmParametersDto $dto, string $parameterName, string $value): void
    {
        match ($parameterName) {
            'source' => $dto->setSource($value),
            'medium' => $dto->setMedium($value),
            'campaign' => $dto->setCampaign($value),
            'term' => $dto->setTerm($value),
            'content' => $dto->setContent($value),
            default => throw new UtmParameterException('Unsupported parameter: ' . $parameterName),
        };
    }

    /**
     * 标准化参数值
     */
    private function sanitizeValue(mixed $value): string
    {
        if (!is_string($value)) {
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $value = (string) $value;
            } else {
                $value = '';
            }
        }

        if (!$this->sanitize) {
            return $value;
        }

        // 去除首尾空格
        $value = trim($value);

        // 过滤XSS风险字符
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
