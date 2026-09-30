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

namespace GuaranteedOpinion\EventListeners;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueue;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use GuaranteedOpinion\Service\OrderService;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

/**
 * Keeps the send queue in step with the order statuses chosen in the configuration: an order enters it once, when
 * it reaches one of them, and leaves it while not sent yet if it moves to another one. The listener returns in
 * every case: the status change goes on.
 */
class OrderListener implements EventSubscriberInterface
{
    /**
     * @throws PropelException
     */
    public function registerOrder(OrderEvent $event): void
    {
        $order = $event->getPlacedOrder();

        if (null !== $order && $this->isExported((int) $order->getStatusId())) {
            $this->enqueue((int) $order->getId());
        }
    }

    /**
     * @throws PropelException
     */
    public function checkOrderInQueue(OrderEvent $event): void
    {
        $order = $event->getOrder();

        if (null === $order) {
            return;
        }

        $orderId = (int) $order->getId();

        if ($this->isExported((int) $order->getStatusId())) {
            $this->enqueue($orderId);

            return;
        }

        GuaranteedOpinionOrderQueueQuery::create()
            ->filterByOrderId($orderId)
            ->filterByStatus(OrderService::STATUS_PENDING)
            ->delete();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::ORDER_UPDATE_STATUS => ['checkOrderInQueue', 64],
            TheliaEvents::ORDER_PAY => ['registerOrder', 64],
        ];
    }

    private function isExported(int $statusId): bool
    {
        $statusesToExport = array_map(
            'intval',
            explode(',', (string) GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY, '4')),
        );

        return \in_array($statusId, $statusesToExport, true);
    }

    /**
     * An order already in the queue, sent or not, is not added again.
     *
     * @throws PropelException
     */
    private function enqueue(int $orderId): void
    {
        if (GuaranteedOpinionOrderQueueQuery::create()->filterByOrderId($orderId)->exists()) {
            return;
        }

        (new GuaranteedOpinionOrderQueue())
            ->setOrderId($orderId)
            ->setStatus(OrderService::STATUS_PENDING)
            ->save();
    }
}
