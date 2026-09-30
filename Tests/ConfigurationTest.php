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

namespace GuaranteedOpinion\Tests;

use GuaranteedOpinion\Form\ConfigurationForm;
use GuaranteedOpinion\GuaranteedOpinion;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\ModuleConfigQuery;
use Thelia\Test\IntegrationTestCase;

/**
 * Runs on a disposable database (`php bin/test-prepare` with DATABASE_NAME ending in `_test`), never on the shop's.
 * The requests go through the kernel itself: symfony/browser-kit is not required by the module.
 */
final class ConfigurationTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ModuleConfigQuery::resetConfigCache();
        // Set by the admin requests handled here, it would make the next tests read the admin language.
        Request::$isAdminEnv = false;
    }

    public function testTheKeysAreSavedForTheEditLanguageOnly(): void
    {
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, 'fr-review-test', 'fr_FR');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, 'fr-order-test', 'fr_FR');
        $german = $this->lang('de_DE');

        $session = $this->adminSession($this->lang('fr_FR'));
        $request = Request::create('/admin/module/GuaranteedOpinion/configuration', 'POST');
        $request->setSession($session);
        $request->request->set('edit_language_id', (string) $german->getId());
        $request->request->set(ConfigurationForm::getName(), [
            'api_key_review' => 'de-review-test',
            'api_key_order' => 'de-order-test',
            'show_rating_url' => 'https://www.g-g-b.de/test',
            'status_to_export' => [],
            'site_review_widget' => '',
            'site_review_widget_iframe' => '',
            '_token' => $this->csrfToken($request, ConfigurationForm::getName()),
        ]);

        $response = $this->handleAsMainRequest($request);

        self::assertSame(302, $response->getStatusCode(), (string) $response->getContent());
        self::assertSame([], $session->getFlashBag()->peek('danger'));
        self::assertStringContainsString('edit_language_id='.$german->getId(), (string) $response->headers->get('Location'));
        ModuleConfigQuery::resetConfigCache();
        self::assertSame('de-review-test', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, null, 'de_DE'));
        self::assertSame('de-order-test', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, null, 'de_DE'));
        self::assertSame('https://www.g-g-b.de/test', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY, null, 'de_DE'));
        self::assertSame('fr-review-test', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, null, 'fr_FR'));
        self::assertSame('fr-order-test', GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, null, 'fr_FR'));
    }

    public function testTheConfigurationPageShowsTheKeysOfTheEditLanguage(): void
    {
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, 'fr-review-page-test', 'fr_FR');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, 'de-review-page-test', 'de_DE');
        $german = $this->lang('de_DE');

        $request = Request::create('/admin/module/GuaranteedOpinion?edit_language_id='.$german->getId());
        $request->setSession($this->adminSession($this->lang('fr_FR')));

        $response = $this->handleAsMainRequest($request);
        $content = (string) $response->getContent();

        self::assertSame(200, $response->getStatusCode(), $content);
        self::assertStringContainsString('de-review-page-test', $content);
        self::assertStringNotContainsString('fr-review-page-test', $content);
        self::assertStringContainsString('name="edit_language_id" value="'.$german->getId().'"', $content);
        self::assertStringContainsString('--locale de_DE', $content);
    }

    private function adminSession(Lang $interfaceLang): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->setAdminUser($this->createFixtureFactory()->admin());
        $session->set('thelia.current.admin_lang', $interfaceLang);
        $session->setAdminEditionLang($interfaceLang);

        return $session;
    }

    /**
     * IntegrationTestCase pushes a synthetic request: it is taken off the stack while the
     * kernel handles this one, so that this request is the main request the controller reads.
     */
    private function handleAsMainRequest(Request $request): Response
    {
        $requestStack = $this->requestStack();
        $pushedRequests = [];
        while (null !== $pushedRequest = $requestStack->pop()) {
            $pushedRequests[] = $pushedRequest;
        }

        try {
            return self::$kernel->handle($request);
        } finally {
            while (null !== $requestStack->pop()) {
            }
            foreach (array_reverse($pushedRequests) as $pushedRequest) {
                $requestStack->push($pushedRequest);
            }
        }
    }

    private function requestStack(): RequestStack
    {
        $requestStack = static::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);

        return $requestStack;
    }

    private function csrfToken(Request $request, string $tokenId): string
    {
        $requestStack = $this->requestStack();
        $tokenManager = static::getContainer()->get('security.csrf.token_manager');
        self::assertInstanceOf(CsrfTokenManagerInterface::class, $tokenManager);

        $requestStack->push($request);
        try {
            return $tokenManager->getToken($tokenId)->getValue();
        } finally {
            $requestStack->pop();
        }
    }

    private function lang(string $locale): Lang
    {
        $lang = LangQuery::create()->findOneByLocale($locale);
        self::assertInstanceOf(Lang::class, $lang, \sprintf('The test database has no %s language.', $locale));

        return $lang;
    }
}
