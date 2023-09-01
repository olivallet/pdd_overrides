<?php

class HTMLTemplateDeliverySlip extends HTMLTemplateDeliverySlipCore
{


    public function __construct(OrderInvoice $order_invoice, Smarty $smarty, $bulk_mode = false)
    {
        $this->order_invoice = $order_invoice;
        $this->order = new Order((int) $this->order_invoice->id_order);
        $this->smarty = $smarty;
        $this->smarty->assign('isTaxEnabled', (bool) Configuration::get('PS_TAX'));

        // If shop_address is null, then update it with current one.
        // But no DB save required here to avoid massive updates for bulk PDF generation case.
        // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
        if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
            $this->order_invoice->shop_address = OrderInvoice::getCurrentFormattedShopAddress((int) $this->order->id_shop);
            if (!$bulk_mode) {
                OrderInvoice::fixAllShopAddresses();
            }
        }

        // header informations
        $this->date = Tools::displayDate($order_invoice->date_add);

        $id_lang = Context::getContext()->language->id;
        $id_shop = Context::getContext()->shop->id;
        $this->title = $order_invoice->getInvoiceNumberFormatted($id_lang, $id_shop);

        $this->shop = new Shop((int) $this->order->id_shop);
    }



    protected function computeLayout(array $params)
    {
        $layout = [
            'reference' => [
                'width' => 15,
            ],
            'product' => [
                'width' => 40,
            ],
            'quantity' => [
                'width' => 12,
            ],
            'tax_code' => [
                'width' => 8,
            ],
            'unit_price_tax_excl' => [
                'width' => 0,
            ],
            'total_tax_excl' => [
                'width' => 0,
            ],
        ];

        if (isset($params['has_discount']) && $params['has_discount']) {
            $layout['before_discount'] = ['width' => 0];
            $layout['product']['width'] -= 7;
            $layout['reference']['width'] -= 3;
        }

        $total_width = 0;
        $free_columns_count = 0;
        foreach ($layout as $data) {
            if ($data['width'] === 0) {
                ++$free_columns_count;
            }

            $total_width += $data['width'];
        }

        $delta = 100 - $total_width;

        foreach ($layout as $row => $data) {
            if ($data['width'] === 0) {
                $layout[$row]['width'] = $delta / $free_columns_count;
            }
        }

        $layout['_colCount'] = count($layout);

        return $layout;
    }


        /**
     * Returns different tax breakdown elements.
     *
     * @return array|bool Different tax breakdown elements
     */
    protected function getTaxBreakdown()
    {
        $breakdowns = [
            'product_tax' => $this->order_invoice->getProductTaxesBreakdown($this->order),
            'shipping_tax' => $this->order_invoice->getShippingTaxesBreakdown($this->order),
            'ecotax_tax' => Configuration::get('PS_USE_ECOTAX') ? $this->order_invoice->getEcoTaxTaxesBreakdown() : [],
            'wrapping_tax' => $this->order_invoice->getWrappingTaxesBreakdown(),
        ];

        foreach ($breakdowns as $type => $bd) {
            if (empty($bd)) {
                unset($breakdowns[$type]);
            }
        }

        if (empty($breakdowns)) {
            return false;
        }

        if (isset($breakdowns['product_tax'])) {
            foreach ($breakdowns['product_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['total_price_tax_excl'];
            }
        }

        if (isset($breakdowns['ecotax_tax'])) {
            foreach ($breakdowns['ecotax_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['ecotax_tax_excl'];
                $bd['total_amount'] = $bd['ecotax_tax_incl'] - $bd['ecotax_tax_excl'];
            }
        }

        return $breakdowns;
    }



}
