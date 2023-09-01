<?php

class AdminProductsController extends AdminProductsControllerCore
{
    public function ajaxProcessProductManufacturers()
    {
        $manufacturers = Manufacturer::getManufacturers(false, 0, false, false, false, false, true);
        $jsonArray = [];

        if ($manufacturers) {
            foreach ($manufacturers as $manufacturer) {
                $tmp = ['optionValue' => $manufacturer['id_manufacturer'], 'optionDisplay' => htmlspecialchars(trim($manufacturer['name']))];
                $jsonArray[] = json_encode($tmp);
            }
        }

        die('[' . implode(',', $jsonArray) . ']');
    }
}