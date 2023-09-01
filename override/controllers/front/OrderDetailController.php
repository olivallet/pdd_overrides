<?php

class OrderDetailController extends OrderDetailControllerCore
{
    /**
     * Assign template vars related to page content.
     *
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        if (Configuration::isCatalogMode()) {
            Tools::redirect('index.php');
        }

        $id_order = (int) Tools::getValue('id_order');
        $id_order = $id_order && Validate::isUnsignedId($id_order) ? $id_order : false;

        if (!$id_order) {
            $reference = Tools::getValue('reference');
            $reference = $reference && Validate::isReference($reference) ? $reference : false;
            $order = $reference ? Order::getByReference($reference)->getFirst() : false;
            $id_order = $order ? $order->id : false;
        }

        if (!$id_order) {
            $this->redirect_after = '404';
            $this->redirect();
        } else {
            if (Tools::getIsset('errorQuantity')) {
                $this->errors[] = $this->trans('You do not have enough products to request an additional merchandise return.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorMsg')) {
                $this->errors[] = $this->trans('Please provide an explanation for your RMA.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorDetail1')) {
                $this->errors[] = $this->trans('Please check at least one product you would like to return.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorDetail2')) {
                $this->errors[] = $this->trans('For each product you wish to add, please specify the desired quantity.', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('errorNotReturnable')) {
                $this->errors[] = $this->trans('This order cannot be returned', [], 'Shop.Notifications.Error');
            } elseif (Tools::getIsset('messagesent')) {
                $this->success[] = $this->trans('Message successfully sent', [], 'Shop.Notifications.Success');
            }

            $order = new Order($id_order);
            if (Validate::isLoadedObject($order) && $order->id_customer == $this->context->customer->id) {
                $id_address = (int) $order->id_address_delivery;
                $cnc_stuff = Db::getInstance()->executeS("select * from ps_pdd_storedelivery_orders where id_order = ".$order->id);
                if ($cnc_stuff !== false && count($cnc_stuff) > 0) {
                    $count = Db::getInstance()->getValue("select count(*) as nb from ps_address where id_address = ".$cnc_stuff[0]['id_address']);
                    if ($count > 0) {
                        $id_address = $cnc_stuff[0]['id_address'];
                    }
                    $order->id_address_delivery = $id_address;
                }
                $this->order_to_display = (new OrderPresenter())->present($order);

                $this->reference = $order->reference;

                $this->context->smarty->assign([
                    'order' => $this->order_to_display,
                    'HOOK_DISPLAYORDERDETAIL' => Hook::exec('displayOrderDetail', ['order' => $order]),
                ]);
            } else {
                $this->redirect_after = '404';
                $this->redirect();
            }
            unset($order);
        }

        parent::initContent();
        $this->setTemplate('customer/order-detail');
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        $breadcrumb['links'][] = $this->addMyAccountToBreadcrumb();
        $breadcrumb['links'][] = [
            'title' => $this->trans('Order history', [], 'Shop.Theme.Customeraccount'),
            'url' => $this->context->link->getPageLink('history'),
        ];

        if (!empty($this->reference)) {
            $breadcrumb['links'][] = [
                'title' => $this->reference,
                'url' => '#',
            ];
        }

        return $breadcrumb;
    }

}