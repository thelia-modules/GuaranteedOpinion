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

namespace GuaranteedOpinion\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use GuaranteedOpinion\Api\State\ProductRatingProvider;
use GuaranteedOpinion\Service\ReviewReader;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Rating of several products in one language, read in one query for a whole grid of product cards
 * (at most {@see ReviewReader::MAXIMUM_PRODUCTS}). Products without a rating are left out.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/front/guaranteed_opinion/product_ratings',
            openapi: new Operation(
                parameters: [
                    new Parameter(name: 'productId[]', in: 'query', required: true, schema: ['type' => 'array', 'items' => ['type' => 'integer']]),
                    new Parameter(name: 'locale', in: 'query', required: false, schema: ['type' => 'string', 'example' => 'fr_FR']),
                ],
            ),
            paginationEnabled: false,
            provider: ProductRatingProvider::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_FRONT_READ]],
)]
final class GuaranteedOpinionProductRating
{
    public const GROUP_FRONT_READ = 'front:guaranteed_opinion_product_rating:read';

    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups([self::GROUP_FRONT_READ])]
        public int $productId,
        #[Groups([self::GROUP_FRONT_READ])]
        public string $locale,
        #[Groups([self::GROUP_FRONT_READ])]
        public int $total,
        #[Groups([self::GROUP_FRONT_READ])]
        public float $average,
    ) {
    }
}
