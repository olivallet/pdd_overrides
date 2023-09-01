<?php 
    

class Group extends GroupCore
{

    /**
     * Return current group object
     * Use context.
     *
     * @return Group Group object
     */
    public static function getCurrent()
    {
        if (self::$ps_unidentified_group === null) {
            self::$ps_unidentified_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
        }

        if (self::$ps_customer_group === null) {
            self::$ps_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        }

        $customer = Context::getContext()->customer;
        $cart = Context::getContext()->cart;
        if (Validate::isLoadedObject($customer)) {
            $id_group = (int) Customer::getDefaultGroupId($customer->id);
        } else {
            $id_group = (int) self::$ps_unidentified_group;
            $mod = Module::getInstanceByName('pdd_purple');
            $id_prod_purple = $mod->getPurpleCardProductId();
            if ($cart !== null && $cart->getProductQuantity($id_prod_purple)['quantity'] > 0) {
                $id_group = $mod->getPurpleGroupId();
            }
        }

        if (!isset(self::$groups[$id_group])) {
            self::$groups[$id_group] = new Group($id_group);
        }

        if (!self::$groups[$id_group]->isAssociatedToShop(Context::getContext()->shop->id)) {
            $id_group = (int) self::$ps_customer_group;
            if (!isset(self::$groups[$id_group])) {
                self::$groups[$id_group] = new Group($id_group);
            }
        }

        return self::$groups[$id_group];
    }

}