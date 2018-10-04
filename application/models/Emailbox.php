<?php

class Application_Model_Emailbox
{
	private function __construct()
	{
	}
	public static function add_email($email, $data)
	{	
		$db = Zend_Db_Table::getDefaultAdapter();
				
		$insert_data=array();
		$insert_data['email']=$email;
		$insert_data['mail_from']=$data['from'];
		$insert_data['mail_date']=date('Y-m-d H:i:s',strtotime($data['date']));
		$insert_data['mail_weekday']=date('N',strtotime($data['date']));
		$insert_data['mail_time']=date('H:i',strtotime($data['date']));
		$insert_data['mail_subject']=$data['subject'];
		$insert_data['mail_uid']=$data['uid'];
		$insert_data['mail_unread']=$data['unread'];
		$insert_data['mail_answered']=$data['answered'];
		$insert_data['mail_body']=$data['body'];
		$insert_data['mail_html']=$data['html'];
		
		$db->insert('emailbox',$insert_data);
		
		return $email;
	}
	public static function get_uidsbyemail($email) {
		$db = Zend_Db_Table::getDefaultAdapter();

		$uids = array();
		
		$result = $db->fetchAll("select mail_uid from emailbox where email='".$email."'");
		foreach($result as $uid ) {
				array_push($uids, $uid['mail_uid']);
		}
		
		return $uids;
	}
}