<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */


namespace Unit\SprykerFeature\Zed\ItemGrouper\Business\Model;

use Generated\Shared\Transfer\CartItemTransfer;
use Generated\Shared\Transfer\GroupableContainerTransfer;
use SprykerFeature\Zed\ItemGrouper\Business\Model\Group;

class GroupTest extends \PHPUnit_Framework_TestCase
{
    public function testIsGroupedBySku()
    {
        $groupAbleContainer = $this->getGroupableContainer();

        $group = new Group($threshold = -1);
        $groupedItems = (array) $group->groupByKey($groupAbleContainer)->getItems();

        $this->assertCount(2, $groupedItems);

        $firstItem = array_shift($groupedItems);
        $this->assertEquals('A', $firstItem->getGroupKey());
        $this->assertEquals(2, $firstItem->getQuantity());

        $secondItem = array_shift($groupedItems);
        $this->assertEquals('B', $secondItem->getGroupKey());
        $this->assertEquals(1, $secondItem->getQuantity());

    }

    public function testIsThresholdValidatorApplied()
    {
        $groupAbleContainer = $this->getGroupableContainer();

        $group = new Group($threshold = 1);
        $groupedItems = (array) $group->groupByKey($groupAbleContainer)->getItems();

        $this->assertCount(3, $groupedItems);

        $firstItem = array_shift($groupedItems);
        $this->assertEquals('A', $firstItem->getGroupKey());
        $this->assertEquals(1, $firstItem->getQuantity());

        $firstItem = array_shift($groupedItems);
        $this->assertEquals('A', $firstItem->getGroupKey());
        $this->assertEquals(1, $firstItem->getQuantity());

        $secondItem = array_shift($groupedItems);
        $this->assertEquals('B', $secondItem->getGroupKey());
        $this->assertEquals(1, $secondItem->getQuantity());
    }

    /**
     * @return GroupableContainerTransfer
     */
    protected function getGroupableContainer()
    {
        $cartItems = [];
        $cartItem = new CartItemTransfer();
        $cartItem->setGroupKey('A');
        $cartItem->setQuantity(1);
        $cartItems[] = $cartItem;

        $cartItem = new CartItemTransfer();
        $cartItem->setGroupKey('A');
        $cartItem->setQuantity(1);
        $cartItems[] = $cartItem;

        $cartItem = new CartItemTransfer();
        $cartItem->setGroupKey('B');
        $cartItem->setQuantity(1);
        $cartItems[] = $cartItem;

        $groupAbleContainer = new GroupableContainerTransfer();
        $groupAbleContainer->setItems(new \ArrayObject($cartItems));

        return $groupAbleContainer;
    }
}
