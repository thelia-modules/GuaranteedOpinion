<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace GuaranteedOpinion;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Symfony\Component\Filesystem\Filesystem;
use Thelia\Install\Database;
use Thelia\Model\Config;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ModuleImageQuery;
use Thelia\Module\BaseModule;

/**
 * Class GuaranteedOpinion
 * @package GuaranteedOpinion
 * @author Chabreuil Antoine <achabreuil@openstudio.com>
 */
class GuaranteedOpinion extends BaseModule
{
    const MESSAGE_DOMAIN = "guaranteed_opinion";

    const CONFIG_API_SECRET = "guaranteed_opinion.api.secret";
    const CONFIG_THROW_EXCEPTION_ON_ERROR = "guaranteed_opinion.throw_exception_on_error";
    const API_URL = "guaranteed_opinion.api.url";
    const STATUS_TO_EXPORT = "guaranteed_opinion.status_to_export";
    const FOOTER_LINK_TITLE = "guaranteed_opinion.footer_link_title";
    const FOOTER_LINK = "guaranteed_opinion.footer_link";
    /** @var string */
    const DOMAIN_NAME = 'guaranteed_opinion';
    const FOOTER_LINK_DISPLAY = "guaranteed_opinion.footer_link_display";
    const SITE_REVIEW_DISPLAY = "guaranteed_opinion.site_review_display";
    const PRODUCT_REVIEW_DISPLAY = "guaranteed_opinion.product_review_display";
    const FOOTER_LINK_HOOK_DISPLAY = "guaranteed_opinion.footer_link_hook_display";
    const SITE_REVIEW_HOOK_DISPLAY = "guaranteed_opinion.site_review_hook_display";
    const PRODUCT_REVIEW_HOOK_DISPLAY = "guaranteed_opinion.product_review_hook_display";
    const PRODUCT_REVIEW_TAB_DISPLAY = "guaranteed_opinion.product_review_tab_display";
    const SHOW_RATING_URL = "guaranteed_opinion.show_rating_url";

    public function postActivation(ConnectionInterface $con = null): void
    {
        $con->beginTransaction();

        try {
            if (null === ConfigQuery::read(static::CONFIG_API_SECRET)) {
                $this->createConfigValue(static::CONFIG_API_SECRET, [
                    "fr_FR" => "Secret d'API pour guaranteed_opinion",
                    "en_US" => "Api secret for guaranteed_opinion",
                ]);
            }

            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/thelia.sql"]);

            $con->commit();

            /* Deploy the module's image */
            $module = $this->getModuleModel();

            if (ModuleImageQuery::create()->filterByModule($module)->count() == 0) {
                $this->deployImageFolder($module, sprintf('%s/images', __DIR__), $con);
            }
        } catch (\Exception $e) {
            $con->rollBack();

            throw $e;
        }
    }

    protected function createConfigValue($name, array $translation, $value = '')
    {
        $config = new Config();
        $config
            ->setName($name)
            ->setValue($value)
        ;

        foreach ($translation as $locale => $title) {
            $config->getTranslation($locale)
                ->setTitle($title)
            ;
        }

        $config->save();
    }


    /**
     * @param string $currentVersion
     * @param string $newVersion
     * @param ConnectionInterface $con
     */
    public function update($currentVersion, $newVersion, ConnectionInterface $con = null): void
    {
        if ($newVersion === '1.3.2') {
            $db = new Database($con);
//
//            $tableExists = $db->execute("SHOW TABLES LIKE 'guaranteed_opinion'")->rowCount();
//
//            if ($tableExists) {
//                // Le champ relation ID change de format.
//                $db->execute("ALTER TABLE `guaranteed_opinion` CHANGE `relation_id` `relation_id` varchar(255) NOT NULL AFTER `email`");
//            }
        }
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR.ucfirst(self::getModuleCode()).'/I18n/*'])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
