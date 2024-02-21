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
     * @throws PropelException
     */
    public function sendOrderToQueue(OrderEvent $orderEvent): void
    {
        $request = $this->requestStack->getCurrentRequest()->request;

        if (null !== GuaranteedOpinionOrderQueueQuery::create()->findOneByOrderId($orderEvent->getOrder()->getId()))
        {
            return;
        }

        $orderStatuses = explode(',', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY));

        foreach ($orderStatuses as $orderStatus)
        {
            if ($orderStatus === $status = $request->get('status_id'))
            {
                $guaranteedOpinionOrderQueue = new GuaranteedOpinionOrderQueue();

                $guaranteedOpinionOrderQueue
                    ->setOrderId($orderEvent->getOrder()->getId())
                    ->setTreatedAt(new DateTime(''))
                    ->setStatus($status)
                ;

                $guaranteedOpinionOrderQueue->save();
            }
        }
    }

    public static function getSubscribedEvents(): array
    {
        return array(
            TheliaEvents::ORDER_UPDATE_STATUS => ["sendOrderToQueue", 192]
        );
    }
}