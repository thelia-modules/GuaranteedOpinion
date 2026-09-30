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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Thelia\Core\Install\Database;

/**
 * Config/update/2.1.0.sql on the schemas a shop may hold (2.0.0 for Thelia 3, 1.1.x from Thelia 2), run twice
 * through the splitter Thelia uses. DDL commits on its own, so the test works in a database of its own, created
 * and dropped here: DATABASE_NAME with "_update" before its "_test" suffix.
 */
final class UpdateScriptTest extends TestCase
{
    private const SCRIPT = __DIR__.'/../Config/update/2.1.0.sql';
    private const INSTALL_SCRIPT = __DIR__.'/../Config/TheliaMain.sql';

    private const PRODUCT_REVIEW_COLUMNS = ['product_review_id', 'locale', 'name', 'rate', 'review', 'review_date', 'product_id', 'order_date', 'reply', 'reply_date'];
    private const SITE_REVIEW_COLUMNS = ['site_review_id', 'locale', 'name', 'rate', 'review', 'review_date', 'order_date', 'reply', 'reply_date'];

    private const COMMON_TABLES = <<<'SQL'
        CREATE TABLE `lang` (`id` INT PRIMARY KEY, `locale` VARCHAR(45), `by_default` TINYINT);
        CREATE TABLE `module` (`id` INT PRIMARY KEY, `code` VARCHAR(55));
        CREATE TABLE `module_config` (`id` INT PRIMARY KEY, `module_id` INT, `name` VARCHAR(255));
        CREATE TABLE `module_config_i18n` (`id` INT, `locale` VARCHAR(5), `value` TEXT, PRIMARY KEY (`id`, `locale`));
        CREATE TABLE `guaranteed_opinion_order_queue` (`id` INT NOT NULL AUTO_INCREMENT, `order_id` INT NOT NULL, `treated_at` DATETIME, `status` INT, PRIMARY KEY (`id`));
        INSERT INTO `lang` VALUES (1, 'en_US', 0), (2, 'fr_FR', 1), (3, 'de_DE', 0);
        INSERT INTO `module` VALUES (10, 'GuaranteedOpinion'), (11, 'Other');
        INSERT INTO `module_config` VALUES (1, 10, 'guaranteedopinion.api.review'), (2, 10, 'guaranteedopinion.api.order'), (3, 10, 'guaranteedopinion.status_to_export'), (4, 11, 'guaranteedopinion.api.review');
        INSERT INTO `module_config_i18n` VALUES (1, 'en_US', 'review-key'), (2, 'en_US', 'order-key'), (2, 'fr_FR', 'french-order-key'), (3, 'en_US', '4'), (4, 'en_US', 'other-module');
        INSERT INTO `guaranteed_opinion_order_queue` (`order_id`, `status`) VALUES (1, 0), (2, 1);
        SQL;

    private const THELIA_TABLES = <<<'SQL'
        CREATE TABLE `lang` (`id` INT PRIMARY KEY, `locale` VARCHAR(45), `by_default` TINYINT);
        CREATE TABLE `module` (`id` INT PRIMARY KEY, `code` VARCHAR(55));
        CREATE TABLE `module_config` (`id` INT PRIMARY KEY, `module_id` INT, `name` VARCHAR(255));
        CREATE TABLE `module_config_i18n` (`id` INT, `locale` VARCHAR(5), `value` TEXT, PRIMARY KEY (`id`, `locale`));
        INSERT INTO `lang` VALUES (1, 'en_US', 0), (2, 'fr_FR', 1);
        INSERT INTO `module` VALUES (10, 'GuaranteedOpinion');
        SQL;

    private \PDO $pdo;

    private string $databaseName;

    protected function setUp(): void
    {
        $testDatabase = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($testDatabase) || !str_ends_with($testDatabase, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $testDatabase));
        }

        $this->databaseName = substr($testDatabase, 0, -\strlen('_test')).'_update_test';
        $server = new \PDO(
            \sprintf('mysql:host=%s;port=%s', (string) ($_SERVER['DATABASE_HOST'] ?? getenv('DATABASE_HOST')), (string) ($_SERVER['DATABASE_PORT'] ?? getenv('DATABASE_PORT') ?: '3306')),
            (string) ($_SERVER['DATABASE_USER'] ?? getenv('DATABASE_USER')),
            (string) ($_SERVER['DATABASE_PASSWORD'] ?? getenv('DATABASE_PASSWORD')),
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
        );
        $server->exec(\sprintf('DROP DATABASE IF EXISTS `%1$s`; CREATE DATABASE `%1$s`', $this->databaseName));
        $server->exec(\sprintf('USE `%s`', $this->databaseName));
        $this->pdo = $server;
    }

    protected function tearDown(): void
    {
        $this->pdo->exec(\sprintf('DROP DATABASE IF EXISTS `%s`', $this->databaseName));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function earlierSchemas(): iterable
    {
        yield '2.0.0 (Thelia 3, no language)' => [<<<'SQL'
            CREATE TABLE `guaranteed_opinion_product_review` (`id` INTEGER NOT NULL AUTO_INCREMENT, `product_review_id` VARCHAR(55) NOT NULL, `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `product_id` INTEGER, `order_id` VARCHAR(255), `order_date` DATETIME,
                `reply` VARCHAR(255), `reply_date` DATETIME, PRIMARY KEY (`id`), UNIQUE INDEX `guaranteed_opinion_product_review_id_unique` (`product_review_id`));
            CREATE TABLE `guaranteed_opinion_site_review` (`id` INTEGER NOT NULL AUTO_INCREMENT, `site_review_id` INTEGER NOT NULL, `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `order_id` VARCHAR(255), `order_date` DATETIME,
                `reply` VARCHAR(255), `reply_date` DATETIME, PRIMARY KEY (`id`, `site_review_id`), UNIQUE INDEX `guaranteed_opinion_site_review_id_unique` (`site_review_id`));
            CREATE TABLE `guaranteed_opinion_product_rating` (`product_id` INTEGER NOT NULL, `total` INTEGER, `average` VARCHAR(255), PRIMARY KEY (`product_id`));
            INSERT INTO `guaranteed_opinion_product_review` (`product_review_id`, `name`, `rate`, `product_id`, `order_id`, `reply`) VALUES ('a1', 'Ann', 4.5, 7, 'o1', 'Merci'), ('a2', 'Bob', 3.0, 7, NULL, NULL);
            INSERT INTO `guaranteed_opinion_site_review` (`site_review_id`, `name`, `rate`, `order_id`) VALUES (500, 'Cid', 5.0, 'o2'), (501, 'Dan', 4.0, NULL);
            INSERT INTO `guaranteed_opinion_product_rating` VALUES (7, 2, '3.75'), (8, 1, '5');
            SQL];

        yield '1.1.1 (language columns added, keys not changed yet)' => [<<<'SQL'
            CREATE TABLE `guaranteed_opinion_product_review` (`id` INTEGER NOT NULL AUTO_INCREMENT, `product_review_id` VARCHAR(55) NOT NULL, `locale` VARCHAR(5), `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `product_id` INTEGER, `order_date` DATETIME,
                `reply` VARBINARY(10000), `reply_date` DATETIME, PRIMARY KEY (`id`), UNIQUE INDEX `guaranteed_opinion_product_review_id_unique` (`product_review_id`));
            CREATE TABLE `guaranteed_opinion_site_review` (`id` INTEGER NOT NULL AUTO_INCREMENT, `site_review_id` INTEGER NOT NULL, `locale` VARCHAR(5), `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `order_date` DATETIME,
                `reply` VARBINARY(10000), `reply_date` DATETIME, PRIMARY KEY (`id`, `site_review_id`), UNIQUE INDEX `guaranteed_opinion_site_review_id_unique` (`site_review_id`));
            CREATE TABLE `guaranteed_opinion_product_rating` (`product_id` INTEGER NOT NULL, `locale` VARCHAR(5), `total` INTEGER, `average` VARCHAR(255), PRIMARY KEY (`product_id`));
            INSERT INTO `guaranteed_opinion_product_review` (`product_review_id`, `locale`, `name`, `rate`, `product_id`, `reply`) VALUES ('a1', NULL, 'Ann', 4.5, 7, 'Merci'), ('a2', 'de_DE', 'Bob', 3.0, 7, NULL);
            INSERT INTO `guaranteed_opinion_site_review` (`site_review_id`, `locale`, `name`, `rate`) VALUES (500, NULL, 'Cid', 5.0), (501, 'de_DE', 'Dan', 4.0);
            INSERT INTO `guaranteed_opinion_product_rating` VALUES (7, NULL, 2, '3.75'), (8, 'de_DE', 1, '5');
            SQL];

        yield '1.1.5 (Thelia 2 in production)' => [<<<'SQL'
            CREATE TABLE `guaranteed_opinion_product_review` (`product_review_id` VARCHAR(55) NOT NULL, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `product_id` INTEGER, `order_date` DATETIME,
                `reply` VARBINARY(10000), `reply_date` DATETIME, PRIMARY KEY (`product_review_id`, `locale`));
            CREATE TABLE `guaranteed_opinion_site_review` (`site_review_id` INTEGER NOT NULL, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(255),
                `rate` DECIMAL(2,1) DEFAULT 0, `review` VARBINARY(10000), `review_date` DATETIME, `order_date` DATETIME,
                `reply` VARBINARY(10000), `reply_date` DATETIME, PRIMARY KEY (`site_review_id`, `locale`));
            CREATE TABLE `guaranteed_opinion_product_rating` (`product_id` INTEGER NOT NULL, `locale` VARCHAR(5) NOT NULL, `total` INTEGER, `average` VARCHAR(255), PRIMARY KEY (`product_id`, `locale`));
            INSERT INTO `guaranteed_opinion_product_review` (`product_review_id`, `locale`, `name`, `rate`, `product_id`, `reply`) VALUES ('a1', 'fr_FR', 'Ann', 4.5, 7, 'Merci'), ('a2', 'de_DE', 'Bob', 3.0, 7, NULL), ('a1', 'de_DE', 'Ann', 4.5, 7, NULL);
            INSERT INTO `guaranteed_opinion_site_review` (`site_review_id`, `locale`, `name`, `rate`) VALUES (500, 'fr_FR', 'Cid', 5.0), (501, 'de_DE', 'Dan', 4.0), (500, 'de_DE', 'Cid', 5.0);
            INSERT INTO `guaranteed_opinion_product_rating` VALUES (7, 'fr_FR', 2, '3.75'), (8, 'de_DE', 1, '5'), (7, 'de_DE', 1, '4');
            SQL];
    }

    #[DataProvider('earlierSchemas')]
    public function testBringsTheSchemaToOneRowPerLanguageWithoutLosingARowAndCanRunAgain(string $schema): void
    {
        $this->pdo->exec(self::COMMON_TABLES);
        $this->pdo->exec($schema);
        $before = $this->counts();

        $this->runScript();
        $afterFirstRun = $this->snapshot();
        $this->runScript();

        self::assertSame($afterFirstRun, $this->snapshot(), 'a second run changes nothing');
        self::assertSame($before, $this->counts(), 'no row lost');

        self::assertSame(self::PRODUCT_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_product_review'));
        self::assertSame(self::SITE_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_site_review'));
        self::assertSame(['product_id', 'locale', 'total', 'average'], $this->columns('guaranteed_opinion_product_rating'));

        self::assertSame(['PRIMARY' => 'product_review_id,locale', 'idx_guaranteed_opinion_product_review_product_locale' => 'product_id,locale,review_date'], $this->indexes('guaranteed_opinion_product_review'));
        self::assertSame(['PRIMARY' => 'site_review_id,locale', 'idx_guaranteed_opinion_site_review_locale_date' => 'locale,review_date'], $this->indexes('guaranteed_opinion_site_review'));
        self::assertSame(['PRIMARY' => 'product_id,locale'], $this->indexes('guaranteed_opinion_product_rating'));

        foreach (['guaranteed_opinion_product_review', 'guaranteed_opinion_site_review', 'guaranteed_opinion_product_rating'] as $table) {
            self::assertSame('NO', $this->column($table, 'locale')['is_nullable'], $table.'.locale is required');
            self::assertSame(0, (int) $this->pdo->query(\sprintf("SELECT COUNT(*) FROM `%s` WHERE `locale` NOT IN ('fr_FR', 'de_DE')", $table))->fetchColumn());
        }
        self::assertSame('varbinary', $this->column('guaranteed_opinion_product_review', 'reply')['data_type']);
        self::assertSame('varbinary', $this->column('guaranteed_opinion_site_review', 'reply')['data_type']);
        self::assertSame('fr_FR', $this->pdo->query("SELECT `locale` FROM `guaranteed_opinion_product_review` WHERE `product_review_id` = 'a1' AND `name` = 'Ann' ORDER BY `locale` DESC LIMIT 1")->fetchColumn(), 'a review without language gets the default language');
        self::assertSame('Merci', $this->pdo->query("SELECT `reply` FROM `guaranteed_opinion_product_review` WHERE `product_review_id` = 'a1' AND `locale` = 'fr_FR'")->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM `guaranteed_opinion_order_queue`')->fetchColumn(), 'the send queue is left as it is');

        self::assertSame([
            [1, 'en_US', 'review-key'],
            [1, 'fr_FR', 'review-key'],
            [2, 'en_US', 'order-key'],
            [2, 'fr_FR', 'french-order-key'],
            [3, 'en_US', '4'],
            [4, 'en_US', 'other-module'],
        ], $this->pdo->query('SELECT `id`, `locale`, `value` FROM `module_config_i18n` ORDER BY `id`, `locale`')->fetchAll(\PDO::FETCH_NUM), 'keys of the module copied to the default language, a key already set there kept');
    }

    /**
     * The order postActivation() follows on a new install: TheliaMain.sql, then the update script.
     */
    public function testANewInstallIsLeftAsTheInstallScriptCreatedIt(): void
    {
        $this->pdo->exec(self::THELIA_TABLES);

        $this->runInstallScript();
        $afterInstall = $this->snapshot();
        $this->runScript();

        self::assertSame($afterInstall, $this->snapshot(), 'the update script has nothing left to change on a new install');
        self::assertSame(self::PRODUCT_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_product_review'));
        self::assertSame(self::SITE_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_site_review'));
        self::assertSame(['product_id', 'locale', 'total', 'average'], $this->columns('guaranteed_opinion_product_rating'));
    }

    /**
     * A module removed without destroy() leaves its 2.0.0 tables and loses is_initialized: activating it again runs
     * TheliaMain.sql, which keeps the tables, then the update script, which brings them to the per-language schema.
     */
    public function testAReinstallOnTheTablesOf200EndsOnThePerLanguageSchemaWithItsRows(): void
    {
        $this->pdo->exec(self::COMMON_TABLES);
        $this->pdo->exec(iterator_to_array(self::earlierSchemas())['2.0.0 (Thelia 3, no language)'][0]);
        $before = $this->counts();

        $this->runInstallScript();
        self::assertNotSame(self::PRODUCT_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_product_review'), 'TheliaMain.sql alone leaves the 2.0.0 tables as they are');
        $this->runScript();

        self::assertSame($before, $this->counts(), 'no row lost');
        self::assertSame(self::PRODUCT_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_product_review'));
        self::assertSame(self::SITE_REVIEW_COLUMNS, $this->columns('guaranteed_opinion_site_review'));
        self::assertSame(['product_id', 'locale', 'total', 'average'], $this->columns('guaranteed_opinion_product_rating'));
        self::assertSame(['PRIMARY' => 'product_review_id,locale', 'idx_guaranteed_opinion_product_review_product_locale' => 'product_id,locale,review_date'], $this->indexes('guaranteed_opinion_product_review'));
    }

    private function runScript(): void
    {
        (new Database($this->pdo))->insertSql(null, [self::SCRIPT]);
    }

    private function runInstallScript(): void
    {
        (new Database($this->pdo))->insertSql(null, [self::INSTALL_SCRIPT]);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $counts = [];
        foreach (['guaranteed_opinion_product_review', 'guaranteed_opinion_site_review', 'guaranteed_opinion_product_rating', 'guaranteed_opinion_order_queue'] as $table) {
            $counts[$table] = (int) $this->pdo->query(\sprintf('SELECT COUNT(*) FROM `%s`', $table))->fetchColumn();
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(): array
    {
        $snapshot = [];
        foreach (['guaranteed_opinion_product_review', 'guaranteed_opinion_site_review', 'guaranteed_opinion_product_rating', 'module_config_i18n'] as $table) {
            $snapshot[$table] = [
                'create' => $this->pdo->query(\sprintf('SHOW CREATE TABLE `%s`', $table))->fetch(\PDO::FETCH_NUM)[1] ?? null,
                'rows' => $this->pdo->query(\sprintf('SELECT * FROM `%s` ORDER BY 1, 2', $table))->fetchAll(\PDO::FETCH_ASSOC),
            ];
        }

        return $snapshot;
    }

    /**
     * @return list<string>
     */
    private function columns(string $table): array
    {
        $statement = $this->pdo->prepare('SELECT `column_name` FROM information_schema.columns WHERE `table_schema` = DATABASE() AND `table_name` = ? ORDER BY `ordinal_position`');
        $statement->execute([$table]);

        return array_map('strval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @return array<string, string> index name => columns
     */
    private function indexes(string $table): array
    {
        $statement = $this->pdo->prepare('SELECT `index_name`, GROUP_CONCAT(`column_name` ORDER BY `seq_in_index` SEPARATOR \',\') FROM information_schema.statistics WHERE `table_schema` = DATABASE() AND `table_name` = ? GROUP BY `index_name` ORDER BY `index_name` = \'PRIMARY\' DESC, `index_name`');
        $statement->execute([$table]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * @return array<string, string>
     */
    private function column(string $table, string $column): array
    {
        $statement = $this->pdo->prepare('SELECT LOWER(`data_type`) AS `data_type`, `is_nullable` FROM information_schema.columns WHERE `table_schema` = DATABASE() AND `table_name` = ? AND `column_name` = ?');
        $statement->execute([$table, $column]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        self::assertIsArray($row, $table.'.'.$column.' is missing');

        return $row;
    }
}
