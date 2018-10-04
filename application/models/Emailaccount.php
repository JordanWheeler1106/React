<?php

class Application_Model_Emailaccount
{
	private function __construct()
	{
	}
	public static function register_account($data)
	{	
		$db = Zend_Db_Table::getDefaultAdapter();
				
		$insert_data=array();
		$insert_data['userid']=$data->userid;
		$insert_data['email']=$data->email;
		$insert_data['password']=$data->password;
		$insert_data['email_type']=$data->email_type;
		
		$db->insert('account',$insert_data);
		
		$account_list = $db->fetchAll("select * from account where userid='".$data->userid."'");
		return $account_list;
	}
	
	public static function emailavailable($email) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$check = $db->fetchRow("select * from account where email='".$email."'");
		
		if($check)
				return false;
				
		return true;
	}
	public static function get_allbyuserid($userid) {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$account_list = $db->fetchAll("select * from account where userid='".$userid."'");
		return $account_list;
	}
	public static function get_all() {
		$db = Zend_Db_Table::getDefaultAdapter();
		
		$account_list = $db->fetchAll("select * from account");
		
		return $account_list;
	}
}