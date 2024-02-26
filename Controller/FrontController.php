<?php

namespace GuaranteedOpinion\Controller;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;

#[Route(path: "/guaranteed_opinion", name: "guaranteed_opinion")]
class FrontController extends BaseFrontController
{
    /**
     * @throws PropelException
     */
    #[Route(path: "/site_reviews/offset/{offset}/limit/{limit}", name: "site_reviews", methods: "GET")]
    public function siteReviews(int $offset, int $limit): JsonResponse
    {
        $reviews = [];

        $siteReviews = GuaranteedOpinionSiteReviewQuery::create()
            ->setLimit($limit)
            ->setOffset($offset)
            ->find()
        ;

        foreach ($siteReviews as $review) {
            $reviews[] = [
                'rate' => $review->getRate(),
                'name' => $review->getName(),
                'date' => $review->getReviewDate()?->format('Y-m-d'),
                'message' => $review->getReview()
            ];
        }

        return new JsonResponse([
            'total' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY),
            'average' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY),
            'reviews' => $reviews
        ]);
    }

    /**
     * @throws PropelException
     */
    #[Route(path: "/product_reviews/{id}/offset/{offset}/limit/{limit}", name: "product_reviews", methods: "GET")]
    public function productReviews(int $id, int $offset, int $limit): JsonResponse
    {
        $reviews = [];

        $productRating = GuaranteedOpinionProductRatingQuery::create()
            ->findOneByProductId($id);

        $productReviews = GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($id)
            ->setLimit($limit)
            ->setOffset($offset)
            ->find();

        foreach ($productReviews as $review) {
            $reviews[] = [
                'rate' => $review->getRate(),
                'name' => $review->getName(),
                'date' => $review->getReviewDate()?->format('Y-m-d'),
                'message' => $review->getReview()
            ];
        }

        return new JsonResponse([
            'total' => $productRating?->getTotal(),
            'average' => $productRating?->getAverage(),
            'reviews' => $reviews
        ]);
    }
}