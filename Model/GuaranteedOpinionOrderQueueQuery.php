<?php

namespace GuaranteedOpinion\Model;

use GuaranteedOpinion\Model\Base\GuaranteedOpinionOrderQueueQuery as BaseGuaranteedOpinionOrderQueueQuery;
use Thelia\Model\Category;
use Thelia\Model\CategoryQuery;
use Thelia\Model\ProductSaleElements;
use Thelia\Model\RewritingUrl;
use Thelia\Model\RewritingUrlQuery;

/**
 * Skeleton subclass for performing query and update operations on the 'guaranteed_opinion_order_queue' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 */
class GuaranteedOpinionOrderQueueQuery extends BaseGuaranteedOpinionOrderQueueQuery
{
    public static function getCategoryByProductSaleElements(ProductSaleElements $productSaleElements): Category
    {
        return CategoryQuery::create()
            ->useProductCategoryQuery()
            ->filterByProductId($productSaleElements->getProductId())
            ->filterByDefaultCategory(1)
            ->endUse()
            ->findOne();
    }

    public static function getProductUrl(int $id): RewritingUrl
    {
        return RewritingUrlQuery::create()
            ->filterByView('product')
            ->filterByViewId($id)
            ->findOne();
    }
}
