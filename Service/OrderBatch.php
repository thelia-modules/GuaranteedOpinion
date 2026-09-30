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
 * The orders of one language ready to be sent, with the queue rows they come from: only those rows are marked as
 * sent once the API has accepted them.
 */
final readonly class OrderBatch
{
    /**
     * @param list<array<string, mixed>> $orders
     * @param list<int>                  $queueIds
     */
    public function __construct(
        public string $locale,
        public array $orders,
        public array $queueIds,
    ) {
    }

    public function isEmpty(): bool
    {
        return [] === $this->orders;
    }

    /**
     * @throws \JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->orders, \JSON_THROW_ON_ERROR);
    }
}
