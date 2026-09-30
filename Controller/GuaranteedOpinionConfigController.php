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

namespace GuaranteedOpinion\Controller;

use GuaranteedOpinion\Form\ConfigurationForm;
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Service\EditionLocaleResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Form\Exception\FormValidationException;

/**
 * The API keys and the reviews page are saved for the edit language, the other settings for every language.
 */
class GuaranteedOpinionConfigController extends BaseAdminController
{
    #[Route('/admin/module/GuaranteedOpinion/configuration', name: 'module_GuaranteedOpinion_configuration', methods: ['POST'])]
    public function saveConfiguration(Request $request, EditionLocaleResolver $editionLocaleResolver): RedirectResponse|Response
    {
        $editionLang = $editionLocaleResolver->resolveLang($request);
        $locale = (string) $editionLang->getLocale();
        $form = $this->createForm(ConfigurationForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, (string) $data['api_key_review'], $locale);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, (string) $data['api_key_order'], $locale);
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY, (string) $data['show_rating_url'], $locale);

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY, implode(',', (array) $data['status_to_export']));

            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY, htmlspecialchars((string) $data['site_review_widget']));
            GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY, htmlspecialchars((string) $data['site_review_widget_iframe']));

            return $this->redirectToConfiguration((int) $editionLang->getId());
        } catch (FormValidationException $e) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($e);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }

        $this->addFlash('danger', $errorMessage);

        return $this->redirectToConfiguration((int) $editionLang->getId());
    }

    private function redirectToConfiguration(int $editLanguageId): Response
    {
        return $this->generateRedirectFromRoute(
            'admin.module.configure',
            [EditionLocaleResolver::PARAMETER => $editLanguageId],
            ['module_code' => 'GuaranteedOpinion'],
        );
    }
}
