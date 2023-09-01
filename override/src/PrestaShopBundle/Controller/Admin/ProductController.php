<?php

class ProductController extends FrameworkBundleAdminController
{

    private function createProductForm(Product $product, AdminModelAdapter $modelMapper)
    {
        $formBuilder = $this->createFormBuilder(
            $modelMapper->getFormData($product),
            ['allow_extra_fields' => true]
        )
            ->add('id_product', HiddenType::class)
            ->add('step1', ProductInformation::class)
            ->add('step2', ProductPrice::class, ['id_product' => $product->id])
            ->add('step3', ProductQuantity::class)
            ->add('step4', ProductShipping::class)
            ->add('step5', ProductSeo::class, [
                'mapping_type' => $product->getRedirectType(),
            ])
            ->add('step6', ProductOptions::class);

        // Prepare combination form (fake but just to validate the form)
        $combinations = $product->getAttributesResume(
            $this->getContext()->language->id
        );

        if (is_array($combinations)) {
            $maxInputVars = (int) ini_get('max_input_vars');
            $combinationsCount = count($combinations) * 25;
            $combinationsInputs = ceil($combinationsCount / 1000) * 1000;

            if ($combinationsInputs > $maxInputVars) {
                $this->addFlash(
                    'error',
                    $this->trans(
                        'The value of the PHP.ini setting "max_input_vars" must be increased to %value% in order to be able to submit the product form.',
                        'Admin.Notifications.Error',
                        ['%value%' => $combinationsInputs]
                    )
                );
            }

            foreach ($combinations as $combination) {
                $formBuilder->add(
                    'combination_' . $combination['id_product_attribute'],
                    ProductCombination::class,
                    ['allow_extra_fields' => true]
                );
            }
        }

        return $formBuilder->getForm();
    }


}