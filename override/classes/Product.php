<?php





class Product extends ProductCore

{



  public function update($null_values = false, $old_price = false)

  {

    $return = parent::update($null_values);

    if($return) {

      Hook::exec('actionProductUpdateAttributeImpacts', array('id_product' => (int)$this->id, 'product' => $this, 'old_price' => $old_price));

    }

    return $return;

  }



}

