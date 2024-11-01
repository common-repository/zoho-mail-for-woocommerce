<?php

/**
 * Zoho mail WooCommerce plugin api class
 *
 * @author Zoho Mail
 */

if(!defined('ABSPATH')){
	exit;
}
class ZohoMailWoo_Api {
	const get_token_uri = "/oauth/v2/token";
	const get_agentlist_uri = "/api/accounts";

    public function __construct() {
		$instance = $this;
    }
	
	private function getAccountsUrl() {
		return "https://accounts.".ZohoMailWoo_Helper::$domainMapping[get_option("zohomailwoo_domain_name")];
	}
	
	private function getZohoMailUrl() {
        return "https://mail.".ZohoMailWoo_Helper::$domainMapping[get_option("zohomailwoo_domain_name")];
		return "https://mail.".ZohoMailWoo_Helper::$domainMapping[get_option("zohomailwoo_domain_name")];
	}
    
    public function getMailAgents() {
        $header = $this->getApiHeader();
        $urlToSend = $this->getZohoMailUrl().$this::get_agentlist_uri;
        $args = array(
                'httpversion' => '1.1',
                'headers' => $header,
                'method' => 'GET'
                );
        $response = wp_remote_get($urlToSend,$args);
        if (is_wp_error($response)) {
            return $response->get_error_message();
        } else {
            return wp_remote_retrieve_body($response);
        }
    }
	public function sendMail($mail_data)
	{
		$accountId = get_option("zohomailwoo_mail_accid");
		return $this->sendZohoMail($mail_data,$accountId);
	}
	public function sendTestMail($mail_data,$accountId)
	{
		return $this->sendZohoMail($mail_data,$accountId);
	}
	
	private function sendZohoMail($dataarray,$accountId){
		$header = $this->getApiHeader();
        $urlToSend = $this->getZohoMailUrl()."/api/accounts/".$accountId."/messages";
		$args = array(
				'body' => json_encode($dataarray),
				'headers' => $header,
				'method' => 'POST'
				);
		$mailRes = array();
		$response = wp_remote_post( $urlToSend, $args );
		$http_code = wp_remote_retrieve_response_code($response);
		if ($http_code != 200) {
			$mailRes['result'] = "failure";
			$mailRes["message"] =wp_remote_retrieve_body($response);
			$mailRes["http_code"] =$http_code;
			
		} else {
			$mailRes['result'] = "success";
		}
		return $mailRes;
	}


	private function getApiHeader() {
		return array(
				'Authorization' => 'Zoho-oauthtoken '.$this->getAccessToken(),
				'User-Agent' => 'ZohoMail_WooCommerce',
                'Content-Type' => 'application/json'
				);
		
	}
	
	private function getAccessToken() {
		if( empty(get_option('zohomailwoo_timestamp')) || time() - get_option('zohomailwoo_timestamp') > 3000) {
			$url = $this->getAccountsUrl()."/oauth/v2/token?refresh_token=".base64_decode(get_option('zohomailwoo_refresh_token'))."&client_id=".base64_decode(get_option('zohomailwoo_client_id'))."&client_secret=".base64_decode(get_option('zohomailwoo_client_secret'))."&redirect_uri=".admin_url()."/admin.php?page=zohomail-config-woo&grant_type=refresh_token";
			
			$bodyAccessTok = wp_remote_retrieve_body(wp_remote_post( $url));
			$respoJs = json_decode($bodyAccessTok);
			
			if($respoJs->access_token)
			{
				update_option('zohomailwoo_access_token',base64_encode($respoJs->access_token), false);
				update_option('zohomailwoo_timestamp',time(), false);
				return $respoJs->access_token;
			}
			else{
				return null;
			}
			
		}
		else{
			
			return base64_decode(get_option('zohomailwoo_access_token'));
		}
	}

	public function getZohoMailAccountDetails() {
        $response = json_decode($this->getMailAgents());
        $emailDetail = array();
        $emailArr = array();
        $accountId = '';
        if(!isset($response->data->errorCode) && isset($response->data)) {
            $jsonbodyAccounts = $response;
            
            for($i=0;$i<count($jsonbodyAccounts->data);$i++)
			{
                $emailData = $jsonbodyAccounts->data[$i];
                if(!empty($emailData->sendMailDetails))
                {
                    $accountId = $jsonbodyAccounts->data[$i]->accountId;
                    for($j=0;$j<count($jsonbodyAccounts->data[$i]->sendMailDetails);$j++) {
                        array_push($emailArr,$jsonbodyAccounts->data[$i]->sendMailDetails[$j]->fromAddress);
                    }
                }
			}
          
        } else {
			$emailDetail['error'] = json_encode($response->data);
		}
        $emailDetail['account_id'] = $accountId;
        $emailDetail["sendmail_details"] = $emailArr;

		

        return $emailDetail;
    }


    
   
}
	
