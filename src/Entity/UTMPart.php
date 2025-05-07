<?php

namespace Tourze\Symfony\UTM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * UTM参数是一种对获客来源进行监督管理与评估的手段。
 * UTM是流量和转化归因的基础。
 * UTM参数一般包含五种元素，分别是：广告的来源渠道（utm_source）、广告活动的媒介（utm_medium）、广告活动的名称（utm_campaign）、广告的具体内容（utm_content）以及广告的关键词（utm_term）。
 * 例子：https://www.fxiaoke.com/?utm_source=baidu&utm_medium=sem&utm_campaign=核心词推广&utm_content=title&utm_term=crm
 *
 * @see https://www.fxiaoke.com/crm/information-14303.html
 * @see https://www.ichdata.com/docs/ga-practice-guide/chap2/utm
 * @see https://docs.zhugeio.com/datamanager/utm_mark.html
 */
#[ORM\Embeddable]
class UTMPart
{
    /**
     * @var string|null 标识来自哪个渠道	utm_source=baidu
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '广告来源渠道'])]
    private ?string $utmSource = null;

    public function setUtmSource(?string $utmSource): self
    {
        $this->utmSource = $utmSource;

        return $this;
    }

    public function getUtmSource(): ?string
    {
        return $this->utmSource;
    }

    /**
     * @var string|null 标识来自何种媒介	utm_medium=cpc
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '广告活动媒介'])]
    private ?string $utmMedium = null;

    public function getUtmMedium(): ?string
    {
        return $this->utmMedium;
    }

    public function setUtmMedium(?string $utmMedium): self
    {
        $this->utmMedium = $utmMedium;

        return $this;
    }

    /**
     * @var string|null utm_campaign=doublescore
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '广告活动名称'])]
    private ?string $utmCampaign = null;

    public function getUtmCampaign(): ?string
    {
        return $this->utmCampaign;
    }

    public function setUtmCampaign(?string $utmCampaign): self
    {
        $this->utmCampaign = $utmCampaign;

        return $this;
    }

    /**
     * @var string|null utm_content=a
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '广告具体内容'])]
    private ?string $utmContent = null;

    public function getUtmContent(): ?string
    {
        return $this->utmContent;
    }

    public function setUtmContent(?string $utmContent): void
    {
        $this->utmContent = $utmContent;
    }

    /**
     * @var string|null utm_term=datadriven
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '广告关键词'])]
    private ?string $utmTerm = null;

    public function getUtmTerm(): ?string
    {
        return $this->utmTerm;
    }

    public function setUtmTerm(?string $utmTerm): void
    {
        $this->utmTerm = $utmTerm;
    }
}
