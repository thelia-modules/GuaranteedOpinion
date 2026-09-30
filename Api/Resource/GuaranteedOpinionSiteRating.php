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
use ApiPlatform\Metadata\Get;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Parameter;
use GuaranteedOpinion\Api\State\SiteRatingProvider;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Store rating of one language, as imported by module:GuaranteedOpinion:GetSiteReview, and the public page
 * listing every review.
 */
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/front/guaranteed_opinion/site_rating',
            uriVariables: [],
            openapi: new Operation(
                parameters: [
                    new Parameter(name: 'locale', in: 'query', required: false, schema: ['type' => 'string', 'example' => 'fr_FR']),
                ],
            ),
            provider: SiteRatingProvider::class,
        ),
    ],
    normalizationContext: ['groups' => [self::GROUP_FRONT_READ]],
)]
final class GuaranteedOpinionSiteRating
{
    public const GROUP_FRONT_READ = 'front:guaranteed_opinion_site_rating:read';

    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups([self::GROUP_FRONT_READ])]
        public string $locale,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?int $total,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?float $average,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $reviewsUrl,
        /** Widget code of the configuration page, HTML written by the shop administrator. */
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $widget = null,
        #[Groups([self::GROUP_FRONT_READ])]
        public ?string $widgetIframe = null,
    ) {
    }
}
