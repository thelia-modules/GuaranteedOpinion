<?php

namespace GuaranteedOpinion\Hook;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class FrontHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY_CONFIG_KEY) => [
                "type" => "front",
                "method" => "displaySiteWidget"
            ],
            GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY_CONFIG_KEY) => [
                "type" => "front",
                "method" => "displayProductWidget"
            ],
            "product.additional" => [
                "type" => "front",
                "method" => "displayProductTab"
            ]
        ];
    }

    public function displaySiteWidget(HookRenderEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_DISPLAY_CONFIG_KEY)) {
            return;
        }

        $event->add(
            $this->render(
                "site/site-review.html",
                [
                    "site_reviews_widget" => htmlspecialchars_decode(
                        GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_CONFIG_KEY)),
                    "site_reviews_widget_iframe" => htmlspecialchars_decode(
                        GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME_CONFIG_KEY)),
                ]
            )
        );
    }

    public function displayProductTab(HookRenderBlockEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY_CONFIG_KEY)) {
            return;
        }

        $productReviews = GuaranteedOpinionProductReviewQuery::create()
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
                    "show_rating_url" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY),
                ]
            )
        ]);
    }

    public function displayProductWidget(HookRenderEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY_CONFIG_KEY)) {
            return;
        }

        $productReviews = GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($event->getTemplateVars()['product_id'])
            ->orderByReviewDate(Criteria::DESC)
            ->find();

        $event->add(
            $this->render(
                "product/product-review.html", [
                    "product_reviews" => $productReviews,
                    "show_rating_url" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL_CONFIG_KEY),
                ]
            )
        );
    }
}
