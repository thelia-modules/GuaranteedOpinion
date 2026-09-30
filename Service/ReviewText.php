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

/**
 * A text field of the API: empty or not a scalar means no value.
 */
final class ReviewText
{
    public static function nullable(mixed $value): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        return \is_scalar($value) ? (string) $value : null;
    }
}
