<?php
/*
 * Обмен 1С 7.7 ТиС  с OpenCart.
 * 
 * eosagm@gmail.com
 * 
 * http://from64.ru
 * 
 * 
 */

class ControllerModuleExchange extends Controller {

    private $error = array(); 
    
    public function index() {
        
    $this->load->language('module/exchange');   
    $this->document->setTitle($this->language->get('heading_title'));
    
   $this->load->model('setting/setting');

		if ($this->request->server['REQUEST_METHOD'] == 'POST')  {
			$this->model_setting_setting->editSetting('exchange', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
    
    $data['heading_title'] = $this->language->get('heading_title');
	$data['text_edit'] = $this->language->get('text_edit');
    $data['button_cancel'] = $this->language->get('button_cancel');
    $data['button_save'] = $this->language->get('button_save');
    $data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
    $data['button_delete'] = $this->language->get('button_delete');
    $data['text_delete'] = $this->language->get('text_delete');
    $data['text_delete2'] = $this->language->get('text_delete2');
    $data['text_delete3'] = $this->language->get('text_delete3');
    $data['text_delete4'] = $this->language->get('text_delete4');
    $data['button_action'] = $this->language->get('button_action');
    $data['exchange_delete_check'] = $this->language->get('button_action');
    $data['exchange_priority_text'] = $this->language->get('exchange_priority_text');
	$data['exchange_auto_text'] = $this->language->get('exchange_auto_text');
	$data['exchange_auto_count_text'] = $this->language->get('exchange_auto_count_text');
    
    if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		}else {
			$data['error_warning'] = '';
		}
                
     $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_home'),
			'href'		=> $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> false
		);

		$data['breadcrumbs'][] = array(
			'text'		=> $this->language->get('text_module'),
			'href'		=> $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator'	=> ' :: '
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/exchange', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
  
    $data['action'] = $this->url->link('module/exchange', 'token=' . $this->session->data['token'], 'SSL');            
                

             $this->load->model('setting/setting');
             
             if (isset($this->request->post['exchange_status'])) {
			    $data['exchange_status'] = $this->request->post['exchange_status'];
		    }
		    else {
			    $data['exchange_status'] = $this->config->get('exchange_status');           
             }
                 
             if (isset($this->request->post['exchange_priority'] )) {
			    $data['exchange_priority'] = $this->request->post['exchange_priority'];
		    }
		    else {
                $data['exchange_priority'] = "";
                    //if (!isset($this->request->post['exchange_status'])) {
			    $data['exchange_priority'] = $this->config->get('exchange_priority');  
                 //   }       
		    }
			
			if (isset($this->request->post['exchange_auto'] )) {
			$data['exchange_auto'] = $this->request->post['exchange_auto'];
		    }
		    else {
                $data['exchange_auto'] = "";
                    //if (!isset($this->request->post['exchange_status'])) {
			    $data['exchange_auto'] = $this->config->get('exchange_auto');
                 //   }       
		    }
			
			if (isset($this->request->post['exchange_auto_count'] )) {
			$data['exchange_auto_count'] = $this->request->post['exchange_auto_count'];
		    }
		    else {
                $data['exchange_auto_count'] = 0;
                    //if (!isset($this->request->post['exchange_status'])) {
			    $data['exchange_auto_count'] = $this->config->get('exchange_auto_count');
                 //   }       
		    }
                
                
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('exchange', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			
		}
        
          if (isset($this->request->post['exchange_status'])) {
              
		if (isset($this->request->post['exchange_delete_check'])){	
                      if ($this->request->post['exchange_status']=='1'){
                          $this->log->write("delete");
                          $this->load->model('tool/exchange');
                          $this->model_tool_exchange->deleteProd();           
                      }
                      if ($this->request->post['exchange_status']=='0'){
                          $this->log->write("deleteAll");
                          $this->load->model('tool/exchange');
                          $this->model_tool_exchange->deleteAll();           
                      }
                      if ($this->request->post['exchange_status']=='2'){
                          $this->log->write("clearProduct");
                          $this->load->model('tool/exchange');
                          $this->model_tool_exchange->clearProduct();           
                      }
                      if ($this->request->post['exchange_status']=='3'){
                          $this->log->write("DeleteDouble");
                          
                          
                          $this->load->model('tool/exchange');                        
                          $this->model_tool_exchange->deleteDouble();
                         
                      }
                      
                     
		} 
          }
     
        
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('module/exchange.tpl', $data));
    }

    function install() {


        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "1c_cat` (
                        `id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
                        `1c_kod_group` VARCHAR( 20 ) NOT NULL ,
                        `1c_name` VARCHAR( 150 ) NOT NULL ,
                        `1c_kod_group_rod` VARCHAR( 20 ) NOT NULL ,
                        `oc_cat_id` INT( 11 ) NOT NULL ,
                        PRIMARY KEY ( `id` ),
                        KEY (`1c_kod_group`),
                        KEY (`1c_kod_group_rod`),
                        KEY (`oc_cat_id`)
                        ) ENGINE = MYISAM DEFAULT CHARSET=utf8");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "1c_product` (
                        `id` INT( 10 ) NOT NULL AUTO_INCREMENT ,
                        `1c_kod_prod` VARCHAR( 20 ) NOT NULL ,
                        `1c_kod_prod_rod` VARCHAR( 20 ) NOT NULL ,
                        `1c_name` VARCHAR( 150 ) NOT NULL ,
                        `1c_ostatok` VARCHAR( 10 ) NOT NULL ,
                        `1c_cena` VARCHAR( 10 ) NOT NULL ,
                        `oc_prod_id` INT( 11 ) NOT NULL ,
                        PRIMARY KEY ( `id` ),
                        KEY (`1c_kod_prod`),
                        KEY (`1c_kod_prod_rod`),
                        KEY (`oc_prod_id`)
                        ) ENGINE = MYISAM DEFAULT CHARSET=utf8");
    }

	function xmlRpc($argu) {
         
		     
			  		//  $this->log->write($argu[0]);
			// $this->log->write($argu[1]);
		 $login = $argu[0];
		 $pass = $argu[1];
		 $action = $argu[2];
		 $kod_1c = $argu[3];
		 $article_1c = $argu[4];
		 $name_1c = $argu[5];
		 $ostatok_1c = $argu[6];
		 $edizm_1c = $argu[7];
		 $cost_1c = $argu[8];
		 $is_group_1c = $argu[9];
		 $kod_own_1c = $argu[10];
	  //  $this->arr_dump($argu);
        //------------
        
        if (!$this->registry->get('user')->isLogged()) {
       //    $this->log->write("незалогинены,логинимся");
      //    $this->log->write($pass);
	//    $this->log->write($login);
            $this->registry->get('user')->login($login, $pass);
                    if(!$this->registry->get('user')->isLogged()){
                      //   $this->log->write("неверный логин или пароль, выходим");
                         exit;
                        }
         //  $this->log->write("успешно залогинились");            
        } else {
         //   $this->log->write("уже залогинены");
           
        }
        $this->load->model('tool/exchange');
        $lang = $this->model_tool_exchange->getLanguageId($this->config->get('config_language'));
        
        switch ($action){
            case 'addCategory':
                if ($is_group_1c){
                    $this->load->model('tool/exchange');
                    $this->model_tool_exchange->addCategory($action, $kod_1c, $name_1c, $ostatok_1c, $cost_1c, $is_group_1c, $kod_own_1c, $lang);
                }
                break;
            case 'addProduct':
                if (!$is_group_1c){             			
                    $this->load->model('tool/exchange');
                    $this->model_tool_exchange->addProduct($action, $kod_1c, $article_1c, $name_1c, $ostatok_1c, $edizm_1c, $cost_1c, $is_group_1c, $kod_own_1c, $lang);
                }
                break;
            case 'getProduct':
                if (!$is_group_1c){
                    $this->load->model('tool/exchange');
                    $this->model_tool_exchange->getProduct($action, $kod_1c, $name_1c, $ostatok_1c, $cost_1c, $is_group_1c, $kod_own_1c);
                }
                break;
            case 'refreshProduct':
                
                    $this->load->model('tool/exchange');
                    $this->model_tool_exchange->refreshProduct($kod_1c, $name_1c, $ostatok_1c, $cost_1c);
                
                break;
            case 'deleteDouble':
                
                    $this->load->model('tool/exchange');
                    $this->model_tool_exchange->deleteDouble();
                
                break;
            case 'Price':
              //   if($this->load->model('tool/pricexls') == false){
             //   if (class_exists('ModelToolPricexls')) {
          //          $this->model_tool_pricexls->getExcelFile();
         //       }
		     //       $this->log->write("нету  tool/pricexls");     
			//	 }
                break;
            
        }
        
 
 
    }
	
    function arr_dump($value) {

        ob_start();
        var_dump($value);
        return ob_get_clean();
    }
    

}

?>