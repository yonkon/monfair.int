<?php

require(dirname(__FILE__) . '/config/config.inc.php');
require_once(_PS_MODULE_DIR_.'combinationdropbox'.DIRECTORY_SEPARATOR.'combinationdropbox.php');
$loop=true;
while($loop) {
  $processing = Configuration::get('CDBX_PROCESSING');

  $generationException = null;
  if (empty($processing)) {
    $processing = false;
  }
  $start_pid = empty($_REQUEST['CDBX_START_PID']) ? Configuration::get('CDBX_START_PID') : $_REQUEST['CDBX_START_PID'];
  if(isset($_REQUEST['CDBX_START_PID']) ) {
    unset($_REQUEST['CDBX_START_PID']);
  }
  $start_pid = empty($start_pid) ? 0 : (int)$start_pid;
  Configuration::updateValue('CDBX_START_PID', $start_pid);

  $length_pid = empty($_REQUEST['CDBX_PID_CHUNK_LENGTH']) ? Configuration::get('CDBX_PID_CHUNK_LENGTH') : $_REQUEST['CDBX_PID_CHUNK_LENGTH'];
  $length_pid = empty($length_pid) ? self::CDBX_LENGTH_PID : (int)$length_pid;
  Configuration::updateValue('CDBX_PID_CHUNK_LENGTH', $length_pid);

  $last_pid = Configuration::get('CDBX_LAST_PID');
  if (empty($_REQUEST['CDBX_MAX_PID']) || $_REQUEST['CDBX_MAX_PID'] == -1) {
    $max_pid = Configuration::get('CDBX_MAX_PID');
    if (empty($max_pid) || empty($_REQUEST['CDBX_MAX_PID']) || $_REQUEST['CDBX_MAX_PID'] == -1 ) {
      $max_pid = Db::getInstance()->executeS("SELECT MAX(id_product) as max_pid FROM ps_product");
      $max_pid = (int)$max_pid[0]['max_pid'];
    }
  } else {
    $max_pid = (int)$_REQUEST['CDBX_MAX_PID'];
  }
  Configuration::updateValue('CDBX_MAX_PID', $max_pid);

  if (!empty($_REQUEST['process']) && ($_REQUEST['process'] == 1)) {
    if (empty($processing) || $processing < 1) {
      Configuration::updateValue('CDBX_PROCESSING', 1);
      Configuration::updateValue('CDBX_ERROR', '');

      $last_pid = processGeneration($start_pid, $length_pid, $max_pid);
      if ($last_pid && empty($generationException)) {
        $loop = true;
        Configuration::updateValue('CDBX_START_PID', $last_pid + 1);
        Configuration::updateValue('CDBX_PROCESSING', 0);
        if ($last_pid == $max_pid) {
          Configuration::updateValue('CDBX_LAST_PID', 0);
          Configuration::updateValue('CDBX_START_PID', 0);
          Configuration::updateValue('CDBX_ERROR', 'Generation complete successful');
          pld('Generation complete successful');
        } else {
          Configuration::updateValue('CDBX_LAST_PID', $last_pid);
          if (!($processing != -1 && ConfigurationCore::get('CDBX_PROCESSING') != -1)) {
            $loop = false;
          }
        }
      } else {
        $loop = false;
        if (empty($generationException)) {
          Configuration::updateValue('CDBX_ERROR', 'Error on generation products combinations');
        } else {
          Configuration::updateValue('CDBX_ERROR', $generationException->getMessage() . '\n' . $generationException->getTraceAsString());
          Configuration::updateValue('CDBX_PROCESSING', 0);
          pld($generationException->getMessage() . '\n' . $generationException->getTraceAsString());
        }
      }
    } else {
      Configuration::updateValue('CDBX_ERROR', 'Already processing');
      pld('Already processing');
      $loop = false;
    }
  } else {
    if (!empty($_REQUEST['process']) && $_REQUEST['process'] == -1) {
      $processing = -1;
      Configuration::updateValue('CDBX_PROCESSING', $processing);
    }
    $loop = false;
  }
}

function processGeneration($start_pid, $cnt_pid, $max_pid)
{
  global $generationException;
  if (Shop::isFeatureActive())
    Shop::setContext(Shop::CONTEXT_ALL);

  try {
    $end_pid = false;

//    $productsAll = Product::getProducts(1, 0, 0, 'id_product', 'ASC');
    $productsAll = Db::getInstance()->executeS("SELECT id_product, price FROM ps_product WHERE id_product >= {$start_pid} ORDER BY id_product LIMIT {$cnt_pid}");
    $productChunk = array();
    $pcount = 0;
    foreach ($productsAll as $product) {
      if ($product['id_product'] < $start_pid) {
        continue;
      }
      if ($product['id_product'] > $max_pid || $pcount >= $cnt_pid) {
        break;
      }
      $end_pid = $product['id_product'];
      $pcount++;
      $productChunk[] = $product;
    }

    $module_combinations_res = Db::getInstance()->executeS("SELECT * FROM ps_combinationdropbox_combinations");
    $combinations = array();
    $combination_values = array();

    foreach($module_combinations_res as $row) {
      $combinations[] = array(
        $row['a1'],
        $row['a2'],
        $row['a3'],
        $row['a4'],
        $row['a5'],
        $row['a6'],
        $row['a7'],
        $row['a8'],
        $row['a9'],
      );
      $combination_values[] = $row['price'];
    }

    foreach ($productChunk as $product) {
      $values = array();
      foreach ($combinations as $c => $combination) {
        $combination_value = $combination_values[$c];
        $values[] = CombinationDropbox::addAttribute($product, $combination, $combination_value);
      }
      $productObj = new Product($product['id_product']);
      if (!empty($productObj->id)) {
        //Clearing product_attribute default combinations
        Db::getInstance()->execute("UPDATE `ps_product_attribute` SET `default_on` = NULL WHERE `id_product` = {$productObj->id}");
        $generationResult = $productObj->cdbxGenerateMultipleCombinations($values, $combinations);
        if ($generationResult) {
          $last_pid = $productObj->id;
          Configuration::updateValue('CDBX_LAST_PID', $last_pid);
        } else {
          Configuration::updateValue('CDBX_PROCESSING', 0);
          throw new Exception('Could not generate combinations for product #' . $productObj->id);
        }
      }
      if(Configuration::get('CDBX_PROCESSING') == -1) {
        Configuration::updateValue('CDBX_PROCESSING', 0);
        break;
      }
    }

    //Setting default combinations
    if (!empty($end_pid)) {
      $zeroCombs = Db::getInstance()->executeS("SELECT *
FROM ps_product_attribute
GROUP BY id_product
HAVING price >= 0
AND id_product>= {$start_pid}
AND id_product <= {$end_pid}
 ORDER BY price ASC");
//      $zeroCombs = array();
      foreach ($zeroCombs as $zeroComb) {
        Db::getInstance()->update('product_shop', array(
          'cache_default_attribute' => $zeroComb['id_product_attribute'],
        ), 'id_product = ' . (int)$zeroComb['id_product'] . ' ' . Shop::addSqlRestriction());

        Db::getInstance()->update('product', array(
          'cache_default_attribute' => $zeroComb['id_product_attribute'],
        ), 'id_product = ' . (int)$zeroComb['id_product']);

        Db::getInstance()->update('product_attribute', array(
          'default_on' => 0,
        ), 'default_on=1 AND id_product = ' . (int)$zeroComb['id_product']);

        Db::getInstance()->update('product_attribute', array(
          'default_on' => 1,
        ), 'id_product_attribute = ' . $zeroComb['id_product_attribute'] . ' AND id_product = ' . (int)$zeroComb['id_product']);

        Db::getInstance()->update('product_attribute_shop', array(
          'default_on' => 0,
        ), " id_product_attribute IN (SELECT id_product_attribute FROM ps_product_attribute WHERE default_on = 0 AND id_product = " .(int)$zeroComb['id_product']. ")"
        );

        Db::getInstance()->update('product_attribute_shop', array(
          'default_on' => 1,
        ), 'id_product_attribute = ' .$zeroComb['id_product_attribute'] );
      }
    }

  } catch (Exception $e) {
    $generationException = $e;
    return false;
  }
  return $end_pid;
}

/**
 * @param $msg object|string
 * print_r $msg and die
 */
function pd($msg) {
  print_r($msg);
  die;
}

/**
 * @param $msg object|string
 */
function pld($msg) {
  $str = print_r($msg, true);
  print($str);
  error_log($str);
  die();
}

die();
