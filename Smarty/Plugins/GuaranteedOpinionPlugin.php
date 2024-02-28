<?php

namespace GuaranteedOpinion\Smarty\Plugins;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;

class GuaranteedOpinionPlugin extends AbstractSmartyPlugin
{
    public function getPluginDescriptors(): array
    {
        return [
            new SmartyPluginDescriptor('function', 'getSiteRate', $this, 'getSiteRate'),
            new SmartyPluginDescriptor('function', 'getProductRate', $this, 'getProductRate')
        ];
    }

    public function getProductRate($param, $smarty): void
    {
        $productRating = GuaranteedOpinionProductRatingQuery::create()->findOneByProductId($param['product_id']);

        $smarty->assign('productTotal', $productRating?->getTotal());
        $smarty->assign('productAverage', $productRating?->getAverage());
    }

    public function getSiteRate($param, $smarty): void
    {
        $smarty->assign('siteTotal', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY));
        $smarty->assign('siteAverage', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY));
    }
}