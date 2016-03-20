<?php





class CartController extends CartControllerCore



{



  protected function processChangeProductInCart() {



    require_once(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'combinationdropbox'.DIRECTORY_SEPARATOR.'combinationdropbox.php');



    parent::processChangeProductInCart();



  }



}



