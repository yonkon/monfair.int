<?php

class AdminCombinationdropboxController extends ModuleAdminControllerCore
{
  const CDBX_LENGTH_PID = 25;

  /**
   * @var $generationException null|Exception
   */
  public $generationException = null;

  public function getTemplatePath() {
    return _PS_MODULE_DIR_.$this->module->name.'/views/templates/admin/';
  }

  public function setTemplate($template)
  {
    if (Tools::file_exists_cache(_PS_THEME_DIR_.'modules/'.$this->module->name.'/'.$template))
      $this->template = _PS_THEME_DIR_.'modules/'.$this->module->name.'/'.$template; //if the file with the template is located in the root directory
    elseif (Tools::file_exists_cache($this->getTemplatePath().$template))
      $this->template = $this->getTemplatePath().$template; //otherwise file in  module_dir/views/templates/front/ should be used
    else
      throw new PrestaShopException("Template '$template'' not found"); //if the file is not found, the exception will be thrown
  }

  public function createTemplate($tpl_name)
  {
    //$this->override_folder = Tools::toUnderscoreCase(substr($this->controller_name, 5)).'/';
    if (file_exists($this->getTemplatePath().$this->override_folder.$tpl_name) && $this->viewAccess()){
      return $this->context->smarty->createTemplate($this->getTemplatePath().$this->override_folder.$tpl_name, $this->context->smarty);
    }

    if (file_exists($this->getTemplatePath().$tpl_name) && $this->viewAccess()) {
      return $this->context->smarty->createTemplate($this->getTemplatePath().$tpl_name, $this->context->smarty);
    }

    if (file_exists($tpl_name) && $this->viewAccess()) {
      return $this->context->smarty->createTemplate($tpl_name, $this->context->smarty);
    }

    return parent::createTemplate($tpl_name);
  }

  public function initContent()
  {
    ini_set('display_errors', 1);
    parent::initContent();
    $this->setTemplate('install.tpl');
  }


  public function postProcess() {
    $processing = Configuration::get('CDBX_PROCESSING');
    if(empty($processing)) {
      $processing = false;
    }
    $start_pid = empty($_REQUEST['CDBX_START_PID']) ? Configuration::get('CDBX_START_PID') : $_REQUEST['CDBX_START_PID'];
    $start_pid = empty($start_pid)? 0 : (int) $start_pid;
    Configuration::updateValue('CDBX_START_PID', $start_pid);

    $length_pid = empty($_REQUEST['CDBX_PID_CHUNK_LENGTH']) ? Configuration::get('CDBX_PID_CHUNK_LENGTH') : $_REQUEST['CDBX_PID_CHUNK_LENGTH'];
    $length_pid = empty($length_pid)? self::CDBX_LENGTH_PID : (int)$length_pid;
    Configuration::updateValue('CDBX_PID_CHUNK_LENGTH', $length_pid);

    $last_pid = Configuration::get('CDBX_LAST_PID');
    if (empty($_REQUEST['CDBX_MAX_PID']) ) {
      $max_pid = Configuration::get('CDBX_MAX_PID');
      if(empty($max_pid)) {
        $max_pid = Db::getInstance()->executeS("SELECT MAX(id_product) as max_pid FROM ps_product");
        $max_pid= (int)$max_pid[0]['max_pid'];
      }
    } else {
      $max_pid = (int)$_REQUEST['CDBX_MAX_PID'];
    }
    Configuration::updateValue('CDBX_MAX_PID', $max_pid);

    if(!empty($_REQUEST['process']) && ($_REQUEST['process'] == 1) ) {
        if (empty($processing) || $processing<1) {
          Configuration::updateValue('CDBX_PROCESSING', 1);
          Configuration::updateValue('CDBX_ERROR','');

          $last_pid = $this->processGeneration($start_pid, $length_pid, $max_pid);
          if ($last_pid && empty($this->generationException)) {
            Configuration::updateValue('CDBX_START_PID', $last_pid+1);
            Configuration::updateValue('CDBX_PROCESSING', 0);
            if($last_pid == $max_pid) {
              Configuration::updateValue('CDBX_LAST_PID', 0);
              Configuration::updateValue('CDBX_START_PID', 0);
              Configuration::updateValue('CDBX_ERROR','Generation complete successful');
              Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCombinationdropbox'));
            } else  {
              Configuration::updateValue('CDBX_LAST_PID', $last_pid);
              if ($processing != -1 && ConfigurationCore::get('CDBX_PROCESSING') != -1) {
                Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCombinationdropbox').'&process=1');
/*
                if ($curl = curl_init()) {
                  $url = $this->context->link->getAdminLink('AdminCombinationdropboxController'). http_build_query(array(
                    'CDBX_START_PID' => $last_pid + 1,
                    'CDBX_MAX_PID' => $max_pid,
                    'CDBX_PID_CHUNK_LENGTH' => $length_pid,
                    'process' => 1
                  ));
                  curl_setopt($curl, CURLOPT_URL, $url);
                  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                  curl_setopt($curl, CURLOPT_POST, true);

                  $out = curl_exec($curl);
                  if (Configuration::get('CDBX_DEBUG')) {
                    echo $out;
                  }
                  curl_close($curl);
                } else {
                  throw new Exception('Unable init cUrl');
                }*/
              }
            }
          } else {
            if (empty($this->generationException)) {
              Configuration::updateValue('CDBX_ERROR', 'Error on generation products combinations');
            } else {
              Configuration::updateValue('CDBX_ERROR', $this->generationException->getMessage() . '\n' . $this->generationException->getTraceAsString());
            }
          }
        } else {
          Configuration::updateValue('CDBX_ERROR', 'Already processing');
        }
      Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCombinationdropbox'));
    } elseif(!empty($_REQUEST['process']) && $_REQUEST['process'] == -1) {
      $processing = -1;
      Configuration::updateValue('CDBX_PROCESSING', $processing);
      Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCombinationdropbox'));
    }

    $error = Configuration::get('CDBX_ERROR');
      $this->context->smarty->assign(array(
          'cdbx' => array(
            'start_pid' => $start_pid,
            'max_pid' => $max_pid,
            'length_pid' => $length_pid,
            'last_pid' => $last_pid,
            'processing' => $processing,
            'CDBX_LENGTH_PID' => self::CDBX_LENGTH_PID,
            'progress' => empty($last_pid) ? 0 : round(100*$last_pid/$max_pid, 1),
            'error' => $error
          )
        )
      );


  }

  public function processGeneration($start_pid, $cnt_pid, $max_pid)
  {
    if (Shop::isFeatureActive())
      Shop::setContext(Shop::CONTEXT_ALL);

    try {
      $last_pid = false;
      $end_pid = false;
      $attr_gr_content = array();
      $sqlOptionNames = array();
      $existed_attrs = array();
      $existed_vals = array();
//      $address = $this->context->shop->getAddress();
//      $tax_manager = TaxManagerFactory::getManager($address, 1);
//      $product_tax_calculator = $tax_manager->getTaxCalculator();

      foreach (CombinationDropbox::$productOptionsNames as $name => $pubname) {
        $sqlOptionNames[] = "'{$name}'";
      }
      $sql = "SELECT a.id_attribute, a.id_attribute_group, gl.name, gl.public_name
FROM ps_attribute a
JOIN ps_attribute_group_lang gl
	ON gl.id_lang=1
    AND gl.id_attribute_group = a.id_attribute_group
    AND name IN (" . join(', ', $sqlOptionNames) . ")";
      $combinationdropboxAll = Db::getInstance()->executeS(
        $sql
      );
      foreach($combinationdropboxAll as $row) {
        $attr_gr_content[$row['id_attribute_group']][$row['id_attribute']] = $row['id_attribute'];
      }

      $productsAll = Product::getProducts(1, 0, 0, 'id_product', 'ASC');
      $prodImpacts = array();
      $productChunk = array();
      $pcount = 0;
      foreach($productsAll as $product) {
        if($product['id_product']<$start_pid) {
          continue;
        }
        if($product['id_product'] > $max_pid || $pcount>$cnt_pid ) {
          break;
        }
        $end_pid = $product['id_product'];
        $pcount++;
        $productChunk[] = $product;

        $prodImpacts = Db::getInstance()->executeS(
          "SELECT ai.id_attribute_impact,
        ai.price,
        a.id_attribute,
        a.id_attribute_group,
        gl.name AS `name`,
        gl.public_name AS lang,
        al.name AS `value`
 FROM ps_attribute_impact ai
JOIN ps_attribute a
  ON a.id_attribute = ai.id_attribute
  AND ai.id_product = {$product['id_product']}
JOIN ps_attribute_lang al
  ON al.id_attribute=a.id_attribute
  AND al.id_lang=1
JOIN ps_attribute_group_lang gl
  ON gl.id_attribute_group=a.id_attribute_group
  AND gl.id_lang=1
WHERE 1
"
        );

        foreach($prodImpacts as $prodImpact) {
          $existed_attrs[$product['id_product']][$prodImpact['id_attribute_group']][$prodImpact['id_attribute']] = $prodImpact['id_attribute'];
          $existed_vals[$product['id_product']][$prodImpact['id_attribute_group']][$prodImpact['id_attribute']] = $prodImpact['price'];
          CombinationDropbox::$attributeImpacts[$prodImpact['id_attribute']] = round($prodImpact['price'], 5);
        }
      }

      foreach ($productChunk as $product) {

        $combination_values = array();
        $attr_gr_content_w_existed = empty($existed_attrs[$product['id_product']]) ?
          $attr_gr_content :
          array_replace($attr_gr_content, $existed_attrs[$product['id_product']] );
        $combinations = array_values(CombinationDropbox::createCombinations(array_values($attr_gr_content_w_existed) ) );
        $combinations = array_reverse($combinations);
        foreach ($combinations as $i => $attrs) {
          $combination_values[$i] = 0;
          foreach ($attrs as $attr) {
            $combination_values[$i] += CombinationDropbox::getAttributeImpact($attr);
            $combination_values[$i] = round($combination_values[$i], 3);
          }
        }

        $values = array();
        foreach ($combinations as $c => $combination) {
          $combination_value = $combination_values[$c];
          $values[] = CombinationDropbox::addAttribute($product, $combination, $combination_value);
        }
        $productObj = new Product($product['id_product']);
        if(!empty($productObj->id) ) {
          $generationResult = $productObj->generateMultipleCombinations($values, $combinations);
//          $generationResult = true;
          if ($generationResult) {
            $last_pid = $productObj->id;
            Configuration::updateValue('CDBX_LAST_PID', $last_pid );
          } else {
            Configuration::updateValue('CDBX_PROCESSING', 0);
            throw new Exception('Could not generate combinations for product #'.$productObj->id);
          }
        }
      }

      //Setting default combinations
      if(!empty($end_pid)) {
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
          ), 'id_product = ' . (int)$zeroComb['id_product'] . ' '.Shop::addSqlRestriction());

          Db::getInstance()->update('product', array(
            'cache_default_attribute' => $zeroComb['id_product_attribute'],
          ), 'id_product = ' . (int)$zeroComb['id_product']);

          Db::getInstance()->update('product_attribute', array(
            'default_on' => 0,
          ), 'default_on=1 AND id_product = ' . (int)$zeroComb['id_product']);

          Db::getInstance()->update('product_attribute', array(
            'default_on' => 1,
          ), 'id_product_attribute = ' . $zeroComb['id_product_attribute'] . ' AND id_product = ' . (int)$zeroComb['id_product']);

//          Db::getInstance()->update('product_attribute_shop', array(
//            'default_on' => 0,
//          ), 'default_on=1 AND id_product_attribute = ' . (int)$zeroComb['id_product_attribute']);

          Db::getInstance()->update('product_attribute_shop', array(
            'default_on' => 1,
          ), 'id_product_attribute = ' . $zeroComb['id_product_attribute']);
        }
      }

    } catch (Exception $e) {
      $this->generationException = $e;
      return false;
    }
    return $end_pid;
  }

}
