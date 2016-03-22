<?php



class AdminAttributeGeneratorController extends  AdminAttributeGeneratorControllerCore
{

  protected function addAttribute($attributes, $price = 0, $weight = 0)
  {
    require_once(_PS_ROOT_DIR_.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'combinationdropbox'.DIRECTORY_SEPARATOR.'combinationdropbox.php');
    foreach ($attributes as $attribute)
    {
      $price += (float)preg_replace('/[^0-9.-]/', '', str_replace(',', '.', Tools::getValue('price_impact_'.(int)$attribute)));
      $weight += (float)preg_replace('/[^0-9.]/', '', str_replace(',', '.', Tools::getValue('weight_impact_'.(int)$attribute)));
    }
    $wholesale_ids = CombinationDropbox::getWholesaleAttributes();
    $is_wholesale = false;

    foreach($attributes as $pa_id) {
      if(in_array($pa_id, $wholesale_ids)) {
        $is_wholesale = true;
        break;
      }
    }

    if ($this->product->id)
    {
      //TODO check if need to add taxes for product price
      $price = $is_wholesale ? (float)$price - (float)$this->product->price : (float)$price ;
      $result =  array(
        'id_product' => (int)$this->product->id,
        'price' => $price,
        'weight' => (float)$weight,
        'ecotax' => 0,
        'quantity' => (int)Tools::getValue('quantity'),
        'reference' => pSQL($_POST['reference']),
        'default_on' => 0,
        'available_date' => '0000-00-00'
      );

      return $result;
    }
    return array();
  }

}