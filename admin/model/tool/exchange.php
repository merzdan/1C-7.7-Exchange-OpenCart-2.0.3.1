<?php
class ModelToolExchange extends Model {
    //приоритет в названиях за 1с
    private $priority;
	private $exchange_auto;
	private $exchange_auto_count;
    
    function __construct($registry) {          
        parent::__construct($registry);
         $this->registry->get('load')->model('setting/setting');
         $this->priority = $this->registry->get('config')->get('exchange_priority');
		 $this->exchange_auto = $this->registry->get('config')->get('exchange_auto');
		 $this->exchange_auto_count = $this->registry->get('config')->get('exchange_auto_count');
    }
	
	
	
    //--------------------------------------------Категории--------------------------------------------------------------------------------------------  
    public function addCategory($action, $kod_1c, $name_1c, $ostatok_1c, $cost_1c, $is_group_1c, $kod_1c_rod, $lang = '0') {

        $data = array();
        $data['name'] = htmlentities($name_1c, ENT_QUOTES, 'UTF-8');
        $data['keyword'] = htmlentities($this->mb_transliterate($name_1c), ENT_QUOTES, 'UTF-8');

        $tmp = $this->isOcIdCat($kod_1c);
        $category_id = $tmp['oc_cat_id'];

        if (!empty($tmp['1c_kod_group'])) {
            //ранее выгружался
            $this->db->query("UPDATE  " . DB_PREFIX . "1c_cat SET 1c_name='$name_1c', 1c_kod_group_rod='$kod_1c_rod' WHERE 1c_kod_group='$kod_1c'");
        } else {
            //добавим
            $this->db->query("INSERT INTO " . DB_PREFIX . "1c_cat (1c_kod_group, 1c_name, 1c_kod_group_rod) VALUES ('$kod_1c','$name_1c','$kod_1c_rod')");
        }

        $this->load->model('catalog/category');

        if (!empty($category_id) && $this->model_catalog_category->getCategory($category_id)) {
            //обновим, если приоритет за 1с
            if ($this->priority){
            $catdata = $this->prepareCat("", $lang, $data);
            $this->model_catalog_category->editCategory($category_id, $catdata);
            }
            
        } else {
            //добавим
            $catdata = $this->prepareCat("", $lang, $data);
            // $this->model_catalog_category->addCategory($catdata);
            $category_id = $this->addCategoryToOC($catdata);
            //добавим категорию в 1с таблицы
            $this->db->query("UPDATE  " . DB_PREFIX . "1c_cat SET oc_cat_id='$category_id' WHERE 1c_kod_group='$kod_1c'");
        }

        $this->updateParentCat();
    } 

    function updateParentCat() {

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_cat");
        //выберем все
        foreach ($query->rows as $row) {

            if (!empty($row['1c_kod_group_rod'])) {
                //выберем родителя
                $query2 = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_cat WHERE 1c_kod_group='" . $row['1c_kod_group_rod'] . "'");
                if (!empty($query2->row['oc_cat_id'])) {
                    //обновим категорию с этим id родителем

                    $category_id = (int) $row['oc_cat_id'];
                    $data = array();
                    (int) $data['parent_id'] = (int) $query2->row['oc_cat_id'];

                    $this->db->query("UPDATE " . DB_PREFIX . "category SET parent_id = '" . (int) $data['parent_id'] . "' WHERE category_id = '" . $category_id . "'");

                    // MySQL Hierarchical Data Closure Table Pattern
                    $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE path_id = '" . (int) $category_id . "' ORDER BY level ASC");

                    if ($query->rows) {
                        foreach ($query->rows as $category_path) {
                            // Delete the path below the current one
                            $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $category_path['category_id'] . "' AND level < '" . (int) $category_path['level'] . "'");

                            $path = array();

                            // Get the nodes new parents
                            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $data['parent_id'] . "' ORDER BY level ASC");

                            foreach ($query->rows as $result) {
                                $path[] = $result['path_id'];
                            }

                            // Get whats left of the nodes current path
                            $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $category_path['category_id'] . "' ORDER BY level ASC");

                            foreach ($query->rows as $result) {
                                $path[] = $result['path_id'];
                            }

                            // Combine the paths with a new level
                            $level = 0;

                            foreach ($path as $path_id) {
                                $this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int) $category_path['category_id'] . "', `path_id` = '" . (int) $path_id . "', level = '" . (int) $level . "'");

                                $level++;
                            }
                        }
                    } else {
                        // Delete the path below the current one
                        $this->db->query("DELETE FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $category_id . "'");

                        // Fix for records with no paths
                        $level = 0;

                        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $data['parent_id'] . "' ORDER BY level ASC");

                        foreach ($query->rows as $result) {
                            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int) $category_id . "', `path_id` = '" . (int) $result['path_id'] . "', level = '" . (int) $level . "'");

                            $level++;
                        }

                        $this->db->query("REPLACE INTO `" . DB_PREFIX . "category_path` SET category_id = '" . (int) $category_id . "', `path_id` = '" . (int) $category_id . "', level = '" . (int) $level . "'");
                    }
                }
            }
        }
    }

	
    function prepareCat($parent, $language_id, $data = array()) {

        $result = array(
            'status' => isset($data['status']) ? $data['status'] : 1
            , 'top' => isset($data['top']) ? $data['top'] : 1
            , 'parent_id' => $parent
            , 'category_store' => isset($data['category_store']) ? $data['category_store'] : array(0)
            , 'keyword' => isset($data['keyword']) ? $data['keyword'] : ''  // сюда добавляем SEO URL
            , 'image' => ''
            , 'sort_order' => 0
            , 'column' => 1
        );

        $result['category_description'] = array(
            $language_id => array(
                'name' => (string) $data['name']
                , 'meta_keyword' => (isset($data['category_description'][$language_id]['meta_keyword'])) ? $data['category_description'][$language_id]['meta_keyword'] : ''
                , 'meta_description' => (isset($data['category_description'][$language_id]['meta_description'])) ? $data['category_description'][$language_id]['meta_description'] : ''
                , 'description' => ''
            ),
        );

        return $result;
    }

    function addCategoryToOC($data) {

        $this->db->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int) $data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int) $data['top'] : 0) . "', `column` = '" . (int) $data['column'] . "', sort_order = '" . (int) $data['sort_order'] . "', status = '" . (int) $data['status'] . "', date_modified = NOW(), date_added = NOW()");

        $category_id = $this->db->getLastId();

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE category_id = '" . (int) $category_id . "'");
        }

        foreach ($data['category_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int) $category_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', description = '" . $this->db->escape($value['description']) . "'");
        }

        // MySQL Hierarchical Data Closure Table Pattern
        $level = 0;

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int) $data['parent_id'] . "' ORDER BY `level` ASC");

        foreach ($query->rows as $result) {
            $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int) $category_id . "', `path_id` = '" . (int) $result['path_id'] . "', `level` = '" . (int) $level . "'");

            $level++;
        }

        $this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int) $category_id . "', `path_id` = '" . (int) $category_id . "', `level` = '" . (int) $level . "'");

        if (isset($data['category_filter'])) {
            foreach ($data['category_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int) $category_id . "', filter_id = '" . (int) $filter_id . "'");
            }
        }

        if (isset($data['category_store'])) {
            foreach ($data['category_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int) $category_id . "', store_id = '" . (int) $store_id . "'");
            }
        }

        // Set which layout to use with this category
        if (isset($data['category_layout'])) {
            foreach ($data['category_layout'] as $store_id => $layout) {
                if ($layout['layout_id']) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int) $category_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout['layout_id'] . "'");
                }
            }
        }

        if ($data['keyword']) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int) $category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
        }

        $this->cache->delete('category');

        return $category_id;
    }

    //--------------------------------------------Продукт----------------------------------------------------------------------------------------------       
    public function addProduct($action, $kod_1c, $article_1c, $name_1c, $ostatok_1c, $edizm_1c, $cost_1c, $is_group_1c, $kod_1c_rod, $lang = '0') {

        $data = array();
        $product = array();
        $product['cost'] = $cost_1c;      
        $product['name'] = htmlentities($name_1c, ENT_QUOTES, 'UTF-8'); 
        $product['model'] = !empty($article_1c) ? $article_1c :'-';
        $product['quantity'] = $ostatok_1c;
        $product['keyword'] = htmlentities($this->mb_transliterate($name_1c), ENT_QUOTES, 'UTF-8');
        $product['option'] = $edizm_1c;

        $option_id = $this->checkOptions($lang);
        
        $product['option_id'] = $option_id;
        
        $tmp = $this->isOcIdProd($kod_1c);
        $product_id = $tmp['oc_prod_id'];

        if (!empty($tmp['1c_kod_prod'])) {
            //ранее выгружался

            $this->db->query("UPDATE  " . DB_PREFIX . "1c_product SET 1c_ostatok='" . $ostatok_1c . "', 1c_cena='" . $cost_1c . "', 1c_name='$name_1c', 1c_kod_prod_rod='$kod_1c_rod'  WHERE 1c_kod_prod='$kod_1c'");
            $product_cat = $this->isOcIdCat($kod_1c_rod);
            $product['product_category'] = (int) $product_cat['oc_cat_id'];
        } else {
            //добавим
            $this->db->query("INSERT INTO " . DB_PREFIX . "1c_product (1c_kod_prod, 1c_name, 1c_kod_prod_rod, 1c_ostatok, 1c_cena  ) VALUES ('$kod_1c','$name_1c','$kod_1c_rod','$ostatok_1c','$cost_1c')");
            $product_cat = $this->isOcIdCat($kod_1c_rod);
            $product['product_category'] = (int) $product_cat['oc_cat_id'];
        }

        $this->load->model('catalog/product');

        if (!empty($product_id) && $this->model_catalog_product->getProduct((int)$product_id)) {
            //обновим
            
              if (!$this->priority){
                  
                  //получим старые keyword и имя
                  
                  $product['name'] = $this->getOldName($product_id);     
                  $product['keyword'] = $this->getOldKeyword($product_id);
             }
                      
            $proddata = $this->prepareProduct($product, $lang);           
            $this->model_catalog_product->editProduct($product_id, $proddata);      
                      
            
        } else {
            //добавим
            $proddata = $this->prepareProduct($product, $lang);
            // $this->model_catalog_category->addCategory($catdata);
            $product_id = $this->addProductToOC($proddata);
            //добавим категорию в 1с таблицы
            $this->db->query("UPDATE  " . DB_PREFIX . "1c_product SET oc_prod_id='$product_id' WHERE 1c_kod_prod='$kod_1c'");
        }
    }
    
    private function getOldName($product_id){
        $query = $this->db->query("SELECT name FROM ".DB_PREFIX."product_description WHERE product_id='".(int)$product_id."'");
        return $query->row['name'];
        
    }
     private function getOldKeyword($product_id){
         $sql = "SELECT keyword FROM ".DB_PREFIX."url_alias WHERE query='product_id=".(int)$product_id."'";
         $query = $this->db->query($sql);
        return $query->row['keyword'];
    }

    private function prepareProduct($product, $language_id) {

        $result = array(
            'model' => isset($product['model']) ? $product['model'] : ''
            , 'sku' => ''
            , 'upc' => ''
            , 'ean' => ''
            , 'jan' => ''
            , 'isbn' => ''
            , 'mpn' => ''
            , 'points' => 0
            , 'location' => ''
            , 'product_store' => array(0)
            , 'keyword' => isset($product['keyword']) ? $product['keyword'] : ''  // сюда добавляем SEO URL
            , 'image' => ''
			, 'video' => ''
            , 'product_image' => array()
            , 'preview' => ''
            , 'manufacturer_id' => 0
            , 'shipping' => 1
            , 'date_available' => date('Y-m-d', time() - 86400)
            , 'quantity' => isset($product['quantity']) ? $product['quantity'] : 0 ///---------
            , 'minimum' => 1
            , 'subtract' => 1
            , 'sort_order' => 1
            , 'stock_status_id' => $this->config->get('config_stock_status_id')
            , 'price' => isset($product['cost']) ? $product['cost'] : 0
            , 'cost' => isset($product['cost']) ? $product['cost'] : 0 //-----------
            , 'status' => 1
            , 'tax_class_id' => 0
            , 'weight' => 0
            , 'weight_class_id' => 1
            , 'length' => ''
            , 'width' => ''
            , 'height' => ''
            , 'length_class_id' => 1
            , 'product_discount' => array()
            , 'product_special' => array()
            , 'product_download' => array()
            , 'product_related' => array()
            , 'product_attribute' => array()
        );

        if (VERSION == '1.5.3.1') {
            $result['product_tag'] = array();
        }

        $result['product_description'] = array(
            $language_id => array(
                'name' => isset($product['name']) ? $product['name'] : 'Имя не задано'  //-----------
                , 'meta_keyword' => isset($product['meta_keyword']) ? trim($product['meta_keyword']) : ''
                , 'meta_description' => isset($product['meta_description']) ? trim($product['meta_description']) : ''
                , 'description' => isset($product['description']) ? nl2br($product['description']) : ''
                , 'seo_title' => isset($product['seo_title']) ? $product['seo_title'] : ''
                , 'seo_h1' => isset($product['seo_h1']) ? $product['seo_h1'] : ''
                , 'tag' => isset($product['tag']) ? $product['tag'] : ''
            ),
        );

        $result['product_option'] = array('0'=>array(
            'type'=>'text',
            'option_value'=>$product['option'],
            'option_id'=>$product['option_id'],
            'required'=>'1'
                )         
        );

        if (!empty($product['product_category'])) {

            $result['product_category'] = isset($product['product_category']) ? array((int) $product['product_category']) : array(0);
            $result['main_category_id'] = isset($product['main_category_id']) ? (int) $product['main_category_id'] : 0;
        }

        return $result;
    }

    public function addProductToOc($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int) $data['quantity'] . "', minimum = '" . (int) $data['minimum'] . "', subtract = '" . (int) $data['subtract'] . "', stock_status_id = '" . (int) $data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int) $data['manufacturer_id'] . "', shipping = '" . (int) $data['shipping'] . "', price = '" . (float) $data['price'] . "', points = '" . (int) $data['points'] . "', weight = '" . (float) $data['weight'] . "', weight_class_id = '" . (int) $data['weight_class_id'] . "', length = '" . (float) $data['length'] . "', width = '" . (float) $data['width'] . "', height = '" . (float) $data['height'] . "', length_class_id = '" . (int) $data['length_class_id'] . "', status = '" . (int) $data['status'] . "', tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', sort_order = '" . (int) $data['sort_order'] . "', date_added = NOW()");

        $product_id = $this->db->getLastId();
				
         if($this->exchange_auto){
			 $count = $this->exchange_auto_count;
		     $product_count = $product_id;
			 
			$category_new = 115;   
			$res = $this->db->query("SELECT product_id FROM " . DB_PREFIX . "product_to_category WHERE category_id = '"  .(int)$category_new."' ORDER BY product_id DESC LIMIT ".(int)$count."");
			 
			$i = $res->num_rows;
						 
			 if($i < $count){
				 $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) ($product_id) . "', category_id = '".(int)$category_new."'");
			 } else {
				   $row = $res->rows;
				   //$this->log->write($row[9]['product_id']);
				   $this->db->query("DELETE FROM ".DB_PREFIX."product_to_category WHERE product_id='".(int)$row[9]['product_id']."'");
				   $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) ($product_count) . "', category_id = ".(int)$category_new."");
			  
			  }
				 
		}
		

        if (isset($data['image'])) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE product_id = '" . (int) $product_id . "'");
        }
		
        foreach ($data['product_description'] as $language_id => $value) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int) $product_id . "', language_id = '" . (int) $language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "'");
        }

        if (isset($data['product_store'])) {
            foreach ($data['product_store'] as $store_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "'");
            }
        }

        if (isset($data['product_attribute'])) {
            foreach ($data['product_attribute'] as $product_attribute) {
                if ($product_attribute['attribute_id']) {
                    $this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "' AND attribute_id = '" . (int) $product_attribute['attribute_id'] . "'");

                    foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
                        $this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int) $product_id . "', attribute_id = '" . (int) $product_attribute['attribute_id'] . "', language_id = '" . (int) $language_id . "', text = '" . $this->db->escape($product_attribute_description['text']) . "'");
                    }
                }
            }
        }

        if (isset($data['product_option'])) {
            foreach ($data['product_option'] as $product_option) {
                if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', required = '" . (int) $product_option['required'] . "'");

                    $product_option_id = $this->db->getLastId();

                    if (isset($product_option['product_option_value']) && count($product_option['product_option_value']) > 0) {
                        foreach ($product_option['product_option_value'] as $product_option_value) {
                            $this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int) $product_option_id . "', product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', option_value_id = '" . (int) $product_option_value['option_value_id'] . "', quantity = '" . (int) $product_option_value['quantity'] . "', subtract = '" . (int) $product_option_value['subtract'] . "', price = '" . (float) $product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int) $product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float) $product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
                        }
                    } else {
                        $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_option_id = '" . $product_option_id . "'");
                    }
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int) $product_id . "', option_id = '" . (int) $product_option['option_id'] . "', value = '" . $this->db->escape($product_option['option_value']) . "', required = '" . (int) $product_option['required'] . "'");
                }
            }
        }

        if (isset($data['product_discount'])) {
            foreach ($data['product_discount'] as $product_discount) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_discount['customer_group_id'] . "', quantity = '" . (int) $product_discount['quantity'] . "', priority = '" . (int) $product_discount['priority'] . "', price = '" . (float) $product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
            }
        }

        if (isset($data['product_special'])) {
            foreach ($data['product_special'] as $product_special) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $product_special['customer_group_id'] . "', priority = '" . (int) $product_special['priority'] . "', price = '" . (float) $product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
            }
        }

        if (isset($data['product_image'])) {
            foreach ($data['product_image'] as $product_image) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int) $product_id . "', image = '" . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int) $product_image['sort_order'] . "'");
            }
        }
		
		if (isset($data['video'])) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product_video SET product_id = '" . (int) $product_id . "', video = '" . $this->db->escape($data['video']) . "'");
        }

        if (isset($data['product_download'])) {
            foreach ($data['product_download'] as $download_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int) $product_id . "', download_id = '" . (int) $download_id . "'");
            }
        }

        if (isset($data['product_category'])) {
            foreach ($data['product_category'] as $category_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int) $product_id . "', category_id = '" . (int) $category_id . "'");
            }
        }

        if (isset($data['product_filter'])) {
            foreach ($data['product_filter'] as $filter_id) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int) $product_id . "', filter_id = '" . (int) $filter_id . "'");
            }
        }

        if (isset($data['product_related'])) {
            foreach ($data['product_related'] as $related_id) {
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "' AND related_id = '" . (int) $related_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $product_id . "', related_id = '" . (int) $related_id . "'");
                $this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $related_id . "' AND related_id = '" . (int) $product_id . "'");
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int) $related_id . "', related_id = '" . (int) $product_id . "'");
            }
        }

        if (isset($data['product_reward'])) {
            foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int) $product_id . "', customer_group_id = '" . (int) $customer_group_id . "', points = '" . (int) $product_reward['points'] . "'");
            }
        }

        if (isset($data['product_layout'])) {
            foreach ($data['product_layout'] as $store_id => $layout) {
                if ($layout['layout_id']) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int) $product_id . "', store_id = '" . (int) $store_id . "', layout_id = '" . (int) $layout['layout_id'] . "'");
                }
            }
        }

        if ($data['keyword']) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int) $product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
        }

        $this->cache->delete('product');
        return $product_id;
    }

    //-------------------------------------------------Транслит-----------------------------------------------------------------------------------------
    function mb_transliterate($string) {
        $table = array(
            'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D',
            'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I',
            'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N',
            'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T',
            'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH',
            'Ш' => 'SH', 'Щ' => 'CSH', 'Ь' => '', 'Ы' => 'Y', 'Ъ' => '',
            'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA',
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
            'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
            'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
            'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
            'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
            'ш' => 'sh', 'щ' => 'csh', 'ь' => '', 'ы' => 'y', 'ъ' => '',
            'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        );

        $output = str_replace(
                array_keys($table), array_values($table), $string
        );

        // таеже те символы что неизвестны
        $output = preg_replace('/[^-a-z0-9._\[\]\'"]/i', ' ', $output);
        $output = preg_replace('/ +/', '-', $output);
        $output = str_replace(array("'", "\"", "."), "", $output);
        
        return strtolower($output);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------
    function isOcIdCat($kod_1c) {
        //выгружалась ли ранее такая группа?

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_cat WHERE 1c_kod_group='" . $kod_1c . "'");
        if (!empty($query->row)) {

            return $query->row;
        }
        return false;
    }

    function isOcIdProd($kod_1c) {
        //выгружался ли ранее такой товар?

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_product WHERE 1c_kod_prod='" . $kod_1c . "'");
        if (!empty($query->row)) {

            return $query->row;
        }
        return false;
    }

    function addOcIdCat($kod_1c, $kod_oc) {
        // Добавим код группы из 1с в таблицу ос


        $this->db->query("INSERT INTO " . DB_PREFIX . "1c_cat SET 1c_kod_group='" . $kod_1c . "' , oc_cat_id='" . (int) $kod_oc . "'");
    }

    function addOcIdProd($kod_1c, $kod_oc) {
        // Добавим код товара из 1с в таблицу ос

        $this->db->query("INSERT INTO " . DB_PREFIX . "1c_product SET 1c_kod_prod='" . $kod_1c . "' , oc_prod_id='" . (int) $kod_oc . "'");
    }

    function deleteAll() {

        $this->deleteProd();
        $this->load->model('catalog/category');

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_cat");

        if (!empty($query->row)) {
            set_time_limit(0);
            foreach ($query->rows as $row) {
                if (!empty($row['oc_cat_id'])) {

                    $this->model_catalog_category->deleteCategory((int) $row['oc_cat_id']);
                    $this->db->query("DELETE FROM " . DB_PREFIX . "1c_cat WHERE oc_cat_id='" . $row['oc_cat_id'] . "'");
                }
            }
        }
    }

    function deleteProd() {

        $this->load->model('catalog/product');

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "1c_product");

        if (!empty($query->row)) {
            set_time_limit(0);
            foreach ($query->rows as $row) {
                if (!empty($row['oc_prod_id'])) {

                    $this->model_catalog_product->deleteProduct((int) $row['oc_prod_id']);
                    $this->db->query("DELETE FROM " . DB_PREFIX . "1c_product WHERE oc_prod_id='" . $row['oc_prod_id'] . "'");
                }
            }
        }
    }

    function clearProduct() {

        $this->load->model('catalog/product');

        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "product");

        if (!empty($query->row)) {
            set_time_limit(0);
            foreach ($query->rows as $row) {
                if (!empty($row['product_id'])) {

                    $this->model_catalog_product->deleteProduct((int) $row['product_id']);
                    //$this->db->query("DELETE FROM " . DB_PREFIX . "1c_product WHERE oc_prod_id='" . $row['oc_prod_id'] . "'");
                }
            }
        }
    }

    public function getProduct() {


        $query2 = $this->db->query("SELECT p.product_id, p.model, d.name, p.quantity, p.price, c.1c_kod_prod AS kod
                            FROM " . DB_PREFIX . "product p
                            Join " . DB_PREFIX . "product_description d ON (d.product_id=p.product_id) 
                            Join " . DB_PREFIX . "1c_product c ON (c.oc_prod_id=p.product_id)
                            WHERE p.product_id IN (SELECT oc_prod_id FROM " . DB_PREFIX . "1c_product)");


        if (!empty($query2->row)) {

            $ret = array();
            $i = 0;
            foreach ($query2->rows as $row) {
                foreach ($row as $key => $value) {
                    $ret[$i][$key] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                }
                $i++;
            }

            return $ret;
        }

        return false;
    }

	
    public function refreshProduct($kod_1c, $name_1c, $ostatok_1c, $cost_1c) {
		
		if(empty($ostatok_1c)){
			
			   $ostatok_1c = 0;
			   if(empty($cost_1c)){
				   
			   $textquery = "UPDATE " . DB_PREFIX . "product SET quantity=" . $ostatok_1c . " WHERE product_id IN (SELECT oc_prod_id FROM " . DB_PREFIX . "1c_product WHERE 1c_kod_prod='$kod_1c')";
			   } else {
				   
				    $textquery = "UPDATE " . DB_PREFIX . "product SET quantity=" . $ostatok_1c . ", price=" . $cost_1c. " WHERE product_id IN (SELECT oc_prod_id FROM " . DB_PREFIX . "1c_product WHERE 1c_kod_prod='$kod_1c')";
				 
			   }
		} else {

               if(empty($cost_1c)){
				   
			   $textquery = "UPDATE " . DB_PREFIX . "product SET quantity=" . $ostatok_1c . " WHERE product_id IN (SELECT oc_prod_id FROM " . DB_PREFIX . "1c_product WHERE 1c_kod_prod='$kod_1c')";
			   } else {
				   
				    $textquery = "UPDATE " . DB_PREFIX . "product SET quantity=" . $ostatok_1c . ", price=" . $cost_1c. " WHERE product_id IN (SELECT oc_prod_id FROM " . DB_PREFIX . "1c_product WHERE 1c_kod_prod='$kod_1c')";
				 
			   }

		}
		
        $this->db->query($textquery);
    } 

	
    public function getLanguageId($lang) {
        $query = $this->db->query('SELECT `language_id` FROM `' . DB_PREFIX . 'language` WHERE `code` = "' . $lang . '"');
        return $query->row['language_id'];
    } 

	
    //удяляем дубли в Url     
    public function deleteDouble() {

        $query = $this->db->query("SELECT DISTINCT p1.url_alias_id, p1.keyword  FROM `" . DB_PREFIX . "url_alias` AS p1 JOIN `" . DB_PREFIX . "url_alias` AS p2 ON(p2.keyword=p1.keyword) WHERE p2.url_alias_id!= p1.url_alias_id");

        $k = count($query->rows);

        $forupdate = array();

        while (list($pos) = each($query->rows)) {

            $keyword = $query->rows[$pos]['keyword'];
            $alias_id = $query->rows[$pos]['url_alias_id'];

            foreach ($query->rows as $row => $val) {

                if ((strtolower($keyword) == strtolower($val['keyword'])) && ($alias_id !== $val['url_alias_id'])) {
                    $forupdate[] = array($val['url_alias_id'], $val['keyword']);

                    // $this->log->write($val['url_alias_id']."|".$alias_id."=".$val['keyword']);

                    unset($query->rows[$row]);
                }
            }

            reset($query->rows);

            unset($query->rows[$pos]);
        }

        foreach ($forupdate as $row) {
            $keyword_ins = $row[1] . "-" . $row[0];
            $alias_id_ins = (int) $row[0];

            $sql_text = "UPDATE " . DB_PREFIX . "url_alias SET keyword='" . $keyword_ins . "' WHERE url_alias_id='" . $alias_id_ins . "'";

            $this->db->query($sql_text);
        }
    } 
    
    public function checkOptions($lang=0){
        $option_id=0;
        $this->load->model('catalog/option');
        
        $option_id = $this->getOptionId();
        
        if ($option_id===0){
            $option = array();
            $option = array(
                'type'=>'text',
                'sort_order'=>1,
                
            );
            
            $option['option_description'] = array($lang => array('name'=> 'ед.изм.'));
            
            $this->model_catalog_option->addOption($option);
            return $this->getOptionId();
        }
        return $option_id;
    } 
    
	
    private function getOptionId(){
        $options = $this->model_catalog_option->getOptions();
        foreach ($options as $row){
            if ($row['name']== 'ед.изм.') return $row['option_id']; 
        }
        return 0;
    }  

} 

?>
