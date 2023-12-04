<?php

namespace GuaranteedOpinion\Hook;

use GuaranteedOpinion\GuaranteedOpinion;
use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class FrontHook extends BaseHook
{
    public static function getSubscribedHooks(): array
    {
        return [
            GuaranteedOpinion::getConfigValue(GuaranteedOpinion::FOOTER_LINK_HOOK_DISPLAY) => [
                "type" => "front",
                "method" => "displayFooterLink"
            ],
            GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_HOOK_DISPLAY) => [
                "type" => "front",
                "method" => "displaySiteWidget"
            ],
            GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_HOOK_DISPLAY) => [
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
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_DISPLAY)) {
            return;
        }

        $event->add(
            $this->render(
                "site/site-review.html",
                [
                    "site_reviews_widget" => htmlspecialchars_decode(
                        GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET)),
                    "site_reviews_widget_iframe" => htmlspecialchars_decode(
                        GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SITE_REVIEW_WIDGET_IFRAME)),
                ]
            )
        );
    }

    public function displayProductTab(HookRenderBlockEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_TAB_DISPLAY)) {
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
                    "show_rating_url" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL),
                ]
            )
        ]);
    }

    public function displayProductWidget(HookRenderEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::PRODUCT_REVIEW_DISPLAY)) {
            return;
        }

        $productReviews = GuaranteedOpinionProductReviewQuery::create()
            ->filterByProductId($event->getTemplateVars()['product_id'])
            ->orderByReviewDate(Criteria::DESC)
            ->limit(3)
            ->find();

        $event->add(
            $this->render(
                "product/product-review.html", [
                    "product_reviews" => $productReviews,
                    "show_rating_url" => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::SHOW_RATING_URL),
                ]
            )
        );
    }

    public function displayFooterLink(HookRenderEvent $event): void
    {
        if (!GuaranteedOpinion::getConfigValue(GuaranteedOpinion::FOOTER_LINK_DISPLAY)) {
            return;
        }

        $content = $this->render('site/footer-link.html', [
            'link_title' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::FOOTER_LINK_TITLE),
            'link' => GuaranteedOpinion::getConfigValue(GuaranteedOpinion::FOOTER_LINK)
        ]);

        $event->add($content);
    }
}
