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

use GuaranteedOpinion\Controller\FrontController;
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductRating;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Service\ReviewReader;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Thelia\Api\Service\DataAccess\DataAccessService;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\LangQuery;
use Thelia\Model\ModuleConfigQuery;
use Thelia\Test\IntegrationTestCase;

/**
 * Runs on a disposable database (`php bin/test-prepare` with DATABASE_NAME ending in `_test`), never on the shop's.
 * The requests go through the kernel itself: symfony/browser-kit is not required by the module.
 */
final class ReviewResourceTest extends IntegrationTestCase
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
    }

    public function testProductReviewsAreThoseOfTheLanguageNewestFirstWithPublicFieldsOnly(): void
    {
        $productId = $this->productId();
        $this->productReview('101', 'fr_FR', $productId, '2026-01-01', 'Très bien');
        $this->productReview('101', 'de_DE', $productId, '2026-01-02', 'Sehr gut');
        $this->productReview('102', 'de_DE', $productId, '2026-03-01', 'Neueste', 'Réponse');
        $this->productReview('103', 'de_DE', $productId, '2026-02-01', 'Mittel');

        $body = $this->get('/api/front/guaranteed_opinion/product_reviews?productId='.$productId.'&locale=de_DE&itemsPerPage=2&page=1');
        $members = $this->members($body);

        self::assertSame(['102', '103'], array_column($members, 'id'));
        self::assertSame(['de_DE'], array_values(array_unique(array_column($members, 'locale'))));
        $publicFields = ['id', 'productId', 'locale', 'rate', 'name', 'message', 'reviewDate', 'reply', 'replyDate'];
        foreach ($members as $member) {
            self::assertSame([], array_values(array_diff(
                array_filter(array_keys($member), static fn (string $key): bool => !str_starts_with($key, '@')),
                $publicFields,
            )), 'public fields only: never the order date, the order nor an e-mail');
        }
        self::assertSame('Réponse', $members[0]['reply']);
        self::assertSame(3, $body['totalItems'] ?? $body['hydra:totalItems'] ?? null);

        $secondPage = $this->members($this->get('/api/front/guaranteed_opinion/product_reviews?productId='.$productId.'&locale=de_DE&itemsPerPage=2&page=2'));
        self::assertSame(['101'], array_column($secondPage, 'id'));
        self::assertSame('Sehr gut', $secondPage[0]['message']);

        $french = $this->members($this->get('/api/front/guaranteed_opinion/product_reviews?productId='.$productId.'&locale=fr_FR'));
        self::assertSame(['Très bien'], array_column($french, 'message'));
    }

    public function testProductReviewsRefuseAMissingProduct(): void
    {
        $response = self::$kernel->handle(Request::create('/api/front/guaranteed_opinion/product_reviews?locale=fr_FR', server: ['HTTP_ACCEPT' => 'application/json']));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testRatingsRefuseAMissingProductInsteadOfAnswering200WithNothing(): void
    {
        $response = self::$kernel->handle(Request::create('/api/front/guaranteed_opinion/product_ratings?locale=fr_FR', server: ['HTTP_ACCEPT' => 'application/json']));

        self::assertSame(400, $response->getStatusCode());
    }

    public function testRatingsOfAGridOfProductsAreThoseOfTheLanguageInOneQuery(): void
    {
        $productIds = [];
        for ($index = 0; $index < 6; ++$index) {
            $productIds[] = $this->productId();
        }
        foreach ($productIds as $index => $productId) {
            $this->rating($productId, 'fr_FR', 10 + $index, '4.'.$index);
            if ($index < 4) {
                $this->rating($productId, 'de_DE', 20 + $index, '3.'.$index);
            }
        }

        $reader = new ReviewReader();
        $queriesForOne = $this->countQueries(static fn () => $reader->productRatings([$productIds[0]], 'de_DE'));
        $ratings = [];
        $queriesForSix = $this->countQueries(static function () use ($reader, $productIds, &$ratings): void {
            $ratings = $reader->productRatings($productIds, 'de_DE');
        });

        self::assertSame(1, $queriesForOne);
        self::assertSame(1, $queriesForSix);
        self::assertSame(\array_slice($productIds, 0, 4), array_keys($ratings));

        $query = implode('&', array_map(static fn (int $productId): string => 'productId[]='.$productId, $productIds));
        $members = $this->members($this->get('/api/front/guaranteed_opinion/product_ratings?'.$query.'&locale=de_DE'));
        $byProduct = array_column($members, null, 'productId');

        self::assertCount(4, $members);
        self::assertSame(21, $byProduct[$productIds[1]]['total']);
        self::assertEqualsWithDelta(3.1, $byProduct[$productIds[1]]['average'], 0.001);
        self::assertSame('de_DE', $byProduct[$productIds[1]]['locale']);
    }

    public function testTheThemeReadsTheRatingsThroughResources(): void
    {
        $productId = $this->productId();
        $this->rating($productId, 'fr_FR', 12, '4.8');
        $this->rating($productId, 'de_DE', 3, '4.1');

        $dataAccess = static::getContainer()->get(DataAccessService::class);
        self::assertInstanceOf(DataAccessService::class, $dataAccess);
        $ratings = $dataAccess->resources('/api/front/guaranteed_opinion/product_ratings', ['productId' => [$productId], 'locale' => 'de_DE']);

        self::assertIsArray($ratings);
        self::assertSame(3, $ratings[0]['total'] ?? null, json_encode($ratings, \JSON_THROW_ON_ERROR));
        self::assertSame('de_DE', $ratings[0]['locale']);
    }

    public function testSiteReviewsAndRatingAreThoseOfTheLanguage(): void
    {
        $this->siteReview(9001, 'fr_FR', '2026-01-01', 'Boutique sérieuse');
        $this->siteReview(9001, 'de_DE', '2026-01-01', 'Zuverlässig');
        $this->siteReview(9002, 'de_DE', '2026-02-01', 'Schnell');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY, '1044', 'de_DE');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_AVERAGE_CONFIG_KEY, '4.7', 'de_DE');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_RATING_TOTAL_CONFIG_KEY, '3506', 'fr_FR');

        $members = $this->members($this->get('/api/front/guaranteed_opinion/site_reviews?locale=de_DE'));
        $rating = $this->get('/api/front/guaranteed_opinion/site_rating?locale=de_DE');

        self::assertSame(['Schnell', 'Zuverlässig'], array_slice(array_column($members, 'message'), 0, 2));
        self::assertNotContains('Boutique sérieuse', array_column($members, 'message'));
        self::assertSame(1044, $rating['total']);
        self::assertEqualsWithDelta(4.7, $rating['average'], 0.001);
        self::assertSame(GuaranteedOpinion::MAPPING_DEFAULT_URL['de_DE'], $rating['reviewsUrl']);
    }

    public function testSiteRatingGivesTheWidgetAndIframeCodeAsSavedByTheConfigurationPage(): void
    {
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY, htmlspecialchars('<div id="widget-test">avis</div>'));
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY, '');

        $rating = $this->get('/api/front/guaranteed_opinion/site_rating?locale=fr_FR');

        self::assertSame('<div id="widget-test">avis</div>', $rating['widget']);
        self::assertArrayNotHasKey('widgetIframe', $rating, 'an empty code is no code (null fields are left out)');
        self::assertSame([], array_diff(array_keys($rating), ['@context', '@id', '@type', 'locale', 'total', 'average', 'reviewsUrl', 'widget']), 'public fields only');
    }

    public function testNextReviewsPageGivesTheRatingOfTheVisitorLanguage(): void
    {
        $productId = $this->productId();
        $this->rating($productId, 'fr_FR', 40, '4.5');
        $this->rating($productId, 'de_DE', 7, '3.9');
        $this->productReview('201', 'de_DE', $productId, '2026-01-01', 'Gut');
        $this->productReview('202', 'fr_FR', $productId, '2026-01-01', 'Bien');

        $request = Request::create('/guaranteed_opinion/product_reviews/'.$productId.'/offset/0/limit/5');
        $session = new Session(new MockArraySessionStorage());
        $session->setLang(LangQuery::create()->findOneByLocale('de_DE') ?? self::fail('de_DE is missing from the test database'));
        $request->setSession($session);

        $response = (new FrontController())->productReviews($productId, 0, 5, $request, new ReviewReader());
        $body = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame(7, $body['total']);
        self::assertSame('3.9', $body['average']);
        self::assertSame(['Gut'], array_column($body['reviews'], 'message'));
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $uri): array
    {
        $response = self::$kernel->handle(Request::create($uri, server: ['HTTP_ACCEPT' => 'application/ld+json']));
        self::assertSame(200, $response->getStatusCode(), (string) $response->getContent());

        $body = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($body);

        return $body;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return list<array<string, mixed>>
     */
    private function members(array $body): array
    {
        $members = $body['member'] ?? $body['hydra:member'] ?? null;
        self::assertIsArray($members, json_encode($body, \JSON_THROW_ON_ERROR));

        return array_values($members);
    }

    private function productId(): int
    {
        $fixtures = $this->createFixtureFactory();

        return (int) $fixtures->product($fixtures->category(), $fixtures->taxRule(), $fixtures->currency())->getId();
    }

    private function productReview(string $id, string $locale, int $productId, string $date, string $message, ?string $reply = null): void
    {
        (new GuaranteedOpinionProductReview())
            ->setReply($reply)
            ->setReplyDate(null === $reply ? null : $date)
            ->setProductReviewId($id)
            ->setLocale($locale)
            ->setProductId($productId)
            ->setName('Client '.$id)
            ->setRate('4.0')
            ->setReview($message)
            ->setReviewDate($date)
            ->setOrderDate($date)
            ->save();
    }

    private function siteReview(int $id, string $locale, string $date, string $message): void
    {
        (new GuaranteedOpinionSiteReview())
            ->setSiteReviewId($id)
            ->setLocale($locale)
            ->setName('Client '.$id)
            ->setRate('5.0')
            ->setReview($message)
            ->setReviewDate($date)
            ->save();
    }

    private function rating(int $productId, string $locale, int $total, string $average): void
    {
        (new GuaranteedOpinionProductRating())
            ->setProductId($productId)
            ->setLocale($locale)
            ->setTotal($total)
            ->setAverage($average)
            ->save();
    }

    private function countQueries(callable $callable): int
    {
        $connection = Propel::getReadConnection('TheliaMain');
        self::assertInstanceOf(ConnectionWrapper::class, $connection);
        $connection->useDebug(true);
        $before = $connection->getQueryCount();

        $callable();

        return $connection->getQueryCount() - $before;
    }
}
