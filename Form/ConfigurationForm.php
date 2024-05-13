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

namespace GuaranteedOpinion\Form;

use GuaranteedOpinion\GuaranteedOpinion;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

/**
 * Class GuaranteedOpinionConfigurationForm
 * @package GuaranteedOpinion\Form
 * @author Chabreuil Antoine <achabreuil@openstudio.com>
 */
class ConfigurationForm extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();

        $orderStatus = [];

        $list = OrderStatusQuery::create()
            ->find();

        /** @var OrderStatus $item */
        foreach ($list as $item) {
            $item->setLocale($this->getRequest()->getSession()?->getLang()->getLocale());
            $orderStatus[$item->getTitle()] = $item->getId();
        }

        $this->formBuilder
            /* API */
            ->add("api_key_review", TextType::class, array(
                "label" => $translator?->trans("Api key review", [], GuaranteedOpinion::DOMAIN_NAME),
                "label_attr" => ["for" => "api_key_review"],
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                ),
                "data" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY)
            ))
            ->add("api_key_order", TextType::class, array(
                "label" => $translator?->trans("Api key order", [], GuaranteedOpinion::DOMAIN_NAME),
                "label_attr" => ["for" => "api_key_order"],
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                ),
                "data" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY)
            ))
            ->add(
                'show_rating_url',
                TextType::class,
                [
                    "data" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY) ?: "https://www.societe-des-avis-garantis.fr/",
                    "label"=>$translator?->trans("Show all opinions url", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                'status_to_export',
                ChoiceType::class,
                [
                    "data" => explode(',', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY)),
                    "label"=>$translator?->trans("Order status to export", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                    'multiple' => true,
                    'choices'  => $orderStatus
                ]
            )

            /* HOOK */
            ->add(
                "site_review_hook_display",
                TextType::class,
                [
                    "data" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY_CONFIG_KEY),
                    "label"=>$translator?->trans("Site review hook display", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add("site_review_display", CheckboxType::class, array(
                "label" => $translator?->trans("Show site review", [], GuaranteedOpinion::DOMAIN_NAME),
                "data" => (bool)GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_DISPLAY_CONFIG_KEY, true),
                'required' => false
            ))
            ->add(
                "product_review_hook_display",
                TextType::class,
                [
                    "data" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY_CONFIG_KEY),
                    "label"=>$translator?->trans("Product review hook display", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add("product_review_display", CheckboxType::class, array(
                "label" => $translator?->trans("Show product review", [], GuaranteedOpinion::DOMAIN_NAME),
                "data" => (bool)GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY_CONFIG_KEY, true),
                'required' => false
            ))
            ->add("product_review_tab_display", CheckboxType::class, array(
                "label" => $translator?->trans("Show product review tab", [], GuaranteedOpinion::DOMAIN_NAME),
                "data" => (bool)GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY_CONFIG_KEY, true),
                'required' => false
            ))
            ->add(
                "site_review_widget",
                TextareaType::class,
                [
                    "data" => htmlspecialchars_decode(GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY)),
                    "label"=>$translator?->trans("Site review widget code", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add(
                "site_review_widget_iframe",
                TextareaType::class,
                [
                    "data" => htmlspecialchars_decode(GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY)),
                    "label"=>$translator?->trans("Site review widget iframe code", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
        ;
    }
}