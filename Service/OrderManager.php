<?php

namespace GuaranteedOpinion\Service;

use GuaranteedOpinion\GuaranteedOpinion;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Model\ConfigQuery;

class OrderManager
{
    const DEBUG_API_URL = "www.preprod.avis-verifies.com";

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

//    /** @var  Request */
//    protected $request;

    public function __construct(EventDispatcherInterface $eventDispatcher) //, Request $request
    {
        $this->eventDispatcher = $eventDispatcher;
//        $this->request = $request;
    }

    public function sendOrderToGuaranteedOpinion($orderId)
    {
//        $idWebSite = GuaranteedOpinion::getConfigValue('id_website');
        $secret = GuaranteedOpinion::getConfigValue('secret_token');
        $apiUrl = GuaranteedOpinion::getConfigValue('api_url');
        $debug = GuaranteedOpinion::getConfigValue('debug_mode', "false");


        if ("true" === $debug) {
            $apiUrl = self::DEBUG_API_URL;
        }

        $netreviewsOrder = $this->getNetreviewsOrder($orderId);
        $products = [];

        /** @var GuaranteedOpinionProduct $product */
        foreach ($netreviewsOrder->getProducts() as $product) {
            $products[] = [
                'id_product' => $product->getId(),
                'name_product' => $product->getName(),
                'url_product' => $product->getUrl(),
                'url_product_image' => $product->getImageUrl(),
                'GTIN_EAN' => $product->getGtinEan()
            ];
        }

        $message = [
            'query' => 'pushCommandeSHA1',
            'order_ref' => $netreviewsOrder->getRef(),
            'email' => $netreviewsOrder->getEmail(),
            'order_date' => $netreviewsOrder->getDate(),
            'firstname' => $netreviewsOrder->getFirstname(),
            'lastname' => $netreviewsOrder->getLastname(),
            'delay' => $netreviewsOrder->getDelay(),
            'PRODUCTS' => $products
        ];

        $message['sign'] = SHA1($message['query'].$message['order_ref'].$message['email'].$message['lastname'].$message['firstname'].$message['order_date'].$message['delay'].$secret);

        $fields = http_build_query(
            [
                'idWebsite'=> $idWebSite,
                'message' => $this->acEncodeBase64(json_encode($message)),
                'type' => 'json'
            ]
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://$apiUrl/index.php?action=act_api_notification_sha1");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
        curl_setopt($ch, CURLOPT_POST, count($fields));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/x-www-form-urlencoded']);
        $response = curl_exec($ch);
        curl_close($ch);

        $decodedResponse = json_decode($this->acDecodeBase64($response));
        $return = $decodedResponse->return;

        if ($return == 1) {
            $netreviewsOrderQueue = NetreviewsOrderQueueQuery::create()
                ->filterByOrderId($orderId)
                ->findOneOrCreate();
            $netreviewsOrderQueue->setTreatedAt(new \DateTime())
                ->setStatus('1')
                ->save();
        }

        return $decodedResponse;
    }

    public function getNetreviewsOrder($orderId)
    {
        /** @var ConnectionWrapper $con */
        $con = Propel::getConnection();
        $delay = GuaranteedOpinion::getConfigValue('email_delay', '3');

        $orderProductSql = "SELECT o.ref, o.created_at, cu.firstname, cu.lastname, cu.email, op.product_ref, op.title, op.ean_code, ru.url, pi.file
                            FROM order_product op 
                            LEFT JOIN `order` o ON (op.order_id = o.id)
                            LEFT JOIN `customer` cu ON (o.customer_id = cu.id)
                            LEFT JOIN `product` p ON (p.ref = op.product_ref)
                            LEFT JOIN `rewriting_url` ru ON (p.id = ru.view_id AND ru.view = 'product' AND ru.redirected IS NULL)
                            LEFT JOIN `product_image` pi ON (p.id = pi.product_id AND pi.position = 1)
                            WHERE o.id = $orderId";

        $stmt = $con->prepare($orderProductSql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        if (empty($results)) {
            throw new \Exception("The order was not ready to be exported");
        }

        $netReviewsOrder = new GuaranteedOpinionOrder();

        $netReviewsOrder->setRef($results[0]['ref'])
            ->setDate($results[0]['created_at'])
            ->setFirstname($results[0]['firstname'])
            ->setLastname($results[0]['lastname'])
            ->setEmail($results[0]['email'])
            ->setDelay($delay);

        $baseUrl = ConfigQuery::read('url_site');

        $products = [];

        foreach ($results as $orderProduct) {
            $product = new GuaranteedOpinionProduct();
            $product
                ->setId($orderProduct['product_ref'])
                ->setName($orderProduct['title']);

//            if ($orderProduct['file'] !== null) {
//                $imageEvent = $this->createProductImageEvent($orderProduct['file']);
//                $this->eventDispatcher->dispatch(TheliaEvents::IMAGE_PROCESS, $imageEvent);
//                $imagePath = $imageEvent->getFileUrl();
//                $product->setImageUrl($imagePath);
//            }

            if ($orderProduct['url'] !== null) {
                $product->setUrl($baseUrl . "/" . $orderProduct['url']);
            }

            if($orderProduct['ean_code']){
                $product->setGtinEan($orderProduct['ean_code']);
            }

            $products[] = $product;
        }

        $netReviewsOrder->setProducts($products);

        return $netReviewsOrder;
    }

//    /**
//     * @param string $imageFile
//     * @return ImageEvent
//     */
//    protected function createProductImageEvent($imageFile)
//    {
//        $imageEvent = new ImageEvent($this->request);
//        $baseSourceFilePath = ConfigQuery::read('images_library_path');
//        if ($baseSourceFilePath === null) {
//            $baseSourceFilePath = THELIA_LOCAL_DIR . 'media' . DS . 'images';
//        } else {
//            $baseSourceFilePath = THELIA_ROOT . $baseSourceFilePath;
//        }
//        // Put source image file path
//        $sourceFilePath = sprintf(
//            '%s/%s/%s',
//            $baseSourceFilePath,
//            'product',
//            $imageFile
//        );
//        $imageEvent->setSourceFilepath($sourceFilePath);
//        $imageEvent->setCacheSubdirectory('product');
//        return $imageEvent;
//    }

    //------Netreviews methods-----//

    protected function acEncodeBase64($sData)
    {
        $sBase64 = base64_encode($sData);
        return strtr($sBase64, '+/', '‐_');
    }

    protected function acDecodeBase64($sData)
    {
        $sBase64 = strtr($sData, '‐_', '+/');
        return base64_decode($sBase64);
    }
}