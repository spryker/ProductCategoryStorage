<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductCategoryStorage\Persistence;

use Generated\Shared\Transfer\FilterTransfer;

interface ProductCategoryStorageRepositoryInterface
{
    /**
     * @return \Generated\Shared\Transfer\CategoryNodeAggregationTransfer[]
     */
    public function getAllCategoryNodeAggregationsOrderedByDescendant(): array;

    /**
     * @return int[]
     */
    public function getAllCategoryNodeIds(): array;

    /**
     * @param int $idCategoryNode
     *
     * @return int[]
     */
    public function getAllCategoryIdsByCategoryNodeId(int $idCategoryNode): array;

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductAbstractLocalizedAttributesTransfer[]
     */
    public function getProductAbstractLocalizedAttributes(array $productAbstractIds): array;

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductCategoryTransfer[]
     */
    public function getProductCategoryWithCategoryNodes(array $productAbstractIds): array;

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductAbstractCategoryStorageTransfer[][][]
     */
    public function getMappedProductAbstractCategoryStorages(array $productAbstractIds): array;

    /**
     * @param int[] $categoryIds
     *
     * @return int[]
     */
    public function getProductAbstractIdsByCategoryIds(array $categoryIds): array;

    /**
     * @param int $offset
     * @param int $limit
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\SynchronizationDataTransfer[]
     */
    public function getProductAbstractCategoryStorageSynchronizationDataTransfersByProductAbstractIds(
        int $offset,
        int $limit,
        array $productAbstractIds
    ): array;

    /**
     * @param \Generated\Shared\Transfer\FilterTransfer $filterTransfer
     *
     * @return \Generated\Shared\Transfer\ProductCategoryTransfer[]
     */
    public function findProductCategoryTransfersByFilter(FilterTransfer $filterTransfer): array;
}
