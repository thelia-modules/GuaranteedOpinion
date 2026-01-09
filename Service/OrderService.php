<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\Event\GuaranteedOpinionEvents;
use GuaranteedOpinion\Event\ProductReviewEvent;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use JsonException;
use Propel\Runtime\Exception\PropelException;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\LangQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Tools\URL;

class OrderService
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * @throws JsonException|RuntimeException|PropelException
     */
    public function prepareOrderRequest(string $locale): string
    {
        $jsonOrder = [];
        $lang = LangQuery::create()->findOneByLocale($locale);

        $guaranteedOpinionOrders = GuaranteedOpinionOrderQueueQuery::create()->filterByTreatedAt(null)->findByStatus(0);

        foreach ($guaranteedOpinionOrders as $guaranteedOpinionOrder) {
            if ($order = OrderQuery::create()->filterByLangId($lang->getId())->findOneById($guaranteedOpinionOrder->getOrderId())) {
                $jsonOrder[] = $this->orderToJsonObject($order, $locale);
            }
        }

        if (empty($jsonOrder)) {
            throw new RuntimeException('No Order found');
        }

        return json_encode($jsonOrder, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws PropelException
     */
    private function orderToJsonObject(Order $order, string $locale): array
    {
        $jsonProduct = [];

        foreach ($order->getOrderProducts() as $orderProduct) {
            $jsonProduct[] = $this->productToJsonObject($orderProduct, $locale);
        }

        return [
            'id_order' => $order->getId(),
            'order_date' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'firstname' => $order->getCustomer()->getFirstname(),
            'lastname' => $order->getCustomer()->getLastname(),
            'email' => $order->getCustomer()->getEmail(),
            'reference' => $order->getRef(),
            'products' => $jsonProduct
        ];
    }

    /**
     * @throws PropelException
     */
    private function productToJsonObject(OrderProduct $orderProduct, string $locale): array
    {
        if (null === $pse = ProductSaleElementsQuery::create()->findOneById($orderProduct->getProductSaleElementsId())) {
            return [];
        }

        $category  = GuaranteedOpinionOrderQueueQuery::getCategoryByProductSaleElements($pse);
        $productReviewEvent = new ProductReviewEvent($pse->getProduct());
        $this->eventDispatcher->dispatch($productReviewEvent, GuaranteedOpinionEvents::SEND_ORDER_PRODUCT_EVENT);
        $url = GuaranteedOpinionOrderQueueQuery::getProductUrl($pse->getProductId(), $locale)?->getUrl();

        return [
            'id' => $productReviewEvent->getGuaranteedOpinionProductId(),
            'name' => $pse->getProduct()->getRef(),
            'category_id' => $category->getId(),
            'category_name' => $category->getTitle(),
            'qty' => $orderProduct->getQuantity(),
            'unit_price' => $orderProduct->getPrice(),
            'mpn' => null,
            'ean13' => $pse->getEanCode(),
            'sku' => null,
            'upc' => null,
            'url' => $url ? URL::getInstance()->absoluteUrl($url) : null
        ];
    }

    /**
     * @throws PropelException
     */
    public function setOrdersAsSend(): void
    {
        $orders = GuaranteedOpinionOrderQueueQuery::create()
            ->filterByTreatedAt(null)
            ->findByStatus(0);

        foreach ($orders as $order) {
            $order
                ->setTreatedAt(new \DateTime())
                ->setStatus(1);

            $order->save();
        }
    }
}
