<?php


class CartPresenter implements PresenterInterface
{

        /**
     * @param Cart $cart
     * @param bool $shouldSeparateGifts
     *
     * @return array
     *
     * @throws \Exception
     */
    public function present($cart, $shouldSeparateGifts = false)
    {
        if (!is_a($cart, 'Cart')) {
            throw new \Exception('CartPresenter can only present instance of Cart');
        }

//        if ($shouldSeparateGifts) {
//            $rawProducts = $cart->getProductsWithSeparatedGifts();
//        } else {
            $rawProducts = $cart->getProducts(true);
//        }

        $products = array_map([$this, 'presentProduct'], $rawProducts);
        $products = $this->addCustomizedData($products, $cart);
        $subtotals = [];

        $productsTotalExcludingTax = $cart->getOrderTotal(false, Cart::ONLY_PRODUCTS);
        $total_excluding_tax = $cart->getOrderTotal(false);
        $total_including_tax = $cart->getOrderTotal(true);
        $total_discount = $cart->getDiscountSubtotalWithoutGifts($this->includeTaxes());
        $totalCartAmount = $cart->getOrderTotal($this->includeTaxes(), Cart::ONLY_PRODUCTS);

        $subtotals['products'] = [
            'type' => 'products',
            'label' => $this->translator->trans('Subtotal', [], 'Shop.Theme.Checkout'),
            'amount' => $totalCartAmount,
            'value' => $this->priceFormatter->format($totalCartAmount),
        ];

        if ($total_discount) {
            $subtotals['discounts'] = [
                'type' => 'discount',
                'label' => $this->translator->trans('Discount(s)', [], 'Shop.Theme.Checkout'),
                'amount' => $total_discount,
                'value' => $this->priceFormatter->format($total_discount),
            ];
        } else {
            $subtotals['discounts'] = null;
        }

        if ($cart->gift) {
            $giftWrappingPrice = ($cart->getGiftWrappingPrice($this->includeTaxes()) != 0)
                ? $cart->getGiftWrappingPrice($this->includeTaxes())
                : 0;

            $subtotals['gift_wrapping'] = [
                'type' => 'gift_wrapping',
                'label' => $this->translator->trans('Gift wrapping', [], 'Shop.Theme.Checkout'),
                'amount' => $giftWrappingPrice,
                'value' => ($giftWrappingPrice > 0)
                    ? $this->priceFormatter->convertAndFormat($giftWrappingPrice)
                    : $this->translator->trans('Free', [], 'Shop.Theme.Checkout'),
            ];
        }

        if (!$cart->isVirtualCart()) {
            $shippingCost = $cart->getTotalShippingCost(null, $this->includeTaxes());
        } else {
            $shippingCost = 0;
        }
        $subtotals['shipping'] = [
            'type' => 'shipping',
            'label' => $this->translator->trans('Shipping', [], 'Shop.Theme.Checkout'),
            'amount' => $shippingCost,
            'value' => $this->getShippingDisplayValue($cart, $shippingCost),
        ];

        $subtotals['tax'] = null;
        if (Configuration::get('PS_TAX_DISPLAY')) {
            $taxAmount = $total_including_tax - $total_excluding_tax;
            $subtotals['tax'] = [
                'type' => 'tax',
                'label' => ($this->includeTaxes())
                    ? $this->translator->trans('Included taxes', [], 'Shop.Theme.Checkout')
                    : $this->translator->trans('Taxes', [], 'Shop.Theme.Checkout'),
                'amount' => $taxAmount,
                'value' => $this->priceFormatter->format($taxAmount),
            ];
        }

        $totals = [
            'total' => [
                'type' => 'total',
                'label' => $this->translator->trans('Total', [], 'Shop.Theme.Checkout'),
                'amount' => $this->includeTaxes() ? $total_including_tax : $total_excluding_tax,
                'value' => $this->priceFormatter->format(
                    $this->includeTaxes() ? $total_including_tax : $total_excluding_tax
                ),
            ],
            'total_including_tax' => [
                'type' => 'total',
                'label' => $this->translator->trans('Total (tax incl.)', [], 'Shop.Theme.Checkout'),
                'amount' => $total_including_tax,
                'value' => $this->priceFormatter->format($total_including_tax),
            ],
            'total_excluding_tax' => [
                'type' => 'total',
                'label' => $this->translator->trans('Total (tax excl.)', [], 'Shop.Theme.Checkout'),
                'amount' => $total_excluding_tax,
                'value' => $this->priceFormatter->format($total_excluding_tax),
            ],
        ];

        $products_count = array_reduce($products, function ($count, $product) {
            return $count + $product['quantity'];
        }, 0);

        $summary_string = $products_count === 1 ?
            $this->translator->trans('1 item', [], 'Shop.Theme.Checkout') :
            $this->translator->trans('%count% items', ['%count%' => $products_count], 'Shop.Theme.Checkout');

        $minimalPurchase = $this->priceFormatter->convertAmount((float) Configuration::get('PS_PURCHASE_MINIMUM'));

        Hook::exec('overrideMinimalPurchasePrice', [
            'minimalPurchase' => &$minimalPurchase,
        ]);

        // TODO: move it to a common parent, since it's copied in OrderPresenter and ProductPresenter
        $labels = [
            'tax_short' => ($this->includeTaxes())
                ? $this->translator->trans('(tax incl.)', [], 'Shop.Theme.Global')
                : $this->translator->trans('(tax excl.)', [], 'Shop.Theme.Global'),
            'tax_long' => ($this->includeTaxes())
                ? $this->translator->trans('(tax included)', [], 'Shop.Theme.Global')
                : $this->translator->trans('(tax excluded)', [], 'Shop.Theme.Global'),
        ];

        $discounts = $cart->getDiscounts();
        $vouchers = $this->getTemplateVarVouchers($cart);

        $cartRulesIds = array_flip(array_map(
            function ($voucher) {
                return $voucher['id_cart_rule'];
            },
            $vouchers['added']
        ));

        $discounts = array_filter($discounts, function ($discount) use ($cartRulesIds, $cart) {
            $voucherCustomerId = (int) $discount['id_customer'];
            $voucherIsRestrictedToASingleCustomer = ($voucherCustomerId !== 0);
            $voucherIsEmptyCode = empty($discount['code']);
            if ($voucherIsRestrictedToASingleCustomer && $cart->id_customer !== $voucherCustomerId && $voucherIsEmptyCode) {
                return false;
            }

            return !array_key_exists($discount['id_cart_rule'], $cartRulesIds);
        });
	//toup
	if ($shouldSeparateGifts) {       
  $rawProducts = $cart->getProductsWithSeparatedGifts();
  $products = array_map([$this, 'presentProduct'], $rawProducts);
  $products = $this->addCustomizedData($products, $cart);
}
//endtoup
        return [
            'products' => $products,
            'totals' => $totals,
            'subtotals' => $subtotals,
            'products_count' => $products_count,
            'summary_string' => $summary_string,
            'labels' => $labels,
            'id_address_delivery' => $cart->id_address_delivery,
            'id_address_invoice' => $cart->id_address_invoice,
            'is_virtual' => $cart->isVirtualCart(),
            'vouchers' => $vouchers,
            'discounts' => $discounts,
            'minimalPurchase' => $minimalPurchase,
            'minimalPurchaseRequired' => ($productsTotalExcludingTax < $minimalPurchase) ?
                $this->translator->trans(
                    'A minimum shopping cart total of %amount% (tax excl.) is required to validate your order. Current cart total is %total% (tax excl.).',
                    [
                        '%amount%' => $this->priceFormatter->format($minimalPurchase),
                        '%total%' => $this->priceFormatter->format($productsTotalExcludingTax),
                    ],
                    'Shop.Theme.Checkout'
                ) :
                '',
        ];
    }

    /**
     * Accepts a cart object with the shipping cost amount and formats the shipping cost display value accordingly.
     * If the shipping cost is 0, then we must check if this is because of a free carrier and thus display 'Free' or
     * simply because the system was unable to determine shipping cost at this point and thus send an empty string to hide the shipping line.
     *
     * @param Cart $cart
     * @param float $shippingCost
     *
     * @return string
     */
    private function getShippingDisplayValue($cart, $shippingCost)
    {
        $shippingDisplayValue = '';

        // if one of the applied cart rules have free shipping, then the shipping display value is 'Free'
        foreach ($cart->getCartRules() as $rule) {
            if ($rule['free_shipping'] && !$rule['carrier_restriction']) {
                return $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
            }
        }

        if ($shippingCost != 0) {
            $shippingDisplayValue = $this->priceFormatter->format($shippingCost);
        } else {
            $defaultCountry = null;

            if (isset(Context::getContext()->cookie->id_country)) {
                $defaultCountry = new Country(Context::getContext()->cookie->id_country);
            }

            $deliveryOptionList = $cart->getDeliveryOptionList($defaultCountry);

            if (isset($deliveryOptionList) && count($deliveryOptionList) > 0) {
                foreach ($deliveryOptionList as $option) {
                    foreach ($option as $currentCarrier) {
                        if (isset($currentCarrier['is_free']) && $currentCarrier['is_free'] > 0) {
                            $shippingDisplayValue = $this->translator->trans('Free', [], 'Shop.Theme.Checkout');
                            break 2;
                        }
                    }
                }
            }
        }

        return $shippingDisplayValue;
    }
    
}