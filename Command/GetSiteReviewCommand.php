<?php

namespace GuaranteedOpinion\Command;

use Exception;
use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Service\SiteReviewService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
            ->setDescription('Get site review from API Avis-Garantis')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale', 'fr_FR')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $siteReviewsAdded = 0;

        try {
            $locale = $input->getOption('locale');

            $apiResponse = $this->client->getReviewsFromApi('site', $locale);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY, $apiResponse['ratings']['total']);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY, $apiResponse['ratings']['average']);

            $siteReviews = $apiResponse['reviews'];

            $output->write("Site Review synchronization start \n");

            foreach ($siteReviews as $key => $siteRow) {
                if ($key % 100 === 0) {
                    $output->write("Rows treated : " . $key . "\n");
                }
                if ($this->siteReviewService->addGuaranteedOpinionSiteRow($siteRow, $locale)) {
                    $siteReviewsAdded ++;
                }
            }
        } catch (Exception $exception) {
            $output->write($exception->getMessage());
            return 0;
        }

        $output->write("End of Site Review synchronization\n");
        $output->write("Site Review Added : " .$siteReviewsAdded. "\n");

        return 1;
    }
}