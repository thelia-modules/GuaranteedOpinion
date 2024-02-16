<?php

namespace GuaranteedOpinion\Command;

use Exception;
use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Service\SiteReviewService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class GetSiteReviewCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected SiteReviewService $siteReviewService
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:GetSiteReview')
            ->setDescription('Get site review from API Avis-Garantis');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $siteReviewsAdded = 0;

        try {
            $siteReviews = $this->client->getReviewsFromApi();

            $output->write("Site Review synchronization start \n");

            foreach ($siteReviews as $key => $siteRow) {
                if ($key % 100 === 0) {
                    $output->write("Rows treated : " . $key . "\n");
                }
                if ($this->siteReviewService->addGuaranteedOpinionSiteRow($siteRow)) {
                    $siteReviewsAdded ++;
                }
            }
        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        $output->write("End of Site Review synchronization\n");
        $output->write("Site Review Added : " .$siteReviewsAdded. "\n");

        return 1;
    }
}