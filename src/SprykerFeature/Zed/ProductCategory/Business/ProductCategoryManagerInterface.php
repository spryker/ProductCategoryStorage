<?php

namespace SprykerFeature\Zed\ProductCategory\Business;

use SprykerFeature\Zed\Product\Business\Exception\MissingProductException;
use SprykerFeature\Zed\ProductCategory\Business\Exception\MissingCategoryNodeException;
use SprykerFeature\Zed\ProductCategory\Business\Exception\ProductCategoryMappingExistsException;
use Propel\Runtime\Exception\PropelException;

interface ProductCategoryManagerInterface
{
    /**
     * @param string $sku
     * @param string $categoryName
     * @param int $localeId
     *
     * @return bool
     */
    public function hasProductCategoryMapping($sku, $categoryName, $localeId);

    /**
     * @param string $sku
     * @param string $categoryName
     * @param int $localeId
     * @return int
     *
     * @throws ProductCategoryMappingExistsException
     * @throws MissingProductException
     * @throws MissingCategoryNodeException
     * @throws PropelException
     */
    public function createProductCategoryMapping($sku, $categoryName, $localeId);
}
