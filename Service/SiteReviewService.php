<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Exception\PropelException;

class SiteReviewService
{
    public function addGuaranteedOpinionSiteReviews($siteReviews): void
    {
        foreach ($siteReviews as $siteRow)
        {
            $this->addGuaranteedOpinionSiteRow($siteRow);
        }
    }

    /**
     * @param $row
     */
    public function addGuaranteedOpinionSiteRow($row): void
    {
        $review = GuaranteedOpinionSiteReviewQuery::create()
            ->findOneBySiteReviewId($row->id);

        if (null !== $review) {
            return;
        }

        $review = new GuaranteedOpinionSiteReview();

        try {
            $review
                ->setSiteReviewId($row->id)
                ->setName($row->c)
                ->setReview($row->txt)
                ->setReviewDate($row->date)
                ->setRate($row->r)
                ->setOrderId($row->o)
                ->setOrderDate($row->odate)
            ;

            if ($row->reply !== "" && $row->rdate !== "") {
                $review
                    ->setReply($row->reply)
                    ->setReplyDate($row->rdate)
                ;
            }

            $review->save();

        } catch (PropelException $e) {
            GuaranteedOpinion::log($e->getMessage());
        }
    }

    /**
     * @throws PropelException
     */
    public function deleteNetreviewsSiteRow($row): void
    {
        $review = GuaranteedOpinionSiteReviewQuery::create()
            ->findOneBySiteReviewId($row->id);

        $review?->delete();
    }

    public function updateNetreviewsSiteRow($row): void
    {
        $review = GuaranteedOpinionSiteReviewQuery::create()
            ->findOneBySiteReviewId($row->id);

        if (null === $review) {
            return;
        }

        try {
            $review
                ->setSiteReviewId($row->id)
                ->setName($row->c)
                ->setReview($row->txt)
                ->setReviewDate($row->date)
                ->setRate($row->r)
                ->setOrderId($row->o)
                ->setOrderDate($row->odate)
                ->setReply($row->reply)
                ->setReplyDate($row->rdate)
                ->save();

        } catch (PropelException $e) {
            GuaranteedOpinion::log($e);
        }
    }

    public function calculateSiteRate(): float
    {
        $reviewsRate = GuaranteedOpinionSiteReviewQuery::create()->find()->getData();

        $averageRate = 0;
        $countRate = count($reviewsRate);

        /** @var GuaranteedOpinionSiteReviewQuery $reviewRate */
        foreach ($reviewsRate as $reviewRate) {
            $averageRate += $reviewRate->getRate();
        }

        $averageRate = round($averageRate / $countRate, 2);

        return round($averageRate *= 2, 1);
    }

    public function getRows($limit = null): array|ObjectCollection
    {
        $reviews = GuaranteedOpinionSiteReviewQuery::create()->orderByReviewDate(Criteria::DESC);

        if ($limit) {
            $reviews->setLimit($limit);
        }

        return $reviews->find();
    }

    /**
     * @throws \JsonException
     */
    public function readRate(): ?bool
    {
        $fileRateJson = __DIR__ . "/../Commands/rate.json";

        if(file_exists($fileRateJson)){
            if ($handle = file_get_contents($fileRateJson)) {
                return json_decode($handle, false, 512, JSON_THROW_ON_ERROR)->rate_site;
            }

            return false;
        }

        return false;
    }
}