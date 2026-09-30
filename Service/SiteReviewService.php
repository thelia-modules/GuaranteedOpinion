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
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\Exception\PropelException;

/**
 * Store reviews imported from the API, one language at a time.
 */
class SiteReviewService
{
    /**
     * @param array<string, mixed> $row a review of the API: id, c (name), txt, date, r (rate), odate, reply, rdate
     */
    public function addGuaranteedOpinionSiteRow(array $row, string $locale): bool
    {
        try {
            $review = GuaranteedOpinionSiteReviewQuery::create()
                ->filterByLocale($locale)
                ->filterBySiteReviewId((int) $row['id'])
                ->findOne();

            if (null === $review) {
                $review = new GuaranteedOpinionSiteReview();
                $review
                    ->setSiteReviewId((int) $row['id'])
                    ->setLocale($locale)
                    ->setName(ReviewText::nullable($row['c'] ?? null))
                    ->setReview(ReviewText::nullable($row['txt'] ?? null))
                    ->setReviewDate(ReviewText::nullable($row['date'] ?? null))
                    ->setRate((string) ($row['r'] ?? '0'))
                    ->setOrderDate(ReviewText::nullable($row['odate'] ?? null));
                $review->save();
            }

            $reply = ReviewText::nullable($row['reply'] ?? null);
            $replyDate = ReviewText::nullable($row['rdate'] ?? null);

            if (null !== $reply && null !== $replyDate) {
                $review
                    ->setReply($reply)
                    ->setReplyDate($replyDate);
                $review->save();
            }
        } catch (PropelException $e) {
            GuaranteedOpinion::log($e->getMessage());

            return false;
        }

        return true;
    }
}
