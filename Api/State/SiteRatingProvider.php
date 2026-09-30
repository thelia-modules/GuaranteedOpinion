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
use GuaranteedOpinion\Api\Resource\GuaranteedOpinionSiteRating;
use GuaranteedOpinion\Service\ReviewReader;

/**
 * @implements ProviderInterface<GuaranteedOpinionSiteRating>
 */
final readonly class SiteRatingProvider implements ProviderInterface
{
    public function __construct(
        private ReviewReader $reviewReader,
        private RequestedLocale $requestedLocale,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GuaranteedOpinionSiteRating
    {
        $locale = $this->requestedLocale->fromContext($context);
        $rating = $this->reviewReader->siteRating($locale);

        return new GuaranteedOpinionSiteRating($locale, $rating['total'], $rating['average'], $rating['reviewsUrl'], $rating['widget'], $rating['widgetIframe']);
    }
}
