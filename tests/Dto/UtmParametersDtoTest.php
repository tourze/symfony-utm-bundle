<?php

namespace Tourze\UtmBundle\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;

class UtmParametersDtoTest extends TestCase
{
    public function testFromArray_withStandardParameters_populatesFields(): void
    {
        $parameters = [
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'spring_sale',
            'utm_term' => 'running shoes',
            'utm_content' => 'banner_1',
        ];

        $dto = UtmParametersDto::fromArray($parameters);

        $this->assertSame('google', $dto->getSource());
        $this->assertSame('cpc', $dto->getMedium());
        $this->assertSame('spring_sale', $dto->getCampaign());
        $this->assertSame('running shoes', $dto->getTerm());
        $this->assertSame('banner_1', $dto->getContent());
        $this->assertEmpty($dto->getAdditionalParameters());
    }

    public function testFromArray_withAdditionalParameters_populatesAdditionalParameters(): void
    {
        $parameters = [
            'utm_source' => 'facebook',
            'utm_custom1' => 'value1',
            'utm_custom2' => 'value2',
            'other_param' => 'should_be_ignored'
        ];

        $dto = UtmParametersDto::fromArray($parameters);

        $this->assertSame('facebook', $dto->getSource());
        $this->assertNull($dto->getMedium());
        $this->assertNull($dto->getCampaign());
        $this->assertNull($dto->getTerm());
        $this->assertNull($dto->getContent());
        
        $additionalParams = $dto->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('value1', $additionalParams['custom1']);
        $this->assertSame('value2', $additionalParams['custom2']);
        $this->assertArrayNotHasKey('other_param', $additionalParams);
    }

    public function testFromArray_withEmptyArray_returnsEmptyDto(): void
    {
        $dto = UtmParametersDto::fromArray([]);

        $this->assertNull($dto->getSource());
        $this->assertNull($dto->getMedium());
        $this->assertNull($dto->getCampaign());
        $this->assertNull($dto->getTerm());
        $this->assertNull($dto->getContent());
        $this->assertEmpty($dto->getAdditionalParameters());
        $this->assertFalse($dto->hasAnyParameter());
    }

    public function testToArray_withStandardParameters_returnsCorrectArray(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('google')
            ->setMedium('cpc')
            ->setCampaign('spring_sale')
            ->setTerm('running shoes')
            ->setContent('banner_1');

        $array = $dto->toArray();

        $this->assertCount(5, $array);
        $this->assertSame('google', $array['utm_source']);
        $this->assertSame('cpc', $array['utm_medium']);
        $this->assertSame('spring_sale', $array['utm_campaign']);
        $this->assertSame('running shoes', $array['utm_term']);
        $this->assertSame('banner_1', $array['utm_content']);
    }

    public function testToArray_withAdditionalParameters_includesAdditionalParameters(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('facebook')
            ->setAdditionalParameters([
                'custom1' => 'value1',
                'custom2' => 'value2'
            ]);

        $array = $dto->toArray();

        $this->assertCount(3, $array);
        $this->assertSame('facebook', $array['utm_source']);
        $this->assertSame('value1', $array['utm_custom1']);
        $this->assertSame('value2', $array['utm_custom2']);
    }

    public function testToArray_withEmptyDto_returnsEmptyArray(): void
    {
        $dto = new UtmParametersDto();
        $array = $dto->toArray();

        $this->assertEmpty($array);
    }

    public function testHasAnyParameter_withNoParameters_returnsFalse(): void
    {
        $dto = new UtmParametersDto();
        $this->assertFalse($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withSource_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('google');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withMedium_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setMedium('cpc');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withCampaign_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setCampaign('spring_sale');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withTerm_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setTerm('running shoes');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withContent_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setContent('banner_1');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameter_withAdditionalParameters_returnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setAdditionalParameters(['custom1' => 'value1']);
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testAddAdditionalParameter_addsParameter(): void
    {
        $dto = new UtmParametersDto();
        $dto->addAdditionalParameter('custom1', 'value1');
        $dto->addAdditionalParameter('custom2', 'value2');

        $additionalParams = $dto->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('value1', $additionalParams['custom1']);
        $this->assertSame('value2', $additionalParams['custom2']);
    }

    public function testAddAdditionalParameter_overwritesExistingParameter(): void
    {
        $dto = new UtmParametersDto();
        $dto->addAdditionalParameter('custom', 'value1');
        $dto->addAdditionalParameter('custom', 'value2');

        $additionalParams = $dto->getAdditionalParameters();
        $this->assertCount(1, $additionalParams);
        $this->assertSame('value2', $additionalParams['custom']);
    }
} 