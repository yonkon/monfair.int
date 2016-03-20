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

      $this->context->smarty->assign(array(

        'HOOKCOMBINATIONDROPBOX' => Hook::exec('combinationdropbox')

      ));

    }

    parent::initContent();



    $this->setTemplate(_PS_THEME_DIR_.'product.tpl');

  }



}

