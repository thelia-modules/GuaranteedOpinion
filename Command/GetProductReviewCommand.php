<?php

namespace GuaranteedOpinion\Command;

use Exception;
use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Service\ProductReviewService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Model\ProductQuery;

class GetProductReviewCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected ProductReviewService $productReviewService
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:GetProductReview')
            ->setDescription('Get product review from API Avis-Garantis');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $products = ProductQuery::create()->findByVisible(1);

            foreach ($products as $product)
            {
                $productReviews = $this->client->getReviewsFromApi($product->getId());

                if ($productReviews !== [])
                {
                    $this->productReviewService->addGuaranteedOpinionProductReviews($productReviews, $product->getId());
                }
            }

        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        return 1;
    }
}