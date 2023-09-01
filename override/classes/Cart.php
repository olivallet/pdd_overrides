<?php

class Cart extends CartCore
{
    public function getUpdatedTotals($cart, $discount_data)
    {
        $mod = Module::getInstanceByName('pdd_purple');
        $id_purple = $mod->getPurpleCardProductId();
        $computed_discount = 0;
        foreach ($discount_data as $d) {
            if (empty($d)) {
                continue;
            }
            foreach ($d as $k) {
                if (!isset($k['redu'])) {
                    continue;
                }
                $computed_discount += $k['redu'];
            }
        }
        $catalog_discount = 0;
        $gifts_discount = 0;
        foreach ($cart['products'] as $prod) {
            if ($prod['id_product'] == $id_purple) {
                continue;
            }
            if ($prod['is_gift']) {
                $gifts_discount += $prod['total_wt'];
                continue;
            }
            if ($prod['price_with_reduction'] != $prod['price_without_reduction']) {
                $add = ($prod['price_without_reduction'] * $prod['quantity']) - $prod['total_wt'];
                $catalog_discount += $add;
            }
        }
        if ($catalog_discount == 0 && $gifts_discount == 0) {
            return $cart;
        }

        $s = Context::getContext()->smarty;
        $new_discount = $computed_discount + $catalog_discount;
        $tots = $cart['subtotals'];
        if (empty($tots['discounts'])) {
            $newtots = ['products' => $tots['products']];
            $newtots['discounts'] = [
                'type' => 'discount',
                'amount' => 0.0,
                'value' => Tools::displayPriceSmarty(['price' => 0.0], $s),
                'label' => 'Réduction(s)'
            ];
            foreach ($tots as $key => $val) {
                if ($key == 'products' || $key == 'discounts') {
                    continue;
                }
                $newtots[$key] = $val;
            }
            $tots = $newtots;
            unset($newtots);
        }
        $old_discount = $tots['discounts']['amount'];
        // dump($old_discount, $new_discount, $computed_discount, $catalog_discount, $gifts_discount);
        $delta = $new_discount - $old_discount;
        $tots['products']['amount'] += $delta;
        $tots['products']['value'] = Tools::displayPriceSmarty(['price' => $tots['products']['amount']], $s);
        // $tots['products']['amount'] -= $gifts_discount;
        $tots['discounts']['amount'] = $new_discount;
        $tots['discounts']['value'] = Tools::displayPriceSmarty(['price' => $tots['discounts']['amount']], $s);
        if ($tots['discounts']['amount'] == 0) {
            unset($tots['discounts']);
        }
        $cart['subtotals'] = $tots;
        return $cart;
        // $to_add = 0;
        // foreach ($cart['products'] as $prod) {
        //     if (!$prod['is_gift']) {
        //         continue;
        //     }
        //     if ($prod['price_with_reduction'] != $prod['price_without_reduction']) {
        //         $add = ($prod['price_without_reduction'] * $prod['quantity']) - $prod['total_wt'];
        //         $to_add += $add;
        //     }
        // }
        // if ($to_add > 0) {
        //     $s = Context::getContext()->smarty;
        //     $tots = $cart['subtotals'];
        //     $new_p = $tots['products']['amount'] + $to_add;
        //     $new_d = $tots['discounts']['amount'] + $to_add;
        //     $tots['products']['amount'] = $new_p;
        //     $tots['discounts']['amount'] = $new_d;
        //     $tots['products']['value'] = Tools::displayPriceSmarty(['price' => $new_p], $s);
        //     $tots['discounts']['value'] = Tools::displayPriceSmarty(['price' => $new_d], $s);
        //     $cart['subtotals'] = $tots;
        // }
        // dump($cart, $discount_data);
        // return $cart;
    }

}