<?php
/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Functional\SprykerFeature\Zed\Sales\Business;

use Codeception\TestCase\Test;
use Functional\SprykerFeature\Zed\Sales\Business\Dependency\CountryFacade;
use Functional\SprykerFeature\Zed\Sales\Business\Dependency\OmsFacade;
use Generated\Shared\Transfer\OrderItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\SalesAddressTransfer;
use Generated\Shared\Transfer\TotalsTransfer;
use SprykerEngine\Zed\Kernel\Business\Factory;
use SprykerEngine\Zed\Kernel\Container;
use SprykerEngine\Zed\Kernel\Locator;
use SprykerFeature\Zed\Country\Persistence\CountryQueryContainer;
use SprykerFeature\Zed\Country\Persistence\Propel\SpyCountry;
use SprykerFeature\Zed\Oms\OmsConfig;
use SprykerFeature\Zed\Oms\Persistence\OmsQueryContainer;
use SprykerFeature\Zed\Oms\Persistence\Propel\SpyOmsOrderItemStateQuery;
use SprykerFeature\Zed\Oms\Persistence\Propel\SpyOmsOrderProcess;
use SprykerFeature\Zed\Sales\Business\SalesFacade;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderAddressQuery;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderItemQuery;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderQuery;
use SprykerFeature\Zed\Sales\SalesDependencyProvider;

/**
 * @group Zed
 * @group Sales
 * @group Business
 * @group SalesFacadeTest
 */
class SalesFacadeTest extends Test
{

    /**
     * @var SalesFacade
     */
    protected $salesFacade;

    protected function setUp()
    {
        parent::setUp();
        $locator = Locator::getInstance();
        $this->salesFacade = new SalesFacade(new Factory('Sales'), $locator);

        $container = new Container();

        $countryFacade = new CountryFacade(new Factory('Country'), $locator);
        $countryFacade->setOwnQueryContainer(
            new CountryQueryContainer(
                new \SprykerEngine\Zed\Kernel\Persistence\Factory('Country'),
                $locator
            )
        );

        $omsFacade = new OmsFacade(new Factory('Oms'), $locator);
        $omsFacade->setOwnQueryContainer(
            new OmsQueryContainer(
                new \SprykerEngine\Zed\Kernel\Persistence\Factory('Oms'),
                $locator
            )
        );

        $container[SalesDependencyProvider::FACADE_COUNTRY] = $countryFacade;
        $container[SalesDependencyProvider::FACADE_OMS] = $omsFacade;

        $this->salesFacade->setExternalDependencies($container);
    }

    /**
     * @return OrderTransfer
     */
    private function getValidBaseOrderTransfer()
    {
        $country = new SpyCountry();
        $country->setIso2Code('ix');
        $country->save();

        $orderTransfer = new OrderTransfer();
        $billingAddress = new SalesAddressTransfer();

        $billingAddress
            ->setIso2Code('ix')
            ->setAddress1('address-1-1-test')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setZipCode('1337')
            ->setCity('SpryHome')
        ;

        $shippingAddress = new SalesAddressTransfer();
        $shippingAddress
            ->setIso2Code('ix')
            ->setAddress1('address-1-2-test')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setZipCode('1337')
            ->setCity('SpryHome')
        ;

        $totals = new TotalsTransfer();
        $totals
            ->setGrandTotalWithDiscounts(1337)
            ->setSubtotal(337)
        ;

        $orderTransfer
            ->setShippingAddress($shippingAddress)
            ->setBillingAddress($billingAddress)
            ->setTotals($totals)
            ->setEmail('max@mustermann.de')
            ->setFirstName('Max')
            ->setLastName('Mustermann')
            ->setProcess('process-test-1')
        ;

        return $orderTransfer;
    }

    public function testSaveOrderCreatesBillingAddressAndAssignsItToOrder()
    {
        $salesOrderAddressQuery =
            SpySalesOrderAddressQuery::create()
            ->filterByAddress1('address-1-1-test')
            ->filterByFirstName('Max')
            ->filterByLastName('Mustermann')
            ->filterByZipCode('1337')
            ->filterByCity('SpryHome')
        ;

        $orderTransfer = $this->getValidBaseOrderTransfer();

        $this->salesFacade->saveOrder($orderTransfer);

        $addressEntity = $salesOrderAddressQuery->findOne();

        $this->assertNotNull($addressEntity);
        $this->assertSame(
            $addressEntity->getIdSalesOrderAddress(),
            $orderTransfer->getBillingAddress()->getIdSalesOrderAddress()
        );
    }

    public function testSaveOrderCreatesShippingAddressAndAssignsItToOrder()
    {
        $salesOrderAddressQuery =
            SpySalesOrderAddressQuery::create()
                ->filterByAddress1('address-1-2-test')
                ->filterByFirstName('Max')
                ->filterByLastName('Mustermann')
                ->filterByCity('SpryHome')
        ;

        $orderTransfer = $this->getValidBaseOrderTransfer();

        $this->salesFacade->saveOrder($orderTransfer);

        $addressEntity = $salesOrderAddressQuery->findOne();

        $this->assertNotNull($addressEntity);
        $this->assertSame(
            $addressEntity->getIdSalesOrderAddress(),
            $orderTransfer->getShippingAddress()->getIdSalesOrderAddress()
        );
    }

    public function testSaveOrderAssignsSavedOrderId()
    {
        $orderTransfer = $this->getValidBaseOrderTransfer();
        $orderTransfer = $this->salesFacade->saveOrder($orderTransfer);

        $this->assertNotNull($orderTransfer->getIdSalesOrder());
    }

    public function testSaveOrderCreatesOrderAndSavesFields()
    {
        $orderTransfer = $this->getValidBaseOrderTransfer();
        $orderTransfer = $this->salesFacade->saveOrder($orderTransfer);

        $orderQuery = SpySalesOrderQuery::create()
            ->filterByPrimaryKey($orderTransfer->getIdSalesOrder())
        ;

        $orderEntity = $orderQuery->findOne();
        $this->assertNotNull($orderEntity);

        $this->assertSame('max@mustermann.de', $orderEntity->getEmail());
        $this->assertSame('Max', $orderEntity->getFirstName());
        $this->assertSame('Mustermann', $orderEntity->getLastName());
    }

    public function testSaveOrderCreatesAndFillsOrderItems()
    {
        $orderTransfer = $this->getValidBaseOrderTransfer();

        $initialState = SpyOmsOrderItemStateQuery::create()->filterByName(OmsConfig::INITIAL_STATUS)->findOneOrCreate();
        $initialState->save();
        $this->assertNotNull($initialState->getIdOmsOrderItemState());

        $item1 = new OrderItemTransfer();
        $item1
            ->setName('item-test-1')
            ->setSku('sku1')
            ->setGrossPrice(120)
            ->setPriceToPay(100)
            ->setQuantity(2)
        ;

        $item2 = new OrderItemTransfer();
        $item2
            ->setName('item-test-2')
            ->setSku('sku2')
            ->setGrossPrice(130)
            ->setPriceToPay(110)
            ->setQuantity(3)
        ;

        $orderTransfer->addItem($item1);
        $orderTransfer->addItem($item2);

        $item1Query = SpySalesOrderItemQuery::create()
            ->filterByName('item-test-1')
        ;

        $item2Query = SpySalesOrderItemQuery::create()
            ->filterByName('item-test-2')
        ;

        $orderTransfer = $this->salesFacade->saveOrder($orderTransfer);

        $item1Entity = $item1Query->findOne();
        $item2Entity = $item2Query->findOne();

        $this->assertNotNull($item1Entity);
        $this->assertNotNull($item2Entity);

        $this->assertSame($item1->getIdSalesOrderItem(), $item1Entity->getIdSalesOrderItem());
        $this->assertSame($item1->getName(), $item1Entity->getName());
        $this->assertSame($orderTransfer->getIdSalesOrder(), $item1Entity->getFkSalesOrder());
        $this->assertSame($initialState->getIdOmsOrderItemState(), $item1Entity->getFkOmsOrderItemState());
        $this->assertSame($item1->getSku(), $item1Entity->getSku());
        $this->assertSame($item1->getGrossPrice(), $item1Entity->getGrossPrice());
        $this->assertSame($item1->getPriceToPay(), $item1Entity->getPriceToPay());
        $this->assertSame($item1->getQuantity(), $item1Entity->getQuantity());

        $this->assertSame($item2->getIdSalesOrderItem(), $item2Entity->getIdSalesOrderItem());
        $this->assertSame($item2->getName(), $item2Entity->getName());
        $this->assertSame($orderTransfer->getIdSalesOrder(), $item2Entity->getFkSalesOrder());
        $this->assertSame($initialState->getIdOmsOrderItemState(), $item2Entity->getFkOmsOrderItemState());
        $this->assertSame($item2->getSku(), $item2Entity->getSku());
        $this->assertSame($item2->getGrossPrice(), $item2Entity->getGrossPrice());
        $this->assertSame($item2->getPriceToPay(), $item2Entity->getPriceToPay());
        $this->assertSame($item2->getQuantity(), $item2Entity->getQuantity());
    }

    public function testSaveOrderAttachesProcessToItem()
    {
        $orderTransfer = $this->getValidBaseOrderTransfer();

        $process = new SpyOmsOrderProcess();
        $process->setName('some process');
        $process->save();

        $item1 = new OrderItemTransfer();
        $item1
            ->setName('item-test-1')
            ->setSku('sku1')
            ->setGrossPrice(120)
            ->setPriceToPay(100)
        ;

        $orderTransfer->addItem($item1);
        $orderTransfer->setProcess('some process');

        $item1Query = SpySalesOrderItemQuery::create()
            ->filterByName('item-test-1')
        ;

        $this->salesFacade->saveOrder($orderTransfer);

        $item1Entity = $item1Query->findOne();
        $this->assertSame($process->getIdOmsOrderProcess(), $item1Entity->getFkOmsOrderProcess());
    }

    public function testSaveOrderGeneratesOrderReference()
    {
        $orderTransfer = $this->getValidBaseOrderTransfer();
        $orderTransfer = $this->salesFacade->saveOrder($orderTransfer);
        $this->assertNotNull($orderTransfer->getOrderReference());
    }

}