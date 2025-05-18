<?php

namespace Tourze\UtmBundle\Service;

use Psr\Log\LoggerInterface;
use Tourze\UtmBundle\Dto\UtmParametersDto;

/**
 * UTM参数验证服务
 *
 * 负责验证UTM参数，过滤无效值，标准化数据
 */
class UtmParametersValidator
{
    private readonly int $maxLength;
    private readonly bool $sanitize;

    public function __construct(
        private readonly LoggerInterface $logger,
        ?int $maxLength = null,
        ?bool $sanitize = null
    ) {
        $this->maxLength = $maxLength ?? 255;
        $this->sanitize = $sanitize ?? true;
    }

    /**
     * 验证UTM参数
     *
     * @param UtmParametersDto $parametersDto 原始UTM参数
     * @return UtmParametersDto 验证后的UTM参数
     */
    public function validate(UtmParametersDto $parametersDto): UtmParametersDto
    {
        if (!$parametersDto->hasAnyParameter()) {
            return new UtmParametersDto();
        }

        $validatedDto = new UtmParametersDto();
        $originalParameters = $parametersDto->toArray();
        $validKeys = [];

        // 验证标准UTM参数
        $source = $parametersDto->getSource();
        if (!empty($source) || $source === '0') {
            $validSource = $this->sanitizeValue($source);
            if (strlen($validSource) > $this->maxLength) {
                $this->logger->warning('UTM参数值过长，已截断', [
                    'parameter' => 'utm_source',
                    'original_length' => strlen($validSource),
                    'max_length' => $this->maxLength,
                ]);
                $validSource = substr($validSource, 0, $this->maxLength);
            }
            $validatedDto->setSource($validSource);
            $validKeys[] = 'utm_source';
        }

        $medium = $parametersDto->getMedium();
        if (!empty($medium) || $medium === '0') {
            $validMedium = $this->sanitizeValue($medium);
            if (strlen($validMedium) > $this->maxLength) {
                $this->logger->warning('UTM参数值过长，已截断', [
                    'parameter' => 'utm_medium',
                    'original_length' => strlen($validMedium),
                    'max_length' => $this->maxLength,
                ]);
                $validMedium = substr($validMedium, 0, $this->maxLength);
            }
            $validatedDto->setMedium($validMedium);
            $validKeys[] = 'utm_medium';
        }

        $campaign = $parametersDto->getCampaign();
        if (!empty($campaign) || $campaign === '0') {
            $validCampaign = $this->sanitizeValue($campaign);
            if (strlen($validCampaign) > $this->maxLength) {
                $this->logger->warning('UTM参数值过长，已截断', [
                    'parameter' => 'utm_campaign',
                    'original_length' => strlen($validCampaign),
                    'max_length' => $this->maxLength,
                ]);
                $validCampaign = substr($validCampaign, 0, $this->maxLength);
            }
            $validatedDto->setCampaign($validCampaign);
            $validKeys[] = 'utm_campaign';
        }

        $term = $parametersDto->getTerm();
        if (!empty($term) || $term === '0') {
            $validTerm = $this->sanitizeValue($term);
            if (strlen($validTerm) > $this->maxLength) {
                $this->logger->warning('UTM参数值过长，已截断', [
                    'parameter' => 'utm_term',
                    'original_length' => strlen($validTerm),
                    'max_length' => $this->maxLength,
                ]);
                $validTerm = substr($validTerm, 0, $this->maxLength);
            }
            $validatedDto->setTerm($validTerm);
            $validKeys[] = 'utm_term';
        }

        $content = $parametersDto->getContent();
        if (!empty($content) || $content === '0') {
            $validContent = $this->sanitizeValue($content);
            if (strlen($validContent) > $this->maxLength) {
                $this->logger->warning('UTM参数值过长，已截断', [
                    'parameter' => 'utm_content',
                    'original_length' => strlen($validContent),
                    'max_length' => $this->maxLength,
                ]);
                $validContent = substr($validContent, 0, $this->maxLength);
            }
            $validatedDto->setContent($validContent);
            $validKeys[] = 'utm_content';
        }

        // 验证附加参数
        $additionalParams = [];
        foreach ($parametersDto->getAdditionalParameters() as $key => $value) {
            if (empty($value) && $value !== '0') {
                continue;
            }

            $validValue = $this->sanitizeValue($value);
            if (strlen($validValue) > $this->maxLength) {
                $this->logger->warning('UTM附加参数值过长，已截断', [
                    'parameter' => 'utm_' . $key,
                    'original_length' => strlen($validValue),
                    'max_length' => $this->maxLength,
                ]);
                $validValue = substr($validValue, 0, $this->maxLength);
            }

            $additionalParams[$key] = $validValue;
            $validKeys[] = 'utm_' . $key;
        }

        if (!empty($additionalParams)) {
            $validatedDto->setAdditionalParameters($additionalParams);
        }

        // 记录被过滤的参数
        $originalKeys = array_keys($originalParameters);
        $filteredKeys = array_diff($originalKeys, $validKeys);

        if (!empty($filteredKeys)) {
            $this->logger->info('某些UTM参数被过滤', [
                'original' => $originalKeys,
                'validated' => $validKeys,
                'filtered' => $filteredKeys,
            ]);
        }

        return $validatedDto;
    }

    /**
     * 标准化参数值
     */
    private function sanitizeValue(mixed $value): string
    {
        if (!is_string($value)) {
            $value = (string)$value;
        }

        if (!$this->sanitize) {
            return $value;
        }

        // 去除首尾空格
        $value = trim($value);

        // 过滤XSS风险字符
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $value;
    }
}
