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
use GuaranteedOpinion\Event\GuaranteedOpinionEvents;
use GuaranteedOpinion\Event\ProductReviewEvent;
use GuaranteedOpinion\Service\ProductReviewService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Model\ProductQuery;

/**
 * Imports the product reviews and ratings of one language, with the review key of that language.
 */
class GetProductReviewCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected ProductReviewService $productReviewService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:GetProductReview')
            ->setDescription('Get product review from API Avis-Garantis')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale', 'fr_FR');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $productReviewsAdded = 0;
        $productReviewsRemoved = 0;
        $rowsTreated = 0;
        $locale = (string) $input->getOption('locale');

        try {
            $products = ProductQuery::create()->findByVisible(1);

            $output->write("Product Review synchronization start \n");
            foreach ($products as $product) {
                $addProductReviewEvent = new ProductReviewEvent($product);
                $this->getDispatcher()->dispatch($addProductReviewEvent, GuaranteedOpinionEvents::ADD_PRODUCT_REVIEW_EVENT);

                $apiResponse = $this->client->getReviewsFromApi($addProductReviewEvent->getGuaranteedOpinionProductId(), $locale);
                $apiReviews = \is_array($apiResponse['reviews'] ?? null) ? $apiResponse['reviews'] : null;

                if (null === $apiReviews) {
                    continue;
                }

                if ([] !== $apiReviews && \is_array($apiResponse['ratings'] ?? null)) {
                    $this->productReviewService->addGuaranteedOpinionProductRating($product->getId(), $apiResponse['ratings'], $locale);
                }

                foreach ($apiReviews as $productRow) {
                    if (0 === $rowsTreated % 100) {
                        $output->write('Rows treated : '.$rowsTreated."\n");
                    }
                    if ($this->productReviewService->addGuaranteedOpinionProductRow($productRow, $product->getId(), $locale)) {
                        ++$productReviewsAdded;
                    }
                    ++$rowsTreated;
                }

                $productReviewsRemoved += $this->productReviewService->removeDeletedReviews($product->getId(), $locale, $apiReviews);
            }
        } catch (\Exception $exception) {
            $output->write($exception->getMessage()."\n");

            return self::FAILURE;
        }

        $output->write("End of Product Review synchronization\n");
        $output->write('Product Reviews Added : '.$productReviewsAdded."\n");
        $output->write('Product Reviews Removed : '.$productReviewsRemoved."\n");

        return self::SUCCESS;
    }
}
