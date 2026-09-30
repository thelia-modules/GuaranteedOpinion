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

namespace GuaranteedOpinion\Command;

use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Service\OrderService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

/**
 * Sends the queued orders placed in one language, with the order key of that language. Only the queue rows of
 * the orders sent are marked as sent: the other languages wait for their own run.
 */
class SendOrderCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected OrderService $orderService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:SendOrder')
            ->setDescription('Send orders to API Avis-Garantis')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale', 'fr_FR');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();
        $locale = (string) $input->getOption('locale');

        try {
            $batch = $this->orderService->prepareOrderBatch($locale);

            if ($batch->isEmpty()) {
                $output->write("No order to send\n");

                return self::SUCCESS;
            }

            $response = $this->client->sendOrder($batch->toJson(), $locale);
            $success = (int) ($response->success ?? 0);

            $output->write(1 === $success ? "Orders sent with success\n" : "Error\n");
            $output->write('Orders imported : '.($response->orders_count ?? '')."\n");
            $output->write('Products imported : '.($response->products_imported ?? '')."\n");
            $output->write('Message : '.($response->message ?? '')."\n");

            if (1 !== $success) {
                return self::FAILURE;
            }

            $this->orderService->setOrdersAsSend($batch);
        } catch (\Exception $exception) {
            $output->write($exception->getMessage()."\n");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
