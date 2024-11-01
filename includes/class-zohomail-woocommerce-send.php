<?php

/**
 * Zohomail WooCommerce plugin send mail class
 *
 * @author Zoho Mail
 */
if(!defined('ABSPATH')){
	exit;
}
if ( class_exists( 'WC_Email' ) ) :

class ZohoMailWoo_Send extends WC_Email {

    public $emailObject;
    
    public function __construct() {
		
		$emailHooks = ZohoMailWoo_Helper::$wcZohoMailEmailsMapping;

        $instance = $this;

        foreach ($emailHooks as $hook) {

            add_action( $hook['hook'], function($param1, $param2 = "",$param3 =null) use ($hook, $instance) {
				if($hook['wcEmail'] == 'WC_Email_Customer_Reset_Password'){
					$instance->handleUserAccount($hook['wcEmail'],true,$param1, $param2,$param3);
				}
				else if($hook['wcEmail'] == 'WC_Email_Customer_New_Account'){
					$instance->handleUserAccount($hook['wcEmail'],false,$param1, $param2,$param3);
					
				}
				else{
					$instance->handleOrderEmail($hook['hook'],$hook['wcEmail'],$param1,$param2);
				}
                
            }, 10, $hook['args']);
        }
		parent::__construct();
    }
	
	
	
	private function handleUserAccount($wcEmail,$isresetpassword,$param1,$param2='',$param3=false)  
    {
		
		$this->sendUserMail( $wcEmail,$isresetpassword,$param1,$param2,$param3);
		
		return [
                'success' => true,
                'status' => 200,
                'response' => 'success'
            ];
	}
	
	
	protected function sendUserMail($wcEmail,$isresetpassword,$param1,$param2='',$param3=false) {
		$user = new WP_User($param1);
		
        if (!$user) {
			
            return [
                'success' => false,
                'response' => 'User not found'
            ];
        }

		$emailObject = new $wcEmail();
		$emailObject->object = $user;
		$emailObject->user_email         = stripslashes( $emailObject->object->user_email );
		$emailObject->recipient  = $emailObject->user_email;
		if($isresetpassword){
			$user_login = $param1;
			$reset_key = $param2;
			$emailObject->object     = get_user_by( 'login', $user_login );
			$emailObject->user_id    = $emailObject->object->ID;
			$emailObject->user_login = $user_login;
			$emailObject->reset_key  = $reset_key;
			$emailObject->user_email = stripslashes( $emailObject->object->user_email );
			$emailObject->recipient  = $emailObject->user_email;
			
			
		}else{
			$user_id = $param1;
			$user_pass = $param2;
			if(is_array($param2)){
				$user_pass = $param2['user_pass'];
			}
			else {
				$user_pass = $param2;
			}
			$password_generated = $param3;
			$emailObject->user_pass          = $user_pass;
			$emailObject->user_login         = stripslashes( $emailObject->object->user_login );
			$emailObject->user_email         = stripslashes( $emailObject->object->user_email );
			$emailObject->recipient          = $emailObject->user_email;
			$emailObject->password_generated = $password_generated;
			
			
			$key = get_password_reset_key( $emailObject->object );
			if ( ! is_wp_error( $key ) ) {
				$action                 = 'newaccount';
				$emailObject->set_password_url = wc_get_account_endpoint_url( 'lost-password' ) . "?action=$action&key=$key&login=" . rawurlencode( $emailObject->object->user_login );
			} else {
				$emailObject->set_password_url = wc_get_account_endpoint_url( 'lost-password' );
			}
			
		}
		
		
		add_filter( 'wp_mail_content_type', array( $emailObject, 'get_content_type' ) );

		
		$mail_callback_params = apply_filters( 'woocommerce_mail_callback_params', array( $emailObject->get_recipient(), $emailObject->get_subject(), $emailObject->style_inline($emailObject->get_content()), $emailObject->get_headers(), $emailObject->get_attachments() ), $emailObject );
		
		$fromAddress = get_option('zohomailwoo_from_name').'<'.$this->getFromEmailForWCEmail($wcEmail).'>';
		$data = array(
				"fromAddress"       => $fromAddress,
				"subject"    => $mail_callback_params[1]
				);
		$current_content_type = apply_filters('wp_mail_content_type', '');
		if($current_content_type== 'text/plain'){
			$data['content']   = $mail_callback_params[2];
			$data["mailFormat"]   = 'plaintext';
		}else{
			$data['content']   = $mail_callback_params[2];
			$data["mailFormat"]   = 'html';
		}				
		$to = explode( ',', $emailObject->recipient );
		if(!empty($to) && !is_array($to)) {
			$to = explode( ',', $to );
		} 
		$toAddresses = implode(',' ,$to);
		$data['toAddress'] = $toAddresses;
		
		$zohoMailAPI = new ZohoMailWoo_Api();
		$responseSending = $zohoMailAPI->sendMail($data);
		
		remove_filter( 'wp_mail_content_type', array( $emailObject, 'get_content_type' ) );
	}


	public function handleOrderEmail( $hook,$wcEmail,$param1,$param2) {
		$this->sendOrderMail( $hook,$wcEmail,$param1,$param2);
	}
	public function sendOrderMail( $hook,$wcEmail,$param1,$param2=null) {
		$emailObject = new $wcEmail();
		$customer_note  = null;
		if($wcEmail == 'WC_Email_Customer_Note') {
			$orderid                       = $param1['order_id'];
			
			$customer_note                 = $param1['customer_note'];
			$order                         = wc_get_order($orderid);
			$emailObject->object           = $order;
			$emailObject->customer_note    = $param1['customer_note'];
		}else if($wcEmail == 'WC_Email_Customer_Refunded_Order') {
			$orderid                       = $param1;
			$order                         = wc_get_order($orderid);
			$emailObject->object           = $order;
			$ispartial                     = true;
			if($hook == 'woocommerce_order_fully_refunded_notification'){
				$ispartial=false;
			}
			$order->refund                 = wc_get_order($param2);
			$emailObject->partial_refund   = $ispartial;
			$emailObject->id               = $emailObject->partial_refund ? 'customer_partially_refunded_order' : 'customer_refunded_order';
			
			$refund_detail = array();
			$refund_detail['is_refund'] = true;
			$refund_detail['is_partial'] = $ispartial;
			
		}else{
			$orderid = $param1;
			$order = wc_get_order($orderid);
			$emailObject->object=$order;
		}
		
		if($wcEmail == 'WC_Email_New_Order'){
			$email_already_sent = $order->get_new_order_email_sent();
			if ( $email_already_sent && ! apply_filters( 'woocommerce_new_order_email_allows_resend', false ) ) {
				return;
			}
		}
		$is_admin_email = false;
		if($wcEmail == 'WC_Email_New_Order' || $wcEmail == 'WC_Email_Failed_Order' || $wcEmail == 'WC_Email_Cancelled_Order'){
			$recipient =  $emailObject->get_option( 'recipient', get_option( 'admin_email' ) );
			$is_admin_email = true;
		}
		else {
			$recipient = $order->get_billing_email();
		}
		$emailObject->recipient  = $recipient;
		
		if($wcEmail != 'WC_Email_Customer_Invoice'){
			$sendmail = $emailObject->is_enabled() && !empty($emailObject->get_recipient());
		}else {
			$sendmail = true;
		}
		
		if(!$sendmail){
			return;
		}
		$data = array();
		$zmtoearr = array();
		if($is_admin_email){
			
			foreach ( explode( ',', $recipient ) as $email ) {

                if ( filter_var( trim(  $email ), FILTER_VALIDATE_EMAIL ) ) 
				{
					array_push($zmtoearr,$email);
                }
            }
			$toAddress = implode(',',$zmtoearr);
		}
		else{
			$toAddress = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name().'<'.$order->get_billing_email().'>'; 
		}
		
		
		
		$fromAddress = get_option('zohomailwoo_from_name').'<'.$this->getFromEmailForWCEmail($wcEmail).'>';
		$data['toAddress'] = $toAddress;
		$data['fromAddress'] = $fromAddress;
		
		
		$zohoMailAPI = new ZohoMailWoo_Api();
		
		
		$emailObject->setup_locale();
		$emailObject->recipient                      = $recipient;
		$emailObject->placeholders['{order_date}']   = wc_format_datetime( $emailObject->object->get_date_created() );
		$emailObject->placeholders['{order_number}'] = $emailObject->object->get_order_number();
		add_filter( 'wp_mail_content_type', array( $emailObject, 'get_content_type' ) );
		$mail_callback_params = apply_filters( 'woocommerce_mail_callback_params', array( $emailObject->get_recipient(), $emailObject->get_subject(), $emailObject->style_inline($emailObject->get_content()), $emailObject->get_headers(), $emailObject->get_attachments() ), $emailObject );
		
		$current_content_type = apply_filters('wp_mail_content_type', '');
		remove_filter( 'wp_mail_content_type', array( $emailObject, 'get_content_type' ) );
		$data["subject"]    = $mail_callback_params[1];
		if($current_content_type== 'text/plain'){
			$data["content"]   = $mail_callback_params[2];
			$data["mailFormat"]   = 'plaintext';
		}else{
			$data["content"]   = $mail_callback_params[2];
			$data["mailFormat"]   = 'html';
		}
        
		$responseSending = $zohoMailAPI->sendMail($data);
		
        
		$emailObject->restore_locale();
		
		if($wcEmail == 'WC_Email_New_Order'){
		$order->update_meta_data( '_new_order_email_sent', 'true' );
				$order->save();
		}
		
	}



	private function getProductImage($product, $size = 'thumbnail')
    {
        if ($product->get_image_id()) {

            $image = wp_get_attachment_image_src( $product->get_image_id(), $size, false );
            list( $src, $width, $height ) = $image;

            return $src;
        } else if ($product->get_parent_id()) {

            $parentProduct = wc_get_product( $product->get_parent_id() );
            if ( $parentProduct ) {

                return $this->getProductImage($parentProduct, $size);
            }
        }

        return null;
    }

	private function getFromEmailForWCEmail($wcEmail){
		$emailkey = str_replace("WC_Email_","",$wcEmail);
		
		return get_option("zohomailwoo_from_".$emailkey);
	}
	
	



}


endif;

