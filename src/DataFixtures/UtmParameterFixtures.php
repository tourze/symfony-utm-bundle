<?php

namespace Tourze\UtmBundle\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Tourze\UtmBundle\Entity\UtmParameter;

class UtmParameterFixtures extends AppFixtures
{
    public const UTM_PARAMETER_REFERENCE_PREFIX = 'utm_parameter_';
    public const UTM_PARAMETER_COUNT = 20;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::UTM_PARAMETER_COUNT; ++$i) {
            $parameters = $this->createUtmParameter();
            $manager->persist($parameters);
            $this->addReference(self::UTM_PARAMETER_REFERENCE_PREFIX . $i, $parameters);
        }

        $this->createSpecialParametersSet($manager);

        $manager->flush();
    }

    private function createUtmParameter(): UtmParameter
    {
        $parameters = new UtmParameter();

        if ($this->faker->boolean(90)) {
            $parameters->setSource($this->generateUtmSource());
        }

        if ($this->faker->boolean(85)) {
            $parameters->setMedium($this->generateUtmMedium());
        }

        if ($this->faker->boolean(80)) {
            $parameters->setCampaign($this->generateUtmCampaign());
        }

        $parameters->setTerm($this->generateUtmTerm());
        $parameters->setContent($this->generateUtmContent());

        if ($this->faker->boolean(30)) {
            $additionalParams = [];
            $paramCount = $this->faker->numberBetween(1, 3);

            for ($j = 0; $j < $paramCount; ++$j) {
                $key = 'utm_' . $this->faker->word();
                $value = $this->faker->word();
                $additionalParams[$key] = $value;
            }

            $parameters->setAdditionalParameters($additionalParams);
        }

        $createTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $parameters->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        return $parameters;
    }

    private function createSpecialParametersSet(ObjectManager $manager): void
    {
        $specialSets = [
            [
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'holiday_sale_2024',
                'term' => 'hotel booking',
                'content' => 'text_ad_001',
            ],
            [
                'source' => 'facebook',
                'medium' => 'social',
                'campaign' => 'brand_awareness',
                'term' => null,
                'content' => 'carousel_ad',
            ],
            [
                'source' => 'newsletter',
                'medium' => 'email',
                'campaign' => 'monthly_newsletter',
                'term' => null,
                'content' => 'header_banner',
            ],
            [
                'source' => 'baidu',
                'medium' => 'cpc',
                'campaign' => 'china_market',
                'term' => '酒店预订',
                'content' => 'chinese_ad',
            ],
        ];

        foreach ($specialSets as $index => $data) {
            $parameters = new UtmParameter();
            $parameters->setSource($data['source']);
            $parameters->setMedium($data['medium']);
            $parameters->setCampaign($data['campaign']);
            $parameters->setTerm($data['term']);
            $parameters->setContent($data['content']);

            $parameters->setCreateTime(new \DateTimeImmutable());

            $manager->persist($parameters);
            $this->addReference(
                self::UTM_PARAMETER_REFERENCE_PREFIX . 'special_' . $index,
                $parameters
            );
        }
    }
}
