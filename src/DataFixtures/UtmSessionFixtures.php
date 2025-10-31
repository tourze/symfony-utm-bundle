<?php

namespace Tourze\UtmBundle\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\UtmBundle\Entity\UtmParameter;
use Tourze\UtmBundle\Entity\UtmSession;

class UtmSessionFixtures extends AppFixtures implements DependentFixtureInterface
{
    public const UTM_SESSION_REFERENCE_PREFIX = 'utm_session_';
    public const UTM_SESSION_COUNT = 25;

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < self::UTM_SESSION_COUNT; ++$i) {
            $session = $this->createUtmSession($i);
            $manager->persist($session);
            $this->addReference(self::UTM_SESSION_REFERENCE_PREFIX . $i, $session);
        }

        $manager->flush();
    }

    private function createUtmSession(int $index): UtmSession
    {
        $session = new UtmSession();
        $session->setSessionId($this->generateSessionId());

        if ($index < UtmParameterFixtures::UTM_PARAMETER_COUNT) {
            $parameters = $this->getReference(
                UtmParameterFixtures::UTM_PARAMETER_REFERENCE_PREFIX . $index,
                UtmParameter::class
            );
            $session->setParameters($parameters);
        }

        $session->setUserIdentifier($this->generateUserIdentifier());
        $session->setClientIp($this->faker->ipv4());
        $session->setUserAgent($this->faker->userAgent());

        if ($this->faker->boolean(80)) {
            $expiresAt = $this->faker->dateTimeBetween('now', '+30 days');
            $session->setExpiresAt(\DateTimeImmutable::createFromMutable($expiresAt));
        }

        if ($this->faker->boolean(40)) {
            $session->setMetadata($this->generateSessionMetadata());
        }

        $createTime = $this->faker->dateTimeBetween('-30 days', 'now');
        $session->setCreateTime(\DateTimeImmutable::createFromMutable($createTime));

        if ($this->faker->boolean(20)) {
            $updateTime = $this->faker->dateTimeBetween($createTime, 'now');
            $session->setUpdateTime(\DateTimeImmutable::createFromMutable($updateTime));
        }

        return $session;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSessionMetadata(): array
    {
        $metadata = [
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera']),
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
        ];

        if ($this->faker->boolean(60)) {
            $metadata['referrer'] = $this->faker->url();
        }

        if ($this->faker->boolean(30)) {
            $metadata['landing_page'] = $this->faker->url();
        }

        if ($this->faker->boolean(40)) {
            $metadata['screen_resolution'] = $this->faker->randomElement([
                '1920x1080', '1366x768', '1440x900', '1280x720', '414x896',
            ]);
        }

        if ($this->faker->boolean(50)) {
            $metadata['timezone'] = $this->faker->timezone();
        }

        if ($this->faker->boolean(25)) {
            $metadata['locale'] = $this->faker->randomElement(['zh-CN', 'en-US', 'ja-JP', 'ko-KR']);
        }

        return $metadata;
    }

    public function getDependencies(): array
    {
        return [
            UtmParameterFixtures::class,
        ];
    }
}
