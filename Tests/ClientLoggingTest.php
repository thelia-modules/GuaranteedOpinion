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
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Thelia\Test\IntegrationTestCase;

/**
 * The URL of the public API carries the review key: the HTTP client of the module must never log it.
 * The request goes to a closed local port, nothing leaves the machine.
 */
final class ClientLoggingTest extends IntegrationTestCase
{
    private const KEY = 'secret-review-key-for-the-logs';

    public function testTheClientOfTheModuleWritesNoLogRecordWithTheKey(): void
    {
        $handler = $this->spyOnTheLoggersOfTheContainer();

        $this->request($this->httpClientOfTheModule());

        self::assertStringNotContainsString(self::KEY, $this->everythingLogged($handler));
    }

    /**
     * Without this control the test above could pass because the spy sees nothing at all.
     */
    public function testTheSpyDoesSeeTheKeyWhenTheClientHasALogger(): void
    {
        $handler = new TestHandler();
        $client = HttpClient::create();
        $client->setLogger(new Logger('http_client', [$handler]));

        $this->request($client);

        self::assertStringContainsString(self::KEY, $this->everythingLogged($handler));
    }

    private function httpClientOfTheModule(): HttpClientInterface
    {
        $client = static::getContainer()->get(GuaranteedOpinionClient::class);
        $httpClient = (new \ReflectionProperty($client, 'httpClient'))->getValue($client);
        self::assertInstanceOf(HttpClientInterface::class, $httpClient);

        return $httpClient;
    }

    private function spyOnTheLoggersOfTheContainer(): TestHandler
    {
        $handler = new TestHandler();
        $container = static::getContainer();

        self::assertTrue($container->has('monolog.logger.http_client'), 'The channel of the HTTP client must be reachable to be spied on');

        foreach (['logger', 'monolog.logger.http_client'] as $serviceId) {
            $logger = $container->get($serviceId);
            self::assertInstanceOf(Logger::class, $logger);
            $logger->pushHandler($handler);
        }

        return $handler;
    }

    private function request(HttpClientInterface $httpClient): void
    {
        try {
            $httpClient->request('GET', 'http://127.0.0.1:9/public/v3/reviews/'.self::KEY.'/site', ['timeout' => 2])->getContent(false);
        } catch (ExceptionInterface) {
            // The port is closed on purpose: only what the client logs matters.
        }
    }

    private function everythingLogged(TestHandler $handler): string
    {
        $lines = [];

        foreach ($handler->getRecords() as $record) {
            $lines[] = $record['message'].' '.var_export($record['context'], true);
        }

        return implode("\n", $lines);
    }
}
