<?php


/**
 * Created by PhpStorm.
 * User: X-iLeR
 * Date: 16.03.2016
 * Time: 5:39
 */
class Cart extends CartCore


{


  public static function cacheSomeAttributesLists($ipa_list, $id_lang)
  {

    if (!Combination::isFeatureActive())
      return;

    require_once(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'combinationdropbox' . DIRECTORY_SEPARATOR . 'combinationdropbox.php');


    $pa_implode = array();

    foreach ($ipa_list as $id_product_attribute)
      if ((int)$id_product_attribute && !array_key_exists($id_product_attribute . '-' . $id_lang, self::$_attributesLists)) {
        $pa_implode[] = (int)$id_product_attribute;
        self::$_attributesLists[(int)$id_product_attribute . '-' . $id_lang] = array('attributes' => '', 'attributes_small' => '');
      }

    if (!count($pa_implode)) {
      return;
    }


    $result = Db::getInstance()->executeS('
			SELECT pac.`id_product_attribute`, agl.`public_name` AS public_group_name, al.`name` AS attribute_name
			FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (
				a.`id_attribute` = al.`id_attribute`
				AND al.`id_lang` = ' . (int)$id_lang . '
			)
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (
				ag.`id_attribute_group` = agl.`id_attribute_group`
				AND agl.`id_lang` = ' . (int)$id_lang . '
			)
			WHERE pac.`id_product_attribute` IN (' . implode(',', $pa_implode) . ')
			ORDER BY ag.`position` ASC, a.`position` ASC'
    );


    foreach ($result as $row) {
      if (in_array($row['public_group_name'], array_values(CombinationDropbox::$productOptionsNames))) {
        if ($row['attribute_name'] != 'No') {
          $attr_impact = ': ' . CombinationDropbox::getProductAttributeImpact($row['id_product_attribute'], $row['public_group_name'], $row['attribute_name']) . '$';
          self::$_attributesLists[$row['id_product_attribute'] . '-' . $id_lang]['attributes'] .=
            $row['public_group_name'] . $attr_impact . ', ';
          self::$_attributesLists[$row['id_product_attribute'] . '-' . $id_lang]['attributes_small'] .= $row['public_group_name'] . ', ';
        }
      } else {
        self::$_attributesLists[$row['id_product_attribute'] . '-' . $id_lang]['attributes'] .=
          $row['public_group_name'] . ' : ' . $row['attribute_name'] . ', ';
        self::$_attributesLists[$row['id_product_attribute'] . '-' . $id_lang]['attributes_small'] .= $row['attribute_name'] . ', ';
      }
    }
    foreach ($pa_implode as $id_product_attribute) {
      self::$_attributesLists[$id_product_attribute . '-' . $id_lang]['attributes'] = rtrim(
        self::$_attributesLists[$id_product_attribute . '-' . $id_lang]['attributes'],
        ', '
      );
      self::$_attributesLists[$id_product_attribute . '-' . $id_lang]['attributes_small'] = rtrim(
        self::$_attributesLists[$id_product_attribute . '-' . $id_lang]['attributes_small'],
        ', '
      );

    }


  }


}



