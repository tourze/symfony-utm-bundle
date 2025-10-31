<?php

namespace Tourze\UtmBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
abstract class AppFixtures extends Fixture
{
    protected Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    abstract public function load(ObjectManager $manager): void;

    protected function generateUtmSource(): string
    {
        $sources = [
            'google', 'facebook', 'twitter', 'linkedin', 'instagram',
            'baidu', 'bing', 'yahoo', 'newsletter', 'direct',
            'wechat', 'weibo', 'tiktok', 'youtube', 'referral',
        ];

        $source = $this->faker->randomElement($sources);
        assert(is_string($source));

        return $source;
    }

    protected function generateUtmMedium(): string
    {
        $mediums = [
            'cpc', 'cpm', 'email', 'social', 'organic',
            'referral', 'banner', 'affiliate', 'paid',
            'display', 'video', 'push', 'sms', 'direct',
        ];

        $medium = $this->faker->randomElement($mediums);
        assert(is_string($medium));

        return $medium;
    }

    protected function generateUtmCampaign(): string
    {
        $campaigns = [
            'spring_sale', 'summer_promo', 'holiday_campaign',
            'new_product_launch', 'brand_awareness', 'retargeting',
            'black_friday', 'double_eleven', 'year_end_sale',
            'welcome_series', 'loyalty_program', 'flash_sale',
        ];

        $campaign = $this->faker->randomElement($campaigns);
        assert(is_string($campaign));

        return $campaign;
    }

    protected function generateUtmTerm(): ?string
    {
        if ($this->faker->boolean(30)) {
            return null;
        }

        $terms = [
            'hotel booking', 'cheap flights', 'travel deals',
            'vacation packages', 'business travel', 'luxury hotels',
            'budget accommodation', 'last minute deals', 'weekend getaway',
            'family vacation', 'romantic getaway', 'adventure travel',
        ];

        $result = $this->faker->randomElement($terms);
        assert(is_string($result));

        return $result;
    }

    protected function generateUtmContent(): ?string
    {
        if ($this->faker->boolean(40)) {
            return null;
        }

        $contents = [
            'banner_top', 'sidebar_ad', 'footer_link', 'header_cta',
            'text_link', 'button_primary', 'image_ad', 'video_ad',
            'popup_modal', 'newsletter_link', 'social_post', 'blog_post',
        ];

        $result = $this->faker->randomElement($contents);
        assert(is_string($result));

        return $result;
    }

    protected function generateSessionId(): string
    {
        return $this->faker->uuid();
    }

    protected function generateUserIdentifier(): ?string
    {
        if ($this->faker->boolean(70)) {
            return 'user_' . $this->faker->numberBetween(1, 10000);
        }

        return null;
    }

    protected function generateEventName(): string
    {
        $events = [
            'purchase', 'signup', 'newsletter_subscribe', 'download',
            'contact_form', 'product_view', 'add_to_cart', 'checkout_start',
            'booking_complete', 'login', 'registration', 'phone_call',
        ];

        $event = $this->faker->randomElement($events);
        assert(is_string($event));

        return $event;
    }

    protected function generateConversionValue(): float
    {
        return $this->faker->randomFloat(2, 0, 999.99);
    }

    /**
     * @return array<string, mixed>
     */
    protected function generateMetadata(): array
    {
        $fields = $this->faker->numberBetween(1, 5);
        $data = [];

        for ($i = 0; $i < $fields; ++$i) {
            $key = $this->faker->word();
            $type = $this->faker->numberBetween(1, 3);

            $value = match ($type) {
                1 => $this->faker->word(),
                2 => $this->faker->numberBetween(1, 1000),
                3 => $this->faker->boolean(),
                default => $this->faker->word(),
            };

            $data[$key] = $value;
        }

        return $data;
    }
}
