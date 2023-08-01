<?php

namespace GuaranteedOpinion\Hook;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Object\GuaranteedOpinionProduct;
use GuaranteedOpinion\Service\OrderManager;
use GuaranteedOpinion\Service\OrderService;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Connection\ConnectionWrapper;
use Propel\Runtime\Propel;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\ConfigQuery;
use Thelia\Model\ProductImage;
use Thelia\Model\ProductQuery;

class FrontHook extends BaseHook
{
    public static function getSubscribedHooks()
    {
        return [
            ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_HOOK_DISPLAY) => [
                "type" => "front",
                "method" => "displayFooterLink"
            ],
            ConfigQuery::read(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY) => [
                "type" => "front",
                "method" => "displaySiteWidget"
            ],
            ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY) => [
                "type" => "front",
                "method" => "displayProductWidget"
            ],
            "product.additional" => [
                "type" => "front",
                "method" => "displayProductTab"
            ]
        ];
    }

//    public function displayTag(HookRenderEvent $event)
//    {
//        $idWebSite = GuaranteedOpinion::getConfigValue('id_website');
//        $secret = GuaranteedOpinion::getConfigValue('secret_token');
//        $order_id = $event->getArgument('order_id');
//
//        $netreviewsOrder = $this->netreviewsOrderService->getNetreviewsOrder($order_id);
//
//        $token = sha1($idWebSite.$secret.$netreviewsOrder->getRef());
//
//        $products = [];
//
//        /** @var GuaranteedOpinionProduct $product */
//        foreach ($netreviewsOrder->getProducts() as $product) {
//            $products[] = [
//                'name_product' => $product->getName(),
//                'id_product' => $product->getId(),
//                'url_product' => $product->getUrl(),
//                'url_image_product' => $product->getImageUrl()
//            ];
//        }
//
//        $netreviews = [
//            "idWebsite" => $idWebSite,
//            "orderRef" => $netreviewsOrder->getRef(),
//            "firstname" => $netreviewsOrder->getFirstname(),
//            "lastname" => $netreviewsOrder->getLastname(),
//            "email" => $netreviewsOrder->getEmail(),
//            "products" => $products,
//            "token" => $token,
//        ];
//
//        $event->add(
//            $this->render(
//                "netreviews/tag-manager.html",
//                ["netreviews" => json_encode($netreviews)]
//            )
//        );
//    }
//
    public function displaySiteWidget(HookRenderEvent $event)
    {
        $display = ConfigQuery::read(GuaranteedOpinion::SITE_REVIEW_DISPLAY);
        if (!$display) {
            return;
        }

        $siteReviews = \GuaranteedOpinionSiteReviewQuery::create()->find();

        $siteRate = 5;
        if ($siteReviews->count() !== 0) {
            $siteRate = 0;
            foreach ($siteReviews as $siteReview) {
                $siteRate += $siteReview->getRating();
            }
            $siteRate /= $siteReviews->count();
        }

        $event->add(
            $this->render(
                "site/site-review.html",
                [
                    "site_reviews" => $siteReviews,
                    "site_rate" => $siteRate
                ]
            )
        );
    }

    public function displayProductTab(HookRenderBlockEvent $event)
    {
        $display = ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY);
        if (!$display) {
            return;
        }

        $productReviews = \GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($event->getTemplateVars()['product_id'])
            ->orderByReviewDate(Criteria::DESC)
            ->limit(10)
            ->find();

        $event->add([
            'id' => 'guaranteedopinion-product-review',
            'class' => 'guaranteedopinion-product-review',
            "title" => "Avis clients",
            "content" => $this->render(
                "product/product-review-tab.html",
                [
                    "product_reviews" => $productReviews,
                    "show_rating_url" => ConfigQuery::read(GuaranteedOpinion::SHOW_RATING_URL),
                ]
            )
        ]);
    }

    public function displayProductWidget(HookRenderEvent $event) {
        $display = ConfigQuery::read(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY);
        if (!$display) {
            return;
        }

        $productReviews = \GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($event->getTemplateVars()['product_id'])
            ->orderByReviewDate(Criteria::DESC)
            ->limit(3)
            ->find();

        $event->add($this->render(
                "product/product-review.html",
                [
                    "product_reviews" => $productReviews,
                    "show_rating_url" => ConfigQuery::read(GuaranteedOpinion::SHOW_RATING_URL),
                ]
            )
        );
    }
//
//    public function displayProductIframe(HookRenderEvent $event)
//    {
//        $code = GuaranteedOpinion::getConfigValue('product_iframe_code');
//
//        $product_ref = $event->getArgument('product_ref');
//
//        $event->add(
//            $this->render(
//                "netreviews/product-iframe.html",
//                [
//                    "product_iframe_code" => $code,
//                    "product_ref" => $product_ref
//                ]
//            )
//        );
//    }

    public function displayFooterLink(HookRenderEvent $event)
    {
        $display = ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_DISPLAY);
        if (!$display) {
            return;
        }

        $linkTitle = ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK_TITLE);
        $link = ConfigQuery::read(GuaranteedOpinion::FOOTER_LINK);

        $content = $this->render('site/footer-link.html', [
            'link_title' => $linkTitle,
            'link' => $link
        ]);

        $event->add($content);
    }
//
//    public function displayProductTabReview(HookRenderBlockEvent $event)
//    {
//        $reviewMode = GuaranteedOpinion::getConfigValue('product_review_mode');
//        $content = null;
//
//        $productId = $event->getArgument('product');
//        $product = ProductQuery::create()
//            ->findPk($productId);
//
//        if (null !== $product) {
//            if ($reviewMode === 'iframe') {
//                $code = GuaranteedOpinion::getConfigValue('product_iframe_code');
//                if ($code != null) {
//                    $content = $this->render(
//                        "netreviews/product-iframe.html",
//                        [
//                            "product_iframe_code" => $code,
//                            "product_ref" => $product->getRef()
//                        ]
//                    );
//                }
//            } elseif ($reviewMode === 'custom') {
//                $content = $this->render(
//                    "netreviews/product-review.html",
//                    [
//                        'product_id' => $productId
//                    ]
//                );
//            }
//
//            if (null != $content) {
//                $event->add(
//                    [
//                        'id' => 'netreviews_tab',
//                        'class' => '',
//                        'title' => $this->trans('Net Reviews', [], GuaranteedOpinion::DOMAIN_NAME),
//                        'content' => $content
//                    ]
//                );
//            }
//        }
//    }
//
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
}
