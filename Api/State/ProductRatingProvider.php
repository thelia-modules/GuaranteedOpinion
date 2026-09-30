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
use ApiPlatform\State\ProviderInterface;
use GuaranteedOpinion\Api\Resource\GuaranteedOpinionProductRating;
use GuaranteedOpinion\Service\ReviewReader;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Ratings of the products asked for, in one query.
 *
 * @implements ProviderInterface<GuaranteedOpinionProductRating>
 */
final readonly class ProductRatingProvider implements ProviderInterface
{
    public function __construct(
        private ReviewReader $reviewReader,
        private RequestedLocale $requestedLocale,
    ) {
    }

    /**
     * @return list<GuaranteedOpinionProductRating>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $requested = $context['filters']['productId'] ?? [];
        $requested = \is_array($requested) ? $requested : [$requested];

        if ([] === $requested) {
            throw new BadRequestHttpException('The productId[] parameter is required');
        }

        $productIds = [];

        foreach ($requested as $productId) {
            if (!is_numeric($productId)) {
                throw new BadRequestHttpException('productId[] only takes product ids');
            }

            $productIds[] = (int) $productId;
        }

        if (\count($productIds) > ReviewReader::MAXIMUM_PRODUCTS) {
            throw new BadRequestHttpException(\sprintf('At most %d products at once', ReviewReader::MAXIMUM_PRODUCTS));
        }

        $locale = $this->requestedLocale->fromContext($context);
        $ratings = [];

        foreach ($this->reviewReader->productRatings($productIds, $locale) as $productId => $rating) {
            $ratings[] = new GuaranteedOpinionProductRating(
                productId: $productId,
                locale: $locale,
                total: (int) $rating->getTotal(),
                average: (float) $rating->getAverage(),
            );
        }

        return $ratings;
    }
}
