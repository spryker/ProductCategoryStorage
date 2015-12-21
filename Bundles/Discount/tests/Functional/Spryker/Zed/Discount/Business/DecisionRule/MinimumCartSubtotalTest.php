<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Functional\Spryker\Zed\Discount\Business\DecisionRule;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\TotalsTransfer;
use Generated\Zed\Ide\AutoCompletion;
use Spryker\Shared\Kernel\AbstractLocatorLocator;
use Spryker\Zed\Discount\Business\DecisionRule\MinimumCartSubtotal;
use Spryker\Zed\Kernel\Locator;
use Generated\Shared\Transfer\OrderTransfer;
use Orm\Zed\Discount\Persistence\SpyDiscountDecisionRule;
use Spryker\Zed\Sales\Business\Model\CalculableContainer;

/**
 * @group DiscountDecisionRuleMinimumCartSubtotalTest
 * @group Discount
 */
class MinimumCartSubtotalTest extends Test
{

    const MINIMUM_CART_SUBTOTAL_TEST_500 = 500;
    const CART_SUBTOTAL_400 = 400;
    const CART_SUBTOTAL_500 = 500;
    const CART_SUBTOTAL_1000 = 1000;

    /**
     * @return void
     */
    public function testShouldReturnTrueForAnOrderWithAHighEnoughSubtotal()
    {
        $order = new CalculableContainer(new OrderTransfer());
        $totals = new TotalsTransfer();
        $totals->setSubtotalWithoutItemExpenses(self::CART_SUBTOTAL_1000);
        $order->getCalculableObject()->setTotals($totals);

        $decisionRuleEntity = $this->getDecisionRuleEntity(self::MINIMUM_CART_SUBTOTAL_TEST_500);

        $decisionRule = $this->createMinimumCartSubtotal();
        $result = $decisionRule->isMinimumCartSubtotalReached($order, $decisionRuleEntity);

        $this->assertTrue($result->isSuccess());
    }

    /**
     * @return void
     */
    public function testShouldReturnFalseForAnOrderWithATooLowSubtotal()
    {
        $order = new CalculableContainer(new OrderTransfer());
        $totals = new TotalsTransfer();
        $totals->setSubtotalWithoutItemExpenses(self::CART_SUBTOTAL_400);
        $order->getCalculableObject()->setTotals($totals);

        $decisionRuleEntity = $this->getDecisionRuleEntity(self::MINIMUM_CART_SUBTOTAL_TEST_500);

        $decisionRule = $this->createMinimumCartSubtotal();
        $result = $decisionRule->isMinimumCartSubtotalReached($order, $decisionRuleEntity);

        $this->assertFalse($result->isSuccess());
    }

    /**
     * @return void
     */
    public function testShouldReturnTrueForAnOrderWithAExactlyMatchingSubtotal()
    {
        $order = new CalculableContainer(new OrderTransfer());
        $totals = new TotalsTransfer();
        $totals->setSubtotalWithoutItemExpenses(self::CART_SUBTOTAL_500);
        $order->getCalculableObject()->setTotals($totals);

        $decisionRuleEntity = $this->getDecisionRuleEntity(self::MINIMUM_CART_SUBTOTAL_TEST_500);

        $decisionRule = $this->createMinimumCartSubtotal();
        $result = $decisionRule->isMinimumCartSubtotalReached($order, $decisionRuleEntity);

        $this->assertTrue($result->isSuccess());
    }

    /**
     * @param int $value
     *
     * @return SpyDiscountDecisionRule
     */
    protected function getDecisionRuleEntity($value)
    {
        $decisionRule = new SpyDiscountDecisionRule();
        $decisionRule->setValue($value);

        return $decisionRule;
    }

    /**
     * @return AbstractLocatorLocator|AutoCompletion
     */
    protected function getLocator()
    {
        return Locator::getInstance();
    }

    /**
     * @return MinimumCartSubtotal
     */
    protected function createMinimumCartSubtotal()
    {
        return new MinimumCartSubtotal();
    }

}