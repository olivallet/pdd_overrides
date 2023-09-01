<?php

class FrontController extends FrontControllerCore
{


    //déja surchargé askip, on verra ça 
    // protected function displayMaintenancePage()
    // {
    //     if ($this->maintenance == true || !(int) Configuration::get('PS_SHOP_ENABLE')) {
    //         $this->maintenance = true;
    //         if (!in_array(Tools::getRemoteAddr(), explode(',', Configuration::get('PS_MAINTENANCE_IP'))) && 
	// 	substr(Tools::getRemoteAddr(), 0, strlen('194.50.38.')) != '194.50.38.' && 
	// 	substr(Tools::getRemoteAddr(), 0, strlen('46.252.178.')) != '46.252.178.') {
    //             header('HTTP/1.1 503 Service Unavailable');
    //             header('Retry-After: 3600');

    //             $this->registerStylesheet('theme-error', '/assets/css/error.css', ['media' => 'all', 'priority' => 50]);
    //             $this->context->smarty->assign([
    //                 'urls' => $this->getTemplateVarUrls(),
    //                 'shop' => $this->getTemplateVarShop(),
    //                 'HOOK_MAINTENANCE' => Hook::exec('displayMaintenance', []),
    //                 'maintenance_text' => Configuration::get('PS_MAINTENANCE_TEXT', (int) $this->context->language->id),
    //                 'stylesheets' => $this->getStylesheets(),
    //             ]);
    //             $this->smartyOutputContent('errors/maintenance.tpl');

    //             exit;
    //         }
    //     }
    // }

    // protected function assignGeneralPurposeVariables()
    // {
    //     $templateVars = [
    //         'cart' => $this->cart_presenter->present($this->context->cart, true),
    //         'currency' => $this->getTemplateVarCurrency(),
    //         'customer' => $this->getTemplateVarCustomer(),
    //         'language' => $this->objectPresenter->present($this->context->language),
    //         'page' => $this->getTemplateVarPage(),
    //         'shop' => $this->getTemplateVarShop(),
    //         'urls' => $this->getTemplateVarUrls(),
    //         'configuration' => $this->getTemplateVarConfiguration(),
    //         'field_required' => $this->context->customer->validateFieldsRequiredDatabase(),
    //         'breadcrumb' => $this->getBreadcrumb(),
    //         'link' => $this->context->link,
    //         'time' => time(),
    //         'static_token' => Tools::getToken(false),
    //         'token' => Tools::getToken(),
    //         'debug' => _PS_MODE_DEV_,
    //     ];

    //     $modulesVariables = Hook::exec(
    //         'actionFrontControllerSetVariables',
    //         [
    //             'templateVars' => &$templateVars,
    //         ],
    //         null,
    //         true
    //     );

    //     if (is_array($modulesVariables)) {
    //         foreach ($modulesVariables as $moduleName => $variables) {
    //             $templateVars['modules'][$moduleName] = $variables;
    //         }
    //     }

    //     $this->context->smarty->assign($templateVars);

    //     Media::addJsDef([
    //         'prestashop' => $this->buildFrontEndObject($templateVars),
    //     ]);
    // }
}
