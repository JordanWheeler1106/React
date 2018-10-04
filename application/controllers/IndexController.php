<?php

class IndexController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

	public function indexAction()
  { 	
			//$this->getHelper("Redirector")->gotoSimple('index','auth');
			echo phpinfo(); exit;
    }		
}