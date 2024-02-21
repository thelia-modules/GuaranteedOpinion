<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace GuaranteedOpinion\Api;

use Exception;
use GuaranteedOpinion\GuaranteedOpinion;
use JsonException;
use RuntimeException;

/**
 * Class GuaranteedOpinionClient
 * @package GuaranteedOpinion\Api
 * @author Chabreuil Antoine <achabreuil@openstudio.com>
 */
class GuaranteedOpinionClient
{
    private const URL_API = "https://api.guaranteed-reviews.com/";
    private const URL_API_REVIEW = "public/v3/reviews";
    private const URL_API_ORDER = "private/v3/orders";

    /**
     * Call API Avis-Garantis
     * Return all the reviews of the store or productId
     *
     * @param string $scope 'site' or productId
     * @return array
     * @throws JsonException
     * @throws RuntimeException
     */
    public function getReviewsFromApi(string $scope = 'site'): array
    {
        $url = self::URL_API . "/" . self::URL_API_REVIEW . "/" . GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_REVIEW_CONFIG_KEY) . "/" . $scope;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        $jsonResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if (isset($jsonResponse['data']) && $jsonResponse['data'] !== 200) {
            throw new RuntimeException($jsonResponse['message']);
        }

        return $jsonResponse;
    }

    /**
     * @throws JsonException
     */
    public function sendOrder($jsonOrder)
    {
        $url = self::URL_API . "/" . self::URL_API_ORDER;

        $request = [
            'api_key' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::API_ORDER_CONFIG_KEY),
            'orders' => $jsonOrder
        ];

        // Prepare CURL request
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

        // Execute CURL request
        $response = curl_exec($ch);

        // Close the connection, release resources used
        curl_close($ch);

        return json_decode($response, false, 512, JSON_THROW_ON_ERROR);
    }
}
