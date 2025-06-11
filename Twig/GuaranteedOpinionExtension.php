<?php

namespace GuaranteedOpinion\Twig;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use GuaranteedOpinion\Service\ProductReviewService;
use GuaranteedOpinion\Service\SiteReviewService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class GuaranteedOpinionExtension extends AbstractExtension
{
    public function __construct(
        protected SiteReviewService $siteReviewService,
        protected ProductReviewService $productReviewService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getSiteRate', [$this, 'getSiteRate']),
            new TwigFunction('getProductRate', [$this, 'getProductRate']),
            new TwigFunction('site_reviews', [$this, 'getSiteReviews']),
            new TwigFunction('product_reviews', [$this, 'getProductReviews']),
        ];
    }

    public function getSiteRate(): array
    {
        return [
            'siteTotal' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY),
            'siteAverage' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY),
        ];
    }

    public function getProductRate(int $productId): array
    {
        $productRating = GuaranteedOpinionProductRatingQuery::create()->findOneByProductId($productId);
        return [
            'productTotal' => $productRating?->getTotal(),
            'productAverage' => $productRating?->getAverage(),
        ];
    }

    /**
     * @throws PropelException
     */
    public function getProductReviews(int $productId, ?string $order = null): array
    {
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
            default:
                $reviews->orderByReviewDate(Criteria::DESC);
        }
        $data = $reviews->findByProductId($productId);
        return $this->productReviewService->formatProductReviews($data);
    }

    /**
     * @throws PropelException
     */
    public function getSiteReviews(?int $limit = null): array
    {
        $reviews = GuaranteedOpinionSiteReviewQuery::create()
            ->orderByOrderDate(Criteria::DESC);
        if ($limit) {
            $reviews->setLimit($limit);
        }
        $data = $reviews->find();
        return $this->siteReviewService->formatSiteReviews($data);
    }
}
