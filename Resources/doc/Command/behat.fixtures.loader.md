#### Description:
 This command can be used to debug fixtures that be used within Behat scenarios

#### Usage: 
```bash
$ app gorgo:fixtures:load "@OroSaleBundle:QuoteFixture.yml"
or
$ app gorgo:fixtures:load "@OroSaleBundle/Tests/Behat/Features/Fixtures/QuoteFixture.yml"
or
$ app gorgo:fixtures:load ../../package/commerce/src/Oro/Bundle/SaleBundle/Tests/Behat/Features/Fixtures/QuoteFixture.yml
```

#### Sample verbose output:
```
[debug] Loading Oro\Bundle\CustomerBundle\Entity\Customer
[debug] Loading Oro\Bundle\CustomerBundle\Entity\CustomerUser
[debug] Loading Oro\Bundle\OrganizationBundle\Entity\BusinessUnit
[debug] Loading Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue
[debug] Loading Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision
[debug] Loading Oro\Bundle\ProductBundle\Entity\Product
[debug] Loading Oro\Bundle\CatalogBundle\Entity\Category
[debug] Loading Oro\Bundle\PricingBundle\Entity\PriceList
[debug] Loading Oro\Bundle\PricingBundle\Entity\PriceListToProduct
[debug] Loading Oro\Bundle\PricingBundle\Entity\ProductPrice
[debug] Loading Oro\Bundle\ShippingBundle\Model\Weight(local)
[debug] Loading Oro\Bundle\ShippingBundle\Model\DimensionsValue(local)
[debug] Loading Oro\Bundle\ShippingBundle\Model\Dimensions(local)
[debug] Loading Oro\Bundle\ShippingBundle\Entity\FreightClass
[debug] Loading Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions
[debug] Loading Oro\Bundle\SaleBundle\Entity\Quote
[debug] Loading Oro\Bundle\SaleBundle\Entity\QuoteProduct
[debug] Loading Oro\Bundle\CurrencyBundle\Entity\Price (local)
[debug] Loading Oro\Bundle\SaleBundle\Entity\QuoteProductOffer
[debug] Loading Oro\Bundle\SaleBundle\Entity\QuoteProductRequest
```

#### Combined usage with [Oro Database Snapshot Bundle](https://github.com/inri13666/gorgo-database-snapshot-bundle)

```
#make backup before fixture processing
app oro:database:snapshot:dump --id=fixtures

#process fixtures
app gorgo:fixtures:load "@OroSaleBundle:QuoteFixture.yml"

#revert all changes to backedup version
app oro:database:snapshot:restore --id=fixtures
```
