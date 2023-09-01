<?php

    class HTMLTemplateInvoice extends HTMLTemplateInvoiceCore
    {
        /**
     * Returns the template's HTML header.
     *
     * @return string HTML header
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $wave_name = "select name from ps_order_state_lang where id_order_state = ".$this->order->current_state." and id_lang = 1";
        $wave_name = Db::getInstance()->getValue($wave_name);
        if (is_string($wave_name)) {
            if (strpos($wave_name, 'VAGUE') === false) {
                $wave_name = '';
            }
            if (strpos($wave_name, 'DAMIEN') !== false) {
                $wave_name = '';
            }
            $wave_name = str_replace('LOGISTIQUE - VAGUE ', 'V', $wave_name);
        }
        $ex = '';
        if (!empty($wave_name)) {
            $ex = ' ('.$wave_name.')';
        }
        $this->smarty->assign(['header' => Context::getContext()->getTranslator()->trans('Delivery', [], 'Shop.Pdf').$ex]);

        return $this->smarty->fetch($this->getTemplate('header'));
    }

    /**
     * Returns the template's HTML content.
     *
     * @return string HTML content
     */
    public function getContent()
    {
    $id_address = (int) $this->order->id_address_delivery;
    $cnc_stuff = Db::getInstance()->executeS("select * from ps_pdd_storedelivery_orders where id_order = ".$this->order->id);
    if ($cnc_stuff !== false && count($cnc_stuff) > 0) {
        $count = Db::getInstance()->getValue("select count(*) as nb from ps_address where id_address = ".$cnc_stuff[0]['id_address']);
        if ($count > 0) {
            $id_address = $cnc_stuff[0]['id_address'];
        }
    }
    $delivery_address = new Address($id_address);
    $formatted_delivery_address = AddressFormat::generateAddress($delivery_address, [], '<br />', ' ');
    $formatted_invoice_address = '';

    if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
        $invoice_address = new Address((int) $this->order->id_address_invoice);
        $formatted_invoice_address = AddressFormat::generateAddress($invoice_address, [], '<br />', ' ');
    }

    $carrier = new Carrier($this->order->id_carrier);
    $carrier->name = ($carrier->name == '0' ? Configuration::get('PS_SHOP_NAME') : $carrier->name);

    $order_details = $this->order_invoice->getProducts();
    $mod = Module::getInstanceByName('pdd_fastmag');
    $shipment_info = $mod->getOrderShipments($this->order->id);
    $skips = [];
    // if (isset($shipment_info['extras'])) {
    //     foreach ($shipment_info['extras'] as $extra) {
    //         foreach ($extra as $line) {
    //             $skips[] = $line['product_id'];
    //         }
    //     }
    // }
    $is_99 = [];
    // if ($this->order->current_state != 66) {
    //     $is_99 = "select distinct co.id_order, co.id_colissimo_order, ce.id_colissimo_label from ps_colissimo_order co inner join ps_colissimo_label cl on cl.id_colissimo_order = co.id_colissimo_order inner join ps_pdd_colissimo_extras ce on ce.id_colissimo_label = cl.id_colissimo_label where ce.is_shipped = 0 and co.id_order = ".$this->order->id;
    // }
    // $is_99 = Db::getInstance()->executeS($is_99);
    foreach ($order_details as &$od) {
        $od['skip'] = 0;
        if (in_array($od['product_id'], $skips)) {
            $od['skip'] = 1;
        }
        // if ($this->order->current_state == 66 || count($is_99) > 0) {
        //     if ($od['skip'] == 0) {
        //         $od['skip'] = 1;
        //     } else {
        //         $od['skip'] = 0;
        //     }
        // }
        $od['pack_content'] = [];
        if (!Pack::isPack($od['product_id'])) {
            if ($od['skip'] == 1) {
                continue;
            }
            $nd[] = $od;
            continue;
        }
        $sql = "select pk.id_product_item, pk.id_product_attribute_item, pk.quantity, pl.name, p.reference as product_ref, pa.reference as pa_ref from ps_pack pk inner join ps_product_lang pl on pl.id_product = pk.id_product_item inner join ps_product p on p.id_product = pk.id_product_item left join ps_product_attribute pa on pa.id_product = pk.id_product_item and pa.id_product_attribute = pk.id_product_attribute_item where pk.id_product_pack = ".$od['product_id'];
        $pack_info = Db::getInstance()->executeS($sql);
        foreach ($pack_info as &$pack_item) {
            if ($pack_item['id_product_attribute_item'] == "0") {
                $pack_item['reference'] = $pack_item['product_ref'];
            } else {
                $pack_item['reference'] = $pack_item['pa_ref'];
            }
            unset($pack_item['pa_ref']);
            unset($pack_item['product_ref']);
        }
        $od['pack_content'] = $pack_info;
        if ($od['skip'] == 1) {
            continue;
        }
        $nd[] = $od;
    }
    $order_details = $nd;

    // var_dump($order_details);
    // die();
    if (Configuration::get('PS_PDF_IMG_DELIVERY')) {
        foreach ($order_details as &$order_detail) {
            if ($order_detail['image'] != null) {
                $name = 'product_mini_' . (int) $order_detail['product_id'] . (isset($order_detail['product_attribute_id']) ? '_' . (int) $order_detail['product_attribute_id'] : '') . '.jpg';
                $path = _PS_PROD_IMG_DIR_ . $order_detail['image']->getExistingImgPath() . '.jpg';

                $order_detail['image_tag'] = preg_replace(
                    '/\.*' . preg_quote(__PS_BASE_URI__, '/') . '/',
                    _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR,
                    ImageManager::thumbnail($path, $name, 45, 'jpg', false),
                    1
                );

                if (file_exists(_PS_TMP_IMG_DIR_ . $name)) {
                    $order_detail['image_size'] = getimagesize(_PS_TMP_IMG_DIR_ . $name);
                } else {
                    $order_detail['image_size'] = false;
                }
            }
        }
    }

    $this->smarty->assign([
        'order' => $this->order,
        'order_details' => $order_details,
        'delivery_address' => $formatted_delivery_address,
        'invoice_address' => $formatted_invoice_address,
        'order_invoice' => $this->order_invoice,
        'carrier' => $carrier,
        'display_product_images' => Configuration::get('PS_PDF_IMG_DELIVERY'),
    ]);

    $tpls = [
        'style_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.style-tab')),
        'addresses_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.addresses-tab')),
        'summary_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.summary-tab')),
        'product_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.product-tab')),
        'payment_tab' => $this->smarty->fetch($this->getTemplate('delivery-slip.payment-tab')),
    ];
    $this->smarty->assign($tpls);

    return $this->smarty->fetch($this->getTemplate('delivery-slip'));
}

}