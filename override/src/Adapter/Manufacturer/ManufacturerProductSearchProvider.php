<?php


use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;


class ManufacturerProductSearchProvider implements ProductSearchProviderInterface
{
  

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     *
     * @return ProductSearchResult
     */
    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $products = $this->getProductsOrCount($context, $query, 'products');
        $count = $this->getProductsOrCount($context, $query, 'count');

        $result = new ProductSearchResult();

        if (!empty($products)) {
            $result
                ->setProducts($products)
                ->setTotalProductsCount($count);
		$sortOrder = new SortOrder('product','sales','desc');
            $result->setAvailableSortOrders(
                array_merge([$sortOrder->setLabel('Meilleures ventes')],$this->sortOrderFactory->getDefaultSortOrders())
            );
        }

        return $result;
    }
}
