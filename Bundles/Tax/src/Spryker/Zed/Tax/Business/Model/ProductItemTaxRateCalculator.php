<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Tax\Business\Model;

use Generated\Shared\Transfer\QuoteTransfer;
use Spryker\Shared\Kernel\Store;
use Spryker\Zed\Tax\Persistence\TaxQueryContainer;
use Spryker\Zed\Tax\Persistence\TaxQueryContainerInterface;

class ProductItemTaxRateCalculator implements CalculatorInterface
{

    /**
     * @var TaxQueryContainerInterface
     */
    protected $taxQueryContainer;

    /**
     * @var TaxDefaultInterface
     */
    protected $taxDefault;

    /**
     * @var array
     */
    protected $taxRates;

    /**
     * TaxProductItemCalculator constructor.
     *
     * @param TaxQueryContainerInterface $taxQueryContainer
     * @param TaxDefaultInterface $taxDefault
     */
    public function __construct(TaxQueryContainerInterface $taxQueryContainer, TaxDefaultInterface $taxDefault)
    {
        $this->taxQueryContainer = $taxQueryContainer;
        $this->taxDefault = $taxDefault;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return void
     */
    public function recalculate(QuoteTransfer $quoteTransfer)
    {
        $country = $this->getShippingCountryIsoCode($quoteTransfer);
        $idsProductAbstract = $this->getIdsAbstractProduct($quoteTransfer);

        $this->taxRates = $this->taxQueryContainer
            ->queryTaxSetByProductAbstractAndCountry(
                $idsProductAbstract,
                $country
            )->find();

        $this->setItemsTax($quoteTransfer);
    }

    /**
     * @param QuoteTransfer $quoteTransfer
     *
     * @return string
     */
    protected function getShippingCountryIsoCode(QuoteTransfer $quoteTransfer)
    {
        if ($quoteTransfer->getShippingAddress() === null) {
            return $this->taxDefault->getDefaultCountry();
        }

        return $quoteTransfer->getShippingAddress()->getIso2Code();
    }

    /**
     * @param QuoteTransfer $quoteTransfer
     *
     * @return array
     */
    protected function getIdsAbstractProduct(QuoteTransfer $quoteTransfer)
    {
        $idsProductAbstract = [];
        foreach ($quoteTransfer->getItems() as $item) {
            $idsProductAbstract[] = $item->getIdProductAbstract();
        }

        return $idsProductAbstract;
    }

    /**
     * @param QuoteTransfer $quoteTransfer
     *
     * @return void
     */
    protected function setItemsTax(QuoteTransfer $quoteTransfer)
    {
        foreach ($quoteTransfer->getItems() as $item) {
            $item->setTaxRate($this->getEffectiveTaxRate($item->getIdProductAbstract()));
        }
    }

    /**
     * @param $idProductAbstract
     *
     * @return float
     */
    protected function getEffectiveTaxRate($idProductAbstract)
    {
        foreach ($this->taxRates as $taxRate) {
            if ($taxRate[TaxQueryContainer::COL_ID_ABSTRACT_PRODUCT] === $idProductAbstract) {
                return (float) $taxRate[TaxQueryContainer::COL_SUM_TAX_RATE];
            }
        }

        return $this->taxDefault->getDefaultTaxRate();
    }
}
