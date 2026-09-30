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

namespace GuaranteedOpinion\Hook;

use GuaranteedOpinion\Form\ConfigurationForm;
use GuaranteedOpinion\Service\EditionLocaleResolver;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

class ConfigurationHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        private readonly EditionLocaleResolver $editionLocaleResolver,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $editionLang = $this->editionLocaleResolver->resolveLang($this->getRequest());
        $form = $this->formFactory->createForm(ConfigurationForm::getName());

        $event->add($this->render('GuaranteedOpinion/module_configuration.html.twig', [
            'form' => $form->createView()->getView(),
            'edit_language_id' => $editionLang->getId(),
            'edit_language_locale' => $editionLang->getLocale(),
        ]));
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                [
                    'type' => 'back',
                    'method' => 'onModuleConfiguration',
                ],
            ],
        ];
    }
}
