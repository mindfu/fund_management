<?php
/**
 * Fund Controller - Contains action related to fund management
 */
class FundController extends Zend_Controller_Action
{
	/**
	 * Action to assign fund to portfolio
	 */
	public function assignAction(){
		$this->__checkAuth();
		Zend_Loader::loadClass("CreateFund", array(FORMS_PATH));	
		Zend_Loader::loadClass("UpdateFund", array(FORMS_PATH));	
		
		Zend_Loader::loadClass("Portfolio", array(MODELS_PATH));
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		Zend_Loader::loadClass("Category", array(MODELS_PATH));
		Zend_Loader::loadClass("Subset", array(MODELS_PATH));
		Zend_Loader::loadClass("LeverageRatio", array(MODELS_PATH));
		
		$db = Zend_Registry::get("main_db");
		$session = new Zend_Session_Namespace("portfolio_session");
		
		//initialize the forms
		$form = new CreateFund();
		$updateForm = new UpdateFund();
		
		$this->view->form = $form;
		$this->view->update_form = $updateForm;
		
		//get portfolio details
		$portfolioTable = new Portfolio();
		if ($this->getRequest()->getQuery("id")){
			$id = $this->getRequest()->getQuery("id");			
		}else{
			$id = $session->created_portfolio;
		}
		
		if (!$id){
			header("Location:/portfolio/");				
		}

		$portfolioTable = new Portfolio();
		$portfolio = $portfolioTable->fetchRow($portfolioTable->select()->where("id = ?", $id));
		if ($portfolio){
			
			$portfolio = $portfolio->toArray();
			
			//get all funds under the portfolio
			$fundTable = new Fund();
			$funds = $fundTable->fetchAll($fundTable->select()->where("portfolio_id = ?", $portfolio["id"])->where("deleted = 0"));
			$funds = $funds->toArray();
			foreach($funds as $key=>$fund){
				$categoryTable = new Category();
				$category = $categoryTable->fetchRow($categoryTable->select()->where("id = ?", $fund["category_id"]));
				$category = $category->toArray();
				if ($fund["fund_id"]!=0){
					$funds[$key]["name"] = $db->fetchOne($db->select()->from("hedgefunds", array("fund_name"))->where("id = ?", $fund["fund_id"]));
				}
				
				$funds[$key]["category"] = $category;
				
				//load subset
				$subsetTable = new Subset();
				$subset = $subsetTable->fetchRow($subsetTable->select()->where("id = ?", $fund["subset_id"]));
				$funds[$key]["subset"] = $subset->subset_name;
				
			}
			$portfolio["funds"] = $funds;
			
			//auto select template based on leveraged ratio
			$ratioTable = new LeverageRatio();
			$this->view->subsets = array();
			$ratio = $ratioTable->fetchRow($ratioTable->select()->where("id = ?", $portfolio["leverage_ratio_id"]));
			if ($ratio){
				$subsetTable = new Subset();
				$subsets = $subsetTable->fetchAll($subsetTable->select()->where("debt = ?", $ratio->debt)->where("equity = ?", $ratio->equity));
				
				
				$options = array();
				$options[""] = "Please Select";
				foreach($subsets as $subset){
					$options[$subset->id] = $subset->subset_name;				
				}								
				$form->getElement("subset_id")->setMultiOptions($options);
				$updateForm->getElement("subset_id")->setMultiOptions($options);
			}
			$form->getElement("portfolio_id")->setValue($id);
			$this->view->portfolio = $portfolio;
		}
		$this->view->headScript()->appendFile("http://ajax.aspnetcdn.com/ajax/jquery.validate/1.11.1/jquery.validate.js", $type="text/javascript");
		$this->view->headScript()->appendFile( "/public/js/fund/assign.js", $type = 'text/javascript' );
		$this->view->headTitle("Assign funds to ".$portfolio["name"]);
		$this->_helper->layout->setLayout("sb_admin");			
	}

	/**
	 * Add Fund to Portfolio
	 */
	public function addAction(){
		Zend_Loader::loadClass("CreateFund", array(FORMS_PATH));	
		Zend_Loader::loadClass("Portfolio", array(MODELS_PATH));
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		Zend_Loader::loadClass("Category", array(MODELS_PATH));
		Zend_Loader::loadClass("Subset", array(MODELS_PATH));
		Zend_Loader::loadClass("LeverageRatio", array(MODELS_PATH));
		
		//get portfolio id
		$portfolio_id = $this->getRequest()->getPost("portfolio_id");
		$portfolioTable = new Portfolio();
		$ratioTable = new LeverageRatio();
		
		$fund_id = $this->getRequest()->getPost("fund_id");
		$subset_id = $this->getRequest()->getPost("subset_id");
		$hedgefund_id = $this->getRequest()->getPost("fund_id");
		
		$portfolio = $portfolioTable->fetchRow($portfolioTable->select()->where("id = ?", $portfolio_id));
		if ($portfolio){
			$portfolio = $portfolio->toArray();
			if ($portfolio["leverage_ratio_id"]){
				$ratio = $ratioTable->fetchRow($ratioTable->select()->where("id = ?", $portfolio["leverage_ratio_id"]));
				if ($ratio){
					$subsetTable = new Subset();
					$subsets = $subsetTable->fetchAll($subsetTable->select()->where("debt = ?", $ratio->debt)->where("equity = ?", $ratio->equity));
					
					
					$options = array();
					$options[""] = "Please Select";
					foreach($subsets as $subset){
						$options[$subset->id] = $subset->subset_name;				
					}	
					$form = new CreateFund();							
					$form->getElement("subset_id")->setMultiOptions($options);
					
					if ($form->isValid($_POST)&&$fund_id){
						$fundTable = new Fund();
						$existingFund = $fundTable->fetchRow($fundTable->select()->where("subset_id = ?", $subset_id)->where("fund_id = ?", $hedgefund_id));
						if (!$existingFund){
							$data = $form->getValues($_POST);
							$data["fund_id"] = $fund_id;
							
							//depracate fund_name field
							unset($data["name"]);
							
							$fundTable->insert($data);
							$this->view->result = array("success"=>true, "id"=>$id);
						}else{
							$this->view->result = array("success"=>false, "error"=>"Fund is already added to the subset.");
						}		

						
						$this->_helper->layout->setLayout("empty");
					}else{
						$this->view->result = array("success"=>false);
						
						$this->_helper->layout->setLayout("empty");
					}
				}else{
					$this->view->result = array("success"=>false);
					
					$this->_helper->layout->setLayout("empty");
				}
			}
			
		}else{
			$this->view->result = array("success"=>false);
			
			$this->_helper->layout->setLayout("empty");
		}
	}
	
	/*
	 * Get the information of a fund using its id
	 */
	public function getInfoAction(){
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		$db = Zend_Registry::get("main_db");
		$id = $this->getRequest()->getQuery("id");
		if ($id){
			$fundTable = new Fund();
			//get the fund based on the id
			$fund = $fundTable->fetchRow($fundTable->select()->where("id = ?", $this->getRequest()->getQuery("id")));
			$fund = $fund->toArray();
			if ($fund["fund_id"]!=0){
				//get the name of the hedgefund
				$fund["name"] = $db->fetchOne($db->select()->from("hedgefunds", array("fund_name"))->where("id = ?", $fund["fund_id"]));
			}
			$this->view->result = array("success"=>true, "fund"=>$fund);
		}else{
			$this->view->result = array("success"=>false);			
		}
		$this->_helper->layout->setLayout("empty");		
	}
	/**
	 * List all fund under a certain portfolio
	 */
	public function listByPortfolioAction(){
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		$fundTable = new Fund();
		$funds = $fundTable->fetchAll($fundTable->select()->where("portfolio_id = ?", $this->getRequest()->getQuery("portfolio_id")));
		$funds = $funds->toArray();
		$this->view->result = array("success"=>true, "funds"=>$funds);
		$this->_helper->layout->setLayout("empty");		
	}

	/**
	 * Delete a fund based on a given id
	 */
	public function deleteAction(){
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		$fundTable = new Fund();
		$id = $this->getRequest()->getQuery("id");
		$fundTable->update(array("deleted"=>1), $fundTable->getAdapter()->quoteInto("id = ?", $id));
		$this->view->result = array("success"=>true, "id"=>$id);
		$this->_helper->layout->setLayout("empty");	
	}
	
	/**
	 * Update a fund information action
	 */
	public function updateAction(){
		Zend_Loader::loadClass("CreateFund", array(FORMS_PATH));	
		Zend_Loader::loadClass("Portfolio", array(MODELS_PATH));
		Zend_Loader::loadClass("Fund", array(MODELS_PATH));
		Zend_Loader::loadClass("Category", array(MODELS_PATH));
		Zend_Loader::loadClass("Subset", array(MODELS_PATH));
		Zend_Loader::loadClass("LeverageRatio", array(MODELS_PATH));
		
		
		//get portfolio id
		$fund_id = $this->getRequest()->getPost("id");
		$hedgefund_id = $this->getRequest()->getPost("fund_id");
		$subset_id = $this->getRequest()->getPost("subset_id");
		
		if ($fund_id){
			//get all subset
			$fundTable = new Fund();
			$portfolioTable = new Portfolio();
			$subsetTable = new Subset();
			$ratioTable = new LeverageRatio();
			$fund = $fundTable->fetchRow($fundTable->select()->where("id = ?", $fund_id));
			if ($fund){
				$portfolio = $portfolioTable->fetchRow($portfolioTable->select()->where("id = ?", $this->getRequest()->getPost("portfolio_id")));
				if ($portfolio){
					$portfolio = $portfolio->toArray();
					$ratio = $ratioTable->fetchRow($ratioTable->select()->where("id = ?", $portfolio["leverage_ratio_id"]));
					if ($ratio){
						$subsetTable = new Subset();
						$subsets = $subsetTable->fetchAll($subsetTable->select()->where("debt = ?", $ratio->debt)->where("equity = ?", $ratio->equity));
						
						
						$options = array();
						$options[""] = "Please Select";
						foreach($subsets as $subset){
							$options[$subset->id] = $subset->subset_name;				
						}	
						$form = new CreateFund();							
						$form->getElement("subset_id")->setMultiOptions($options);
						
						if ($form->isValid($_POST)&&$hedgefund_id){
							
							$existingFund = $fundTable->fetchRow($fundTable->select()->where("subset_id = ?", $subset_id)->where("fund_id = ?", $hedgefund_id));
							if (!$existingFund){
								$data = $form->getValues($_POST);
								unset($data["name"]);
								$data["fund_id"] = $hedgefund_id;
								$fundTable->update($data, $fundTable->getAdapter()->quoteInto("id = ?", $fund_id));
								$this->view->result = array("success"=>true);								
							}else{
								$this->view->result = array("success"=>false, "error"=>"Fund is already added to the subset.");
							}
							
							
						}else{
							$this->view->result = array("success"=>false, "errors"=>$form->getErrors());
						}
					}else{
						$this->view->result = array("success"=>false, "error"=>"Leverage Ratio not Found");
						
					}
				}else{
					$this->view->result = array("success"=>false, "error"=>"Portfolio Does Not Exist");
				}			
			}else{
				$this->view->result = array("success"=>false, "error"=>"Fund does not exist");			
			}

		}else{
			$this->view->result = array("success"=>false);
		}
		$this->_helper->layout->setLayout("empty");
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

	
}
	