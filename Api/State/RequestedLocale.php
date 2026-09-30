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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Thelia\Domain\Localization\Service\LangService;

/**
 * The language of a review request: the `locale` query parameter (always given by the theme's resources()), else
 * the language of the visitor, else the default language of the shop.
 */
final readonly class RequestedLocale
{
    public function __construct(
        private LangService $langService,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function fromContext(array $context): string
    {
        $locale = $context['filters']['locale'] ?? null;

        if (null === $locale || '' === $locale) {
            return (string) $this->langService->getLocale();
        }

        if (!\is_string($locale) || 1 !== preg_match('/^[a-z]{2}_[A-Z]{2}$/', $locale)) {
            throw new BadRequestHttpException('The locale parameter must look like fr_FR');
        }

        return $locale;
    }
}
