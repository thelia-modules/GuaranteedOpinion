<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRating;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use Propel\Runtime\Exception\PropelException;

class ProductReviewService
{
    public function addGuaranteedOpinionProductReviews(array $productReviews, int $productId, string $locale): void
    {
        foreach ($productReviews as $productRow)
        {
            $this->addGuaranteedOpinionProductRow($productRow, $productId, $locale);
        }
    }

    public function addGuaranteedOpinionProductRow($row, int $productId, string $locale): bool
    {
        try {
            $review = GuaranteedOpinionProductReviewQuery::create()
                ->findOneByProductReviewId($row['id']);

            if (null === $review) {
                $review = new GuaranteedOpinionProductReview();
                $review
                    ->setProductReviewId($row['id'])
                    ->setLocale($locale)
                    ->setName($row['c'])
                    ->setReview($row['txt'])
                    ->setReviewDate($row['date'])
                    ->setRate($row['r'])
                    ->setOrderDate($row['odate'])
                    ->setProductId($productId)
                ;
                $review->save();
            }

            if ($row['reply'] !== "" && $row['rdate'] !== "") {
                $review
                    ->setReply($row['reply'])
                    ->setReplyDate($row['rdate'])
                ;
                $review->save();
            }

        } catch (PropelException $e) {
            GuaranteedOpinion::log($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * @throws PropelException
     */
    public function deleteReview(int $reviewId): void
    {
        $reviewData = GuaranteedOpinionProductReviewQuery::create()->findOneByProductReviewId($reviewId);

        $reviewData?->delete();
    }

    /**
     * @param string $xml
     * @return array
     */
    public function xmlToArray(string $xml): array
    {
        $result = [];
        $this->normalizeSimpleXML(simplexml_load_string($xml, null, LIBXML_NOCDATA), $result);
        return $result;
    }

    protected function normalizeSimpleXML($obj, &$result): void
    {
        $data = $obj;
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $res = null;
                $this->normalizeSimpleXML($value, $res);
                if (($key === '@attributes') && ($key)) {
                    $result = $res;
                } else {
                    $result[$key] = $res;
                }
            }
        } else {
            $result = $data;
        }
    }

    /**
     * @throws PropelException
     */
    public function addGuaranteedOpinionProductRating(int $productId, array $ratings): void
    {
        if (null === $productRating = GuaranteedOpinionProductRatingQuery::create()->findOneByProductId($productId)) {
            $productRating = new GuaranteedOpinionProductRating();
        }

        $productRating
            ->setProductId($productId)
            ->setTotal($ratings['total'])
            ->setAverage($ratings['average'])
            ->save();
    }

    public function formatProductReviews($reviews): array
    {
        $tabReviews = [];

        /** @var GuaranteedOpinionProductReview $review */
        foreach ($reviews as $key => $review) {
            $tabReviews[$key]['rate'] = $review->getRate();
            $tabReviews[$key]['review_date'] = $review->getReviewDate();
            $tabReviews[$key]['name'] = $review->getName();
            $tabReviews[$key]['order_date'] = $review->getOrderDate();
            $tabReviews[$key]['review'] = $review->getReview();
        }

        return $tabReviews;
    }
}
