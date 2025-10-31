<?php

namespace Tourze\UtmBundle\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\UtmBundle\Dto\UtmParametersDto;

/**
 * @internal
 */
#[CoversClass(UtmParametersDto::class)]
final class UtmParametersDtoTest extends TestCase
{
    public function testFromArrayWithStandardParametersPopulatesFields(): void
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

    public function testFromArrayWithAdditionalParametersPopulatesAdditionalParameters(): void
    {
        $parameters = [
            'utm_source' => 'facebook',
            'utm_custom1' => 'value1',
            'utm_custom2' => 'value2',
            'other_param' => 'should_be_ignored',
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

    public function testFromArrayWithEmptyArrayReturnsEmptyDto(): void
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

    public function testToArrayWithStandardParametersReturnsCorrectArray(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('google');
        $dto->setMedium('cpc');
        $dto->setCampaign('spring_sale');
        $dto->setTerm('running shoes');
        $dto->setContent('banner_1');

        $array = $dto->toArray();

        $this->assertCount(5, $array);
        $this->assertSame('google', $array['utm_source']);
        $this->assertSame('cpc', $array['utm_medium']);
        $this->assertSame('spring_sale', $array['utm_campaign']);
        $this->assertSame('running shoes', $array['utm_term']);
        $this->assertSame('banner_1', $array['utm_content']);
    }

    public function testToArrayWithAdditionalParametersIncludesAdditionalParameters(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('facebook');
        $dto->setAdditionalParameters([
            'custom1' => 'value1',
            'custom2' => 'value2',
        ]);

        $array = $dto->toArray();

        $this->assertCount(3, $array);
        $this->assertSame('facebook', $array['utm_source']);
        $this->assertSame('value1', $array['utm_custom1']);
        $this->assertSame('value2', $array['utm_custom2']);
    }

    public function testToArrayWithEmptyDtoReturnsEmptyArray(): void
    {
        $dto = new UtmParametersDto();
        $array = $dto->toArray();

        $this->assertEmpty($array);
    }

    public function testHasAnyParameterWithNoParametersReturnsFalse(): void
    {
        $dto = new UtmParametersDto();
        $this->assertFalse($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithSourceReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setSource('google');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithMediumReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setMedium('cpc');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithCampaignReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setCampaign('spring_sale');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithTermReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setTerm('running shoes');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithContentReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setContent('banner_1');
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testHasAnyParameterWithAdditionalParametersReturnsTrue(): void
    {
        $dto = new UtmParametersDto();
        $dto->setAdditionalParameters(['custom1' => 'value1']);
        $this->assertTrue($dto->hasAnyParameter());
    }

    public function testAddAdditionalParameterAddsParameter(): void
    {
        $dto = new UtmParametersDto();
        $dto->addAdditionalParameter('custom1', 'value1');
        $dto->addAdditionalParameter('custom2', 'value2');

        $additionalParams = $dto->getAdditionalParameters();
        $this->assertCount(2, $additionalParams);
        $this->assertSame('value1', $additionalParams['custom1']);
        $this->assertSame('value2', $additionalParams['custom2']);
    }

    public function testAddAdditionalParameterOverwritesExistingParameter(): void
    {
        $dto = new UtmParametersDto();
        $dto->addAdditionalParameter('custom', 'value1');
        $dto->addAdditionalParameter('custom', 'value2');

        $additionalParams = $dto->getAdditionalParameters();
        $this->assertCount(1, $additionalParams);
        $this->assertSame('value2', $additionalParams['custom']);
    }
}
