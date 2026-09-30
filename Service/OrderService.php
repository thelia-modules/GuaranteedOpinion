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

use GuaranteedOpinion\Event\GuaranteedOpinionEvents;
use GuaranteedOpinion\Event\ProductReviewEvent;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\LangQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderProduct;
use Thelia\Model\OrderQuery;
use Thelia\Model\ProductSaleElementsQuery;
use Thelia\Tools\URL;

/**
 * The queue holds the orders of every language; each language is sent with the key of its own account, so a batch
 * only takes the orders placed in that language and only its rows are marked as sent.
 */
class OrderService
{
    public const STATUS_PENDING = 0;
    public const STATUS_SENT = 1;

    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws PropelException
     * @throws \RuntimeException when the language is unknown
     */
    public function prepareOrderBatch(string $locale): OrderBatch
    {
        $lang = LangQuery::create()->findOneByLocale($locale);

        if (null === $lang) {
            throw new \RuntimeException(\sprintf('Unknown language %s', $locale));
        }

        $pendingRows = GuaranteedOpinionOrderQueueQuery::create()
            ->filterByTreatedAt(null, Criteria::ISNULL)
            ->filterByStatus(self::STATUS_PENDING)
            ->orderById()
            ->find();

        $orderIds = [];
        foreach ($pendingRows as $pendingRow) {
            $orderIds[] = $pendingRow->getOrderId();
        }

        if ([] === $orderIds) {
            return new OrderBatch($locale, [], []);
        }

        $ordersOfLanguage = [];
        foreach (OrderQuery::create()->filterById(array_values(array_unique($orderIds)), Criteria::IN)->filterByLangId($lang->getId())->find() as $order) {
            $ordersOfLanguage[$order->getId()] = $order;
        }

        $jsonOrders = [];
        $queueIds = [];

        foreach ($pendingRows as $pendingRow) {
            $order = $ordersOfLanguage[$pendingRow->getOrderId()] ?? null;

            if (null === $order) {
                continue;
            }

            $queueIds[] = $pendingRow->getId();
            $jsonOrders[$order->getId()] ??= $this->orderToJsonObject($order, $locale);
        }

        return new OrderBatch($locale, array_values($jsonOrders), $queueIds);
    }

    /**
     * @throws PropelException
     */
    public function setOrdersAsSend(OrderBatch $batch): void
    {
        if ([] === $batch->queueIds) {
            return;
        }

        $rows = GuaranteedOpinionOrderQueueQuery::create()
            ->filterById($batch->queueIds, Criteria::IN)
            ->filterByStatus(self::STATUS_PENDING)
            ->find();

        foreach ($rows as $row) {
            $row
                ->setTreatedAt(new \DateTime())
                ->setStatus(self::STATUS_SENT)
                ->save();
        }
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PropelException
     */
    private function orderToJsonObject(Order $order, string $locale): array
    {
        $jsonProduct = [];

        foreach ($order->getOrderProducts() as $orderProduct) {
            $product = $this->productToJsonObject($orderProduct, $locale);

            if ([] !== $product) {
                $jsonProduct[] = $product;
            }
        }

        $customer = $order->getCustomer();

        return [
            'id_order' => $order->getId(),
            'order_date' => $order->getCreatedAt()?->format('Y-m-d H:i:s'),
            'firstname' => $customer?->getFirstname(),
            'lastname' => $customer?->getLastname(),
            'email' => $customer?->getEmail(),
            'reference' => $order->getRef(),
            'products' => $jsonProduct,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PropelException
     */
    private function productToJsonObject(OrderProduct $orderProduct, string $locale): array
    {
        $pse = ProductSaleElementsQuery::create()->findOneById($orderProduct->getProductSaleElementsId());
        $product = $pse?->getProduct();

        if (null === $pse || null === $product) {
            return [];
        }

        $category = GuaranteedOpinionOrderQueueQuery::getCategoryByProductSaleElements($pse);
        $productReviewEvent = new ProductReviewEvent($product);
        $this->eventDispatcher->dispatch($productReviewEvent, GuaranteedOpinionEvents::SEND_ORDER_PRODUCT_EVENT);
        $url = GuaranteedOpinionOrderQueueQuery::getProductUrl($pse->getProductId(), $locale)?->getUrl();

        return [
            'id' => $productReviewEvent->getGuaranteedOpinionProductId(),
            'name' => $product->getRef(),
            'category_id' => $category?->getId(),
            'category_name' => $category?->getTitle(),
            'qty' => $orderProduct->getQuantity(),
            'unit_price' => $orderProduct->getPrice(),
            'mpn' => null,
            'ean13' => $pse->getEanCode(),
            'sku' => null,
            'upc' => null,
            'url' => $url ? URL::getInstance()->absoluteUrl($url) : null,
        ];
    }
}
