<?php

namespace GuaranteedOpinion\EventListeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

class OrderListener implements EventSubscriberInterface
{

    private function newOrder(OrderEvent $orderEvent){
        $order = $orderEvent->getOrder();

        $form = $this->createForm("guaranteed_opinion_send_order_form");

        try {
            $data = $this->validateForm($form)->getData();

            /** @var OrderService $orderService */
            $orderService = $this->container->get('netreviews.order.service');

            $response = $orderService->sendOrderToNetReviews($orderId);
            $return = $response->return;

            if ($return != 1) {
                $debug = $response->debug;
                throw new \Exception($debug);
            }
        } catch (\Exception $e) {
            $this->setupFormErrorContext(
                Translator::getInstance()->trans(
                    "Error",
                    [],
                    NetReviews::DOMAIN_NAME
                ),
                $e->getMessage(),
                $form
            );
        }

        return $this->generateSuccessRedirect($form);
    }

    public static function getSubscribedEvents()
    {
        return array(
            TheliaEvents::ORDER_PAY => ["newOrder", 192]
        );
    }
}