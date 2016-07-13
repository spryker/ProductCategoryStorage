<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Discount\Business\Voucher;

use Generated\Shared\Transfer\DiscountVoucherTransfer;
use Orm\Zed\Discount\Persistence\SpyDiscountVoucher;
use Orm\Zed\Discount\Persistence\SpyDiscountVoucherQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Spryker\Shared\Discount\DiscountConstants;
use Spryker\Zed\Discount\Business\Voucher\VoucherEngine;
use Spryker\Zed\Discount\DiscountConfig;
use Spryker\Zed\Discount\Persistence\DiscountQueryContainerInterface;

class VoucherEngineTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function testCreateVoucherCodeShouldPersistItemsFromTransfer()
    {
        $discountVoucherEntityMock = $this->createDiscountVoucherEntityMock();
        $discountVoucherEntityMock
            ->expects($this->once())
            ->method('save')
            ->willReturn(true);

        $voucherEngine = $this->createVoucherEngine(
            null,
            null,
            $discountVoucherEntityMock
        );

        $discountVoucherTransfer = $this->createDiscountVoucherTransfer();

        $voucherEngine->createVoucherCode($discountVoucherTransfer);
    }

    /**
     * @return void
     */
    public function testCreateVoucherCodesShouldGenerateListOfCodesFromGivenTransfer()
    {
        $discountConfigMock = $this->createDiscountConfigMock();
        $this->configureDiscountConfigMock($discountConfigMock);

        $discountVoucherEntityMock = $this->createDiscountVoucherEntityMock();
        $discountVoucherEntityMock
            ->expects($this->once())
            ->method('getVoucherBatch')
            ->willReturn(2);

        $discountVoucherEntityMock
            ->expects($this->exactly(5))
            ->method('save');

        $discountVoucherQueryMock = $this->createDiscountVoucherQueryMock();
        $discountVoucherQueryMock->method('filterByFkDiscountVoucherPool')
            ->willReturn($discountVoucherQueryMock);

        $discountVoucherQueryMock->method('orderByVoucherBatch')
            ->willReturnSelf();

        $discountVoucherQueryMock->method('findOne')
            ->willReturn($discountVoucherEntityMock);

        $discountVoucherQueryMock->method('findOneByCode')->willReturn(null);

        $discountVoucherContainerMock = $this->createDiscountQueryContainerMock();
        $discountVoucherContainerMock->method('queryDiscountVoucher')
            ->willReturn($discountVoucherQueryMock);

        $connectionMock = $this->createConnectionMock();
        $connectionMock->expects($this->once())
            ->method('beginTransaction');

        $connectionMock->expects($this->once())
            ->method('commit');

        $discountVoucherContainerMock->method('getConnection')
            ->willReturn($connectionMock);

        $voucherEngine = $this->createVoucherEngine(
            $discountConfigMock,
            $discountVoucherContainerMock,
            $discountVoucherEntityMock
        );

        $discountVoucherTransfer = $this->createDiscountVoucherTransfer();

        $voucherCreateInfoTransfer = $voucherEngine->createVoucherCodes($discountVoucherTransfer);

        $this->assertEquals(DiscountConstants::MESSAGE_TYPE_SUCCESS, $voucherCreateInfoTransfer->getType());
    }

    /**
     * @return void
     */
    public function testGenerateCodesWhenLengthAndCustomCodeIsNotSetShouldReturnErrorMessage()
    {
        $discountConfigMock = $this->createDiscountConfigMock();
        $this->configureDiscountConfigMock($discountConfigMock);

        $discountVoucherQueryMock = $this->createDiscountVoucherQueryMock();
        $discountVoucherQueryMock->expects($this->once())
            ->method('filterByFkDiscountVoucherPool')
            ->willReturn($discountVoucherQueryMock);

        $discountVoucherQueryMock->expects($this->once())
            ->method('orderByVoucherBatch')
            ->willReturnSelf();

        $discountVoucherContainerMock = $this->createDiscountQueryContainerMock();
        $discountVoucherContainerMock->method('queryDiscountVoucher')
            ->willReturn($discountVoucherQueryMock);

        $connectionMock = $this->createConnectionMock();
        $connectionMock->expects($this->once())
            ->method('beginTransaction');

        $discountVoucherContainerMock->method('getConnection')
            ->willReturn($connectionMock);

        $voucherEngine = $this->createVoucherEngine(
            null,
            $discountVoucherContainerMock,
            null
        );

        $discountVoucherTransfer = $this->createDiscountVoucherTransfer();
        $discountVoucherTransfer->setRandomGeneratedCodeLength(0);
        $discountVoucherTransfer->setCustomCode('');

        $voucherCreateInfoTransfer = $voucherEngine->createVoucherCodes($discountVoucherTransfer);

        $this->assertEquals(DiscountConstants::MESSAGE_TYPE_ERROR, $voucherCreateInfoTransfer->getType());
    }

    /**
     * @return void
     */
    public function testGenerateCodesWhenAllCodesCollideShouldReturnError()
    {
        $discountConfigMock = $this->createDiscountConfigMock();
        $this->configureDiscountConfigMock($discountConfigMock);

        $discountVoucherQueryMock = $this->createDiscountVoucherQueryMock();
        $discountVoucherQueryMock->expects($this->once())
            ->method('filterByFkDiscountVoucherPool')
            ->willReturn($discountVoucherQueryMock);

        $discountVoucherQueryMock->expects($this->once())
            ->method('orderByVoucherBatch')
            ->willReturnSelf();

        $discountVoucherQueryMock->method('findOneByCode')
            ->willReturn(new \stdClass());

        $discountVoucherContainerMock = $this->createDiscountQueryContainerMock();
        $discountVoucherContainerMock->method('queryDiscountVoucher')
            ->willReturn($discountVoucherQueryMock);

        $connectionMock = $this->createConnectionMock();
        $connectionMock->expects($this->once())
            ->method('beginTransaction');

        $discountVoucherContainerMock->method('getConnection')
            ->willReturn($connectionMock);

        $discountConfigMock = $this->createDiscountConfigMock();
        $this->configureDiscountConfigMock($discountConfigMock);

        $voucherEngine = $this->createVoucherEngine(
            $discountConfigMock,
            $discountVoucherContainerMock,
            null
        );

        $discountVoucherTransfer = $this->createDiscountVoucherTransfer();

        $voucherCreateInfoTransfer = $voucherEngine->createVoucherCodes($discountVoucherTransfer);

        $this->assertEquals(DiscountConstants::MESSAGE_TYPE_ERROR, $voucherCreateInfoTransfer->getType());
    }

    /**
     * @param \Spryker\Zed\Discount\DiscountConfig $discountConfigMock
     * @param \Spryker\Zed\Discount\Persistence\DiscountQueryContainerInterface $discountQueryContainerMock
     * @param \Orm\Zed\Discount\Persistence\SpyDiscountVoucher $discountVoucherEntity
     *
     * @return \Spryker\Zed\Discount\Business\Voucher\VoucherEngineInterface
     */
    protected function createVoucherEngine(
        DiscountConfig $discountConfigMock = null,
        DiscountQueryContainerInterface $discountQueryContainerMock = null,
        SpyDiscountVoucher $discountVoucherEntity = null
    ) {

        if (!$discountConfigMock) {
            $discountConfigMock = $this->createDiscountConfigMock();
        }

        if (!$discountQueryContainerMock) {
            $discountQueryContainerMock = $this->createDiscountQueryContainerMock();
        }

        $voucherEngineMock = $this->getMock(
            VoucherEngine::class,
            ['createDiscountVoucherEntity'],
            [
                $discountConfigMock,
                $discountQueryContainerMock
            ]
        );

        $voucherEngineMock->method('createDiscountVoucherEntity')
            ->willReturn($discountVoucherEntity);

        return $voucherEngineMock;

    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Discount\DiscountConfig $discountConfigMock
     *
     * @return void
     */
    protected function configureDiscountConfigMock(DiscountConfig $discountConfigMock)
    {
        $discountConfigMock
            ->method('getVoucherCodeCharacters')
            ->willReturn($this->getVoucherCodeCharacters());

        $discountConfigMock
            ->method('getVoucherPoolTemplateReplacementString')
            ->willReturn('[template]');
    }

    /**
     * @return array
     */
    protected function getVoucherCodeCharacters()
    {
        return [
            DiscountConfig::KEY_VOUCHER_CODE_CONSONANTS => [
                'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z',
            ],
            DiscountConfig::KEY_VOUCHER_CODE_VOWELS => [
                'a', 'e', 'u',
            ],
            DiscountConfig::KEY_VOUCHER_CODE_NUMBERS => [
                1, 2, 3, 4, 5, 6, 7, 8, 9,
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Discount\DiscountConfig
     */
    protected function createDiscountConfigMock()
    {
        return $this->getMock(DiscountConfig::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Discount\Persistence\DiscountQueryContainerInterface
     */
    protected function createDiscountQueryContainerMock()
    {
        return $this->getMock(DiscountQueryContainerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Orm\Zed\Discount\Persistence\SpyDiscountVoucher
     */
    protected function createDiscountVoucherEntityMock()
    {
        return $this->getMockBuilder(SpyDiscountVoucher::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Orm\Zed\Discount\Persistence\SpyDiscountVoucherQuery
     */
    protected function createDiscountVoucherQueryMock()
    {
        return $this->getMock(
            SpyDiscountVoucherQuery::class,
            [
                'orderByVoucherBatch',
                'filterByFkDiscountVoucherPool',
                'findOne',
                'findOneByCode'
            ]
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Propel\Runtime\Connection\ConnectionInterface
     */
    protected function createConnectionMock()
    {
        return $this->getMock(ConnectionInterface::class);
    }

    /**
     * @return \Generated\Shared\Transfer\DiscountVoucherTransfer
     */
    protected function createDiscountVoucherTransfer()
    {
        $discountVoucherTransfer = new DiscountVoucherTransfer();
        $discountVoucherTransfer->setCode('test');
        $discountVoucherTransfer->setCustomCode('prefix');
        $discountVoucherTransfer->setMaxNumberOfUses(0);
        $discountVoucherTransfer->setRandomGeneratedCodeLength(5);
        $discountVoucherTransfer->setQuantity(5);

        return $discountVoucherTransfer;
    }

}