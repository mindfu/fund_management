<?php
/**
 * Controller related to Hedgefund Management CRUD and other action
 */
class HedgefundsController extends Zend_Controller_Action
{
	/**
	 * Index page action
	 */
	public function indexAction(){
		
		$this->__checkAuth();
		$session = new Zend_Session_Namespace("capitalp");
		
		$db = Zend_Registry::get("main_db");
		
		Zend_Loader::loadClass("HedgeFund", array(MODELS_PATH));
		
		$this->view->headTitle("List of Funds - Capital Protection");
		$page = $this->getRequest()->getQuery("page");
		
		if (!$page){
			$page = 1;
		}else{
			$page = intval($page);
		}
		
		$rows = 50;
		/**
		 * Get all hedgefunds based on the owner
		 */
		$sql = $db->select()->from("hedgefunds", array(new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")))
					->where("user_id = ?", $session->manager_id)
					->order("date_updated DESC")
					->limitPage($page, $rows);
		$q = $this->getRequest()->getQuery("q");
		if ($q){
			$q = addslashes($q);
			$sql->where("fund_name LIKE '%".$q."%'");
		}	
		$hedgeFunds = $db->fetchAll($sql);
		
		$count = $db->fetchOne("SELECT FOUND_ROWS()");
	
		$this->view->hedge_funds = $hedgeFunds;
		$this->view->count = $count;
		$this->view->current_page = $page;
		
		$query = array();
		foreach($_GET as $key=>$val){
			if ($key=="page"){
				continue;
			}
			$query[] = $key."=".$val;
		}
		
		$query = implode("&", $query);
		
		$this->view->query = $query;
		
		$this->view->headScript()->appendFile("/public/js/hedgefund/index.js", $type="text/javascript");
		
		$this->_helper->layout->setLayout("sb_admin");
	}
	
	/**
	 * Update a hedgefund information action
	 */
	public function updateAction(){
		$id = $this->getRequest()->getQuery("id");
		$this->__checkAuth();
		Zend_Loader::loadClass("HedgeFund", array(MODELS_PATH));
		Zend_Loader::loadClass("UpdateHedgeFund", array(FORMS_PATH));
		$db = Zend_Registry::get("main_db");
		$hfTable = new HedgeFund();
		$hedgeFund = $hfTable->fetchRow($hfTable->select()->where("id = ?", $id));
		$hedgeFund = $hedgeFund->toArray();
		$form = new UpdateHedgeFund();
		foreach($hedgeFund as $key=>$val){
			try{
				if ($form->getElement($key)!=null){
					$form->getElement($key)->setValue($val);					
				}
			}catch(Exception $e){
				
			}			
		}
		
		/**
		 * List all fields then based on the list of fields perform setting of values for the form
		 */
		$contact_types = array("general_partner", "manager", "administrator", "custodian", "accountant", "prime_broker", "legal_counsel", "contact_person");
		foreach($contact_types as $contact_type){
			$personnel = $this->getPersonalRecord($hedgeFund[$contact_type."_id"]);
			$key = $contact_type."_fname";
			try{
				if ($form->getElement($key)!=null){
					$form->getElement($key)->setValue($personnel["first_name"]);					
				}
			}catch(Exception $e){
				
			}
			$key = $contact_type."_lname";
			try{
				if ($form->getElement($key)!=null){
					$form->getElement($key)->setValue($personnel["last_name"]);					
				}
			}catch(Exception $e){
				
			}
			$key = $contact_type."_title";
			try{
				if ($form->getElement($key)!=null){
					$form->getElement($key)->setValue($personnel["title"]);					
				}
			}catch(Exception $e){
				
			}
			
			
		}
		
		//get all fund percs
		$this->view->fund_percs = $db->fetchAll($db->select()->from("fund_percentages")->where("fund_id = ?", $id));
		
		$this->view->form = $form;
		$this->view->headTitle("Update ".$hedgeFund["fund_name"]);
		$this->view->fund_name = $hedgeFund["fund_name"];
		$this->view->headScript()->appendFile("http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.js", $type="text/javascript");
		$this->view->headScript()->appendFile("/public/js/hedgefund/update.js", $type="text/javascript");
		
		$this->_helper->layout->setLayout("sb_admin");

	}
	
	
	/**
	 * Action for rendering view for creating a hedgefund
	 */
	public function setupAction(){
		$this->__checkAuth();
		$session = new Zend_Session_Namespace("capitalp");
		Zend_Loader::loadClass("CreateHedgeFund", array(FORMS_PATH));
		$form = new CreateHedgeFund();
		$this->view->form = $form;
		$this->view->headTitle("Create New HedgeFund");
		$this->view->headScript()->appendFile("http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.js", $type="text/javascript");
		$this->view->headScript()->appendFile("/public/js/hedgefund/setup.js", $type="text/javascript");
		
		$this->_helper->layout->setLayout("sb_admin");
	}
	
	private function __checkAuth(){
		Zend_Loader::loadClass("CheckAuth", array(COMPONENTS_PATH));
		CheckAuth::__checkAuth();
		$session = new Zend_Session_Namespace("capitalp");
		Zend_Loader::loadClass("User", array(MODELS_PATH));
		//get authenticated user
		$userTable = new User();
		$manager = $userTable->find($session->manager_id);
		$manager = $manager->toArray();
		$manager = $manager[0];
		$this->view->manager = $manager;
	}
	
	private function createPersonalRecord($data, $type){
		$db = Zend_Registry::get("main_db");
		if ($data[$type."_fname"]){
			$personnel = array(
								"first_name"=>$data[$type."_fname"],
								"last_name"=>$data[$type."_lname"],
								"title"=>$data[$type."_title"],
								"type"=>$type,
								"date_created"=>date("Y-m-d H:i:s")
							);
			$db->insert("fund_personnels", $personnel);
			return $db->lastInsertId("fund_personnels");	
		}else{
			return 0;
		}
		
		
	}
	/**
	 * Update personal record given a fund and the type of personnel
	 */
	private function updatePersonalRecord($data, $hedgefund, $type){
		$db = Zend_Registry::get("main_db");
		$updatePersonnel = array(
			"first_name"=>$data[$type."_fname"],
			"last_name"=>$data[$type."_lname"],
			"title"=>$data[$type."_title"],
		);
		$db->update("fund_personnels", $updatePersonnel, $db->quoteInto("id = ?", $hedgefund[$type."_id"]));
	}
	
	/**
	 * Get Personal record
	 */
	private function getPersonalRecord($id){
		$db = Zend_Registry::get("main_db");
		return $db->fetchRow($db->select()->from("fund_personnels")->where("id = ?", $id));
	}
	
	/**
	 * Deletes a hedgefund
	 */
	public function deleteAction(){
		$this->__checkAuth();
		$this->_helper->layout->setLayout("empty");
		$db = Zend_Registry::get("main_db");
		$db->delete("hedgefunds", $db->quoteInto("id = ?", $this->getRequest()->getQuery("id")));
		$this->view->result = array("success"=>true);
		
	}
	
	/**
	 * Save a updated hedgefund action
	 */
	public function saveAction(){
		$this->__checkAuth();
		$this->_helper->layout->setLayout("empty");
		$db = Zend_Registry::get("main_db");
		Zend_Loader::loadClass("UpdateHedgeFund", array(FORMS_PATH));
		Zend_Loader::loadClass("HedgeFund", array(MODELS_PATH));
		
		$session = new Zend_Session_Namespace("capitalp");
		$form = new UpdateHedgeFund();
		if ($form->isValid($_POST)){
			$data = $form->getValues();
			//load existing hedgefund
			$hedgeFundTable = new HedgeFund();
			$hedgeFund = $hedgeFundTable->fetchRow($hedgeFundTable->select()->where("id = ?", $data["id"]));
			$hedgeFund = $hedgeFund->toArray();
			
			//update personnel information
			$this->updatePersonalRecord($data, $hedgeFund, "general_partner");
			unset($data["general_partner_fname"]);
			unset($data["general_partner_lname"]);
			unset($data["general_partner_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "manager");
			unset($data["manager_fname"]);
			unset($data["manager_lname"]);
			unset($data["manager_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "administrator");
			unset($data["administrator_fname"]);
			unset($data["administrator_lname"]);
			unset($data["administrator_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "custodian");
			unset($data["custodian_fname"]);
			unset($data["custodian_lname"]);
			unset($data["custodian_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "accountant");
			unset($data["accountant_fname"]);
			unset($data["accountant_lname"]);
			unset($data["accountant_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "prime_broker");
			unset($data["prime_broker_fname"]);
			unset($data["prime_broker_lname"]);
			unset($data["prime_broker_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "legal_counsel");
			unset($data["legal_counsel_fname"]);
			unset($data["legal_counsel_lname"]);
			unset($data["legal_counsel_title"]);
			$this->updatePersonalRecord($data, $hedgeFund, "contact_person");
			unset($data["contact_person_fname"]);
			unset($data["contact_person_lname"]);
			unset($data["contact_person_title"]);
			$data["date_updated"] = date("Y-m-d H:i:s");
			
			$db->update("hedgefunds", $data, $db->quoteInto("id = ?", $data["id"]));
			
			$this->view->result = array("success"=>true);
		}else{
			$this->view->result = array("success"=>false, "errors"=>$form->getErrors());
		}
		
	}
	/**
	 * Search a specific fund
	 */
	public function searchAction(){
		$this->__checkAuth();
		$this->_helper->layout->setLayout("empty");
		$db = Zend_Registry::get("main_db");
		$query = $this->getRequest()->getQuery("q");
		$this->view->result = $db->fetchAll($db->select()->from("hedgefunds", array("id", "fund_name"))->where("fund_name LIKE '%".addslashes($query)."%'")->limit(100));
	}
	
	/**
	 * Save the newly created hedgefund on db
	 */
	public function createAction(){
		$this->__checkAuth();
		$this->_helper->layout->setLayout("empty");
		$db = Zend_Registry::get("main_db");
		Zend_Loader::loadClass("CreateHedgeFund", array(FORMS_PATH));
		$session = new Zend_Session_Namespace("capitalp");
		$form = new CreateHedgeFund();
		if ($form->isValid($_POST)){
			$data = $form->getValues();
			$general_partner_id = $this->createPersonalRecord($data, "general_partner");
			unset($data["general_partner_fname"]);
			unset($data["general_partner_lname"]);
			unset($data["general_partner_title"]);
			$manager_id = $this->createPersonalRecord($data, "manager");
			unset($data["manager_fname"]);
			unset($data["manager_lname"]);
			unset($data["manager_title"]);
			$administrator_id = $this->createPersonalRecord($data, "administrator");
			unset($data["administrator_fname"]);
			unset($data["administrator_lname"]);
			unset($data["administrator_title"]);
			$custodian_id = $this->createPersonalRecord($data, "custodian");
			unset($data["custodian_fname"]);
			unset($data["custodian_lname"]);
			unset($data["custodian_title"]);
			$accountant_id = $this->createPersonalRecord($data, "accountant");
			unset($data["accountant_fname"]);
			unset($data["accountant_lname"]);
			unset($data["accountant_title"]);
			$prime_broker_id = $this->createPersonalRecord($data, "prime_broker");
			unset($data["prime_broker_fname"]);
			unset($data["prime_broker_lname"]);
			unset($data["prime_broker_title"]);
			$legal_counsel_id = $this->createPersonalRecord($data, "legal_counsel");
			unset($data["legal_counsel_fname"]);
			unset($data["legal_counsel_lname"]);
			unset($data["legal_counsel_title"]);
			$contact_person_id = $this->createPersonalRecord($data, "contact_person");
			unset($data["contact_person_fname"]);		
			unset($data["contact_person_lname"]);
			unset($data["contact_person_title"]);
			
			$data["general_partner_id"] = $general_partner_id;			
			$data["manager_id"] = $manager_id;
			$data["administrator_id"] = $administrator_id;
			$data["custodian_id"] = $custodian_id;
			$data["prime_broker_id"] = $prime_broker_id;
			$data["legal_counsel_id"] = $legal_counsel_id;
			$data["contact_person_id"] = $contact_person_id;
			$data["accountant_id"] = $accountant_id;
			$data["date_updated"] = date("Y-m-d H:i:s");
			$data["date_created"] = date("Y-m-d H:i:s");
			$data["user_id"] = $session->manager_id;
			
			$db->insert("hedgefunds", $data);
			$this->view->result = array("success"=>true);
		}else{
			$this->view->result = array("success"=>false, "errors"=>$form->getErrors());
		}
	}
	
	/**
	 * Update fund performance information
	 */
	public function updatePerformanceAction(){
		$this->__checkAuth();
		$this->_helper->layout->setLayout("empty");
		$db = Zend_Registry::get("main_db");
		$data = $_POST;
		$perc = $db->fetchRow($db->select()->from(array("fp"=>"fund_percentages"))->where("fund_id = ?", $data["fund_id"])->where("month = ?", $data["month"])->where("year = ?", $data["year"]));
		if ($perc){
			$db->update("fund_percentages", $data, $db->quoteInto("id = ?", $perc["id"]));	
		}else{
			$data["date_created"] = date("Y-m-d H:i:s");
			$db->insert("fund_percentages", $data);
		}
		$this->view->result = array("success"=>true);
	}
}
	