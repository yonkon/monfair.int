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

  public function cdbxGenerateMultipleCombinations($combinations, $attributes)
  {
    set_time_limit(180000);
    $res = true;
    $default_on = 1;
    $time = $_SERVER['REQUEST_TIME'];
    print("<pre>
Product ID: {$this->id}
");

    foreach ($combinations as $key => $combination)
    {
      if( (empty($key) && $key != 0) || empty($attributes[$key]) ) {
        print("<pre>bad key=");
        print_r($key);
        print_r($attributes);
        print('</pre>');
        die();
      }
      if(!($key%10)){
        print("<p>KEY = {$key}</p>");
        flush();
      }
      $id_combination = $this->productAttributeExists($attributes[$key], false, null, true, true);
      if(!empty($id_combination)) {
        $obj = new CombinationCore($id_combination);
      } else {
        $obj = new CombinationCore();
      }

      if(!is_object($obj)) {
        throw new Exception('Could not instantiate Combination object');
      }


      if ($id_combination)
      {
        $obj->minimal_quantity = 1;
        $obj->available_date = '0000-00-00';
      }

      foreach ($combination as $field => $value) {
        $obj->$field = $value;
      }

      $obj->default_on = $default_on;
      $default_on = 0;
      $this->setAvailableDate();
      /** @var $obj CombinationCore */
      $obj->save();

      if(empty($obj->id)) {
        throw new Exception('Cannot add Combination');
      }

      $now = time();
      $past = $now - $time;
      print($past.'s'.PHP_EOL);
      flush();
      if (!$id_combination)
      {
        $attribute_list = array();

        $curatr = $attributes[$key];
        foreach ($curatr as $id_attribute) {
          $al = array(
            'id_product_attribute' => (int)$obj->id,
            'id_attribute' => (int)$id_attribute
          );
          $attribute_list[] = $al;
        }

        $res &= Db::getInstance()->insert('product_attribute_combination', $attribute_list);
//        print("DB Query executed, result {$res}".PHP_EOL);
//        die();

        if(!$res){
          print("DB Query executed, result false".PHP_EOL);
          print_r($attribute_list);
          return false;
        }
      }
    }

    return $res;
  }



}

