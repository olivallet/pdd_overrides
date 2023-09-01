<?php

class ProductInformation extends CommonAbstractType
{


    public function __construct(
        $translator,
        $legacyContext,
        $router,
        $categoryDataProvider,
        $productDataProvider,
        $featureDataProvider,
        $manufacturerDataProvider
    ) {
        $this->context = $legacyContext;
        $this->translator = $translator;
        $this->router = $router;
        $this->productDataProvider = $productDataProvider;
        $this->productAdapter = $this->productDataProvider;
        $this->categoryDataProvider = $categoryDataProvider;
        $this->manufacturerDataProvider = $manufacturerDataProvider;
        $this->featureDataProvider = $featureDataProvider;

        $this->configuration = new Configuration();
        $this->locales = $this->context->getLanguages();
        $this->currency = $this->context->getContext()->currency;

        $this->categories = $this->formatDataChoicesList(
            $this->categoryDataProvider->getAllCategoriesName(
                $root_category = null,
                $id_lang = false,
                $active = false
            ),
            'id_category'
        );

        $this->nested_categories = $this->categoryDataProvider->getNestedCategories(
            $root_category = null,
            $id_lang = false,
            $active = false
        );

        $this->manufacturers = $this->formatDataChoicesList(
            $this->manufacturerDataProvider->getManufacturers(
                $get_nb_products = false,
                $id_lang = 0,
                $active = false,
                $p = false,
                $n = false,
                $all_group = false,
                $group_by = true
            ),
            'id_manufacturer'
        );
    }


}