<?php
/*
* $Id: mailer.class.php
* Simple Mail email sending implementation base class
* @author: Dhens <rudenyl@gmail.c>
*/

defined('_PRIVATE') or die('Direct access not allowed');

final class Mailer
{
	/**
	Class constructor
		@public
	**/
	function __construct()
	{
	}

	/**
	Mail send short-hand using mail function
		@param $from string
		@param $to string
		@param $subject string
		@param $message string
		@param $html boolean
		@param $coded_message boolean
		@public
	**/
	function _( $from, $to, $subject, $message, $html=false, $coded_message=false )
	{
		$headers	= array();
		
		if ($html) {
			// To send HTML mail, the Content-type header must be set
			$headers[]	= 'MIME-Version: 1.0';
			$headers[]	= 'Content-type: text/html; charset=utf-8';
		}

		// Additional headers
		$headers[]	= 'To: <recipient/>';
		$headers[]	= 'From: <sender/>';
		
		/*
		// parse recipient email
		$to_email	= $to;
		if (preg_match('/"(.*?)" <(.*?)>/siU', $to, $match)) {
			if (isset($match[2])) {
				$to_email	= $match[2];
			}
		}
		// parse sender email
		$from_email	= $from;
		if (preg_match('/"(.*?)" <(.*?)>/siU', $from, $match)) {
			if (isset($match[2])) {
				$from_email	= $match[2];
			}
		}
		*/
		
		// load headers
		$headers	= implode("\r\n", $headers);
		
		$headers	= str_replace('<sender/>', $from, $headers);
		$headers	= str_replace('<recipient/>', $to, $headers);
		
		// decode 
		if ($coded_message) {
			$message	= base64_decode($message);
		}
		
		try {
			mail($to, $subject, $message, $headers);
			
			return true;
		}
		catch (Exception $err) {}
		
		return false;
	}
	
	/**
	Send mail using PHPMailer class
		@param $sender string
		@param $recipient string
		@param $subject string
		@param $message string
		@param $attachment mixed
		@public
	**/
	function PHPMailer( $sender, $recipient, $subject, $message, $html=true, $attachment=null )
	{
		include_once( dirname(__FILE__) .DS. '3rdparty' .DS. 'class.phpmailer.php' );
		
		$mail	= new PHPMailer();
		
		$mail->IsHTML($html);
		$mail->AddAddress($recipient);
		
		// get recipient
		$sender	= self::parseEmail($sender);
		foreach($sender as $email=>$name) {
			$mail->SetFrom($email, $name);
		}
		
		if ($attachment) {
			$mail->AddAttachment($attachment);
		}
		
		$mail->Subject	= $subject;
		$mail->Body		= $message;
		
		try {
			ob_start();
			@$mail->Send(); // send message
			$error	= ob_get_clean();
			
			return true;
		}
		catch (Exception $err) {}
		
		return false;
	}
	
	/**
	Get email address from string
		@param $value string
		@private
	**/
	private function parseEmail( $value )
	{
		$emails	= array();

		if(preg_match_all('/\s*"?([^><,"]+)"?\s*((?:<[^><,]+>)?)\s*/', $value, $matches, PREG_SET_ORDER) > 0) {
			foreach($matches as $m) {
				if(! empty($m[2])) {
					$emails[trim($m[2], '<>')]	= $m[1];
				}
				else {
					$emails[$m[1]]	= '';
				}
			} // foreach
		}
		
		return $emails;
	}
}
