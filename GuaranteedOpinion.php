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

namespace GuaranteedOpinion;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Thelia\Core\Install\Database;
use Thelia\Log\Tlog;
use Thelia\Module\BaseModule;

class GuaranteedOpinion extends BaseModule
{
    public const DOMAIN_NAME = 'guaranteedopinion';

    /** HTTP client of the API, built without a logger: the framework client logs every URL, review key included. */
    public const HTTP_CLIENT_SERVICE = 'guaranteed_opinion.http_client';

    /** Read and written by language: each shop language has its own Guaranteed Reviews account. */
    public const API_REVIEW_CONFIG_KEY = 'guaranteedopinion.api.review';
    public const API_ORDER_CONFIG_KEY = 'guaranteedopinion.api.order';
    public const SHOW_RATING_URL_CONFIG_KEY = 'guaranteedopinion.show_rating_url';
    public const SITE_RATING_TOTAL_CONFIG_KEY = 'guaranteedopinion.site_rating_total';
    public const SITE_RATING_AVERAGE_CONFIG_KEY = 'guaranteedopinion.site_rating_average';

    public const STATUS_TO_EXPORT_CONFIG_KEY = 'guaranteedopinion.status_to_export';

    public const SITE_REVIEW_WIDGET_CONFIG_KEY = 'guaranteedopinion.site_review_widget';
    public const SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY = 'guaranteedopinion.site_review_widget_iframe';

    /** Public page listing every review, by language, when none is configured. */
    public const MAPPING_DEFAULT_URL = [
        'fr_FR' => 'https://www.societe-des-avis-garantis.fr/',
        'en_US' => 'https://www.guaranteed-reviews.com/',
        'de_DE' => 'https://www.g-g-b.de/',
        'es_ES' => 'https://www.sociedad-de-opiniones-contrastadas.es/',
        'it_IT' => 'https://www.societa-recensioni-garantite.it/',
        'nl_NL' => 'https://www.g-b-n.nl/',
    ];

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([
                __DIR__.'/I18n/*',
                __DIR__.'/Model/*',
                __DIR__.'/Tests/*',
                __DIR__.'/templates/*',
            ])
            ->autowire(true)
            ->autoconfigure(true);

        $servicesConfigurator->set(self::HTTP_CLIENT_SERVICE, HttpClientInterface::class)
            ->factory([HttpClient::class, 'create']);
    }

    /**
     * Executes the files of Config/update/ named after a version above the installed one (ex: 2.1.0.sql).
     */
    public function update($currentVersion, $newVersion, ?ConnectionInterface $con = null): void
    {
        $updateDir = __DIR__.DS.'Config'.DS.'update';

        if (!is_dir($updateDir)) {
            return;
        }

        $finder = Finder::create()
            ->name('*.sql')
            ->depth(0)
            ->sortByName()
            ->in($updateDir);

        $database = new Database($con);

        /** @var \SplFileInfo $file */
        foreach ($finder as $file) {
            if (version_compare((string) $currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public static function log(mixed $message): void
    {
        $now = new \DateTime();
        $logger = Tlog::getNewInstance();
        $logger->setDestinations('\\Thelia\\Log\\Destination\\TlogDestinationFile');
        $logger->setConfig(
            '\\Thelia\\Log\\Destination\\TlogDestinationFile',
            0,
            THELIA_ROOT.'log'.DS.'guaranteedopinion'.DS.$now->format('Ym').'.txt'
        );
        $logger->addAlert('MESSAGE => '.print_r($message, true));
    }

    /**
     * The tables are created when missing, never dropped: activating the module again keeps its reviews.
     */
    public function postActivation(?ConnectionInterface $con = null): void
    {
        if (!self::getConfigValue('is_initialized', false)) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__.'/Config/TheliaMain.sql']);
            // Tables of a 2.0.0 install left behind by a module removed without destroy() are kept by
            // TheliaMain.sql (CREATE IF NOT EXISTS): the update script brings them to the per-language schema.
            $database->insertSql(null, [__DIR__.'/Config/update/2.1.0.sql']);
            self::setConfigValue('is_initialized', true);
        }
    }
}
