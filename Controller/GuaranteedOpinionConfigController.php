<?php

namespace GuaranteedOpinion\Controller;

use Exception;
use GuaranteedOpinion\Form\ConfigurationForm;
use GuaranteedOpinion\GuaranteedOpinion;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Response;
use Thelia\Core\Template\ParserContext;
use Thelia\Form\Exception\FormValidationException;

#[Route(path: "/admin/module/GuaranteedOpinion", name: "module_GuaranteedOpinion")]
class GuaranteedOpinionConfigController extends BaseAdminController
{
    #[Route('/configuration', name: 'configuration', methods: 'POST')]
    public function saveConfiguration(ParserContext $parserContext) : RedirectResponse|Response
    {
        $form = $this->createForm(ConfigurationForm::getName());
        try {
            $data = $this->validateForm($form)->getData();

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::CONFIG_API_REVIEW, $data["api_key_review"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::CONFIG_API_ORDER, $data["api_key_order"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SHOW_RATING_URL, $data["show_rating_url"]);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::STORE_URL, $data["store_url"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT, implode(',', $data["status_to_export"]));
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::EMAIL_DELAY, $data["email_delay"]);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::FOOTER_LINK_TITLE, $data["footer_link_title"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::FOOTER_LINK, $data["footer_link"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::FOOTER_LINK_HOOK_DISPLAY, $data["footer_link_hook_display"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::FOOTER_LINK_DISPLAY, (bool)$data["footer_link_display"]);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY, $data["site_review_hook_display"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_DISPLAY, (bool)$data["site_review_display"]);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY, $data["product_review_hook_display"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY, (bool)$data["product_review_display"]);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY, (bool)$data["product_review_tab_display"]);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET, htmlspecialchars($data["site_review_widget"]));
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME, htmlspecialchars($data["site_review_widget_iframe"]));

            return $this->generateSuccessRedirect($form);

        } catch (FormValidationException $e) {
            $error_message = $this->createStandardFormValidationErrorMessage($e);
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
        $form->setErrorMessage($error_message);

        $parserContext
            ->addForm($form)
            ->setGeneralError($error_message);

        return $this->generateErrorRedirect($form);
    }
}