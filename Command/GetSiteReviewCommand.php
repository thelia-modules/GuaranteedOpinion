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
        try {
            $siteReviews = $this->client->getReviewsFromApi();
            $this->siteReviewService->addGuaranteedOpinionSiteReviews($siteReviews);

        } catch (Exception $exception) {
            $output->write($exception->getMessage());
        }

        return 1;
    }
}