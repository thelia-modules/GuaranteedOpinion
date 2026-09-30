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

namespace GuaranteedOpinion\Loop;

use GuaranteedOpinion\Model\GuaranteedOpinionSiteReview;
use GuaranteedOpinion\Model\GuaranteedOpinionSiteReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

/**
 * Store reviews of one language: the lang_id argument, else the language of the visitor.
 *
 * @method int|null getMinRate()
 * @method int|null getLangId()
 */
class GuaranteedSiteLoop extends BaseLoop implements PropelSearchLoopInterface
{
    use LoopLocaleTrait;

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var GuaranteedOpinionSiteReview $review */
        foreach ($loopResult->getResultDataCollection() as $review) {
            $loopResultRow = new LoopResultRow($review);

            $loopResultRow
                ->set('SITE_REVIEW_ID', $review->getSiteReviewId())
                ->set('PRODUCT_REVIEW_ID', $review->getSiteReviewId())
                ->set('LOCALE', $review->getLocale())
                ->set('NAME', $review->getName())
                ->set('RATE', $review->getRate())
                ->set('REVIEW', $review->getReview())
                ->set('REVIEW_DATE', $review->getReviewDate()?->format('Y-m-d'))
                ->set('ORDER_DATE', $review->getOrderDate()?->format('Y-m-d'))
                ->set('REPLY', $review->getReply())
                ->set('REPLY_DATE', $review->getReplyDate()?->format('Y-m-d'));
            $this->addOutputFields($loopResultRow, $review);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    public function buildModelCriteria(): ModelCriteria
    {
        $search = GuaranteedOpinionSiteReviewQuery::create();

        if (null !== $minRate = $this->getMinRate()) {
            $search->filterByRate($minRate, Criteria::GREATER_EQUAL);
        }

        $search->filterByLocale($this->loopLocale($this->getLangId()));
        $search->orderByReviewDate(Criteria::DESC);

        return $search;
    }

    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('min_rate'),
            Argument::createIntTypeArgument('lang_id')
        );
    }
}
