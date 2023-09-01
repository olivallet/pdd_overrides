<?php


class CategoryController extends CategoryControllerCore
{

        /**
     * Initializes controller.
     *
     * @see FrontController::init()
     *
     * @throws PrestaShopException
     */
    public function init()
    {
        $id_category = (int) Tools::getValue('id_category');
        $this->category = new Category(
            $id_category,
            $this->context->language->id
        );

        if (!Validate::isLoadedObject($this->category) || !$this->category->active) {
            Tools::redirect('index.php?controller=404');
        }

        parent::init();

        if (!$this->category->checkAccess($this->context->customer->id) && $this->context->customer->logged) {
            header('HTTP/1.1 403 Forbidden');
            header('Status: 403 Forbidden');
            $this->errors[] = $this->trans('You do not have access to this category.', [], 'Shop.Notifications.Error');
            $this->setTemplate('errors/forbidden');

            return;
        }

        $categoryVar = $this->getTemplateVarCategory();

        $filteredCategory = Hook::exec(
            'filterCategoryContent',
            ['object' => $categoryVar],
            $id_module = null,
            $array_return = false,
            $check_exceptions = true,
            $use_push = false,
            $id_shop = null,
            $chain = true
        );
        if (!empty($filteredCategory['object'])) {
            $categoryVar = $filteredCategory['object'];
        }

        $this->context->smarty->assign([
            'category' => $categoryVar,
            'subcategories' => $this->getTemplateVarSubCategories(),
        ]);
    }


    public function initContent()
    {
        parent::initContent();
        if (1==1 || $this->category->checkAccess($this->context->customer->id)) {
            $this->doProductSearch(
                'catalog/listing/category',
                [
                    'entity' => 'category',
                    'id' => $this->category->id,
                ]
            );
        } else {
            $form = new CustomerLoginForm(
                $this->context->smarty,
                $this->context,
                $this->getTranslator(),
                new CustomerLoginFormatter($this->getTranslator()),
                $this->context->controller->getTemplateVarUrls()
            );
            $url = $this->context->link->getCategoryLink($this->category->id);
            $form->setAction('index.php?controller=authentication&back='.urlencode($url));
            $form->fillWith(['back' => $url]);
            //$form->fillWith(Tools::getAllValues());
            $register_url = 'index.php?controller=authentication&create_account=1&back='.urlencode($url);
            $this->context->smarty->assign([
                'login_form' => $form->getProxy(),
                'register_url' => $register_url
            ]);
            $this->setTemplate('catalog/listing/restricted-category.tpl');
        }
    }

    public function getLayout()
    {
        if (2==1 && !$this->category->checkAccess($this->context->customer->id) && !$this->context->customer->logged) {
            return 'catalog/listing/restricted-category.tpl';
        }

        if (!$this->category->checkAccess($this->context->customer->id)) {
            return 'layouts/layout-full-width.tpl';
        }

        return parent::getLayout();
    }

    protected function getProductSearchQuery()
    {
        $query = new ProductSearchQuery();
        $query
            ->setIdCategory($this->category->id)
            ->setSortOrder(new SortOrder('product', 'sales', 'desc'));
        // ->setSortOrder(new SortOrder('product', Tools::getProductsOrder('by'), Tools::getProductsOrder('way')));

        return $query;
    }

}
