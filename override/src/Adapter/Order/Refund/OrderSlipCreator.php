<?php

class OrderSlipCreator
{

    public function create(
        Order $order,
        OrderRefundSummary $orderRefundSummary
    ) {
        if ($orderRefundSummary->getRefundedAmount() > 0) {
            $orderSlipCreated = $this->createOrderSlip(
                $order,
                $orderRefundSummary->getProductRefunds(),
                $orderRefundSummary->getRefundedShipping(),
                $orderRefundSummary->getVoucherAmount(),
                $orderRefundSummary->isVoucherChosen(),
                !$orderRefundSummary->isTaxIncluded(),
                $orderRefundSummary->getPrecision()
            );

            if (!$orderSlipCreated) {
                throw new OrderException('You cannot generate a partial credit slip.');
            }

            $fullQuantityList = array_map(function ($orderDetail) { return $orderDetail['quantity']; }, $orderRefundSummary->getProductRefunds());
            Hook::exec('actionOrderSlipAdd', [
                'order' => $order,
                'productList' => $orderRefundSummary->getProductRefunds(),
                'qtyList' => $fullQuantityList,
            ], null, false, true, false, $order->id_shop);

            $customer = new Customer((int) $order->id_customer);

            // @todo: use private method to send mail
            $params = [
                '{lastname}' => $customer->lastname,
                '{firstname}' => $customer->firstname,
                '{id_order}' => $order->id,
                '{order_name}' => $order->getUniqReference(),
            ];

            $orderLanguage = new Language((int) $order->id_lang);

            // @todo: use a dedicated Mail class (see #13945)
            // @todo: remove this @and have a proper error handling
	    /*
            @Mail::Send(
                (int) $order->id_lang,
                'credit_slip',
                $this->translator->trans(
                    'New credit slip regarding your order',
                    [],
                    'Emails.Subject',
                    $orderLanguage->locale
                ),
                $params,
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname,
                null,
                null,
                null,
                null,
                _PS_MAIL_DIR_,
                true,
                (int) $order->id_shop
            );
            */
            /** @var OrderDetail $orderDetail */
            foreach ($orderRefundSummary->getOrderDetails() as $orderDetail) {
                if ($this->configuration->get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    StockAvailable::synchronize($orderDetail->product_id);
                }
            }
        } else {
            throw new InvalidCancelProductException(InvalidCancelProductException::INVALID_AMOUNT);
        }
    }
}