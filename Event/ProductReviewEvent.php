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

namespace GuaranteedOpinion\Event;

use Thelia\Core\Event\ActionEvent;
use Thelia\Model\Product;

/**
 * Lets a listener replace the product id sent to Guaranteed Reviews (by default the Thelia product id).
 */
class ProductReviewEvent extends ActionEvent
{
    private ?Product $product;

    private string $guaranteedOpinionProductId;

    public function __construct(?Product $product = null)
    {
        $this->product = $product;
        $this->guaranteedOpinionProductId = (string) $product?->getId();
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getGuaranteedOpinionProductId(): string
    {
        return $this->guaranteedOpinionProductId;
    }

    public function setGuaranteedOpinionProductId(string $guaranteedOpinionProductId): self
    {
        $this->guaranteedOpinionProductId = $guaranteedOpinionProductId;

        return $this;
    }
}
