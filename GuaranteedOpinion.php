<?php

namespace GuaranteedOpinion;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Finder\Finder;
use Thelia\Install\Database;
use Thelia\Log\Tlog;
use Thelia\Module\BaseModule;

class GuaranteedOpinion extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'guaranteedopinion';

    public const CONFIG_API_REVIEW = "guaranteedopinion.api.review";
    public const CONFIG_API_ORDER = "guaranteedopinion.api.order";

    public const STATUS_TO_EXPORT = "guaranteedopinion.status_to_export";
    public const EMAIL_DELAY = "guaranteedopinion.email_delay";
    public const STORE_URL = "guaranteedopinion.store_url";

    public const SHOW_RATING_URL = "guaranteedopinion.show_rating_url";
    public const FOOTER_LINK_TITLE = "guaranteedopinion.footer_link_title";
    public const FOOTER_LINK = "guaranteedopinion.footer_link";
    public const FOOTER_LINK_HOOK_DISPLAY = "guaranteedopinion.footer_link_hook_display";
    public const FOOTER_LINK_DISPLAY = "guaranteedopinion.footer_link_display";

    public const SITE_REVIEW_DISPLAY = "guaranteedopinion.site_review_display";
    public const SITE_REVIEW_HOOK_DISPLAY = "guaranteedopinion.site_review_hook_display";

    public const SITE_REVIEW_WIDGET = "guaranteedopinion.site_review_widget";
    public const SITE_REVIEW_WIDGET_IFRAME = "guaranteedopinion.site_review_widget_iframe";

    public const PRODUCT_REVIEW_DISPLAY = "guaranteedopinion.product_review_display";
    public const PRODUCT_REVIEW_HOOK_DISPLAY = "guaranteedopinion.product_review_hook_display";
    public const PRODUCT_REVIEW_TAB_DISPLAY = "guaranteedopinion.product_review_tab_display";
    /*
     * You may now override BaseModuleInterface methods, such as:
     * install, destroy, preActivation, postActivation, preDeactivation, postDeactivation
     *
     * Have fun !
     */

    /**
     * Defines how services are loaded in your modules
     *
     * @param ServicesConfigurator $servicesConfigurator
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }

    /**
     * Execute sql files in Config/update/ folder named with module version (ex: 1.0.1.sql).
     *
     * @param $currentVersion
     * @param $newVersion
     * @param ConnectionInterface|null $con
     */
    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        $updateDir = __DIR__.DS.'Config'.DS.'update';

        if (! is_dir($updateDir)) {
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
            if (version_compare($currentVersion, $file->getBasename('.sql'), '<')) {
                $database->insertSql(null, [$file->getPathname()]);
            }
        }
    }

    public static function log($msg): void
    {
        $year = (new \DateTime())->format('Y');
        $month = (new \DateTime())->format('m');
        $logger = Tlog::getNewInstance();
        $logger->setDestinations("\\Thelia\\Log\\Destination\\TlogDestinationFile");
        $logger->setConfig(
            "\\Thelia\\Log\\Destination\\TlogDestinationFile",
            0,
            THELIA_ROOT . "log" . DS . "guaranteedopinion" . DS . $year.$month.".txt"
        );
        $logger->addAlert("MESSAGE => " . print_r($msg, true));
    }
}
