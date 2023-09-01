<?php

/**
 * Class CartRuleCore.
 */
class CartRule extends CartRuleCore
{
    public static function autoAddToCart(Context $context = null)
    {
        if ($context === null) {
            $context = Context::getContext();
        }
        if (!CartRule::isFeatureActive() || !Validate::isLoadedObject($context->cart)) {
            return;
        }
        if (Db::getInstance()->getValue("select count(*) as nb from ps_cart_rule where code = '' and active = 1") < 1) {
            return;
        }
        $sql = '
		SELECT SQL_NO_CACHE cr.*
		FROM ' . _DB_PREFIX_ . 'cart_rule cr
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_shop crs ON cr.id_cart_rule = crs.id_cart_rule
		' . (!Validate::isLoadedObject($context->customer) && Group::isFeatureActive() ? ' LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_group crg ON cr.id_cart_rule = crg.id_cart_rule' : '') . '
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_carrier crca ON cr.id_cart_rule = crca.id_cart_rule
		' . ($context->cart->id_carrier ? 'LEFT JOIN ' . _DB_PREFIX_ . 'carrier c ON (c.id_reference = crca.id_carrier AND c.deleted = 0)' : '') . '
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_country crco ON cr.id_cart_rule = crco.id_cart_rule
		WHERE cr.active = 1
		AND cr.code = ""
		AND cr.quantity > 0
		AND NOW() BETWEEN cr.date_from AND cr.date_to
		AND (
			cr.id_customer = 0
			' . (Validate::isLoadedObject($context->customer) ? 'OR cr.id_customer = ' . (int) $context->cart->id_customer : '') . '
		)
		AND (
			cr.`carrier_restriction` = 0
			' . ($context->cart->id_carrier ? 'OR c.id_carrier = ' . (int) $context->cart->id_carrier : '') . '
		)
		AND (
			cr.`shop_restriction` = 0
			' . ((Shop::isFeatureActive() && $context->shop->id) ? 'OR crs.id_shop = ' . (int) $context->shop->id : '') . '
		)
		AND (
			cr.`group_restriction` = 0
			' . (Validate::isLoadedObject($context->customer) ? 'OR EXISTS (
				SELECT 1
				FROM `' . _DB_PREFIX_ . 'customer_group` cg
				INNER JOIN `' . _DB_PREFIX_ . 'cart_rule_group` crg ON cg.id_group = crg.id_group
				WHERE cr.`id_cart_rule` = crg.`id_cart_rule`
				AND cg.`id_customer` = ' . (int) $context->customer->id . '
				LIMIT 1
			)' : (Group::isFeatureActive() ? 'OR crg.`id_group` = ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP') : '')) . '
		)
		AND (
			cr.`reduction_product` <= 0
			OR EXISTS (
				SELECT 1
				FROM `' . _DB_PREFIX_ . 'cart_product`
				WHERE `' . _DB_PREFIX_ . 'cart_product`.`id_product` = cr.`reduction_product` AND `id_cart` = ' . (int) $context->cart->id . '
			)
		)
		AND NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'cart_cart_rule WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_cart_rule.id_cart_rule
																			AND id_cart = ' . (int) $context->cart->id . ')
		ORDER BY priority';
        $result = Db::getInstance()->executeS($sql, true, false);
        if ($result) {
            $cart_rules = ObjectModel::hydrateCollection('CartRule', $result);
            if ($cart_rules) {
                foreach ($cart_rules as $cart_rule) {
                    /** @var CartRule $cart_rule */
                    if ($cart_rule->checkValidity($context, false, false)) {
                        $context->cart->addCartRule($cart_rule->id);
                    }
                }
            }
        }
    }

}