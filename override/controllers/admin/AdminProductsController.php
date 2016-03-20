<?php



ini_set('max_input_vars', 9999);

ini_set('max_execution_time', 3600);



class AdminProductsController extends AdminProductsControllerCore {

  public function processUpdate()
  {
    $existing_product = $this->object;

    $this->checkProduct();

    if (!empty($this->errors))
    {
      $this->display = 'edit';
      return false;
    }

    $id = (int)Tools::getValue('id_'.$this->table);
    /* Update an existing product */
    if (isset($id) && !empty($id))
    {
      $object = new $this->className((int)$id);
      $this->object = $object;
      $old_price = $object->price;

      if (Validate::isLoadedObject($object))
      {
        $this->_removeTaxFromEcotax();
        $product_type_before = $object->getType();
        $this->copyFromPost($object, $this->table);
        $object->indexed = 0;

        if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP)
          $object->setFieldsToUpdate((array)Tools::getValue('multishop_check', array()));

        // Duplicate combinations if not associated to shop
        if ($this->context->shop->getContext() == Shop::CONTEXT_SHOP && !$object->isAssociatedToShop())
        {
          $is_associated_to_shop = false;
          $combinations = Product::getProductAttributesIds($object->id);
          if ($combinations)
          {
            foreach ($combinations as $id_combination)
            {
              $combination = new Combination((int)$id_combination['id_product_attribute']);
              $default_combination = new Combination((int)$id_combination['id_product_attribute'], null, (int)$this->object->id_shop_default);

              $def = ObjectModel::getDefinition($default_combination);
              foreach ($def['fields'] as $field_name => $row)
                $combination->$field_name = ObjectModel::formatValue($default_combination->$field_name, $def['fields'][$field_name]['type']);

              $combination->save();
            }
          }
        }
        else
          $is_associated_to_shop = true;

        if ($object->update(false, $old_price))
        {
          // If the product doesn't exist in the current shop but exists in another shop
          if (Shop::getContext() == Shop::CONTEXT_SHOP && !$existing_product->isAssociatedToShop($this->context->shop->id))
          {
            $out_of_stock = StockAvailable::outOfStock($existing_product->id, $existing_product->id_shop_default);
            $depends_on_stock = StockAvailable::dependsOnStock($existing_product->id, $existing_product->id_shop_default);
            StockAvailable::setProductOutOfStock((int)$this->object->id, $out_of_stock, $this->context->shop->id);
            StockAvailable::setProductDependsOnStock((int)$this->object->id, $depends_on_stock, $this->context->shop->id);
          }

          PrestaShopLogger::addLog(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->className), 1, null, $this->className, (int)$this->object->id, true, (int)$this->context->employee->id);
          if (in_array($this->context->shop->getContext(), array(Shop::CONTEXT_SHOP, Shop::CONTEXT_ALL)))
          {
            if ($this->isTabSubmitted('Shipping'))
              $this->addCarriers();
            if ($this->isTabSubmitted('Associations'))
              $this->updateAccessories($object);
            if ($this->isTabSubmitted('Suppliers'))
              $this->processSuppliers();
            if ($this->isTabSubmitted('Features'))
              $this->processFeatures();
            if ($this->isTabSubmitted('Combinations'))
              $this->processProductAttribute();
            if ($this->isTabSubmitted('Prices'))
            {
              $this->processPriceAddition();
              $this->processSpecificPricePriorities();
            }
            if ($this->isTabSubmitted('Customization'))
              $this->processCustomizationConfiguration();
            if ($this->isTabSubmitted('Attachments'))
              $this->processAttachments();
            if ($this->isTabSubmitted('Images'))
              $this->processImageLegends();

            $this->updatePackItems($object);
            // Disallow avanced stock management if the product become a pack
            if ($product_type_before == Product::PTYPE_SIMPLE && $object->getType() == Product::PTYPE_PACK)
              StockAvailable::setProductDependsOnStock((int)$object->id, false);
            $this->updateDownloadProduct($object, 1);
            $this->updateTags(Language::getLanguages(false), $object);

            if ($this->isProductFieldUpdated('category_box') && !$object->updateCategories(Tools::getValue('categoryBox')))
              $this->errors[] = Tools::displayError('An error occurred while linking the object.').' <b>'.$this->table.'</b> '.Tools::displayError('To categories');
          }

          if ($this->isTabSubmitted('Warehouses'))
            $this->processWarehouses();
          if (empty($this->errors))
          {
            if (in_array($object->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION'))
              Search::indexation(false, $object->id);

            // Save and preview
            if (Tools::isSubmit('submitAddProductAndPreview'))
              $this->redirect_after = $this->getPreviewUrl($object);
            else
            {
              // Save and stay on same form
              if ($this->display == 'edit')
              {
                $this->confirmations[] = $this->l('Update successful');
                $this->redirect_after = self::$currentIndex.'&id_product='.(int)$this->object->id
                  .(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '')
                  .'&updateproduct&conf=4&key_tab='.Tools::safeOutput(Tools::getValue('key_tab')).'&token='.$this->token;
              }
              else
                // Default behavior (save and back)
                $this->redirect_after = self::$currentIndex.(Tools::getIsset('id_category') ? '&id_category='.(int)Tools::getValue('id_category') : '').'&conf=4&token='.$this->token;
            }
          }
          // if errors : stay on edit page
          else
            $this->display = 'edit';
        }
        else
        {
          if (!$is_associated_to_shop && $combinations)
            foreach ($combinations as $id_combination)
            {
              $combination = new Combination((int)$id_combination['id_product_attribute']);
              $combination->delete();
            }
          $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Db::getInstance()->getMsgError().')';
        }
      }
      else
        $this->errors[] = Tools::displayError('An error occurred while updating an object.').' <b>'.$this->table.'</b> ('.Tools::displayError('The object cannot be loaded. ').')';
      return $object;
    }
  }


}



