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

namespace GuaranteedOpinion\Api\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use GuaranteedOpinion\Api\Resource\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Service\ReviewReader;

/**
 * One page of store reviews and their count: two queries whatever the page size.
 *
 * @implements ProviderInterface<GuaranteedOpinionSiteReview>
 */
final readonly class SiteReviewProvider implements ProviderInterface
{
    public function __construct(
        private ReviewReader $reviewReader,
        private RequestedLocale $requestedLocale,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<GuaranteedOpinionSiteReview>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $locale = $this->requestedLocale->fromContext($context);
        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $reviews = [];
        foreach ($this->reviewReader->siteReviews($locale, $offset, $limit) as $review) {
            $reviews[] = new GuaranteedOpinionSiteReview(
                id: (int) $review->getSiteReviewId(),
                locale: (string) $review->getLocale(),
                rate: (float) $review->getRate(),
                name: $review->getName(),
                message: $review->getReview(),
                reviewDate: $review->getReviewDate(),
                reply: $review->getReply(),
                replyDate: $review->getReplyDate(),
            );
        }

        return new TraversablePaginator(new \ArrayIterator($reviews), $page, $limit, $this->reviewReader->countSiteReviews($locale));
    }
}
