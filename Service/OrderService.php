<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueue;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
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
     * @throws \JsonException
     */
    public function prepareOrderRequest()
    {
        $guaranteedOpinionOrders = GuaranteedOpinionOrderQueueQuery::create()->find();

        if ($guaranteedOpinionOrders === null)
        {
            return;
        }

        $jsonOrder = [];

        foreach ($guaranteedOpinionOrders as $guaranteedOpinionOrder) {
            $jsonOrder[] = $this->orderToJsonObject($guaranteedOpinionOrder);
        }

        return json_encode($jsonOrder, JSON_THROW_ON_ERROR);
    }

    private function orderToJsonObject(GuaranteedOpinionOrderQueue $guaranteedOpinionOrder): array
    {
        $order = OrderQuery::create()->findOneById($guaranteedOpinionOrder->getOrderId());

        $jsonProduct = [];

        foreach ($order->getOrderProducts() as $orderProduct) {
            $jsonProduct[] = $this->productToJsonObject($orderProduct);
        }

        return [
            'id_order' => $order->getId(),
            'order_date' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'firstname' => $order->getCustomer()->getFirstname(),
            'lastname' => $order->getCustomer()->getLastname(),
            'email' => $order->getCustomer()->getEmail(),
            'reference' => $order->getRef(),
            'products' => $jsonProduct
        ];
    }

    private function productToJsonObject(OrderProduct $orderProduct): array
    {
        $pse = ProductSaleElementsQuery::create()->findOneById($orderProduct->getProductSaleElementsId());

        $category  = GuaranteedOpinionOrderQueueQuery::getCategoryByProductSaleElements($pse);

        return [
            'id' => $pse->getProductId(),
            'name' => $pse->getProduct()->getRef(),
            'category_id' => $category->getId(),
            'category_name' => $category->getTitle(),
            'qty' => $orderProduct->getQuantity(),
            'unit_price' => $orderProduct->getPrice(),
            'mpn' => null,
            'ean13' => $pse->getEanCode(),
            'sku' => null,
            'upc' => null,
            'url' => GuaranteedOpinion::STORE_URL . '/'.
                GuaranteedOpinionOrderQueueQuery::getProductUrl($pse->getProductId())->getUrl(),
        ];
    }

    public function clearOrderQueueTable(): void
    {
        $orders = GuaranteedOpinionOrderQueueQuery::create()->find();

        foreach ($orders as $order) {
            $order->delete();
        }
    }
}