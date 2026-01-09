<?php

namespace GuaranteedOpinion\EventListeners;

use DateTime;
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueue;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use GuaranteedOpinion\Service\OrderService;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

class OrderListener implements EventSubscriberInterface
{
    public function __construct(
        protected RequestStack $requestStack,
        protected OrderService $orderService
    ) {}

    /**
     * Add the order in the queue if the order status is in the list of statuses to export.
     *
     * @throws PropelException
     */
    public function registerOrder(OrderEvent $event): void
    {
        $statusToExport = explode(',', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY, '4'));

        if (in_array($event->getPlacedOrder()->getStatusId(), $statusToExport, false)) {
            $guaranteedReviewsOrderQueue = new GuaranteedOpinionOrderQueue();
            $guaranteedReviewsOrderQueue->setOrderId($event->getPlacedOrder()->getId())
                ->setStatus(0)
                ->setTreatedAt(new DateTime(''))
                ->save()
            ;
        }
    }

    /**
     * Add the order in the queue if the order status is in the list of statuses to export.
     * Remove the order from the queue if the order status is not in the list of statuses to export and the order was not sent yet.
     *
     * @param OrderEvent $event
     * @throws PropelException
     */
    public function checkOrderInQueue(OrderEvent $event): void
    {
        $statusToExport = explode(',', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY, '4'));

        $newStatus = $event->getOrder()->getStatusId();
        $orderId = $event->getOrder()->getId();

        $guaranteedReviewsOrderQueue = GuaranteedOpinionOrderQueueQuery::create()
            ->filterByOrderId($orderId)
            ->findOne();

        if (null !== $guaranteedReviewsOrderQueue) {
            if (!in_array($newStatus, $statusToExport, false) && (int)$guaranteedReviewsOrderQueue->getStatus() === 0){
                $guaranteedReviewsOrderQueue->delete();
                exit();
            }
        }

        if (in_array($newStatus, $statusToExport, false)){
            $guaranteedReviewsOrderQueue = new GuaranteedOpinionOrderQueue();
            $guaranteedReviewsOrderQueue->setOrderId($orderId)
                ->setStatus(0)
                ->save();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            TheliaEvents::ORDER_UPDATE_STATUS => ["checkOrderInQueue", 64],
            TheliaEvents::ORDER_PAY => ['registerOrder', 64],
        );
    }
}