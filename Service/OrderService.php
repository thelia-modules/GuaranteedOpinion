<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueue;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use JsonException;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderQuery;
use Thelia\Model\ProductSaleElementsQuery;

class OrderService
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @throws JsonException|RuntimeException|PropelException
     */
    public function prepareOrderRequest(): string
    {
        $jsonOrder = [];

        $guaranteedOpinionOrders = GuaranteedOpinionOrderQueueQuery::create()->find();

        foreach ($guaranteedOpinionOrders as $guaranteedOpinionOrder) {
            $jsonOrder[] = $this->orderToJsonObject($guaranteedOpinionOrder);
        }

        if (empty($jsonOrder)) {
            throw new RuntimeException('No Order found');
        }

        return json_encode($jsonOrder, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws PropelException
     */
    private function orderToJsonObject(GuaranteedOpinionOrderQueue $guaranteedOpinionOrder): array
    {
        $order = OrderQuery::create()->findOneById($guaranteedOpinionOrder->getOrderId());

        $jsonProduct = [];

        foreach ($order?->getOrderProducts() as $orderProduct) {
            $jsonProduct[] = $this->productToJsonObject($orderProduct);
        }

        return [
            'id_order' => $order?->getId(),
            'order_date' => $order?->getCreatedAt()?->format('Y-m-d H:i:s'),
            'firstname' => $order?->getCustomer()->getFirstname(),
            'lastname' => $order?->getCustomer()->getLastname(),
            'email' => $order?->getCustomer()->getEmail(),
            'reference' => $order?->getRef(),
            'products' => $jsonProduct
        ];
    }

    /**
     * @throws PropelException
     */
    private function productToJsonObject(OrderProduct $orderProduct): array
    {
        if (null === $pse = ProductSaleElementsQuery::create()->findOneById($orderProduct->getProductSaleElementsId())) {
            return [];
        }

        $category  = GuaranteedOpinionOrderQueueQuery::getCategoryByProductSaleElements($pse);

        return [
            'id' => $pse?->getProductId(),
            'name' => $pse?->getProduct()->getRef(),
            'category_id' => $category->getId(),
            'category_name' => $category->getTitle(),
            'qty' => $orderProduct->getQuantity(),
            'unit_price' => $orderProduct->getPrice(),
            'mpn' => null,
            'ean13' => $pse?->getEanCode(),
            'sku' => null,
            'upc' => null,
            'url' => GuaranteedOpinion::STORE_URL_CONFIG_KEY . '/'.
                GuaranteedOpinionOrderQueueQuery::getProductUrl($pse?->getProductId())->getUrl(),
        ];
    }

    /**
     * @throws PropelException
     */
    public function clearOrderQueueTable(): void
    {
        $orders = GuaranteedOpinionOrderQueueQuery::create()->find();

        foreach ($orders as $order) {
            $order->delete();
        }
    }
}