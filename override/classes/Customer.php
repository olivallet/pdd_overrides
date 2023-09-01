<?php

class Customer extends CustomerCore
{
    public static function getDefaultGroupId($idCustomer)
    {
        if (!Group::isFeatureActive()) {
            static $psCustomerGroup = null;
            if ($psCustomerGroup === null) {
                $psCustomerGroup = Configuration::get('PS_CUSTOMER_GROUP');
            }

            return $psCustomerGroup;
        }

        if (!isset(self::$_defaultGroupId[(int) $idCustomer])) {
            self::$_defaultGroupId[(int) $idCustomer] = Db::getInstance()->getValue(
                '
                SELECT `id_default_group`
                FROM `' . _DB_PREFIX_ . 'customer`
                WHERE `id_customer` = ' . (int) $idCustomer
            );
        }
        $customer = Context::getContext()->customer;
        $idc = 0;
        if (Validate::isLoadedObject($customer)) {
            $idc = $customer->id;
        }
        if (self::$_defaultGroupId[(int) $idCustomer] == ((int)Configuration::get('PS_CUSTOMER_GROUP')) && $idCustomer == $idc) {
            $cart = Context::getContext()->cart;
            $mod = Module::getInstanceByName('pdd_purple');
            $id_product_purple = $mod->getPurpleCardProductId();
            if ($cart !== null && $cart->getProductQuantity($id_product_purple)['quantity'] > 0) {
                return $mod->getPurpleGroupId();
            }
        }
        return self::$_defaultGroupId[(int) $idCustomer];
    }

}