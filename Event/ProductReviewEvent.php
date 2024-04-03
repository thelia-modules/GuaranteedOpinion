<?php

namespace GuaranteedOpinion\Event;

use Thelia\Core\Event\ActionEvent;
use Thelia\Model\Product;

class ProductReviewEvent extends ActionEvent
{
    private ?Product $product;

    private ?string $guaranteedOpinionProductId;

    public function __construct($product = null)
    {
        $this->product = $product;
        $this->guaranteedOpinionProductId = $product->getId();
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @param Product|null $product
     * @return ProductReviewEvent
     */
    public function setProduct(?Product $product): ProductReviewEvent
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return string
     */
    public function getGuaranteedOpinionProductId(): string
    {
        return $this->guaranteedOpinionProductId;
    }

    /**
     * @param string $guaranteedOpinionProductId
     * @return ProductReviewEvent
     */
    public function setGuaranteedOpinionProductId(string $guaranteedOpinionProductId): ProductReviewEvent
    {
        $this->guaranteedOpinionProductId = $guaranteedOpinionProductId;

        return $this;
    }
}
