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

use GuaranteedOpinion\GuaranteedOpinion as GuaranteedOpinionModule;
use Thelia\Model\Base\Product;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;
use Thelia\Model\OrderProduct;

/**
 * Class GuaranteedOpinionClient
 * @package GuaranteedOpinion\Api
 * @author Chabreuil Antoine <achabreuil@openstudio.com>
 */
class GuaranteedOpinionClient
{

    const URL_API = "https://www.societe-des-avis-garantis.fr/";
    const SAGAPIENDPOINT = "wp-content/plugins/ag-core/api/";

    function getLast(int $howMany, ?Product $product = null) {
        //todo: call guaranteed-opinions api to get the last $howMany reviews by product or global
//        $url = "https://www.guaranteed-opinions.com/api/last/$howMany";
//        if ($product) {
//            $url .= "?product_id=" . $product->getId();
//        }
//        $curl = curl_init($url);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        $response = curl_exec($curl);
//        curl_close($curl);
//        return json_decode($response);
    }

    function postOrder(Customer $customer, OrderProduct $orderProduct) {
        //todo send this new thelia order to guaranteed-opinions
    }

    function tokenCheck(){
        $domainUrl = $this::URL_API;
        $apiKey = ConfigQuery::read(GuaranteedOpinionModule::CONFIG_API_SECRET);
        $url = $domainUrl . $this::SAGAPIENDPOINT . "checkToken.php?token=" . $token . "&apiKey=" . $apiKey;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        return curl_exec($ch);
    }
}
