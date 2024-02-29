<?php

namespace GuaranteedOpinion\Smarty\Plugins;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use GuaranteedOpinion\Service\ProductReviewService;
use GuaranteedOpinion\Service\SiteReviewService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use TheliaSmarty\Template\AbstractSmartyPlugin;
use TheliaSmarty\Template\SmartyPluginDescriptor;

class GuaranteedOpinionPlugin extends AbstractSmartyPlugin
{
    public function __construct(
        protected SiteReviewService $siteReviewService,
        protected ProductReviewService $productReviewService
    ) {
    }

    public function getPluginDescriptors(): array
    {
        return [
            new SmartyPluginDescriptor('function', 'getSiteRate', $this, 'getSiteRate'),
            new SmartyPluginDescriptor('function', 'getProductRate', $this, 'getProductRate'),
            new SmartyPluginDescriptor('function', 'site_reviews', $this, 'getSiteReviews'),
            new SmartyPluginDescriptor('function', 'product_reviews', $this, 'getProductReviews')
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

    public function getProductReviews($param, $smarty): void
    {
        if (isset($param['product_id'])) {
            $productId = $param['product_id'];
            $order = $param['order'];

            $reviews = GuaranteedOpinionProductReviewQuery::create();
            switch ($order) {
                case 'review_date.asc':
                    $reviews->orderByReviewDate(Criteria::ASC);
                    break;
                case 'rate.asc':
                    $reviews->orderByRate(Criteria::ASC);
                    break;
                case 'rate.desc':
                    $reviews->orderByRate(Criteria::DESC);
                    break;
                case 'review_date.desc':
                default:
                $reviews->orderByReviewDate(Criteria::DESC);
                    break;
            }
            $reviews->findByProductId($productId);

            $smarty->assign('product_reviews', $this->productReviewService->formatProductReviews($reviews));
        }
    }

    /**
     * @throws PropelException
     */
    public function getSiteReviews($param, $smarty): void
    {
        $reviews = GuaranteedOpinionSiteReviewQuery::create()->orderByOrderDate(Criteria::DESC);
        if (isset($param['limit'])) {
            $reviews->setLimit($param['limit']);
        }
        $reviews->find();

        $smarty->assign('site_reviews', $this->siteReviewService->formatSiteReviews($reviews));
    }
}