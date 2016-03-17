<?php

/**
 * Created by PhpStorm.
 * User: Vlaimip
 * Date: 25.01.2016
 * Time: 7:57
 */
class ProductController extends ProductControllerCore
{

  public function initContent() {

    if (!$this->errors) {
      $lampStart = 0;
      $lampLimit = 5;
      $lampOrderBy = 'id_product';
      $lampOrderWay = 'ASC';
      $lampOnlyActive = true;
      $lampIdCategory = 42;
      $id_lang = (int)$this->context->language->id;
      $lampIdCategories = CategoryCore::getChildren($lampIdCategory, $id_lang);
      Product::getProducts(
        $id_lang,
        $lampStart,
        $lampLimit,
        $lampOrderBy,
        $lampOrderWay,
        $lampIdCategory,
        $lampOnlyActive
      );
      $prod = $this->product;
      $this->context->smarty->assign(array(
        'HOOKCOMBINATIONDROPBOX' => Hook::exec('combinationdropbox')
      ));
    }
    parent::initContent();

    $this->setTemplate(_PS_THEME_DIR_.'product.tpl');
  }

}
