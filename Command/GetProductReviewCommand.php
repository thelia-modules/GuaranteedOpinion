<?php

namespace GuaranteedOpinion\Command;

use Exception;
use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Event\ProductReviewEvent;
use GuaranteedOpinion\Event\GuaranteedOpinionEvents;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Service\ProductReviewService;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Get product review from API Avis-Garantis')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale', 'fr_FR')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $productReviewsAdded = 0;
        $rowsTreated = 0;

        try {
            $locale = $input->getOption('locale');

            $products = ProductQuery::create()->findByVisible(1);

            $output->write("Product Review synchronization start \n");
            foreach ($products as $product)
            {
                $addProductReviewEvent = new ProductReviewEvent($product);
                $this->getDispatcher()->dispatch($addProductReviewEvent, GuaranteedOpinionEvents::ADD_PRODUCT_REVIEW_EVENT);

                $productReviews = GuaranteedOpinionProductReviewQuery::create()->filterByProductId($product->getId())->find();

                $apiResponse = $this->client->getReviewsFromApi($addProductReviewEvent->getGuaranteedOpinionProductId());

                if ($apiResponse['reviews'] !== []) {
                    $this->productReviewService->addGuaranteedOpinionProductRating($product->getId(), $apiResponse['ratings']);

                    foreach ($apiResponse['reviews'] as $productRow) {
                        if ($rowsTreated % 100 === 0) {
                            $output->write("Rows treated : " . $rowsTreated . "\n");
                        }
                        if ($this->productReviewService->addGuaranteedOpinionProductRow($productRow, $product->getId(), $locale)) {
                            $productReviewsAdded ++;
                        }
                        $rowsTreated++;
                    }
                }

                $this->removeDeletedReview($productReviews, $apiResponse['reviews']);
            }
        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        $output->write("End of Product Review synchronization\n");
        $output->write("Product Reviews Added : " .$productReviewsAdded. "\n");

        return 1;
    }

    /**
     * @throws PropelException
     */
    private function removeDeletedReview($productReviews, $apiResultReviews): void
    {
        /** @var GuaranteedOpinionProductReview $productReview */
        foreach ($productReviews as $productReview) {
            $exist = false;
            foreach ($apiResultReviews as $apiResultReview) {
                if ((int)$apiResultReview['id'] === (int)$productReview->getProductReviewId()) {
                    $exist = true;
                    break;
                }
            }
            if (!$exist){
                $productReview->delete();
            }
        }
    }
}
