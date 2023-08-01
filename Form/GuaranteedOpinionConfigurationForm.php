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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\ConfigQuery;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;

/**
 * Class GuaranteedOpinionConfigurationForm
 * @package GuaranteedOpinion\Form
 * @author Chabreuil Antoine <achabreuil@openstudio.com>
 */
class GuaranteedOpinionConfigurationForm extends BaseForm
{
    /**
     *
     * in this function you add all the fields you need for your Form.
     * Form this you have to call add method on $this->formBuilder attribute :
     *
     * $this->formBuilder->add("name", "text")
     *   ->add("email", "email", array(
     *           "attr" => array(
     *               "class" => "field"
     *           ),
     *           "label" => "email",
     *           "constraints" => array(
     *               new \Symfony\Component\Validator\Constraints\NotBlank()
     *           )
     *       )
     *   )
     *   ->add('age', 'integer');
     */
    protected function buildForm()
    {
        $translator = Translator::getInstance();

        $orderStatus = [];

        $list = OrderStatusQuery::create()
            ->find();

        $orderStatusChoice = ["4"]; // 1: Not paid, 2: Paid, 3: Processing, 4: Sent, 5: Canceled, 6: Refunded

        /** @var OrderStatus $item */
        foreach ($list as $item) {
            $item->setLocale($this->getRequest()->getSession()->getLang()->getLocale());
            $orderStatus[$item->getTitle()] = $item->getId();
        }

        $statusRaw = ConfigQuery::read(GuaranteedOpinion::STATUS_TO_EXPORT);
        $orderStatusChoices = explode(",", $statusRaw);
        if (null !== $statusRaw && "" !== $statusRaw && count($orderStatusChoices) > 0) {
            $orderStatusChoice = null;
        }

        $this->formBuilder
            ->add("api_key", TextType::class, array(
                "label" => $translator->trans("Api key", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "label_attr" => ["for" => "api_key"],
                "required" => true,
                "constraints" => array(
                    new NotBlank(),
                ),
                "data" => ConfigQuery::read(GuaranteedOpinion::CONFIG_API_SECRET)
            ))
            ->add("exception_on_errors", CheckboxType::class, array(
                "label" => $translator->trans("Throw exception on GuaranteedOpinion error", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "data" => (bool)ConfigQuery::read(GuaranteedOpinion::CONFIG_THROW_EXCEPTION_ON_ERROR, false),
                'required' => false,
                "label_attr" => [
                    'help' => $translator->trans(
                        "The module will throw an error if something wrong happens whan talking to GuaranteedOpinion. Warning ! This could prevent user registration if GuaranteedOpinion server is down or unreachable !",
                        [],
                        GuaranteedOpinion::MESSAGE_DOMAIN
                    )
                ]
            ))
            ->add(
                'api_url',
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::API_URL),
                    "label"=>Translator::getInstance()->trans("API URL", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                'show_rating_url',
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::SHOW_RATING_URL),
                    "label"=>Translator::getInstance()->trans("Show all opinions url", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                'status_to_export',
                ChoiceType::class,
                [
                    "data" => $orderStatusChoice ?: $orderStatusChoices,
                    "label"=>Translator::getInstance()->trans("Order status to export", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                    'multiple' => true,
                    'choices'  => $orderStatus,
                ]
            )
            ->add(
                "footer_link_title",
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_TITLE),
                    "label"=>Translator::getInstance()->trans("Footer link title", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                "footer_link",
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK),
                    "label"=>Translator::getInstance()->trans("Footer link", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false
                ]
            )
            ->add(
                "footer_link_hook_display",
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_HOOK_DISPLAY) ?: "main.footer-bottom",
                    "label"=>Translator::getInstance()->trans("Footer link hook display", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add("footer_link_display", CheckboxType::class, array(
                "label" => $translator->trans("Show footer link", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "data" => (bool)ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_DISPLAY, true),
                'required' => false
            ))
            ->add(
                "site_review_hook_display",
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY) ?: "main.content-bottom",
                    "label"=>Translator::getInstance()->trans("Site review hook display", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add("site_review_display", CheckboxType::class, array(
                "label" => $translator->trans("Show site review", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "data" => (bool)ConfigQuery::read(GuaranteedOpinion::SITE_REVIEW_DISPLAY, true),
                'required' => false
            ))
            ->add(
                "product_review_hook_display",
                TextType::class,
                [
                    "data" => ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY) ?: "product.bottom",
                    "label"=>Translator::getInstance()->trans("Product review hook display", array(), GuaranteedOpinion::DOMAIN_NAME),
                    "required" => false,
                ]
            )
            ->add("product_review_display", CheckboxType::class, array(
                "label" => $translator->trans("Show product review", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "data" => (bool)ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY, true),
                'required' => false
            ))
            ->add("product_review_tab_display", CheckboxType::class, array(
                "label" => $translator->trans("Show product review tab", [], GuaranteedOpinion::MESSAGE_DOMAIN),
                "data" => (bool)ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY, true),
                'required' => false
            ))
        ;

        // new datas from netReviews in classicride
//            ->add(
//                "id_website",
//                TextType::class,
//                [
//                    "data" => GuaranteedOpinion::getConfigValue("id_website"),
//                    "label"=>Translator::getInstance()->trans("Id website", array(), GuaranteedOpinion::DOMAIN_NAME),
//                    "label_attr" => ["for" => "id_website"],
//                    "required" => true
//                ]
//            )
        // il faut surement garder ça à voir avec les test de l'api
//            ->add(
//                "site_url_import",
//                TextType::class,
//                [
//                    "data" => GuaranteedOpinion::getConfigValue("site_url_import"),
//                    "label"=>Translator::getInstance()->trans("Site url import", array(), GuaranteedOpinion::DOMAIN_NAME)
//                ]
//            )
        //peut être intéressant d'ajouter ce cronq
    }

    /**
     * @return string the name of you form. This name must be unique
     */
    public static function getName()
    {
        return "guaranteed_opinion_configuration";
    }
}
