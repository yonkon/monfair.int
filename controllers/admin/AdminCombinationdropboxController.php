<?php

class AdminCombinationdropboxController extends ModuleAdminControllerCore
{
  public function initContent()
  {
    ini_set('display_errors', 1);
    parent::initContent();
    $this->setTemplate('../install.tpl');
  }
}
