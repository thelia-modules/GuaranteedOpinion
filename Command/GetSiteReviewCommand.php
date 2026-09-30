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
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Service\SiteReviewService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

/**
 * Imports the store reviews and rating of one language, with the review key of that language.
 */
class GetSiteReviewCommand extends ContainerAwareCommand
{
    public function __construct(
        protected GuaranteedOpinionClient $client,
        protected SiteReviewService $siteReviewService,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('module:GuaranteedOpinion:GetSiteReview')
            ->setDescription('Get site review from API Avis-Garantis')
            ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'locale', 'fr_FR');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $siteReviewsAdded = 0;
        $locale = (string) $input->getOption('locale');

        try {
            $apiResponse = $this->client->getReviewsFromApi('site', $locale);

            if (\is_array($apiResponse['ratings'] ?? null)) {
                GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY, (string) ($apiResponse['ratings']['total'] ?? ''), $locale);
                GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY, (string) ($apiResponse['ratings']['average'] ?? ''), $locale);
            }

            $siteReviews = \is_array($apiResponse['reviews'] ?? null) ? $apiResponse['reviews'] : [];

            $output->write("Site Review synchronization start \n");

            foreach (array_values($siteReviews) as $key => $siteRow) {
                if (0 === $key % 100) {
                    $output->write('Rows treated : '.$key."\n");
                }
                if ($this->siteReviewService->addGuaranteedOpinionSiteRow($siteRow, $locale)) {
                    ++$siteReviewsAdded;
                }
            }
        } catch (\Exception $exception) {
            $output->write($exception->getMessage()."\n");

            return self::FAILURE;
        }

        $output->write("End of Site Review synchronization\n");
        $output->write('Site Review Added : '.$siteReviewsAdded."\n");

        return self::SUCCESS;
    }
}
