<?php

namespace GuaranteedOpinion\Loop;

use GuaranteedOpinion\Model\GuaranteedOpinionProductReviewQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

/**
 * @method getMinRate()
 * @method getProduct()
 */
class GuaranteedProductLoop extends BaseLoop implements PropelSearchLoopInterface
{
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        foreach ($loopResult->getResultDataCollection() as $review) {
            $loopResultRow = new LoopResultRow($review);

            $loopResultRow
                ->set('ID', $review->getId())
                ->set('PRODUCT_REVIEW_ID', $review->getProductReviewId())
                ->set('NAME', $review->getName())
                ->set('RATE', $review->getRate())
                ->set('REVIEW', $review->getReview())
                ->set('REVIEW_DATE', $review->getReviewDate()?->format('Y-m-d'))
                ->set('PRODUCT_ID', $review->getProductId())
                ->set('ORDER_ID', $review->getOrderId())
                ->set('ORDER_DATE', $review->getOrderDate()?->format('Y-m-d'))
                ->set('REPLY', $review->getReply())
                ->set('REPLY_DATE', $review->getReplyDate()?->format('Y-m-d'));
            $this->addOutputFields($loopResultRow, $review);

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }

    public function buildModelCriteria(): GuaranteedOpinionProductReviewQuery|ModelCriteria
    {
        $search = GuaranteedOpinionProductReviewQuery::create();

        if (null !== $productId = $this->getProduct()) {
            $search->filterByProductId($productId);
        }

        if (null !== $minRate = $this->getMinRate()) {
            $search->filterByRate($minRate, Criteria::GREATER_EQUAL);
        }

        if ((null !== $offset = $this->getOffset()) && (null !== $limit = $this->getLimit())) {
            $search->setOffset($offset)->setLimit($limit);
        }

        return $search;
    }

    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('product'),
            Argument::createIntTypeArgument('min_rate'),
            Argument::createIntTypeArgument('limit', null),
            Argument::createIntTypeArgument('offset', 0)
        );
    }
}
