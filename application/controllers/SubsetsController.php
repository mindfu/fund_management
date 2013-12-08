<?php

class SubsetsController extends Zend_Controller_Action
{
	public function indexAction(){
		$db = Zend_Registry::get("main_db");
		
		$rows = 50;
		$page = $this->getRequest()->getQuery("page");
		if (!$page){
			$page = 1;
		}
		
		$subsets = $db->fetchAll($db->select()->from("subsets")->limitPage($page, $rows));
	}
}
	