<?php

use GlotioPhpParser\Node\Expr\AssignOp\Mod;
use PrestaShop\Module\DemoConsoleCommand\ConverterTools;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\TranslateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
class Pdd_Overrides extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pdd_overrides';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'PDD';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('[PDD] Gestion des overrides');
        $this->description = $this->l('mettre les overrides ici plutot qu\'a la racine dans le dossier override');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
    }


    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        return parent::install() &&
        $this->installOverrides();
    }



    public function getContent()
    {
        echo('on reset les overrides');
        $this->resetOverrides();
        // echo("tout est uninstalled");
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
}
    //reset all overrides
    public function resetOverrides()
    {
        return $this->uninstallOverrides() && $this->installOverrides();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function installDb()
    {
        return true;
    }

    public function uninstallDb()
    {
        return true;
    
    }
}
