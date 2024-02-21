<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
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
     * @return bool
     */
    public function addGuaranteedOpinionSiteRow($row): bool
    {
        $review = GuaranteedOpinionSiteReviewQuery::create()
            ->findOneBySiteReviewId($row["id"]);

        if (null !== $review) {
            return false;
        }

        try {
            $review = new GuaranteedOpinionSiteReview();

            $review
                ->setSiteReviewId($row["id"])
                ->setName($row["c"])
                ->setReview($row["txt"])
                ->setReviewDate($row["date"])
                ->setRate($row["r"])
                ->setOrderId($row["o"])
                ->setOrderDate($row["odate"])
            ;

            if ($row["reply"] !== "" && $row["rdate"] !== "") {
                $review
                    ->setReply($row["reply"])
                    ->setReplyDate($row["rdate"])
                ;
            }

            $review->save();

        } catch (PropelException $e) {
            GuaranteedOpinion::log($e->getMessage());
            return false;
        }

        return true;
    }
}