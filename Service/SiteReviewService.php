<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\Exception\PropelException;

class SiteReviewService
{
    public function addGuaranteedOpinionSiteReviews($siteReviews, string $locale): void
    {
        foreach ($siteReviews as $siteRow)
        {
            $this->addGuaranteedOpinionSiteRow($siteRow, $locale);
        }
    }

    public function addGuaranteedOpinionSiteRow(array $row, string $locale): bool
    {
        try {
            $review = GuaranteedOpinionSiteReviewQuery::create()
                ->findOneBySiteReviewId($row["id"]);

            if (null === $review) {
                $review = new GuaranteedOpinionSiteReview();
                $review
                    ->setSiteReviewId($row["id"])
                    ->setLocale($locale)
                    ->setName($row["c"])
                    ->setReview($row["txt"])
                    ->setReviewDate($row["date"])
                    ->setRate($row["r"])
                    ->setOrderDate($row["odate"])
                ;
                $review->save();
            }

            if ($row["reply"] !== "" && $row["rdate"] !== "") {
                $review
                    ->setReply($row["reply"])
                    ->setReplyDate($row["rdate"])
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
    public function formatSiteReviews($reviews): array
    {
        $tabReviews = [];

        /** @var GuaranteedOpinionSiteReview $review */
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
