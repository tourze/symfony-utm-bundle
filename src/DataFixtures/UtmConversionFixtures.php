<?php

namespace Tourze\UtmBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\UtmBundle\Entity\UtmConversion;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;

class UtmConversionFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const UTM_CONVERSION_REFERENCE_PREFIX = 'utm_conversion_';
    public const UTM_CONVERSION_COUNT = 30;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::UTM_CONVERSION_COUNT; ++$i) {
            $conversion = $this->createUtmConversion($i);
            $manager->persist($conversion);
            $this->addReference(self::UTM_CONVERSION_REFERENCE_PREFIX . $i, $conversion);
        }

        $manager->flush();
    }

    private function createUtmConversion(int $index): UtmConversion
    {
        $conversion = new UtmConversion();
        $conversion->setEventName($this->generateEventName());
        $conversion->setUserIdentifier($this->generateUserIdentifier());

        if ($index < UtmParameterFixtures::UTM_PARAMETER_COUNT) {
            $parameters = $this->getReference(
                UtmParameterFixtures::UTM_PARAMETER_REFERENCE_PREFIX . $index,
                UtmParameter::class
            );
            $conversion->setParameters($parameters);
        }

        if ($index < UtmSessionFixtures::UTM_SESSION_COUNT) {
            $session = $this->getReference(
                UtmSessionFixtures::UTM_SESSION_REFERENCE_PREFIX . $index,
                UtmSession::class
            );
            $conversion->setSession($session);
        }

        $conversion->setValue($this->generateConversionValue());

        if ($this->faker->boolean(50)) {
            $conversion->setMetadata($this->generateConversionMetadata());
        }

        $createTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $conversion->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        return $conversion;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateConversionMetadata(): array
    {
        $metadata = [];

        if ($this->faker->boolean(70)) {
            $metadata['product_id'] = $this->faker->numberBetween(1, 1000);
        }

        if ($this->faker->boolean(50)) {
            $metadata['category'] = $this->faker->randomElement([
                'electronics', 'fashion', 'home', 'sports', 'books',
                'travel', 'automotive', 'health', 'beauty', 'food',
            ]);
        }

        if ($this->faker->boolean(60)) {
            $metadata['order_id'] = 'ORDER_' . $this->faker->bothify('##??##');
        }

        if ($this->faker->boolean(40)) {
            $metadata['currency'] = $this->faker->randomElement(['USD', 'EUR', 'CNY', 'JPY', 'GBP']);
        }

        if ($this->faker->boolean(30)) {
            $metadata['discount_code'] = $this->faker->regexify('[A-Z]{4}[0-9]{4}');
        }

        if ($this->faker->boolean(45)) {
            $metadata['page_url'] = $this->faker->url();
        }

        if ($this->faker->boolean(35)) {
            $metadata['funnel_step'] = $this->faker->numberBetween(1, 5);
        }

        if ($this->faker->boolean(25)) {
            $metadata['ab_test_variant'] = $this->faker->randomElement(['A', 'B', 'C', 'control']);
        }

        return $metadata;
    }

    public function getDependencies(): array
    {
        return [
            UtmParameterFixtures::class,
            UtmSessionFixtures::class,
        ];
    }
}
