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
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Response;

#[Route(path: "/guaranteed_opinion", name: "guaranteed_opinion")]
class FrontController extends BaseFrontController
{
  /**
   * @throws PropelException
   */
  #[Route(path: "/site_reviews/offset/{offset}/limit/{limit}", name: "site_reviews", methods: "GET")]
  public function siteReviews(int $offset, int $limit, Request $request): JsonResponse|Response
  {
    $reviews = [];

    $locale = $request->getSession()->getLang()->getLocale();

    $siteReviews = GuaranteedOpinionSiteReviewQuery::create()
      ->filterByLocale($locale)
      ->setLimit($limit)
      ->setOffset($offset)
      ->find();

    foreach ($siteReviews as $review) {
      $reviews[] = [
        'rate' => $review->getRate(),
        'name' => $review->getName(),
        'date' => $review->getReviewDate()?->format('Y-m-d'),
        'message' => $review->getReview()
      ];
    }

    $responseData = [
      'total' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY),
      'average' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY),
      'reviews' => $reviews
    ];

    if ($request->headers->get('Accept') === 'text/html') {
      $response = $this->render('includes/next-site-reviews', $responseData, count($reviews) > 0 ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);

      $response->headers->set('X-Remaining-Reviews', $responseData["total"] - $offset - $limit);

      return $response;
    }

    return new JsonResponse($responseData);
  }

  /**
   * @throws PropelException
   */
  #[Route(path: "/product_reviews/{id}/offset/{offset}/limit/{limit}", name: "product_reviews", methods: "GET")]
  public function productReviews(int $id, int $offset, int $limit, Request $request): JsonResponse|Response
  {
    $reviews = [];
    $locale = $request->getSession()->getLang()->getLocale();

    $productRating = GuaranteedOpinionProductRatingQuery::create()
      ->findOneByProductId($id);

    $productReviews = GuaranteedOpinionProductReviewQuery::create()
      ->filterByProductId($id)
      ->filterByLocale($locale)
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

    $responseData = [
      'total' => $productRating?->getTotal(),
      'average' => $productRating?->getAverage(),
      'reviews' => $reviews
    ];

    if ($request->headers->get('Accept') === 'text/html') {
      $response = $this->render('includes/next-product-reviews', $responseData, count($reviews) > 0 ? Response::HTTP_OK : Response::HTTP_NO_CONTENT);

      $response->headers->set('X-Remaining-Reviews', $responseData["total"] - $offset - $limit);

      return $response;
    }

    return new JsonResponse($responseData);
  }
}
