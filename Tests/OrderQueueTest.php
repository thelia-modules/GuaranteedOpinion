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

use GuaranteedOpinion\Api\GuaranteedOpinionClient;
use GuaranteedOpinion\Command\SendOrderCommand;
use GuaranteedOpinion\EventListeners\OrderListener;
use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueue;
use GuaranteedOpinion\Model\GuaranteedOpinionOrderQueueQuery;
use GuaranteedOpinion\Service\OrderService;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Model\LangQuery;
use Thelia\Model\ModuleConfigQuery;
use Thelia\Model\Order;
use Thelia\Model\OrderStatus;
use Thelia\Model\OrderStatusQuery;
use Thelia\Test\IntegrationTestCase;

/**
 * Runs on a disposable database (`php bin/test-prepare` with DATABASE_NAME ending in `_test`), never on the shop's.
 * No request leaves: the Guaranteed Reviews API is a MockHttpClient.
 */
final class OrderQueueTest extends IntegrationTestCase
{
    private const FRENCH_ORDER_KEY = 'fr-order-key-test';
    private const GERMAN_ORDER_KEY = 'de-order-key-test';

    protected function setUp(): void
    {
        $databaseName = $_SERVER['DATABASE_NAME'] ?? getenv('DATABASE_NAME');
        if (!\is_string($databaseName) || !str_ends_with($databaseName, '_test')) {
            self::fail(\sprintf('Refusing to run on the database "%s": use a *_test database.', (string) $databaseName));
        }

        parent::setUp();

        GuaranteedOpinionOrderQueueQuery::create()->deleteAll();
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, self::FRENCH_ORDER_KEY, 'fr_FR');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, self::GERMAN_ORDER_KEY, 'de_DE');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY, '', 'en_US');
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::STATUS_TO_EXPORT_CONFIG_KEY, (string) $this->statusId(OrderStatus::CODE_SENT));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        ModuleConfigQuery::resetConfigCache();
    }

    public function testSendingOneLanguageUsesItsKeyAndLeavesTheOtherLanguagePending(): void
    {
        $frenchOrder = $this->order('fr_FR');
        $germanOrder = $this->order('de_DE');
        $frenchRow = $this->queue($frenchOrder);
        $germanRow = $this->queue($germanOrder);

        $sent = [];
        $client = new GuaranteedOpinionClient(new MockHttpClient(static function (string $method, string $url, array $options) use (&$sent): MockResponse {
            $sent[] = self::readBody($options);

            return new MockResponse('{"success":1,"orders_count":1,"products_imported":0,"message":"ok"}');
        }));

        $command = new SendOrderCommand($client, new OrderService($this->dispatcher()));
        $command->setContainer(static::getContainer());
        $tester = new CommandTester($command);

        self::assertSame(0, $tester->execute(['--locale' => 'fr_FR']), $tester->getDisplay());
        self::assertCount(1, $sent);
        self::assertStringContainsString(self::FRENCH_ORDER_KEY, $sent[0]);
        self::assertStringNotContainsString(self::GERMAN_ORDER_KEY, $sent[0]);
        self::assertStringContainsString('"id_order":'.$frenchOrder->getId(), $sent[0]);
        self::assertStringNotContainsString('"id_order":'.$germanOrder->getId(), $sent[0]);

        self::assertSame([OrderService::STATUS_SENT, true], $this->rowState($frenchRow));
        self::assertSame([OrderService::STATUS_PENDING, false], $this->rowState($germanRow), 'the German order waits for the German run');

        self::assertSame(0, $tester->execute(['--locale' => 'de_DE']), $tester->getDisplay());
        self::assertCount(2, $sent);
        self::assertStringContainsString(self::GERMAN_ORDER_KEY, $sent[1]);
        self::assertSame([OrderService::STATUS_SENT, true], $this->rowState($germanRow));
    }

    public function testARefusedBatchStaysPending(): void
    {
        $row = $this->queue($this->order('fr_FR'));
        $client = new GuaranteedOpinionClient(new MockHttpClient(new MockResponse('{"success":0,"orders_count":0,"products_imported":0,"message":"refused"}')));

        $command = new SendOrderCommand($client, new OrderService($this->dispatcher()));
        $command->setContainer(static::getContainer());

        self::assertSame(1, (new CommandTester($command))->execute(['--locale' => 'fr_FR']));
        self::assertSame([OrderService::STATUS_PENDING, false], $this->rowState($row));
    }

    public function testWithoutAKeyForTheLanguageNothingIsSent(): void
    {
        $mock = new MockHttpClient(new MockResponse('{"success":1}'));

        try {
            (new GuaranteedOpinionClient($mock))->sendOrder('[]', 'en_US');
            self::fail('No key, no request');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('en_US', $exception->getMessage());
        }

        self::assertSame(0, $mock->getRequestsCount());
    }

    public function testAnUnreachableApiNeverWritesTheKeyInTheMessage(): void
    {
        GuaranteedOpinion::setConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, 'secret-review-key-test', 'fr_FR');
        $client = new GuaranteedOpinionClient(new MockHttpClient(static function (string $method, string $url): never {
            throw new TransportException('Could not resolve host for "'.$url.'"');
        }));

        try {
            $client->getReviewsFromApi('site', 'fr_FR');
            self::fail('An unreachable API must throw');
        } catch (\RuntimeException $exception) {
            self::assertStringNotContainsString('secret-review-key-test', $exception->getMessage());
        }
    }

    public function testAStatusChangeOfAQueuedOrderGoesOnAndNeverAddsItTwice(): void
    {
        $order = $this->order('fr_FR');
        $listener = new OrderListener();

        $order->setStatusId($this->statusId(OrderStatus::CODE_SENT));
        $listener->checkOrderInQueue(new OrderEvent($order));
        $listener->checkOrderInQueue(new OrderEvent($order));
        self::assertSame(1, GuaranteedOpinionOrderQueueQuery::create()->filterByOrderId($order->getId())->count(), 'queued once');

        $order->setStatusId($this->statusId(OrderStatus::CODE_CANCELED));
        $listener->checkOrderInQueue(new OrderEvent($order));
        $reachedAfterTheListener = true;

        self::assertTrue($reachedAfterTheListener, 'the status change goes on after the listener');
        self::assertSame(0, GuaranteedOpinionOrderQueueQuery::create()->filterByOrderId($order->getId())->count(), 'a pending order leaving the exported statuses leaves the queue');

        $order->setStatusId($this->statusId(OrderStatus::CODE_SENT));
        $placed = new OrderEvent($order);
        $placed->setPlacedOrder($order);
        $listener->registerOrder($placed);
        $listener->registerOrder($placed);
        $rows = GuaranteedOpinionOrderQueueQuery::create()->filterByOrderId($order->getId())->find();

        self::assertCount(1, $rows);
        self::assertNull($rows->getFirst()?->getTreatedAt(), 'a paid order is queued pending, so that it is sent');
    }

    public function testASentOrderIsNotRemovedNorQueuedAgain(): void
    {
        $order = $this->order('fr_FR');
        $row = $this->queue($order);
        $row->setStatus(OrderService::STATUS_SENT)->setTreatedAt(new \DateTime())->save();
        $listener = new OrderListener();

        $order->setStatusId($this->statusId(OrderStatus::CODE_CANCELED));
        $listener->checkOrderInQueue(new OrderEvent($order));
        $order->setStatusId($this->statusId(OrderStatus::CODE_SENT));
        $listener->checkOrderInQueue(new OrderEvent($order));

        self::assertSame(1, GuaranteedOpinionOrderQueueQuery::create()->filterByOrderId($order->getId())->count());
        self::assertSame([OrderService::STATUS_SENT, true], $this->rowState($row), 'the row of the sent order is kept');
    }

    private function order(string $locale): Order
    {
        $order = $this->createFixtureFactory()->order();
        $order->setLangId((int) (LangQuery::create()->findOneByLocale($locale)?->getId() ?? self::fail($locale.' is missing from the test database')));
        $order->save();

        return $order;
    }

    private function queue(Order $order): GuaranteedOpinionOrderQueue
    {
        $row = (new GuaranteedOpinionOrderQueue())->setOrderId($order->getId())->setStatus(OrderService::STATUS_PENDING);
        $row->save();

        return $row;
    }

    /**
     * @return array{0: int|null, 1: bool} status, treated
     */
    private function rowState(GuaranteedOpinionOrderQueue $row): array
    {
        $fresh = GuaranteedOpinionOrderQueueQuery::create()->findPk($row->getId()) ?? self::fail('queue row gone');

        return [$fresh->getStatus(), null !== $fresh->getTreatedAt()];
    }

    private function statusId(string $code): int
    {
        return (int) (OrderStatusQuery::create()->findOneByCode($code)?->getId() ?? self::fail('order status '.$code.' is missing'));
    }

    private function dispatcher(): EventDispatcherInterface
    {
        $dispatcher = static::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        return $dispatcher;
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function readBody(array $options): string
    {
        $body = $options['body'] ?? '';

        if (!$body instanceof \Closure) {
            return \is_string($body) ? $body : '';
        }

        $content = '';
        while ('' !== $chunk = $body(16372)) {
            $content .= $chunk;
        }

        return $content;
    }
}
