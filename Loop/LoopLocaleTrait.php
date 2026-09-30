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

namespace GuaranteedOpinion\Loop;

use Thelia\Model\Lang;
use Thelia\Model\LangQuery;

/**
 * The language of a review loop: the lang_id argument, else the language of the visitor, else the default one.
 */
trait LoopLocaleTrait
{
    private function loopLocale(?int $langId): string
    {
        if (null !== $langId && null !== $lang = LangQuery::create()->findPk($langId)) {
            return (string) $lang->getLocale();
        }

        $request = $this->requestStack->getCurrentRequest();
        $session = null !== $request && $request->hasSession() ? $request->getSession() : null;
        $lang = null !== $session && method_exists($session, 'getLang') ? $session->getLang() : null;

        return (string) ($lang instanceof Lang ? $lang : Lang::getDefaultLanguage())->getLocale();
    }
}
