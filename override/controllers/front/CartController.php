<?php



/**

 * Created by PhpStorm.

 * User: X-iLeR

 * Date: 16.03.2016

 * Time: 5:42

 */

class CartController extends CartControllerCore

{

  protected function processChangeProductInCart() {

    require_once(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'combinationdropbox'.DIRECTORY_SEPARATOR.'combinationdropbox.php');

    parent::processChangeProductInCart();

  }

}

