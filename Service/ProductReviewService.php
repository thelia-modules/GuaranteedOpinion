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
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;

/**
 * Product reviews and ratings imported from the API, one language at a time: a review id may exist in several
 * languages, each language has its own rating.
 */
class ProductReviewService
{
    /**
     * @param array<string, mixed> $row a review of the API: id, c (name), txt, date, r (rate), odate, reply, rdate
     */
    public function addGuaranteedOpinionProductRow(array $row, int $productId, string $locale): bool
    {
        try {
            $review = GuaranteedOpinionProductReviewQuery::create()
                ->filterByLocale($locale)
                ->filterByProductReviewId((string) $row['id'])
                ->findOne();

            if (null === $review) {
                $review = new GuaranteedOpinionProductReview();
                $review
                    ->setProductReviewId((string) $row['id'])
                    ->setLocale($locale)
                    ->setName(ReviewText::nullable($row['c'] ?? null))
                    ->setReview(ReviewText::nullable($row['txt'] ?? null))
                    ->setReviewDate(ReviewText::nullable($row['date'] ?? null))
                    ->setRate((string) ($row['r'] ?? '0'))
                    ->setOrderDate(ReviewText::nullable($row['odate'] ?? null))
                    ->setProductId($productId);
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

    /**
     * Deletes the reviews of the product in this language that the API no longer returns. The other languages are
     * left as they are: the API answers for the account of one language.
     *
     * @param iterable<array<string, mixed>> $apiReviews
     *
     * @throws PropelException
     */
    public function removeDeletedReviews(int $productId, string $locale, iterable $apiReviews): int
    {
        $keptIds = [];

        foreach ($apiReviews as $apiReview) {
            $keptIds[] = (string) $apiReview['id'];
        }

        $query = GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($productId)
            ->filterByLocale($locale);

        if ([] !== $keptIds) {
            $query->filterByProductReviewId($keptIds, Criteria::NOT_IN);
        }

        return $query->delete();
    }

    /**
     * @param array<string, mixed> $ratings total and average of the API
     *
     * @throws PropelException
     */
    public function addGuaranteedOpinionProductRating(int $productId, array $ratings, string $locale): void
    {
        $productRating = GuaranteedOpinionProductRatingQuery::create()
            ->filterByProductId($productId)
            ->filterByLocale($locale)
            ->findOne() ?? new GuaranteedOpinionProductRating();

        $productRating
            ->setProductId($productId)
            ->setLocale($locale)
            ->setTotal((int) ($ratings['total'] ?? 0))
            ->setAverage((string) ($ratings['average'] ?? '0'))
            ->save();
    }
}
