<?php

class AdminCartRulesController extends AdminCartRulesControllerCore
{

    protected function afterUpdate($current_object)
    {
        // All the associations are deleted for an update, then recreated when we call the "afterAdd" method
        $id_cart_rule = Tools::getValue('id_cart_rule');
        foreach (['country', 'carrier', 'group', 'product_rule_group', 'shop'] as $type) {
            Db::getInstance()->delete('cart_rule_' . $type, '`id_cart_rule` = ' . (int) $id_cart_rule);
        }

        Db::getInstance()->delete('cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`
			WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule_group` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`.`id_product_rule_group`)');
        Db::getInstance()->delete('cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule`
			WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`.`id_product_rule` = `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule`)');
        // PDD on vire !
        // Db::getInstance()->delete('cart_rule_combination', '`id_cart_rule_1` = ' . (int) $id_cart_rule . ' OR `id_cart_rule_2` = ' . (int) $id_cart_rule);

        $this->afterAdd($current_object);
    }

    protected function afterAdd($currentObject)
    {
        // Add restrictions for generic entities like country, carrier and group
        foreach (['country', 'carrier', 'group', 'shop'] as $type) {
            if (Tools::getValue($type . '_restriction') && is_array($array = Tools::getValue($type . '_select')) && count($array)) {
                $values = [];
                foreach ($array as $id) {
                    $values[] = '(' . (int) $currentObject->id . ',' . (int) $id . ')';
                }
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_' . $type . '` (`id_cart_rule`, `id_' . $type . '`) VALUES ' . implode(',', $values));
            }
        }
        // Add cart rule restrictions
        //PDD on vire !
        if (2 == 1 && Tools::getValue('cart_rule_restriction') && is_array($array = Tools::getValue('cart_rule_select')) && count($array)) {
            $values = [];
            foreach ($array as $id) {
                $values[] = '(' . (int) $currentObject->id . ',' . (int) $id . ')';
            }
            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) VALUES ' . implode(',', $values));
        }
        // Add product rule restrictions
        if (Tools::getValue('product_restriction') && is_array($ruleGroupArray = Tools::getValue('product_rule_group')) && count($ruleGroupArray)) {
            foreach ($ruleGroupArray as $ruleGroupId) {
                Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` (`id_cart_rule`, `quantity`)
				VALUES (' . (int) $currentObject->id . ', ' . (int) Tools::getValue('product_rule_group_' . $ruleGroupId . '_quantity') . ')');
                $id_product_rule_group = Db::getInstance()->Insert_ID();

                if (is_array($ruleArray = Tools::getValue('product_rule_' . $ruleGroupId)) && count($ruleArray)) {
                    foreach ($ruleArray as $ruleId) {
                        Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule` (`id_product_rule_group`, `type`)
						VALUES (' . (int) $id_product_rule_group . ', "' . pSQL(Tools::getValue('product_rule_' . $ruleGroupId . '_' . $ruleId . '_type')) . '")');
                        $id_product_rule = Db::getInstance()->Insert_ID();

                        $values = [];
                        foreach (Tools::getValue('product_rule_select_' . $ruleGroupId . '_' . $ruleId) as $id) {
                            $values[] = '(' . (int) $id_product_rule . ',' . (int) $id . ')';
                        }
                        $values = array_unique($values);
                        if (count($values)) {
                            Db::getInstance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` (`id_product_rule`, `id_item`) VALUES ' . implode(',', $values));
                        }
                    }
                }
            }
        }

        // If the new rule has no cart rule restriction, then it must be added to the white list of the other cart rules that have restrictions
        //PDD on vire !
        if (2 == 1) {
            if (!Tools::getValue('cart_rule_restriction')) {
                Db::getInstance()->execute('
                INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
                    SELECT id_cart_rule, ' . (int) $currentObject->id . ' FROM `' . _DB_PREFIX_ . 'cart_rule` WHERE cart_rule_restriction = 1
                )');
            } else {
                // And if the new cart rule has restrictions, previously unrestricted cart rules may now be restricted (a mug of coffee is strongly advised to understand this sentence)
                $ruleCombinations = Db::getInstance()->executeS('
                SELECT cr.id_cart_rule
                FROM ' . _DB_PREFIX_ . 'cart_rule cr
                WHERE cr.id_cart_rule != ' . (int) $currentObject->id . '
                AND cr.cart_rule_restriction = 0
                AND NOT EXISTS (
                    SELECT 1
                    FROM ' . _DB_PREFIX_ . 'cart_rule_combination
                    WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_2 AND ' . (int) $currentObject->id . ' = id_cart_rule_1
                )
                AND NOT EXISTS (
                    SELECT 1
                    FROM ' . _DB_PREFIX_ . 'cart_rule_combination
                    WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_1 AND ' . (int) $currentObject->id . ' = id_cart_rule_2
                )
                ');
                foreach ($ruleCombinations as $incompatibleRule) {
                    Db::getInstance()->execute('UPDATE `' . _DB_PREFIX_ . 'cart_rule` SET cart_rule_restriction = 1 WHERE id_cart_rule = ' . (int) $incompatibleRule['id_cart_rule'] . ' LIMIT 1');
                    Db::getInstance()->execute('
                    INSERT IGNORE INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`) (
                        SELECT id_cart_rule, ' . (int) $incompatibleRule['id_cart_rule'] . ' FROM `' . _DB_PREFIX_ . 'cart_rule`
                        WHERE active = 1
                        AND id_cart_rule != ' . (int) $currentObject->id . '
                        AND id_cart_rule != ' . (int) $incompatibleRule['id_cart_rule'] . '
                    )');
                }
            }
        }
    }

    protected function searchProducts($search)
    {
        if ($products = Product::searchByName((int) $this->context->language->id, $search)) {
            foreach ($products as &$product) {
                $combinations = [];
                $productObj = new Product((int) $product['id_product'], false, (int) $this->context->language->id);
                $attributes = $productObj->getAttributesGroups((int) $this->context->language->id);
                $product['formatted_price'] = $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($product['price_tax_incl'], $this->context->currency), $this->context->currency->iso_code);
                //$product['formatted_price'] = $product['price_tax_incl']
                //    ? $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($product['price_tax_incl'], $this->context->currency), $this->context->currency->iso_code)
                //    : '';
                foreach ($attributes as $attribute) {
                    if (!isset($combinations[$attribute['id_product_attribute']]['attributes'])) {
                        $combinations[$attribute['id_product_attribute']]['attributes'] = '';
                    }
                    $combinations[$attribute['id_product_attribute']]['attributes'] .= $attribute['attribute_name'] . ' - ';
                    $combinations[$attribute['id_product_attribute']]['id_product_attribute'] = $attribute['id_product_attribute'];
                    $combinations[$attribute['id_product_attribute']]['default_on'] = $attribute['default_on'];
                    if (!isset($combinations[$attribute['id_product_attribute']]['price'])) {
                        $price_tax_incl = Product::getPriceStatic((int) $product['id_product'], true, $attribute['id_product_attribute']);
                        $combinations[$attribute['id_product_attribute']]['formatted_price'] = $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($price_tax_incl, $this->context->currency), $this->context->currency->iso_code);
                        //$combinations[$attribute['id_product_attribute']]['formatted_price'] = $price_tax_incl
                        //    ? $this->context->getCurrentLocale()->formatPrice(Tools::convertPrice($price_tax_incl, $this->context->currency), $this->context->currency->iso_code)
                        //    : '';
                    }
                }

                foreach ($combinations as &$combination) {
                    $combination['attributes'] = rtrim($combination['attributes'], ' - ');
                }
                $product['combinations'] = $combinations;
            }

            return [
                'products' => $products,
                'found' => true,
            ];
        } else {
            return ['found' => false, 'notfound' => $this->trans('No product has been found.', [], 'Admin.Catalog.Notification')];
        }
    }

}
