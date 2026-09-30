<?php

declare(strict_types=1);

/*
 * This file is part of the Thelia package.
 * http://www.thelia.net
 *
 * (c) OpenStudio <info@thelia.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRating;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;

/**
 * Reviews and ratings of one language, newest first. Every method runs a single query, whatever the number of
 * products asked for.
 */
final readonly class ReviewReader
{
    public const MAXIMUM_PRODUCTS = 100;

    /**
     * @return list<GuaranteedOpinionProductReview>
     */
    public function productReviews(int $productId, string $locale, int $offset, int $limit): array
    {
        return array_values(iterator_to_array(
            GuaranteedOpinionProductReviewQuery::create()
                ->filterByProductId($productId)
                ->filterByLocale($locale)
                ->orderByReviewDate(Criteria::DESC)
                ->orderByProductReviewId(Criteria::DESC)
                ->offset($offset)
                ->limit($limit)
                ->find(),
        ));
    }

    public function countProductReviews(int $productId, string $locale): int
    {
        return GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($productId)
            ->filterByLocale($locale)
            ->count();
    }

    /**
     * @param list<int> $productIds
     *
     * @return array<int, GuaranteedOpinionProductRating> product id => rating, products without a rating left out
     */
    public function productRatings(array $productIds, string $locale): array
    {
        $productIds = array_values(array_unique(array_filter($productIds, static fn (int $productId): bool => $productId > 0)));

        if ([] === $productIds) {
            return [];
        }

        if (\count($productIds) > self::MAXIMUM_PRODUCTS) {
            throw new \InvalidArgumentException(\sprintf('At most %d products at once', self::MAXIMUM_PRODUCTS));
        }

        $ratings = [];

        foreach (GuaranteedOpinionProductRatingQuery::create()->filterByProductId($productIds, Criteria::IN)->filterByLocale($locale)->find() as $rating) {
            $ratings[(int) $rating->getProductId()] = $rating;
        }

        return $ratings;
    }

    /**
     * @return list<GuaranteedOpinionSiteReview>
     */
    public function siteReviews(string $locale, int $offset, int $limit): array
    {
        return array_values(iterator_to_array(
            GuaranteedOpinionSiteReviewQuery::create()
                ->filterByLocale($locale)
                ->orderByReviewDate(Criteria::DESC)
                ->orderBySiteReviewId(Criteria::DESC)
                ->offset($offset)
                ->limit($limit)
                ->find(),
        ));
    }

    public function countSiteReviews(string $locale): int
    {
        return GuaranteedOpinionSiteReviewQuery::create()->filterByLocale($locale)->count();
    }

    /**
     * @return array{total: int|null, average: float|null, reviewsUrl: string|null, widget: string|null, widgetIframe: string|null}
     */
    public function siteRating(string $locale): array
    {
        $total = GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY, null, $locale);
        $average = GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY, null, $locale);
        $reviewsUrl = GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY, null, $locale);

        return [
            'total' => is_numeric($total) ? (int) $total : null,
            'average' => is_numeric($average) ? (float) $average : null,
            'reviewsUrl' => null !== $reviewsUrl && '' !== $reviewsUrl ? $reviewsUrl : (GuaranteedOpinion::MAPPING_DEFAULT_URL[$locale] ?? null),
            'widget' => $this->widgetCode(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY),
            'widgetIframe' => $this->widgetCode(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY),
        ];
    }

    /**
     * The configuration page saves the code escaped.
     */
    private function widgetCode(string $configKey): ?string
    {
        $code = trim(htmlspecialchars_decode((string) GuaranteedOpinion::getConfigValue($configKey)));

        return '' === $code ? null : $code;
    }
}
