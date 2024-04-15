<?php

namespace GuaranteedOpinion\Command;

use Exception;
use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Service\OrderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class SendOrderCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected OrderService $orderService
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:SendOrder')
            ->setDescription('Send orders to API Avis-Garantis');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();

        try {
            $order = $this->orderService->prepareOrderRequest();

            $response = $this->client->sendOrder($order);

            if ($response->success === 1)
            {
                $output->write("Orders sent with success\n");
            }

            if ($response->success === 0)
            {
                $output->write("Error\n");
            }

            $output->write("Orders imported : " . $response->orders_count ."\n");
            $output->write("Products imported : " . $response->products_imported ."\n");
            $output->write("Message : " . $response->message ."\n");

            if ($response->success === 1)
            {
                $this->orderService->setOrdersAsSend();
            }
        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        return 1;
    }
}