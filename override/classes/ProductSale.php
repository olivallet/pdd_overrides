<?php

class ProductSale extends ProductSaleCore
{
    public static function fillProductSales()
    {
        $sql = 'truncate table ps_product_sale;
REPLACE INTO ps_product_sale
				(`id_product`, `quantity`, `sale_nbr`, `date_upd`)
				SELECT od.product_id, SUM(od.product_quantity), COUNT(od.product_id), NOW()
							FROM ps_order_detail od inner join ps_orders o on o.id_order = od.id_order 
                            where o.valid = 1 and datediff(NOW(),o.date_add) <= 30 
                          GROUP BY od.product_id;';
        /*        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'product_sale
                        (`id_product`, `quantity`, `sale_nbr`, `date_upd`)
                        SELECT od.product_id, SUM(od.product_quantity), COUNT(od.product_id), NOW()
                                    FROM ' . _DB_PREFIX_ . 'order_detail od where od.product_id <> 1028 GROUP BY od.product_id';*/

        return Db::getInstance()->execute($sql);
    }



    public static function addProductSale($productId, $qty = 1)
    {
        if ($productId == 1028) {
            return true;
        }
        return Db::getInstance()->execute('
        INSERT INTO ' . _DB_PREFIX_ . 'product_sale
        (`id_product`, `quantity`, `sale_nbr`, `date_upd`)
        VALUES (' . (int) $productId . ', ' . (int) $qty . ', 1, NOW())
        ON DUPLICATE KEY UPDATE `quantity` = `quantity` + ' . (int) $qty . ', `sale_nbr` = `sale_nbr` + 1, `date_upd` = NOW()');
    }

}