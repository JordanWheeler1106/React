<?php
ini_set('display_errors', 0);
require( 'Imap.php' );
require( 'php-mailer/class.phpmailer.php' );

class ApiController extends Zend_Controller_Action
{
		public $mailbox = array("gmail"=>"imap.gmail.com:993/imap", 
								"yahoo"=>"imap.mail.yahoo.com:993/imap",
								"aol"=>"imap.aol.com:993/imap",
								"hotmail"=>"imap-mail.outlook.com:993/imap",
								"outlook"=>"imap-mail.outlook.com:993/imap",
								"icloud"=>"imap.mail.me.com:993/imap",
								"exchange"=>"outlook.office365.com:993/imap");
		
		public $domain_name = array("gmail"=>"gmail.com", 
								"yahoo"=>"yahoo.com",
								"aol"=>"aol.com",
								"hotmail"=>"hotmail.com",
								"outlook"=>"outlook.com",
								"icloud"=>array("me.com","mac.com","icloud.com"));
								
		public $encryption = array("gmail"=>"ssl", 
										"yahoo"=>"ssl",
										"aol"=>"ssl",
										"hotmail"=>"ssl",
										"outlook"=>"ssl",
										"icloud"=>"ssl",
										"exchange"=>"ssl");
										
		public $smtp = array("gmail"=>"smtp.gmail.com", 
								"yahoo"=>"smtp.mail.yahoo.com",
								"aol"=>"smtp.aol.com",
								"hotmail"=>"smtp-mail.outlook.com",
								"outlook"=>"smtp-mail.outlook.com",
								"icloud"=>"smtp.mail.me.com",
								"exchange"=>"smtp.office365.com");
								
		public $smtp_encryption = array("gmail"=>"ssl", 
										"yahoo"=>"ssl",
										"aol"=>"tls",
										"hotmail"=>"ssl",
										"outlook"=>"tls",
										"icloud"=>"ssl",
										"exchange"=>"tls");
    public function init()
    {
    }

    public function indexAction()
    {
    	echo "Please try correct api url"; exit;
        // action body
    }
    public function registerRealEmailAction() {
    		header('Content-Type: application/json; charset=utf-8');
				$data = json_decode($_REQUEST['sync_email']);
				$db = Zend_Db_Table::getDefaultAdapter();		
				$insert_data=array();
				$insert_data['userid']=$data->userid;
				$insert_data['email']=$data->email;
				$insert_data['password']=$data->password;
				$insert_data['email_type']=$data->email_type;
				
				$db->insert('account',$insert_data);
				
				$account_list = $db->fetchAll("select * from account where userid='".$data->userid."'");
							
				echo json_encode(array("result"=>$account_list));
				exit;
    }
    public function registerEmailAction() {
    		header('Content-Type: application/json; charset=utf-8');
				$data = json_decode($_REQUEST['sync_email']);
				
				$result=array();				
				if(!isset($data->email) || !isset($data->password) || !isset($data->email_type) || !isset($data->userid)) {
						$result['result']=0;
						$result['error'] = "Parameter is not valid.";
				} else {
						$flag = true;
						$domain = substr(strrchr($data->email, "@"), 1);
						if($data->email_type!="exchange" && strtolower($domain)!=$this->domain_name[$data->email_type])
								$flag=false;
						else if($data->email_type=="exchange" &&
										(strtolower($domain)=="gmail.com" ||
										strtolower($domain)=="yahoo.com" ||
										strtolower($domain)=="hotmail.com" ||
										strtolower($domain)=="outlook.com" ||
										in_array(strtolower($domain), $this->domain_name["icould"])))
								$flag=false;
						if($data->email_type=="icloud" && in_array(strtolower($domain), $this->domain_name["icloud"]))
								$flag=true;
										
						if($flag) {
								$imap = new Imap($this->mailbox[$data->email_type], $data->email, $data->password, $this->encryption[$data->email_type]);										
		
								if($imap->isConnected()) {										
										$db = Zend_Db_Table::getDefaultAdapter();		
										$check = $db->fetchRow("select * from account where email='".$data->email."'");
										if($check) {
												$result['result']=0;
												$result['error'] = "Email account has been already existed.";										
										} else {												
												$result['result']=1;										
										}								
								} else {
										$result['result']=0;
										$result['error'] = "Email and Password is incorrect.";
								}
						} else {
								$result['result']=0;
								$result['error'] = "Email account type does not support.";
						}
				}				
	    	echo json_encode($result);
	    	exit;
    }
    public function getAccountAction() {
    		$userid = $_REQUEST['userid'];
    		$db = Zend_Db_Table::getDefaultAdapter();		
    		$result=array();
    		
		   	$account_list = $db->fetchAll("select * from account where userid='".$userid."'");   				
    		
    		header('Content-Type: application/json');
	    	echo json_encode($account_list);
	    	exit;
    }
    public function deleteAccountAction() {
    		$email = $_REQUEST['email'];
    		$db = Zend_Db_Table::getDefaultAdapter();		    		
    		
    		$db->delete('account','email="'.$email.'"');
    		$db->delete('emailbox','email="'.$email.'"');
    		
    		header('Content-Type: application/json');
	    	echo json_encode(array("result"=>1));
	    	exit;
    }
    public function deleteFeedingEmailAction() {
    		$emailid = $_REQUEST['emailid'];
    		$db = Zend_Db_Table::getDefaultAdapter();		    		
    		
    		$db->update('emailbox',array('status'=>'removed'), 'id='.$emailid);
    		
    		header('Content-Type: application/json');
	    	echo json_encode(array("result"=>1));
	    	exit;
    }
    public function sendNewEmailAction() {
    		$from_email = $_REQUEST['from_email'];
    		$to_email = $_REQUEST['to_email'];
    		$subject = $_REQUEST['subject'];
    		$message = $_REQUEST['message'];
    		
    		$db = Zend_Db_Table::getDefaultAdapter();		
    		    		
		   	$account = $db->fetchRow("select * from account where email='".$from_email."'");
				
				$mail = new PHPMailer();
				
				$mail->IsSMTP();
    		$mail->SMTPAuth = true;
    		$mail->SMTPSecure = $this->smtp_encryption[$account['email_type']];
    		$mail->Host = $this->smtp[$account['email_type']];
    		
    		if($this->smtp_encryption[$account['email_type']]=="ssl")
    				$mail->Port = 465;
    		else
    				$mail->Port = 587;
    		$mail->IsHTML(true);
    		$mail->Username = $account['email'];
    		$mail->Password = $account['password'];
    		$mail->SetFrom($account['email']);
    		
    		$mail->Subject = $subject;
    		$mail->Body = $message;
    		$mail->AddAddress($to_email);
   		
    		
				header('Content-Type: application/json');
				
				if(!$mail->Send())
	    			echo json_encode(array("result"=>0, "error"=>$mail->ErrorInfo));
	    	else
	    			echo json_encode(array("result"=>1));
	    	exit;
    }
    public function forwardEmailAction() {
    		$emailid=$_REQUEST['emailid'];
    		$email = $_REQUEST['email'];

    		$db = Zend_Db_Table::getDefaultAdapter();    		
    		$emailinfo = $db->fetchRow('select * from emailbox where id='.$emailid);
    		$account = $db->fetchRow("select * from account where email='".$emailinfo['email']."'");
    		
    		$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				
				$mail = new PHPMailer();
    		
    		$mail->IsSMTP();
    		$mail->SMTPAuth = true;
    		$mail->SMTPSecure = $this->smtp_encryption[$account['email_type']];
    		$mail->Host = $this->smtp[$account['email_type']];
    		if($this->smtp_encryption[$account['email_type']]=="ssl")
    				$mail->Port = 465;
    		else
    				$mail->Port = 587;
    		$mail->IsHTML(true);
    		$mail->Username = $account['email'];
    		$mail->Password = $account['password'];
    		$mail->SetFrom($account['email']);
    		$mail->Subject = $emailinfo['mail_subject'];
    		$mail->Body = $emailinfo['mail_body'];
    		$mail->AddAddress($email);

				header('Content-Type: application/json');
				
				if(!$mail->Send())
	    			echo json_encode(array("result"=>0, "error"=>$mail->ErrorInfo));
	    	else
	    			echo json_encode(array("result"=>1));
	    	exit;
    }
    public function get_string_bewteen($string, $start, $end) {
    		$string = " ".$string;
		    $ini = strpos($string,$start);
		    if ($ini == 0) return "";
		    $ini += strlen($start);
		    $len = strpos($string,$end,$ini) - $ini;
		    return substr($string,$ini,$len);
    }
    public function replyEmailAction() {
    		$emailid=$_REQUEST['emailid'];
    		$email = $_REQUEST['email'];
    		$message = $_REQUEST['message'];
    		
    		$db = Zend_Db_Table::getDefaultAdapter();    		
    		$emailinfo = $db->fetchRow('select * from emailbox where id='.$emailid);
    		$account = $db->fetchRow("select * from account where email='".$emailinfo['email']."'");
    		
    		$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				
				$mail = new PHPMailer();
    		
    		$mail->IsSMTP();
    		$mail->SMTPAuth = true;
    		$mail->SMTPSecure = $this->smtp_encryption[$account['email_type']];
    		$mail->Host = $this->smtp[$account['email_type']];
    		if($this->smtp_encryption[$account['email_type']]=="ssl")
    				$mail->Port = 465;
    		else
    				$mail->Port = 587;
    		$mail->IsHTML(true);
    		$mail->Username = $account['email'];
    		$mail->Password = $account['password'];
    		$mail->SetFrom($account['email']);
    		$mail->Subject = "Reply on ".$emailinfo['mail_subject'];
    		$mail->Body = $message;
    		$mail->AddAddress(self::get_string_bewteen($email, '<', '>'));
    		
				header('Content-Type: application/json');
				
				if(!$mail->Send())
	    			echo json_encode(array("result"=>0, "error"=>$mail->ErrorInfo));
	    	else
	    			echo json_encode(array("result"=>1));
	    	exit;
    }
    public function getFeedingEmailAction() {
    		$emailid = $_REQUEST['emailid'];
    		header('Content-Type: application/json');    		
    		$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$emails = $db->fetchRow('select * from emailbox where id='.$emailid);
    		echo json_encode($emails);
	    	exit;	
    }
    public function feedingFokusEmailAction() {    		
    		$fokus_list = json_decode($_POST['fokuslist']);
    		header('Content-Type: application/json');    		
    		$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
			// open connection
				foreach($fokus_list as $fokus) {
						$where = "where status='moved' and email='".$fokus->email."'";
						
						if(isset($fokus->email_from_address) && isset($fokus->email_from_domain))
								$where .= ' and LOWER(mail_from) like "%'.strtolower($fokus->email_from_address).'%"';
						else if(isset($fokus->email_from_address))
								$where .= ' and LOWER(mail_from) like "%'.strtolower($fokus->email_from_address).'%"';
						else if(isset($fokus->email_from_domain))
								$where .= ' and LOWER(mail_from) like "%'.strtolower($fokus->email_from_domain).'%"';
								
						if(isset($fokus->reply_only) && $fokus->reply_only=="on")
								$where .= ' and LOWER(mail_from) not like "%no-reply%"';
						
						if(isset($fokus->email_keyword)) 
								$where .= ' and LOWER(mail_subject) like "%'.strtolower($fokus->email_keyword).'%"';
						
						switch($fokus->period_type) {
								case "continous":
								case "advanced":
										if(isset($fokus->date_from))
    										$where .= ' and mail_date >="'.date('Y-m-d',strtotime($fokus->date_from)).'"';
		    						if(isset($fokus->date_to))
		    								$where .= ' and mail_date <="'.date('Y-m-d',strtotime($fokus->date_to)).'"';
										break;
								case "next_24hours":
										if(isset($fokus->date_from))
    										$where .= ' and mail_date >= "'.date('Y-m-d',strtotime($fokus->_createdAt)-86400).'"';    								
		    						if(isset($fokus->date_to))
		    								$where .= ' and mail_date <= "'.date('Y-m-d',strtotime($fokus->_createdAt)+86400).'"'; 
										break;
								case "next_week":
										if(isset($fokus->date_from))
		    								$where .= ' and mail_date >= "'.date('Y-m-d',strtotime($fokus->_createdAt)-86400).'"';
		    						if(isset($fokus->date_to))
		    								$where .= ' and mail_date <= "'.date('Y-m-d',strtotime($fokus->_createAt)+691200).'"';
										break;
						}
						
						if(isset($fokus->time_from))
								$where .= ' and mail_time >="'.$fokus->time_from.'"';
						if(isset($fokus->time_to))
								$where .= ' and mail_time <="'.$fokus->time_to.'"';
						if(isset($fokus->weekday) && is_array(json_decode($fokus->weekday)) && count(json_decode($fokus->weekday))>0)
								$where .=" and mail_weekday in(".implode(',',json_decode($fokus->weekday)).")";
								
						$emails = $db->fetchAll('select id,mail_subject, mail_date, mail_from from emailbox '.$where.' order by mail_date desc limit 20');

						foreach($emails as $email) {
								$temp=array();
								$temp['emailid']=$email['id'];
								$temp['subject']=$email['mail_subject'];
								$temp['date']=$email['mail_date'];
								$temp['from']=$email['mail_from'];
								$temp['fokus_color'] = $fokus->label_color;
								$temp['fokus_name'] = $fokus->fokusname;
								$sent_time = strtotime($email['mail_date']);
		            if(date('Y-m-d')==date('Y-m-d',$sent_time))            
		            		$temp['show_date']="Today";
		            else if(strtotime(date('Y-m-d 00:00:00'))-86400< $sent_time) {
		            		$temp['show_date']="Yesterday";
		            } else if(strtotime(date('Y-01-01 00:00:00'))< $sent_time) {
		            		$temp['show_date']=date('M d',$sent_time);
		            } else {
		            		$temp['show_date']=date('m/d/y',$sent_time);
		            }
		            
								$result[]=$temp;
						}
				}
				function sortByOrder($a, $b) {
		    		if(strtotime($a['date']) < strtotime($b['date'])) return true;
				    else return false;
				}
				usort($result, 'sortByOrder');
    		echo json_encode($result);
	    	exit;	
    }

    public function feedingEmailAction() {
    		$type = $_REQUEST["email_type"];
    		$email = $_REQUEST["email"];
    		$password = $_REQUEST["password"];
    		$imap = new Imap($this->mailbox[$type], $email, $password, $this->encryption[$type]);
				$uids_list = Application_Model_Emailbox::get_uidsbyemail($email);
				if($imap->isConnected()) {
						$email_uids = $imap->searchMessages();
						$index=0;
						foreach($email_uids as $uid) {
								if(!in_array($uid, $uids_list)) {												
										$inbox = $imap->formatMessage($uid);
										Application_Model_Emailbox::add_email($email, $inbox);	
										$index++;
										if($index>=25) break;
								}										
						}
				}
				exit();
    }
    public function feedingAllEmailAction() {
    		$email_accounts = Application_Model_Emailaccount::get_all();
    		
    		$curly = array();
    		    		
    		$action_helper = new Zend_View_Helper_Url();				
    		$mh = curl_multi_init();

    		foreach($email_accounts as $account) {
				    $curly[$account['email']]=curl_init();				    
				    $url = 'http://'.$_SERVER['SERVER_NAME'].$action_helper->url(array('controller' => 'api' , 'action' => 'feeding-email'), null, true)."?email_type=".$account['email_type']."&email=".$account['email']."&password=".$account['password'];
				    curl_setopt($curly[$account['email']], CURLOPT_URL, $url);
				    curl_setopt($curly[$account['email']], CURLOPT_HEADER, 0);				    
				    
				    curl_multi_add_handle($mh, $curly[$account['email']]);
				}
				
				$running=null;
				do {
						curl_multi_exec($mh, $running);
				}while($running >0);
				
				curl_multi_close($mh);
    		exit();
    }
		public function addFeedbackAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$insert_data = array();
    		$insert_data['userid']=$data['userid'];
    		$insert_data['feedback']=$data['feedback'];
    		$db->insert("feedback",$insert_data);
    		
    		$result['result']=1;
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit();
		}
		public function registerUserAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$previous_data = $db->fetchRow("select * from users where username='".$data['username']."'");
    		if($previous_data) {
		    		$result['result']=0;
		    		$result['error']="Username is not valid.";
		    } else {
		    		$previous_data = $db->fetchRow("select * from users where email='".$data['email']."'");
		    		if($previous_data) {
		    				$result['result']=0;
		    				$result['error']="Email is not valid.";
		    		} else {
				    		$insert_data = array();
				    		$insert_data['username']=$data['username'];
				    		$insert_data['password']=$data['password'];
				    		$insert_data['email']=$data['email'];
				    		$insert_data['company']=$data['company'];
				    		$insert_data['account_level']=$data['account_level'];
				    		$db->insert("users",$insert_data);
				    		
				    		$result['result']=1;
				    }
		    }
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit();
		}
		public function upgradeUserAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		
    		$update_data = array();
    		$update_data['company']=$data['company'];
    		$update_data['account_level']=$data['account_level'];

    		$db->update("users",$update_data, "id=".$data['userid']);
    		
    		$result['result']=1;

    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit();
		}
		public function loginUserAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$user_data = $db->fetchRow("select * from users where username='".$data['username']."' and password='".$data['password']."'");
    		if($user_data) {
		    		$result['result']=1;
		    		$result['data']=$user_data;
		    } else {
		    		$result['result']=0;
		    		$result['error']="Username and password doesn't match.";
		    }
		    header('Content-Type: application/json');
    		echo json_encode($result);
				exit();
		}
		public function getFokusListAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$result = $db->fetchAll("select * from fokus where userid=".$data['userid']);
    		
		    header('Content-Type: application/json');
    		echo json_encode($result);
				exit;
		}
		
		public function createFokusAction() {			
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();    		
    		
    		$insert_data = array();
    		$insert_data['userid']=$data['userid'];
    		$insert_data['email']=$data['email'];
    		$insert_data['period_type']=$data['period_type'];
    		$insert_data['email_from_address']=$data['email_from_address'];
    		$insert_data['email_from_domain']=$data['email_from_domain'];
    		$insert_data['email_keyword']=$data['email_keyword'];
    		$insert_data['reply_only']=$data['reply_only'];
    		$insert_data['date_from']=$data['date_from'];
    		$insert_data['date_to']=$data['date_to'];
    		$insert_data['fokusname']=$data['fokusname'];
    		$insert_data['label_color']=$data['label_color'];
    		$insert_data['send_notification']=$data['send_notification'];
    		$insert_data['weekday']=$data['weekday'];
    		
    		$db->insert("fokus",$insert_data);

    		$result['result']=1;
    		
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit;
		}
		public function removeFokusAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();    		
    		
    		$db->insert("fokus",$data['fokusid']);
    		
    		$result['result']=1;
    		
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit;
		}
		public function updateFokusAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		$update_data = array();
    		$update_data['userid']=$data['userid'];
    		$update_data['email']=$data['email'];
    		$update_data['period_type']=$data['period_type'];
    		$update_data['email_from_address']=$data['email_from_address'];
    		$update_data['email_from_domain']=$data['email_from_domain'];
    		$update_data['email_keyword']=$data['email_keyword'];
    		$update_data['reply_only']=$data['reply_only'];
    		$update_data['date_from']=$data['date_from'];
    		$update_data['date_to']=$data['date_to'];
    		$update_data['fokusname']=$data['fokusname'];
    		$update_data['label_color']=$data['label_color'];
    		$update_data['send_notification']=$data['send_notification'];
    		$update_data['weekday']=$data['weekday'];
    		$db->update("fokus",$update_data, "id=".$data['id']);
    		
    		$result['result']=1;
    		
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit();
		}
		public function getFokusAction() {
				$data = $_REQUEST;
				$db = Zend_Db_Table::getDefaultAdapter();
    		$result=array();
    		
    		$result = $db->fetchRow("select * from fokus where id=".$data['fokusid']);
    		    		
    		header('Content-Type: application/json');
    		echo json_encode($result);
				exit;
		}
}