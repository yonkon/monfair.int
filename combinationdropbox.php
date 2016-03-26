<?php
if(!defined('_PS_VERSION_') )
  exit;
/*
 * updated 11.03.2016
 */
ini_set('display_errors',1);
error_reporting(E_ALL|E_STRICT);
ini_set('max_execution_time', 360000);

class CombinationDropbox extends Module {

  public static $length_pid = 5;
  public static $productOptionsNames = array(
    'Front_Wheel_Fender'  => 'Front Wheel Fender',
    'Left_Side'           => 'Left Side',
    'Right_Side'          => 'Right Side',
    'Nose_Fairings'       => 'Nose Fairings',
    'Tail_Section'        => 'Tail Section',
    'Left_and_Right_Full' => 'Left and Right Full',
    'Painted'             => 'Painted',
    'Seat Cowl'           => 'Free Seat Cowl',
    'Bolt Kit'           =>   'Official Bolt Kit - $65',
  );
  public static $notDropbox = array('Seat Cowl', 'Bolt Kit' );
 /* public static $productOptionsImpacts = array(
    'Front_Wheel_Fender'    => 160*4/3,
    'Left_Side'             => 220*4/3,
    'Right_Side'            => 220*4/3,
    'Nose_Fairings'         => 205*4/3,
    'Tail_Section'          => 210*4/3,
    'Left_and_Right_Full'   => 405*4/3,
    'Painted'               => 45*4/3,
    'Seat Cowl'             => 0,
    'Bolt Kit'              => 65*4/3,
  );*/
  public static $productOptionsImpacts = array(
    'Front_Wheel_Fender'    => 160,
    'Left_Side'             => 220,
    'Right_Side'            => 220,
    'Nose_Fairings'         => 205,
    'Tail_Section'          => 210,
    'Left_and_Right_Full'   => 405,
    'Painted'               => 45,
    'Seat Cowl'             => 0,
    'Bolt Kit'              => 65,
  );

  public static $attributeImpacts = array();
  public static $wholesaleAttributes = array();
  public static $wholesaleAttributeNamesSql = " 'Front_Wheel_Fender', 'Left_Side' , 'Right_Side' , 'Nose_Fairings' , 'Tail_Section' , 'Left_and_Right_Full'";
  public static $wholesaleAttributeNames = array(
    'Front_Wheel_Fender', 'Left_Side' , 'Right_Side' , 'Nose_Fairings' , 'Tail_Section' , 'Left_and_Right_Full'
  );
  public static $backupTables = array(
    'attribute',
    'attribute_group',
    'attribute_group_lang',
    'attribute_group_shop',
    'attribute_impact',
    'attribute_lang',
    'attribute_shop',
    'product_attribute',
    'product_attribute_combination',
    'product_attribute_image',
    'product_attribute_shop',
);


  public function __construct()
  {
    $this->name = 'combinationdropbox';
    $this->tab = 'front_office_features';
    $this->version = '1.0';
    $this->author = 'Vladimir Sudarkov';
    $this->need_instance = 0;
    $this->ps_versions_compliancy = array('min' => '1.5', 'max' => '1.7');
    $this->dependencies = array();


    parent::__construct();

    $this->displayName = $this->l('Combination Dropbox');
    $this->description = $this->l('Original module for monsterfairings.com. Display individual parts as dropdown list on product page');

    $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

    if (!Configuration::get('COMBINATIONDROPBOX_NAME'))
      $this->warning = $this->l('No name provided');
  }

  public static function getAttributeImpact($attr)
  {
    if(isset(self::$attributeImpacts[$attr])) {
      return self::$attributeImpacts[$attr];
    }
    $attrNamesSql = array();
    foreach(self::$productOptionsNames as $name=>$pub) {
      $attrNamesSql[] = "'{$name}'";
    }
    $attrNamesSql = join(', ', $attrNamesSql);
    $sql = "SELECT i.id_attribute, i.price
FROM ps_attribute_impact i
JOIN ps_attribute_lang l
  ON l.id_lang=1
  AND l.id_attribute = i.id_attribute
  AND l.name IN ({$attrNamesSql})
  GROUP BY i.id_attribute";
    $ais = Db::getInstance()->executeS($sql);
    foreach($ais as $ai) {
      self::$attributeImpacts[$ai['id_attribute']] = $ai['price'];
    }
    if(!isset(self::$attributeImpacts[$attr])) {
      return 0;
    } else {
      return self::$attributeImpacts[$attr];
    }
  }


  public function install()
  {
    if (Shop::isFeatureActive())
      Shop::setContext(Shop::CONTEXT_ALL);

    try {
      Db::getInstance()->execute("DROP TABLE IF EXISTS `" . _DB_PREFIX_. "combinationdropbox_combinations`");
      Db::getInstance()->execute("DROP TABLE IF EXISTS `" . _DB_PREFIX_. "combinationdropbox_inserts`");
      //combinationdropbox_inserts
      $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "combinationdropbox_inserts` (
  `id_combinationdropbox_insert` int(11) NOT NULL AUTO_INCREMENT,
  `table` varchar(128) NOT NULL,
  `pk_name` varchar(128) NOT NULL,
  `pk_value` int(11) NOT NULL,
  PRIMARY KEY (`id_combinationdropbox_insert`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ";
      $sql2 = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_. "combinationdropbox_combinations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a1` int(11) NOT NULL,
  `a2` int(11) NOT NULL,
  `a3` int(11) NOT NULL,
  `a4` int(11) NOT NULL,
  `a5` int(11) NOT NULL,
  `a6` int(11) NOT NULL,
  `a7` int(11) NOT NULL,
  `a8` int(11) NOT NULL,
  `a9` int(11) NOT NULL,
  `price` decimal(20,6) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
      if (!(Db::getInstance()->execute($sql) && Db::getInstance()->execute($sql2)) ) {
        return false;
      }

      foreach(self::$backupTables as $tbl) {
        $sql1 = "DROP TABLE IF EXISTS " ._DB_PREFIX_. "combinationdropbox_{$tbl}";
        $sql2 = "CREATE TABLE " ._DB_PREFIX_. "combinationdropbox_{$tbl} SELECT * FROM " ._DB_PREFIX_. "{$tbl}";
        if (!(Db::getInstance()->execute($sql1) &&
          Db::getInstance()->execute($sql2))) {
          return false;
        } else {
          Db::getInstance()->execute("TRUNCATE " ._DB_PREFIX_. "{$tbl}");
        }
      }

      $res = parent::install() &&
        $this->registerHook('COMBINATIONDROPBOX') &&
        $this->registerHook('header') &&
        $this->registerHook('actionProductAdd') &&
        $this->registerHook('actionProductUpdateAttributeImpacts') &&
        Configuration::updateValue('COMBINATIONDROPBOX_NAME', 'Combination Dropbox');

      $tab = new Tab('Combinationdropbox');
      $tab->active = 1;
      $tab->name = array();
      $tab->class_name = 'AdminCombinationdropbox';

      foreach (Language::getLanguages(true) as $lang) {
        $tab->name[$lang['id_lang']] = 'combinationdropboxInstall';
      }
      $tab->id_parent = -1;
      $tab->module = 'combinationdropbox';
      $res = $res && $tab->add();

      $comb_inserted = array();
      $attr_gr_content = array();
      $sqlOptionNames = array();

      $address = $this->context->shop->getAddress();
      $tax_manager = TaxManagerFactory::getManager($address, 1);
      $product_tax_calculator = $tax_manager->getTaxCalculator();

      foreach (self::$productOptionsNames as $name => $pubname) {
        $sqlOptionNames[] = "'{$name}'";
      }

      $productsAll = Product::getProducts(1, 0, 0, 'id_product', 'ASC');

      foreach (self::$productOptionsNames as $name => $pubname) {
        $attr_gr_id = Db::getInstance()->insert('attribute_group', array(
          'id_attribute_group' => null,
          'is_color_group' => 0,
          'group_type' => in_array($name, self::$notDropbox ) ? 'select' : 'radio',
          'position' => 0
        ));
        if ($attr_gr_id) {
          $attr_gr_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_group'][] = array(
            't' => 'attribute_group',
            'n' => 'id_attribute_group',
            'v' => $attr_gr_id
          );
        } else {
          $res = false;
        }

        $attr_gr_lang_id = Db::getInstance()->insert('attribute_group_lang', array(
          'id_attribute_group' => $attr_gr_id,
          'id_lang' => 1,
          'name' => $name,
          'public_name' => $pubname
        ));
        if ($attr_gr_lang_id) {
          $attr_gr_lang_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_group_lang'][] = array(
            't' => 'attribute_group_lang',
            'n' => 'id_attribute_group',
            'v' => $attr_gr_id
          );
        } else {
          $res = false;
        }

        $attr_gr_shop_id = Db::getInstance()->insert('attribute_group_shop', array(
          'id_attribute_group' => $attr_gr_id,
          'id_shop' => 1
        ));
        if ($attr_gr_shop_id) {
          $attr_gr_shop_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_group_shop'][] = array(
            't' => 'attribute_group_shop',
            'n' => 'id_attribute_group',
            'v' => $attr_gr_id
          );
        } else {
          $res = false;
        }

        $attr_gr_content_item = array();
        $attr_y_id = Db::getInstance()->insert('attribute', array(
          'id_attribute' => null,
          'id_attribute_group' => $attr_gr_id,
          'color' => null,
          'position' => 0
        ));
        if ($attr_y_id) {
          $attr_y_id = Db::getInstance()->Insert_ID();
          $attr_gr_content_item[$attr_y_id] = $attr_y_id;
          $comb_inserted['attribute'][] = array(
            't' => 'attribute',
            'n' => 'id_attribute',
            'v' => $attr_y_id
          );
          if($name != 'Painted') {
            self::$wholesaleAttributes[] = $attr_y_id;
          }
        } else {
          $res = false;
        }

        $attr_n_id = Db::getInstance()->insert('attribute', array(
          'id_attribute' => null,
          'id_attribute_group' => $attr_gr_id,
          'color' => null,
          'position' => 0
        ));
        if ($attr_n_id) {
          $attr_n_id = Db::getInstance()->Insert_ID();
          $attr_gr_content_item[$attr_n_id] = $attr_n_id;
          $comb_inserted['attribute'][] = array(
            't' => 'attribute',
            'n' => 'id_attribute',
            'v' => $attr_n_id
          );
        } else {
          $res = false;
        }

        $attr_gr_content[] = $attr_gr_content_item;

        $attr_shop_y_id = Db::getInstance()->insert('attribute_shop', array(
          'id_attribute' => $attr_y_id,
          'id_shop' => 1
        ));
        if ($attr_shop_y_id) {
          $attr_shop_y_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_shop'][] = array(
            't' => 'attribute_shop',
            'n' => 'id_attribute',
            'v' => $attr_y_id
          );
        } else {
          $res = false;
        }

        $attr_shop_n_id = Db::getInstance()->insert('attribute_shop', array(
          'id_attribute' => $attr_n_id,
          'id_shop' => 1
        ));
        if ($attr_shop_n_id) {
          $attr_shop_n_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_shop'][] = array(
            't' => 'attribute_shop',
            'n' => 'id_attribute',
            'v' => $attr_n_id
          );
        } else {
          $res = false;
        }

        $attr_lang_y_id = Db::getInstance()->insert('attribute_lang', array(
          'id_attribute' => $attr_y_id,
          'id_lang' => 1,
          'name' => $name
        ));
        if ($attr_lang_y_id) {
          $attr_lang_y_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_lang'][] = array(
            't' => 'attribute_lang',
            'n' => 'id_attribute',
            'v' => $attr_y_id
          );
        } else {
          $res = false;
        }

        $attr_lang_n_id = Db::getInstance()->insert('attribute_lang', array(
          'id_attribute' => $attr_n_id,
          'id_lang' => 1,
          'name' => 'No'
        ));
        if ($attr_lang_n_id) {
          $attr_lang_n_id = Db::getInstance()->Insert_ID();
          $comb_inserted['attribute_lang'][] = array(
            't' => 'attribute_lang',
            'n' => 'id_attribute',
            'v' => $attr_n_id
          );
        } else {
          $res = false;
        }

        foreach ($productsAll as $product) {
          $tax_excl = round($product_tax_calculator->removeTaxes(self::$productOptionsImpacts[$name]), 5);
          $attr_impact_y_id = Db::getInstance()->insert('attribute_impact', array(
            'id_attribute_impact' => null,
            'id_product' => $product['id_product'],
            'id_attribute' => $attr_y_id,
            'weight' => 0,
            'price' => $tax_excl
          ));
          if ($attr_impact_y_id) {
            $attr_impact_y_id = Db::getInstance()->Insert_ID();
            $comb_inserted['attribute_impact'][] = array(
              't' => 'attribute_impact',
              'n' => 'id_attribute_impact',
              'v' => $attr_impact_y_id
            );
            self::$attributeImpacts[$attr_y_id] = $tax_excl;
          }

          $attr_impact_n_id = Db::getInstance()->insert('attribute_impact', array(
            'id_attribute_impact' => null,
            'id_product' => $product['id_product'],
            'id_attribute' => $attr_n_id,
            'weight' => 0,
            'price' => 0
          ));
          if ($attr_impact_n_id) {
            $attr_impact_n_id = Db::getInstance()->Insert_ID();
            $comb_inserted['attribute_impact'][] = array(
              't' => 'attribute_impact',
              'n' => 'id_attribute_impact',
              'v' => $attr_impact_n_id
            );
            self::$attributeImpacts[$attr_n_id] = 0;
          }
        }
      }

      foreach ($comb_inserted as $table => $rows) {
        $vals = array();
        foreach ($rows as $row) {
          $vals[] = "(NULL, '{$table}' , '{$row['n']}', {$row['v']})";
        }
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "combinationdropbox_inserts`(`id_combinationdropbox_insert`, `table`, `pk_name`, `pk_value`) VALUES " . join(', ', $vals);
        Db::getInstance()->execute($sql);
      }

      $combination_values = array();
      $combinations = array_values(self::createCombinations($attr_gr_content));
      $combinations = array_reverse($combinations);
      $comb_combs = array();
      foreach ($combinations as $i => $attrs) {
        $combination_values[$i] = 0;
        foreach ($attrs as $attr) {
          $combination_values[$i] += self::$attributeImpacts[$attr];
          $combination_values[$i] = number_format($combination_values[$i], 4, '.', '');
        }
        $comb_combs[] = "( NULL , {$attrs[0]}, {$attrs[1]}, {$attrs[2]}, {$attrs[3]}, {$attrs[4]}, {$attrs[5]}, {$attrs[6]}, {$attrs[7]}, {$attrs[8]},{$combination_values[$i]} ) ";
      }
      $comb_comb_sql = "INSERT INTO `" . _DB_PREFIX_ . "combinationdropbox_combinations`(`id`, `a1`, `a2`, `a3`, `a4`, `a5`, `a6`, `a7`, `a8`, `a9`, `price`) VALUES " . join(', ', $comb_combs);
      Db::getInstance()->execute($comb_comb_sql);
      if (!$res) {
        $this->uninstall();
        return false;
      }
    } catch (Exception $e) {
      error_log( $e->getMessage() .$e->getTraceAsString() );
      if(empty($_REQUEST['debug '])) {
        echo $e->getMessage();
      }
      $this->uninstall();
      return false;
    }

//    combinationdropboxRemoveConfigs();
    return true;
  }

  public function uninstall()
  {
    if (!parent::uninstall() ||
      !Configuration::deleteByName('COMBINATIONDROPBOX_NAME')) {
      return false;     }
    $sql = "SELECT * FROM ". _DB_PREFIX_ ."combinationdropbox_inserts";
    $ins = Db::getInstance()->executeS($sql);
    $inserted_attr_ids = array();
    foreach($ins as $row) {
      $sql = "DELETE FROM " . _DB_PREFIX_ . $row['table'] . " WHERE `" . $row['pk_name'] ."` = " .$row['pk_value'];
      Db::getInstance()->execute($sql);
      if($row['table'] == 'attribute') {
        $inserted_attr_ids[] = $row['pk_value'];
      }
    }

    if(empty($_REQUEST['no_backup'])) {
      foreach(self::$backupTables as $tbl) {
        $bck = Db::getInstance()->executeS("SELECT * FROM "._DB_PREFIX_."combinationdropbox_{$tbl} LIMIT 1");
        if(!empty($bck)) {
          Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."{$tbl}");
          Db::getInstance()->execute("INSERT INTO "._DB_PREFIX_."{$tbl} SELECT * FROM "._DB_PREFIX_."combinationdropbox_{$tbl}");
        }
      }
    }


    Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."combinationdropbox_inserts");
    Db::getInstance()->execute("TRUNCATE "._DB_PREFIX_."combinationdropbox_combinations");

    combinationdropboxRemoveConfigs();

    return true;
  }

  public function hookActionProductAdd(&$params){
    /**
     * @var $product ProductCore
     * @var $id_product integer
     */
    $context = Context::getContext();
    $product = $params['product'];
    $id_product = $params['id_product'];
    $comb_inserted = array();
    try {
      $combinations = Db::getInstance()->executeS("SELECT * FROM " . _DB_PREFIX_ . "combinationdropbox_combinations");
      $values = array();
      $combinations_clear = array();
      foreach ($combinations as $c => $cur_comb) {
        $combination_value = $cur_comb['price'];
        $combination = array();
        for ($i = 1; $i < 10; $i++) {
          $combination[] = $cur_comb['a' . $i];
        }
        $combinations_clear[] = $combination;
        $values[] = self::addAttribute(
          array('id_product' => $product->id, 'price' => $product->price),
          $combination,
          $combination_value
        );
      }

      $attrs = Db::getInstance()->executeS(
        "SELECT al.id_attribute, al.name AS value,agl.name AS name
FROM ps_attribute_lang al
JOIN ps_combinationdropbox_inserts cdi
  ON cdi.table='attribute_lang'
  AND cdi.pk_value = al.id_attribute
JOIN ps_attribute a
  ON a.id_attribute = al.id_attribute
JOIN ps_attribute_group_lang agl
  ON agl.id_lang = 1
  AND agl.id_attribute_group = a.id_attribute_group
");
      $address = $context->shop->getAddress();
      $tax_manager = TaxManagerFactory::getManager($address, 1);
      $product_tax_calculator = $tax_manager->getTaxCalculator();

      foreach($attrs as $attr) {
        if ($attr['value'] != 'No') {
          $tax_excl = round($product_tax_calculator->removeTaxes(self::$productOptionsImpacts[$attr['name']]), 5);
          $attr_impact_y_id = Db::getInstance()->insert('attribute_impact', array(
            'id_attribute_impact' => null,
            'id_product' => $id_product,
            'id_attribute' => $attr['id_attribute'],
            'weight' => 0,
            'price' => $tax_excl
          ));
          if ($attr_impact_y_id) {
            $attr_impact_y_id = Db::getInstance()->Insert_ID();
            $comb_inserted['attribute_impact'][] = array(
              't' => 'attribute_impact',
              'n' => 'id_attribute_impact',
              'v' => $attr_impact_y_id
            );
            self::$attributeImpacts[$attr['id_attribute']] = $tax_excl;
          }
        }
        if($attr['value'] == 'No') {
          $attr_impact_n_id = Db::getInstance()->insert('attribute_impact', array(
            'id_attribute_impact' => null,
            'id_product' => $id_product,
            'id_attribute' => $attr['id_attribute'],
            'weight' => 0,
            'price' => 0
          ));
          if ($attr_impact_n_id) {
            $attr_impact_n_id = Db::getInstance()->Insert_ID();
            $comb_inserted['attribute_impact'][] = array(
              't' => 'attribute_impact',
              'n' => 'id_attribute_impact',
              'v' => $attr_impact_n_id
            );
            self::$attributeImpacts[$attr['id_attribute']] = 0;
          }
        }
      }

      /**
       * @var $product Product
       */
      $product->cdbxGenerateMultipleCombinations($values, $combinations_clear);

      //Setting default combinations
      $zeroCombs = Db::getInstance()->executeS("SELECT * FROM ps_product_attribute GROUP BY id_product HAVING id_product={$id_product} AND price >= 0 ORDER BY price ASC");

      foreach($zeroCombs as $zeroComb) {
        Db::getInstance()->update('product_shop', array(
          'cache_default_attribute' => $zeroComb['id_product_attribute'],
        ), 'id_product = '.(int)$zeroComb['id_product']);

        Db::getInstance()->update('product', array(
          'cache_default_attribute' => $zeroComb['id_product_attribute'],
        ), 'id_product = '.(int)$zeroComb['id_product']);

        Db::getInstance()->update('product_attribute', array(
          'default_on' => 0,
        ), 'default_on=1 AND id_product = '.(int)$zeroComb['id_product']);

        Db::getInstance()->update('product_attribute', array(
          'default_on' => 1,
        ), 'id_product_attribute = ' .$zeroComb['id_product_attribute']. ' AND id_product = '.(int)$zeroComb['id_product']);


        Db::getInstance()->update('product_attribute_shop', array(
          'default_on' => 0,
          ), " id_product_attribute IN (SELECT id_product_attribute FROM ps_product_attribute WHERE default_on = 0 AND id_product = " .(int)$zeroComb['id_product']. ")"
        );

        Db::getInstance()->update('product_attribute_shop', array(
          'default_on' => 1,
        ), 'id_product_attribute = ' .$zeroComb['id_product_attribute']. ' AND id_product = '.(int)$zeroComb['id_product']);

      }

      foreach ($comb_inserted as $table => $rows) {
        $vals = array();
        foreach ($rows as $row) {
          $vals[] = "(NULL, '{$table}' , '{$row['n']}', {$row['v']})";
        }
        $sql = "INSERT INTO `" . _DB_PREFIX_ . "combinationdropbox_inserts`(`id_combinationdropbox_insert`, `table`, `pk_name`, `pk_value`) VALUES " . join(', ', $vals);
        Db::getInstance()->execute($sql);
      }
    } catch(Exception $e) {
      if(!empty($_REQUEST['debug '])) {
        echo $e->getMessage();
      }
    }
  }

  public function hookActionProductUpdateAttributeImpacts(&$params)
  {
    /**
     * @var $product Product
     * @var $id_product integer
     * @var $old_price float
     */
    $context = Context::getContext();
    $product = $params['product'];
    $id_product = $params['id_product'];
    $old_price = $params['old_price'];
    if ($old_price == $product->price) {
      return;
    }
    $comb_inserted = array();
    $wholesaleAttributes = self::getWholesaleAttributes();
    $product_attributes_grouped = array();
    $product_attributes = Db::getInstance()->executeS(
      "SELECT * FROM ps_product_attribute ppa
JOIN ps_product_attribute_combination ppac
 ON ppac.id_product_attribute = ppa.id_product_attribute
 AND ppa.id_product = {$id_product}
"
    );
    foreach($product_attributes as $pa ) {
      $product_attributes_grouped[$pa['id_product_attribute']]['values'] = $pa;
      $product_attributes_grouped[$pa['id_product_attribute']]['attributes'][] = $pa['id_attribute'];
    }
    foreach($product_attributes_grouped as $id_pa => $pa) {
      foreach($pa['attributes'] as $attr_id) {
        if(in_array($attr_id, $wholesaleAttributes) ) {
          $new_impact = $pa['values']['price'] + $old_price - $product->price;
          Db::getInstance()->update('product_attribute', array('price' => $new_impact), " id_product_attribute = {$id_pa}");
          Db::getInstance()->update('product_attribute_shop', array('price' => $new_impact), " id_product_attribute = {$id_pa}");
          break;
        }
      }
    }
  }

  public function getContent()
  {
    Tools::redirectAdmin(Context::getContext()->link->getAdminLink('AdminCombinationdropbox'));
  }

  public function displayForm()
  {
    // Get default Language
    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

    // Init Fields form array
    $fields_form[0]['form'] = array(
      'legend' => array(
        'title' => $this->l('Settings'),
      ),
      'input' => array(
        array(
          'type' => 'text',
          'label' => $this->l('Максимальное количествово вкладок категорий лампочек'),
          'name' => 'COMBINATIONDROPBOX_TABCOUNT',
          'size' => 20,
          'required' => true,
          'value' => Configuration::get('COMBINATIONDROPBOX_TABCOUNT')
        ),
        array(
          'type' => 'text',
          'label' => $this->l('Максимальное количествово лампочек на вкладку'),
          'name' => 'COMBINATIONDROPBOX_ITEMCOUNT',
          'size' => 20,
          'required' => true,
          'value' => Configuration::get('COMBINATIONDROPBOX_ITEMCOUNT')
        )
      ),
      'submit' => array(
        'title' => $this->l('Save'),
        'class' => 'button'
      )
    );

    $helper = new HelperForm();

    // Module, token and currentIndex
    $helper->module = $this;
    $helper->name_controller = $this->name;
    $helper->token = Tools::getAdminTokenLite('AdminModules');
    $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

    // Language
    $helper->default_form_language = $default_lang;
    $helper->allow_employee_form_lang = $default_lang;

    // Title and toolbar
    $helper->title = $this->displayName;
    $helper->show_toolbar = true;        // false -> remove toolbar
    $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
    $helper->submit_action = 'submit'.$this->name;
    $helper->toolbar_btn = array(
      'save' =>
        array(
          'desc' => $this->l('Save'),
          'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
            '&token='.Tools::getAdminTokenLite('AdminModules'),
        ),
      'back' => array(
        'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
        'desc' => $this->l('Back to list')
      )
    );

    // Load current value
    $helper->fields_value['COMBINATIONDROPBOX_TABCOUNT'] = Configuration::get('COMBINATIONDROPBOX_TABCOUNT');
    $helper->fields_value['COMBINATIONDROPBOX_ITEMCOUNT'] = Configuration::get('COMBINATIONDROPBOX_ITEMCOUNT');

    return $helper->generateForm($fields_form);
  }


  public function hookCombinationDropbox(&$params) {

    $context = Context::getContext();
    $controller = $context->controller;
    $id_lang = (int)$context->language->id;
    /**
     * @var $prod ProductCore
     */
    $prod = $controller->getProduct();
    if(empty($prod)) {
      return '';
    }

    $combinationdropboxAll = Db::getInstance()->executeS(
      "SELECT ag.`id_attribute_group`,
	ag.public_name as group_lang,
        ag.name as group_name,
        a.id_attribute,
        al.name as value,
        ai.price as price
FROM `ps_attribute_group_lang` ag
JOIN ps_combinationdropbox_inserts cbi
	ON cbi.table='attribute_group'
        AND cbi.pk_value = ag.`id_attribute_group`
join ps_attribute a
	ON a.id_attribute_group = ag.id_attribute_group
join ps_attribute_lang al
	ON al.id_lang=1
        AND al.id_attribute = a.id_attribute
JOIN ps_attribute_impact ai
	ON ai.id_attribute=a.id_attribute
        AND ai.id_product={$prod->id}"
    );
    $combinationdropbox = array(
      'individual' => array(
        'section_name' => 'Individual parts',
        'groups' => array()
      ),
      'painted' => array(
        'section_name' => 'Parts Paint',
        'groups' => array()
      )
    );
    $address = $context->shop->getAddress();
    $tax_manager = TaxManagerFactory::getManager($address, 1);
    $product_tax_calculator = $tax_manager->getTaxCalculator();
    /**
     * @var $product_tax_calculator TaxCalculatorCore
     */
    foreach ($combinationdropboxAll as $c ) {
      if(in_array($c['group_name'], self::$notDropbox)) {
        continue;
      }
      if($c['group_name'] == 'Painted') {
        $combinationdropbox['painted'][$c['value']] = array(
          'attr' => $c['id_attribute']
        );
        if($c['price'] > 0) {
          $tax_incl = round($product_tax_calculator->addTaxes($c['price']) ,0);
          $combinationdropbox['painted']['price'] = $tax_incl;
        }
        $combinationdropbox['painted']['gr'] = $c['id_attribute_group'];
      } else {
        if($c['value'] == 'No') {
          $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['No'] = $c['id_attribute'];
        } else {
          $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['Yes'] = $c['id_attribute'];
        }
        $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['gr'] = $c['id_attribute_group'];
        $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['name'] = $c['group_name'];
        $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['lang'] = $c['group_lang'];
        if($c['price'] > 0) {
          $tax_incl = round($product_tax_calculator->addTaxes($c['price']) ,0);
          $combinationdropbox['individual']['groups'][$c['id_attribute_group']]['price'] = $tax_incl;
        }
      }
    }


    $this->context->smarty->assign(
      array(
        'combinationdropbox' => $combinationdropbox,
      )
    );

    if(!empty($_REQUEST['debug'])) {
      return "<pre>" . print_r($combinationdropbox, true) . "</pre>";
    }
    return $this->display(__FILE__, 'combinationdropbox.tpl');
  }

  public function hookDisplayHeader()
  {
    $this->context->controller->addCSS($this->_path.'css/combinationdropbox.css', 'all');
  }


  public static function createCombinations($list)
  {
    if (count($list) <= 1)
      return count($list) ? array_map('enarray', $list[0]) : $list;
    $res = array();
    $first = array_pop($list);
    foreach ($first as $attribute)
    {
      $tab = self::createCombinations($list);
      foreach ($tab as $to_add)
        $res[] = is_array($to_add) ? array_merge($to_add, array($attribute)) : array($to_add, $attribute);
    }
    return $res;
  }

  /**
   * @param $product array Must contain id_product AND price
   * @param $attributes array id_attributes, that combination contain
   * @param int $price float Combination price impact
   * @param int $weight float Combination weight impact
   * @return array Values for generateMultipleCombinations | cdbxGenerateMultipleCombinations
   */
  public static function addAttribute($product, $attributes, $price = 0, $weight = 0)
  {
    $wholesale_ids = self::getWholesaleAttributes();
    $is_wholesale = false;
    foreach($attributes as $pa_id) {
      if(in_array($pa_id, $wholesale_ids)) {
        $is_wholesale = true;
        break;
      }
    }
    if ($product['id_product'])
    {
      //TODO check if need to add taxes for product price
      $price = $is_wholesale ? (float)$price - (float)$product['price'] : (float)$price ;
      $price = round($price, 5);
      $result =  array(
        'id_product' => (int)$product['id_product'],
        'price' => $price,
        'weight' => (float)$weight,
        'ecotax' => 0,
        'quantity' => (int)Tools::getValue('quantity'),
        'reference' => 0, //pSQL($_POST['reference']),
        'default_on' => 0,
        'available_date' => '0000-00-00'
      );

      return $result;
    }
    return array();
  }

  public static function getProductAttributeImpact($id_product_attribute, $attribute_pub_name, $attr_value,  $add_tax = true)
  {
    $name = pSql($attribute_pub_name);
    $value = pSql($attr_value);
    $impact = Db::getInstance()->executeS(
      "SELECT ai.price
FROM ps_attribute_impact ai
JOIN ps_product_attribute pa
  ON pa.id_product_attribute = {$id_product_attribute}
  AND pa.id_product=ai.id_product
JOIN ps_attribute a
  ON a.id_attribute = ai.id_attribute
JOIN ps_attribute_lang l
  ON l.id_lang=1
  AND l.name = '{$value}'
  AND l.id_attribute = a.id_attribute
JOIN ps_attribute_group_lang al
  ON al.id_attribute_group = a.id_attribute_group
  AND al.id_lang = 1
  AND al.public_name = '{$name}'
");
    if(!empty($impact)){
      $impact = $impact[0]['price'];
    } else {
      return 0;
    }
    if($add_tax) {
      $address = Context::getContext()->shop->getAddress();
      $tax_manager = TaxManagerFactory::getManager($address, 1);
      $product_tax_calculator = $tax_manager->getTaxCalculator();
      $impact = round($product_tax_calculator->addTaxes($impact), 0);
    }
    return $impact;
  }

  public static function getWholesaleAttributes()
  {
    if(!empty(self::$wholesaleAttributes)) {
      return self::$wholesaleAttributes;
    }
    $wsaSqlNames = self::$wholesaleAttributeNamesSql;
    $wsa = Db::getInstance()->executeS(
      "SELECT a.id_attribute, g.id_attribute_group, gl.name AS `group`, gl.public_name AS `name`, al.name as `value`
 FROM ps_attribute a
JOIN ps_attribute_lang al
  ON al.id_attribute = a.id_attribute
  AND al.id_lang = 1
  AND al.name != 'No'
JOIN ps_attribute_group g
  ON g.id_attribute_group = a.id_attribute_group
JOIN ps_attribute_group_lang gl
  ON g.id_attribute_group = gl.id_attribute_group
  AND gl.id_lang = 1
WHERE gl.name IN ({$wsaSqlNames })
"
    );
    $result = array();
    foreach($wsa as $row) {
      $result[] = $row['id_attribute'];
    }
    self::$wholesaleAttributes = $result;
    return $result;
  }

}

function enarray($v) {
  return (array($v));
}

function combinationdropboxRemoveConfigs() {
  Configuration::updateValue('CDBX_START_PID', null);
  Configuration::updateValue('CDBX_MAX_PID', null);
  Configuration::updateValue('CDBX_LAST_PID', null);
  Configuration::updateValue('CDBX_PROCESSING', null);
  Configuration::updateValue('CDBX_ERROR', null);
  Configuration::updateValue('CDBX_PID_CHUNK_LENGTH', null);
}
