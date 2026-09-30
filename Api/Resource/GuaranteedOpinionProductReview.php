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
use GuaranteedOpinion\Api\State\ProductReviewProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Reviews of one product in one language, newest first. Public fields only: never the order nor the e-mail.
 */
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/front/guaranteed_opinion/product_reviews',
            openapi: new Operation(
                parameters: [
                    new Parameter(name: 'productId', in: 'query', required: true, schema: ['type' => 'integer']),
                    new Parameter(name: 'locale', in: 'query', required: false, schema: ['type' => 'string', 'example' => 'fr_FR']),
                ],
            ),
            paginationItemsPerPage: 10,
            paginationMaximumItemsPerPage: 50,
            paginationClientItemsPerPage: true,
            provider: ProductReviewProvider::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_FRONT_READ]],
)]
final class GuaranteedOpinionProductReview
{
    public const GROUP_FRONT_READ = 'front:guaranteed_opinion_product_review:read';

    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups([self::GROUP_FRONT_READ])]
        public string $id,
        #[Groups([self::GROUP_FRONT_READ])]
        public int $productId,
        #[Groups([self::GROUP_FRONT_READ])]
        public string $locale,
        #[Groups([self::GROUP_FRONT_READ])]
        public float $rate,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $name,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $message,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?\DateTimeInterface $reviewDate,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $reply,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?\DateTimeInterface $replyDate,
    ) {
    }
}
