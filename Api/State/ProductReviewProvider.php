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
use GuaranteedOpinion\Api\Resource\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Service\ReviewReader;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * One page of reviews and their count: two queries whatever the page size.
 *
 * @implements ProviderInterface<GuaranteedOpinionProductReview>
 */
final readonly class ProductReviewProvider implements ProviderInterface
{
    public function __construct(
        private ReviewReader $reviewReader,
        private RequestedLocale $requestedLocale,
        private Pagination $pagination,
    ) {
    }

    /**
     * @return TraversablePaginator<GuaranteedOpinionProductReview>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $productId = $context['filters']['productId'] ?? null;

        if (!is_numeric($productId) || (int) $productId <= 0) {
            throw new BadRequestHttpException('The productId parameter is required');
        }

        $productId = (int) $productId;
        $locale = $this->requestedLocale->fromContext($context);
        [$page, $offset, $limit] = $this->pagination->getPagination($operation, $context);

        $reviews = [];
        foreach ($this->reviewReader->productReviews($productId, $locale, $offset, $limit) as $review) {
            $reviews[] = new GuaranteedOpinionProductReview(
                id: (string) $review->getProductReviewId(),
                productId: (int) $review->getProductId(),
                locale: (string) $review->getLocale(),
                rate: (float) $review->getRate(),
                name: $review->getName(),
                message: $review->getReview(),
                reviewDate: $review->getReviewDate(),
                reply: $review->getReply(),
                replyDate: $review->getReplyDate(),
            );
        }

        return new TraversablePaginator(
            new \ArrayIterator($reviews),
            $page,
            $limit,
            $this->reviewReader->countProductReviews($productId, $locale),
        );
    }
}
