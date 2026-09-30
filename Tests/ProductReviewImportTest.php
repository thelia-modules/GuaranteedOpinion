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

use GuaranteedOpinion\Model\GuaranteedOpinionProductRatingQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use GuaranteedOpinion\Service\ProductReviewService;
use GuaranteedOpinion\Service\SiteReviewService;
use Thelia\Test\IntegrationTestCase;

/**
 * Runs on a disposable database (`php bin/test-prepare` with DATABASE_NAME ending in `_test`), never on the shop's.
 */
final class ProductReviewImportTest extends IntegrationTestCase
{
    private const PRODUCT_ID = 987654;

    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        parent::setUp();
    }

    public function testTheSameReviewIdIsKeptOncePerLanguage(): void
    {
        $service = new ProductReviewService();

        self::assertTrue($service->addGuaranteedOpinionProductRow($this->apiReview('55'), self::PRODUCT_ID, 'fr_FR'));
        self::assertTrue($service->addGuaranteedOpinionProductRow($this->apiReview('55'), self::PRODUCT_ID, 'de_DE'));
        self::assertTrue($service->addGuaranteedOpinionProductRow($this->apiReview('55', 'Merci'), self::PRODUCT_ID, 'de_DE'));

        self::assertSame(['de_DE', 'fr_FR'], $this->localesOf('55'));
        $german = GuaranteedOpinionProductReviewQuery::create()->findPk(['55', 'de_DE']);
        self::assertSame('Merci', $german?->getReply());
        self::assertNull(GuaranteedOpinionProductReviewQuery::create()->findPk(['55', 'fr_FR'])?->getReply());
    }

    public function testRemovingTheReviewsDeletedFromOneLanguageKeepsTheOtherLanguages(): void
    {
        $service = new ProductReviewService();
        foreach (['61', '62'] as $reviewId) {
            $service->addGuaranteedOpinionProductRow($this->apiReview($reviewId), self::PRODUCT_ID, 'fr_FR');
            $service->addGuaranteedOpinionProductRow($this->apiReview($reviewId), self::PRODUCT_ID, 'de_DE');
        }

        $removed = $service->removeDeletedReviews(self::PRODUCT_ID, 'de_DE', [$this->apiReview('61')]);

        self::assertSame(1, $removed);
        self::assertSame(['de_DE', 'fr_FR'], $this->localesOf('61'));
        self::assertSame(['fr_FR'], $this->localesOf('62'), 'the French review 62 is still returned by the French account');
    }

    public function testTheRatingIsStoredByLanguage(): void
    {
        $service = new ProductReviewService();

        $service->addGuaranteedOpinionProductRating(self::PRODUCT_ID, ['total' => 12, 'average' => '4.6'], 'fr_FR');
        $service->addGuaranteedOpinionProductRating(self::PRODUCT_ID, ['total' => 3, 'average' => '4.1'], 'de_DE');
        $service->addGuaranteedOpinionProductRating(self::PRODUCT_ID, ['total' => 4, 'average' => '4.2'], 'de_DE');

        self::assertSame(12, GuaranteedOpinionProductRatingQuery::create()->findPk([self::PRODUCT_ID, 'fr_FR'])?->getTotal());
        self::assertSame(4, GuaranteedOpinionProductRatingQuery::create()->findPk([self::PRODUCT_ID, 'de_DE'])?->getTotal());
        self::assertSame(2, GuaranteedOpinionProductRatingQuery::create()->filterByProductId(self::PRODUCT_ID)->count());
    }

    public function testTheSameStoreReviewIdIsKeptOncePerLanguage(): void
    {
        $service = new SiteReviewService();

        self::assertTrue($service->addGuaranteedOpinionSiteRow($this->apiReview('7001'), 'fr_FR'));
        self::assertTrue($service->addGuaranteedOpinionSiteRow($this->apiReview('7001'), 'de_DE'));
        self::assertTrue($service->addGuaranteedOpinionSiteRow($this->apiReview('7001'), 'de_DE'));

        self::assertSame(2, GuaranteedOpinionSiteReviewQuery::create()->filterBySiteReviewId(7001)->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function apiReview(string $id, string $reply = ''): array
    {
        return [
            'id' => $id,
            'c' => 'Client '.$id,
            'txt' => 'Avis '.$id,
            'date' => '2026-05-01 10:00:00',
            'r' => 4.5,
            'odate' => '2026-04-20 09:00:00',
            'reply' => $reply,
            'rdate' => '' === $reply ? '' : '2026-05-02 10:00:00',
        ];
    }

    /**
     * @return list<string>
     */
    private function localesOf(string $reviewId): array
    {
        $locales = [];
        foreach (GuaranteedOpinionProductReviewQuery::create()->filterByProductReviewId($reviewId)->orderByLocale()->find() as $review) {
            $locales[] = (string) $review->getLocale();
        }

        return $locales;
    }
}
