<?php







abstract class Db extends DbCore {



  /**

   * Execute an INSERT query. Fixed null interpretation as empty string

   *

   * @param string $table Table name without prefix

   * @param array $data Data to insert as associative array. If $data is a list of arrays, multiple insert will be done

   * @param bool $null_values If we want to use NULL values instead of empty quotes

   * @param bool $use_cache

   * @param int $type Must be Db::INSERT or Db::INSERT_IGNORE or Db::REPLACE

   * @param bool $add_prefix Add or not _DB_PREFIX_ before table name

   * @return bool

   */



  public function insert($table, $data, $null_values = false, $use_cache = true, $type = Db::INSERT, $add_prefix = true)



  {



    if (!$data && !$null_values)



      return true;







    if ($add_prefix)



      $table = _DB_PREFIX_.$table;







    if ($type == Db::INSERT)



      $insert_keyword = 'INSERT';



    elseif ($type == Db::INSERT_IGNORE)



      $insert_keyword = 'INSERT IGNORE';



    elseif ($type == Db::REPLACE)



      $insert_keyword = 'REPLACE';



    else



      throw new PrestaShopDatabaseException('Bad keyword, must be Db::INSERT or Db::INSERT_IGNORE or Db::REPLACE');







    // Check if $data is a list of row



    $current = current($data);



    if (!is_array($current) || isset($current['type']))



      $data = array($data);







    $keys = array();



    $values_stringified = array();



    foreach ($data as $row_data)



    {



      $values = array();



      foreach ($row_data as $key => $value)



      {



        if (isset($keys_stringified))



        {



          // Check if row array mapping are the same



          if (!in_array("`$key`", $keys))



            throw new PrestaShopDatabaseException('Keys form $data subarray don\'t match');



        }



        else



          $keys[] = '`'.bqSQL($key).'`';







        if (!is_array($value))



          $value = array('type' => 'text', 'value' => $value);



        if ($value['type'] == 'sql')



          $values[] = $value['value'];



        else



          $values[] = ($null_values && ($value['value'] === '') || is_null($value['value'])) ? 'NULL' : "'{$value['value']}'";



      }



      $keys_stringified = implode(', ', $keys);



      $values_stringified[] = '('.implode(', ', $values).')';



    }







    $sql = $insert_keyword.' INTO `'.$table.'` ('.$keys_stringified.') VALUES '.implode(', ', $values_stringified);



    return (bool)$this->q($sql, $use_cache);



  }







}



