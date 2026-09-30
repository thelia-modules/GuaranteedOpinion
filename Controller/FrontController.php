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

namespace GuaranteedOpinion\Controller;

use GuaranteedOpinion\Model\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Service\ReviewReader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Lang;

/**
 * JSON pages of reviews in the language of the visitor ("next reviews" buttons). The theme reads the API resources
 * of the module ({@see \GuaranteedOpinion\Api\Resource\GuaranteedOpinionProductReview}) for everything else.
 */
#[Route(path: '/guaranteed_opinion', name: 'guaranteed_opinion')]
class FrontController extends BaseFrontController
{
    private const MAXIMUM_LIMIT = 50;

    #[Route(path: '/site_reviews/offset/{offset}/limit/{limit}', name: 'site_reviews', requirements: ['offset' => '\d+', 'limit' => '\d+'], methods: 'GET')]
    public function siteReviews(int $offset, int $limit, Request $request, ReviewReader $reviewReader): JsonResponse
    {
        $locale = $this->visitorLocale($request);
        $rating = $reviewReader->siteRating($locale);

        return new JsonResponse([
            'total' => $rating['total'],
            'average' => $rating['average'],
            'reviews' => array_map(
                self::reviewToArray(...),
                $reviewReader->siteReviews($locale, $offset, min($limit, self::MAXIMUM_LIMIT)),
            ),
        ]);
    }

    #[Route(path: '/product_reviews/{id}/offset/{offset}/limit/{limit}', name: 'product_reviews', requirements: ['id' => '\d+', 'offset' => '\d+', 'limit' => '\d+'], methods: 'GET')]
    public function productReviews(int $id, int $offset, int $limit, Request $request, ReviewReader $reviewReader): JsonResponse
    {
        $locale = $this->visitorLocale($request);
        $rating = $reviewReader->productRatings([$id], $locale)[$id] ?? null;

        return new JsonResponse([
            'total' => $rating?->getTotal(),
            'average' => $rating?->getAverage(),
            'reviews' => array_map(
                self::reviewToArray(...),
                $reviewReader->productReviews($id, $locale, $offset, min($limit, self::MAXIMUM_LIMIT)),
            ),
        ]);
    }

    /**
     * @return array{rate: float, name: string|null, date: string|null, message: string|null}
     */
    private static function reviewToArray(GuaranteedOpinionProductReview|GuaranteedOpinionSiteReview $review): array
    {
        return [
            'rate' => (float) $review->getRate(),
            'name' => $review->getName(),
            'date' => $review->getReviewDate()?->format('Y-m-d'),
            'message' => $review->getReview(),
        ];
    }

    private function visitorLocale(Request $request): string
    {
        $lang = $request->hasSession() ? $request->getSession()->getLang() : null;

        return ($lang ?? Lang::getDefaultLanguage())->getLocale();
    }
}
