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
        $productReviewsAdded = 0;
        $rowsTreated = 0;

        try {
            $products = ProductQuery::create()->findByVisible(1);

            $output->write("Product Review synchronization start \n");
            foreach ($products as $product)
            {
                $apiResponse = $this->client->getReviewsFromApi($product->getId());

                if ($apiResponse['reviews'] !== []) {
                    $this->productReviewService->addGuaranteedOpinionProductRating($product->getId(), $apiResponse['ratings']);

                    foreach ($apiResponse['reviews'] as $productRow) {
                        if ($rowsTreated % 100 === 0) {
                            $output->write("Rows treated : " . $rowsTreated . "\n");
                        }
                        if ($this->productReviewService->addGuaranteedOpinionProductRow($productRow, $product->getId())) {
                            $productReviewsAdded ++;
                        }
                        $rowsTreated++;
                    }
                }
            }
        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        $output->write("End of Product Review synchronization\n");
        $output->write("Product Reviews Added : " .$productReviewsAdded. "\n");

        return 1;
    }
}