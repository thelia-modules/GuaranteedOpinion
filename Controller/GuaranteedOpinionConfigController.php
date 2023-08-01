<?php

namespace GuaranteedOpinion\Controller;

use GuaranteedOpinion\Form\GuaranteedOpinionConfigurationForm;
use GuaranteedOpinion\GuaranteedOpinion;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Template\ParserContext;
use Thelia\Model\ConfigQuery;
use Thelia\Tools\URL;

#[Route(path: "/admin/module", name: "module_GuaranteedOpinion")]
class GuaranteedOpinionConfigController extends BaseAdminController
{

    #[Route(path: "/GuaranteedOpinion", name: "_save", methods: ["POST"])]
    public function saveAction(Request $request, ParserContext $parserContext)
    {
        $baseForm = $this->createForm(GuaranteedOpinionConfigurationForm::getName());

        try {
            $form = $this->validateForm($baseForm);
            $data = $form->getData();

            ConfigQuery::write(GuaranteedOpinion::CONFIG_API_SECRET, $data["api_key"]);
            ConfigQuery::write(GuaranteedOpinion::CONFIG_THROW_EXCEPTION_ON_ERROR, (bool)$data["exception_on_errors"]);

            ConfigQuery::write(GuaranteedOpinion::API_URL, $data["api_url"]);
            ConfigQuery::write(GuaranteedOpinion::STATUS_TO_EXPORT, implode(',', $data["status_to_export"]));
            ConfigQuery::write(GuaranteedOpinion::FOOTER_LINK_TITLE, $data["footer_link_title"]);
            ConfigQuery::write(GuaranteedOpinion::FOOTER_LINK, $data["footer_link"]);
            ConfigQuery::write(GuaranteedOpinion::FOOTER_LINK_HOOK_DISPLAY, $data["footer_link_hook_display"]);
            ConfigQuery::write(GuaranteedOpinion::FOOTER_LINK_DISPLAY, (bool)$data["footer_link_display"]);
            ConfigQuery::write(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY, $data["site_review_hook_display"]);
            ConfigQuery::write(GuaranteedOpinion::SITE_REVIEW_DISPLAY, (bool)$data["site_review_display"]);
            ConfigQuery::write(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY, $data["product_review_hook_display"]);
            ConfigQuery::write(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY, (bool)$data["product_review_display"]);
            ConfigQuery::write(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY, (bool)$data["product_review_tab_display"]);
            ConfigQuery::write(GuaranteedOpinion::SHOW_RATING_URL, $data["show_rating_url"]);


            $parserContext->set("success", true);

            if ("close" === $request->request->get("save_mode")) {
                return new RedirectResponse(URL::getInstance()->absoluteUrl("/admin/modules"));
            }
        } catch (\Exception $e) {
            $parserContext
                ->setGeneralError($e->getMessage())
                ->addForm($baseForm)
            ;
        }

        return $this->render('module-configure', [ 'module_code' => 'GuaranteedOpinion' ]);
    }

}