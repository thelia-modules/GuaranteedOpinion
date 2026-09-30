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

namespace GuaranteedOpinion\Form;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Service\EditionLocaleResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use Thelia\Model\OrderStatusQuery;

/**
 * The API keys and the reviews page are those of the edit language ({@see EditionLocaleResolver}): one
 * Guaranteed Reviews account per shop language.
 */
class ConfigurationForm extends BaseForm
{
    public static function getName(): string
    {
        return 'guaranteedopinion_form_configuration_form';
    }

    protected function buildForm(): void
    {
        $translator = Translator::getInstance();
        $request = $this->getRequest();

        $lang = (new EditionLocaleResolver())->resolveLang($request);
        $locale = (string) $lang->getLocale();
        $code = strtoupper((string) $lang->getCode());

        $orderStatus = [];
        $interfaceLocale = $request->hasSession() ? $request->getSession()->getLang()?->getLocale() : null;

        foreach (OrderStatusQuery::create()->find() as $item) {
            $item->setLocale($interfaceLocale ?? $locale);
            $orderStatus[(string) $item->getTitle()] = $item->getId();
        }

        $this->formBuilder
            /* API */
            ->add('api_key_review', TextType::class, [
                'label' => $translator->trans('Api key %code review', ['%code' => $code], GuaranteedOpinion::DOMAIN_NAME),
                'label_attr' => ['for' => 'api_key_review'],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'data' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, null, $locale),
            ])
            ->add('api_key_order', TextType::class, [
                'label' => $translator->trans('Api key %code order', ['%code' => $code], GuaranteedOpinion::DOMAIN_NAME),
                'label_attr' => ['for' => 'api_key_order'],
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                ],
                'data' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, null, $locale),
            ])
            ->add(
                'show_rating_url',
                TextType::class,
                [
                    'data' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY, GuaranteedOpinion::MAPPING_DEFAULT_URL[$locale] ?? null, $locale),
                    'label' => $translator->trans('Show all opinions url %code', ['%code' => $code], GuaranteedOpinion::DOMAIN_NAME),
                    'required' => false,
                ]
            )
            ->add(
                'status_to_export',
                ChoiceType::class,
                [
                    'data' => array_map('intval', array_filter(explode(',', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY) ?? ''), static fn (string $id): bool => '' !== $id)),
                    'label' => $translator->trans('Order status to export', [], GuaranteedOpinion::DOMAIN_NAME),
                    'required' => false,
                    'multiple' => true,
                    'choices' => $orderStatus,
                ]
            )

            ->add(
                'site_review_widget',
                TextareaType::class,
                [
                    'data' => htmlspecialchars_decode(GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY) ?? ''),
                    'label' => $translator->trans('Site review widget code', [], GuaranteedOpinion::DOMAIN_NAME),
                    'required' => false,
                ]
            )
            ->add(
                'site_review_widget_iframe',
                TextareaType::class,
                [
                    'data' => htmlspecialchars_decode(GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY) ?? ''),
                    'label' => $translator->trans('Site review widget iframe code', [], GuaranteedOpinion::DOMAIN_NAME),
                    'required' => false,
                ]
            );
    }
}
