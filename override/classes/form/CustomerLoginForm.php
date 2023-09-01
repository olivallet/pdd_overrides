<?php
class CustomerLoginForm extends CustomerLoginFormCore
{
    public function submit()
    {
        if ($this->validate()) {
            Hook::exec('actionAuthenticationBefore');

            $customer = new Customer();
            $exists = Db::getInstance()->getValue("select count(*) as nb from ps_customer where email = '".pSQL($this->getValue('email'))."'");

            $authentication = $customer->getByEmail(
                $this->getValue('email'),
                $this->getValue('password')
            );

            if (isset($authentication->active) && !$authentication->active) {
                $this->errors[''][] = $this->translator->trans('Your account isn\'t available at this time, please contact us', [], 'Shop.Notifications.Error');
            } elseif (!$authentication || !$customer->id || $customer->is_guest) {
                if ($exists == 0) {
                    // $this->errors[''][] = $this->translator->trans('No user account with this email.');
                    $this->errors[''][] = 'Il n\'y a pas de compte client avec cette adresse email.';
                } else {
                    $this->errors[''][] = $this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error');
                }
            } else {
                $this->context->updateCustomer($customer);

                Hook::exec('actionAuthentication', ['customer' => $this->context->customer]);

                // Login information have changed, so we check if the cart rules still apply
                CartRule::autoRemoveFromCart($this->context);
                CartRule::autoAddToCart($this->context);
            }
        }

        return !$this->hasErrors();
    }
}