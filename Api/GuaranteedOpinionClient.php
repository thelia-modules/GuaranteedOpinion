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

use GuaranteedOpinion\GuaranteedOpinion;
use Thelia\Model\Order;

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
     * @throws \JsonException
     */
    public function getReviewsFromApi(string $scope = 'site'): array
    {
        $url = self::URL_API . "/" . self::URL_API_REVIEW . "/" . GuaranteedOpinion::getConfigValue(GuaranteedOpinion::CONFIG_API_REVIEW) . "/" . $scope;

        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        return json_decode($response, false, 512, JSON_THROW_ON_ERROR)->reviews;
    }

    /**
     * @throws \JsonException
     */
    public function sendOrder($jsonOrder)
    {
        $url = self::URL_API . "/" . self::URL_API_ORDER;

        $request = [
            'api_key' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::CONFIG_API_ORDER),
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
