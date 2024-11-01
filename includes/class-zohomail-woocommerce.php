<?php

/**
 * ZohoMail WooCommerce plugin main class
 *
 * @author Zoho Mail
 */
 
 
if(!defined('ABSPATH')){
	exit;
}
class ZohoMailWoo {

	
	protected $loader;

	/**
	 * @var string zohomailwoo The string used to uniquely identify this plugin.
	 */
	protected $zohomailwoo;
	
	public static $woocommerce_loaded;


	
	public function __construct() {

		$this->get_zohomailwoo = 'zoho-mail-for-woocommerce';
		$this->prepare_admin();
		
	}
	public function initialize_hooks() {
		self::$woocommerce_loaded = did_action( 'woocommerce_loaded' ) > 0;
		
		if ( ! self::$woocommerce_loaded ) {
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-helper.php';
		if(!empty(get_option('zohomailwoo_access_token') && !empty(get_option('zohomailwoo_is_configured')))){
			add_filter( 'woocommerce_email_classes', array( $this, 'register_zohomailemail_hooks' ));
		}
	}

	public function register_zohomailemail_hooks($emails )
    {
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-helper.php';
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-api.php';
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-send.php';
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-newaccount-woocommerce.php';
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-invoice.php';
		
        
		$wcEmailHooks = ZohoMailWoo_Helper::$wcZohoMailEmailsMapping;

        foreach ($wcEmailHooks as $hook) {
			$priority = has_action($hook['hook'], array(  $emails[ $hook['wcEmail'] ], $hook['triggerAction']));
			
            remove_action($hook['hook'], array( $emails[ $hook['wcEmail'] ], $hook['triggerAction']), $priority);
			
        }
		
		$emails['WC_Email_Customer_New_Account']=new ZohoMailWoo_WC_Email_Customer_New_Account();
		$emails['WC_Email_Customer_Invoice']=new ZohoMailWoo_WC_Email_Customer_Invoice();
	   
		$zohomail_send = new ZohoMailWoo_Send();
		 
		
		return $emails;

	}

	
	public function zohomailwoo_save_mailaccount(){
		$response = json_decode('{}',true);
		$nonce = sanitize_text_field($_REQUEST['_wpnonce']);
		if (!wp_verify_nonce($nonce, 'zohomailwoo_email_submit_nonce')) {
			$this->writeErrorMsg($response,"Unauthorized access");
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-api.php';
		
		
		$zohomailFromName = sanitize_text_field($_POST["zohomailwoo_from_name"]);
		
		$emailCategoryMap = array();
		foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue){
			$zohomailFromAddress = sanitize_email($_POST["zohomailwoo_from_".$zohomailTriggerKey]);
			if (!filter_var($zohomailFromAddress, FILTER_VALIDATE_EMAIL)) {
				$this->writeErrorMsg($response,"Invalid ".$zohomailTriggerValue['label']." email id");
				return;
			}
			$categories = array();
			if(isset($emailCategoryMap[$zohomailFromAddress])){
				$categories = $emailCategoryMap[$zohomailFromAddress];
			}
			array_push($categories,$zohomailTriggerKey);
			$emailCategoryMap[$zohomailFromAddress] = $categories;

		}
		$zohomailAPI = new ZohoMailWoo_Api();
		$emailDetail = $zohomailAPI->getZohoMailAccountDetails();

		
		$isUpdated = false;
		
		$hasEmailError =false;
		$failedCategories = array();

		if(empty($emailDetail["error"])) {
			 
			foreach ($emailCategoryMap as $zohomailFromAddress => $categories){
				$mailRes = $this->sendTestMail($zohomailFromAddress,$categories,$zohomailAPI,$emailDetail["account_id"]);
				if($mailRes['result'] == 'failure'){
					$responseData = json_decode($mailRes["message"]);
					$moreInfo = isset($responseData->data->moreInfo) ? $responseData->data->moreInfo : 'Unknown error';
					$response['result'] = "failure";
					
					foreach($categories as $category) {
						$errorDetail = array();
						$errorDetail["errorMsg"] = $moreInfo;
						$errorDetail["type"] = $category;
						array_push($failedCategories, $errorDetail);
					}
					$hasEmailError = true;
				} 
			}


			if(!$hasEmailError){
				update_option("zohomailwoo_mail_accid",$emailDetail["account_id"],true);
				update_option("zohomailwoo_from_name",$zohomailFromName,true);
				update_option("zohomailwoo_is_configured",true,true);
				foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue){
					$zohomailFromAddress = sanitize_email($_POST["zohomailwoo_from_".$zohomailTriggerKey]);
					update_option("zohomailwoo_from_".$zohomailTriggerKey,$zohomailFromAddress,true);
				}
				$isUpdated = true;
				
			} else {
				$response['result'] = "failure";
				$response["failed_categories"] = $failedCategories;
			}
			
			
        }
		else{
			$response['result'] = "failure";
			$response["errorMsg"] = $emailDetail["error"];
		}
		
		echo wp_json_encode($response);
		 wp_die();
	}
	public function zohomail_test(){
		$response = json_decode('{}',true);
		$nonce = sanitize_text_field($_REQUEST['_wpnonce']);
		if (!wp_verify_nonce($nonce, 'zohomailwoo_email_submit_nonce')) {
			$this->writeErrorMsg($response,"Unauthorized access");
			return;
		}
		require_once plugin_dir_path( __FILE__ ) . '/class-zohomail-woocommerce-api.php';
		
		$zohomailFromName = get_option("zohomailwoo_from_name");
		
		
		
		$zohomailAPI = new ZohoMailWoo_Api();
		$hasEmailError =false;
		$failedCategories = array();
		
		
		$emailDetail = $zohomailAPI->getZohoMailAccountDetails();
		if(empty($emailDetail["error"])) {
			$emailIdList = $emailDetail["sendmail_details"];
			
			$emailCategoryMap = array();
			foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue){
				$zohomailFromAddress = get_option("zohomailwoo_from_".$zohomailTriggerKey);
				$categories = array();
				if(isset($emailCategoryMap[$zohomailFromAddress])){
					$categories = $emailCategoryMap[$zohomailFromAddress];
				}
				array_push($categories,$zohomailTriggerKey);
				$emailCategoryMap[$zohomailFromAddress] = $categories;
			}


			foreach ($emailCategoryMap as $zohomailFromAddress => $categories){
				$mailRes = $this->sendTestMail($zohomailFromAddress,$categories,$zohomailAPI,$emailDetail["account_id"]);
				
				if($mailRes['result'] == 'failure'){
					$responseData = json_decode($mailRes["message"]);
					$moreInfo = isset($responseData->data->moreInfo) ? $responseData->data->moreInfo : 'Unknown error';
					$response['result'] = "failure";
					
					foreach($categories as $category) {
						$errorDetail = array();
						$errorDetail["errorMsg"] = $moreInfo;
						$errorDetail["type"] = $category;
						array_push($failedCategories, $errorDetail);
					}
					$hasEmailError = true;
				} 

			}
			if(!$hasEmailError){
				$response['result'] = "success";
			}else {
				$response['result'] = "failure";
				$response["failed_categories"] = $failedCategories;
			}
			
        } else {
			$response['result'] = "failure";
			$response["errorMsg"] = $emailDetail["error"];
		}
		
		

		
		echo wp_json_encode($response);
		 wp_die();
	}


	private function prepare_admin()
    {
		add_action("wp_ajax_zohomailwoo_save_mailaccount" , array( $this,"zohomailwoo_save_mailaccount"));
		add_action("wp_ajax_zohomail_test_woo" , array( $this,"zohomail_test"));
		add_filter('plugin_action_links_' . ZOHOMAILWOO_PLUGIN_NAME_BASE_NAME, array( $this, 'plugin_settings_link' ));
		
	}
	

	public function plugin_settings_link( $links) {
		$settings_link = '<a href="' . admin_url() . 'admin.php?page=zohomail-config-woo">Settings</a>';
		array_unshift($links, $settings_link);
		return $links;
	}

	public function get_zohomailwoo() {
		return $this->get_zohomailwoo;
	}

	public function writeErrorMsg($response,$errorMsg){
		$response["result"] = "failure";
		$response["errorMsg"] = $errorMsg;
		echo wp_json_encode($response);
		wp_die();
	}
	public function sendTestMail($fromAddress,$categories,$zohoMailApi,$accountId)
    {
		$mail_data = array();
		$from = json_decode('{}');
		$from->address = $fromAddress;
		$emailDetail = array();
		$toArray = array();
		
		$mail_data['fromAddress'] = $fromAddress;
		$mail_data['toAddress'] = $fromAddress;
		$categoryDetails = '<ul dir="ltr">';
		foreach($categories as $category) {
			$categoryDetails = $categoryDetails . '<li>'.ZohoMailWoo_Helper::$zohomailWCTriggerMapping[$category]['label'].'</li>';
		}
		$categoryDetails = $categoryDetails. '</ul>';

		$mail_data['subject'] = 'This is a test email ';
		$mail_data['content'] = 'This is a test email for the following transactional '.(count($categories) == 1?'email':'emails').' :'.$categoryDetails;
		
		
		return $zohoMailApi->sendTestMail($mail_data,$accountId);
        
    }




}
