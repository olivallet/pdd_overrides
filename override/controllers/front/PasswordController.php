<?php
class PasswordController extends PasswordControllerCore
{
    protected function sendRenewPasswordLink()
    {
        if (!($email = $this->IDNConverter->emailToUtf8(trim(Tools::getValue('email')))) || !Validate::isEmail($email)) {
            $this->errors[] = $this->trans('Invalid email address.', [], 'Shop.Notifications.Error');
        } else {
            $customer = new Customer();
            $customer->getByEmail($email);
            if (null === $customer->email) {
                $customer->email = Tools::getValue('email');
            }

            if (!Validate::isLoadedObject($customer)) {
                // $this->success[] = $this->trans(
                //     'If this email address has been registered in our shop, you will receive a link to reset your password at %email%.',
                //     ['%email%' => $customer->email],
                //     'Shop.Notifications.Success'
                // );
                $this->errors[] = 'Il n\'y a pas de compte client avec cette adresse email.';
            //$this->setTemplate('customer/password-infos');
            } elseif (!$customer->active) {
                $this->errors[] = $this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error');
            } elseif ((strtotime($customer->last_passwd_gen . '+' . ($minTime = (int) Configuration::get('PS_PASSWD_TIME_FRONT')) . ' minutes') - time()) > 0) {
                $this->errors[] = $this->trans('You can regenerate your password only every %d minute(s)', [(int) $minTime], 'Shop.Notifications.Error');
            } else {
                if (!$customer->hasRecentResetPasswordToken()) {
                    $customer->stampResetPasswordToken();
                    $customer->update();
                }

                $mailParams = [
                    '{email}' => $customer->email,
                    '{lastname}' => $customer->lastname,
                    '{firstname}' => $customer->firstname,
                    '{url}' => $this->context->link->getPageLink('password', true, null, 'token=' . $customer->secure_key . '&id_customer=' . (int) $customer->id . '&reset_token=' . $customer->reset_password_token),
                ];

                if (
                    Mail::Send(
                        $this->context->language->id,
                        'password_query',
                        $this->trans(
                            'Password query confirmation',
                            [],
                            'Emails.Subject'
                        ),
                        $mailParams,
                        $customer->email,
                        $customer->firstname . ' ' . $customer->lastname
                    )
                ) {
                    $this->success[] = $this->trans('If this email address has been registered in our shop, you will receive a link to reset your password at %email%.', ['%email%' => $customer->email], 'Shop.Notifications.Success');
                    $this->setTemplate('customer/password-infos');
                } else {
                    $this->errors[] = $this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error');
                }
            }
        }
    }

}