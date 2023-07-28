<?php

namespace GuaranteedOpinion\Service;

class SiteReviewManager
{
    /**
     * @param $row
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function addNetreviewsSiteRow($row)
    {
        $review = NetreviewsSiteReviewQuery::create()
            ->findOneByReviewId($row->id_review);

        if (null !== $review) {
            return;
        }

        $review = new NetreviewsSiteReview();

        try {
            $review
                ->setReviewId($row->id_review)
                ->setLastname($row->lastname)
                ->setFirstname($row->firstname)
                ->setReview($row->review)
                ->setReviewDate($row->review_date)
                ->setRate($row->rate)
                ->setOrderRef($row->order_ref)
                ->setOrderDate($row->order_date)
                ->save();

        } catch (PropelException $e) {
            NetReviews::log($e);
        }
    }

    public function deleteNetreviewsSiteRow($row)
    {
        $review = NetreviewsSiteReviewQuery::create()
            ->findOneByReviewId($row->id_review);

        if (null !== $review) {
            $review->delete();
        }
    }

    public function updateNetreviewsSiteRow($row)
    {
        $review = NetreviewsSiteReviewQuery::create()
            ->findOneByReviewId($row->id_review);

        if (null === $review) {
            return;
        }

        try {
            $review
                ->setReviewId($row->id_review)
                ->setLastname($row->lastname)
                ->setFirstname($row->firstname)
                ->setReview($row->review)
                ->setReviewDate($row->review_date)
                ->setRate($row->rate)
                ->setOrderRef($row->order_ref)
                ->setOrderDate($row->order_date)
                ->save();

        } catch (PropelException $e) {
            NetReviews::log($e);
        }
    }

    public function calculateSiteRate()
    {
        $reviewsRate = NetreviewsSiteReviewQuery::create()->find()->getData();

        $averageRate = 0;
        $countRate = count($reviewsRate);

        /** @var NetreviewsSiteReviewQuery $reviewRate */
        foreach ($reviewsRate as $reviewRate) {
            $averageRate += $reviewRate->getRate();
        }

        $averageRate = round($averageRate / $countRate, 2);

        return round($averageRate = $averageRate * 2, 1);
    }

    public function getRows($limit = null)
    {
        $reviews = NetreviewsSiteReviewQuery::create()->orderByReviewDate(Criteria::DESC);
        if ($limit) {
            $reviews->setLimit($limit);
        }
        return $reviews->find();
    }
    public function readRate()
    {
        $fileRateJson = __DIR__ . "/../Command/rate.json";

        if(file_exists($fileRateJson)){
            if ($handle = file_get_contents($fileRateJson)) {
                $jsonRate = json_decode($handle);
                return $jsonRate->rate_site;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}