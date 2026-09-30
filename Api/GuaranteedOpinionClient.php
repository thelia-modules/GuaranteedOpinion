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

namespace GuaranteedOpinion\Api;

use GuaranteedOpinion\GuaranteedOpinion;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Guaranteed Reviews API, with the keys of the language asked for: each shop language has its own account.
 * The keys never appear in an exception message nor in a log (the URL of the public API carries the review key): the
 * client is the one of the module, built without a logger, never the framework one.
 */
final readonly class GuaranteedOpinionClient
{
    private const URL_API = 'https://api.guaranteed-reviews.com/';
    private const URL_API_REVIEW = 'public/v3/reviews';
    private const URL_API_ORDER = 'private/v3/orders';

    public function __construct(
        #[Autowire(service: GuaranteedOpinion::HTTP_CLIENT_SERVICE)]
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * All the reviews of the store ($scope 'site') or of one product ($scope: its Guaranteed Reviews id).
     *
     * @return array<string, mixed>
     *
     * @throws \JsonException
     * @throws \RuntimeException
     */
    public function getReviewsFromApi(string $scope, string $locale): array
    {
        $key = $this->key(GuaranteedOpinion::API_REVIEW_CONFIG_KEY, $locale);

        $content = $this->call(
            'GET',
            self::URL_API.self::URL_API_REVIEW.'/'.rawurlencode($key).'/'.rawurlencode($scope),
            [],
        );

        $jsonResponse = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($jsonResponse)) {
            throw new \RuntimeException('Unexpected answer of the Guaranteed Reviews API');
        }

        if (isset($jsonResponse['data']) && 200 !== $jsonResponse['data']) {
            throw new \RuntimeException(\is_string($jsonResponse['message'] ?? null) ? $jsonResponse['message'] : 'Guaranteed Reviews API error');
        }

        return $jsonResponse;
    }

    /**
     * @throws \JsonException
     * @throws \RuntimeException
     */
    public function sendOrder(string $jsonOrder, string $locale): object
    {
        $formData = new FormDataPart([
            'api_key' => $this->key(GuaranteedOpinion::API_ORDER_CONFIG_KEY, $locale),
            'orders' => $jsonOrder,
        ]);

        // Same address as the 1.1.x line in production, double slash included.
        $content = $this->call('POST', self::URL_API.'/'.self::URL_API_ORDER, [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        $jsonResponse = json_decode($content, false, 512, \JSON_THROW_ON_ERROR);

        if (!\is_object($jsonResponse)) {
            throw new \RuntimeException('Unexpected answer of the Guaranteed Reviews API');
        }

        return $jsonResponse;
    }

    private function key(string $configKey, string $locale): string
    {
        $key = trim((string) GuaranteedOpinion::getConfigValue($configKey, null, $locale));

        if ('' === $key) {
            throw new \RuntimeException(\sprintf('No Guaranteed Reviews key "%s" configured for the language %s: nothing is sent', $configKey, $locale));
        }

        return $key;
    }

    /**
     * @param array<string, mixed> $options
     */
    private function call(string $method, string $url, array $options): string
    {
        try {
            return $this->httpClient->request($method, $url, $options)->getContent(false);
        } catch (ExceptionInterface) {
            throw new \RuntimeException(\sprintf('The Guaranteed Reviews API could not be reached (%s %s)', $method, parse_url($url, \PHP_URL_HOST)));
        }
    }
}
