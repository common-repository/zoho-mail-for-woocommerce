<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<?php if (isset($_POST['zohomailwoo_submit']) && !empty($_POST)) {
      $nonce = sanitize_text_field($_REQUEST['_wpnonce']);
      if (!wp_verify_nonce($nonce, 'zohomailwoo_settings_nonce')) {
                  echo esc_html('<div class="error"><p><strong>'.esc_html__('Reload the page again','zoho-mail-for-woocommerce').'</strong></p></div>\n');
                } 
                else {
        $zohomailwoo_client_id = sanitize_text_field($_POST['zohomailwoo_client_id']);
        $zohomailwoo_client_secret = sanitize_text_field($_POST['zohomailwoo_client_secret']);
        $zohomailwoo_domain_name = sanitize_text_field($_POST['zohomailwoo_domain_name']);
        $state=base64_encode(implode(":", array($zohomailwoo_client_id, $zohomailwoo_client_secret,$zohomailwoo_domain_name,$nonce)));
		 
         ?>
         <head> <meta http-equiv="refresh" content="0; url=<?php $completeRedirectUrl=esc_url(admin_url().'admin.php?page=zohomail-config-woo'); $test="https://accounts.".ZohoMailWoo_Helper::$domainMapping[$zohomailwoo_domain_name]."/oauth/v2/auth?response_type=code&client_id=".$zohomailwoo_client_id."&scope=VirtualOffice.messages.CREATE,VirtualOffice.accounts.READ&redirect_uri=".$completeRedirectUrl."&prompt=consent&access_type=offline&state=".$state; echo esc_url($test);?>"/> </head>

		 
         <?php
		 return;

    }
    }
	$zohomailwoo_variables = array();
	$zohomailwoo_variables['order'] = wc_help_tip("<b>You can use the following variables:<br/>
                                            <p class='unset-fontsize'>
                                                {{order_number}}<br/>
                                                {{order_date}}<br/>
                                                {{net_total}}<br/>
                                                {{total_discount}}<br/>
                                                {{total_shipping}}<br />
                                                {{shipping_methods}}<br/>
                                                {{payment_details.method_title}}<br/>
                                            </p>");
	$zohomailwoo_variables['user'] = wc_help_tip("<b>You can use the following variables:<br/>
                                            <p class='unset-fontsize'>
                                                {{site_title}}
												{{site_url}}
												{{user_login}}
												{{user_reset_url}}<br/>
                                            </p>");											

	if(isset($_GET['code'])) {
		$completeRedirectUrl=esc_url(admin_url().'admin.php?page=zohomail-config-woo');
		$state=explode(":",base64_decode(wp_kses_post($_GET['state'])));
		$zohomailwoo_client_id = $state[0];
		$zohomailwoo_client_secret = $state[1];
		$zohomailwoo_domain_name = $state[2];
		$zohomailwoo_nonce = $state[3];
		
		$url = "https://accounts.".ZohoMailWoo_Helper::$domainMapping[$zohomailwoo_domain_name]."/oauth/v2/token?code=".wp_kses_post($_GET['code'])."&client_id=".$zohomailwoo_client_id."&client_secret=".$zohomailwoo_client_secret."&redirect_uri=".$completeRedirectUrl."&grant_type=authorization_code&state=".wp_kses_post($_GET['state']);
		
		$responseSending = wp_remote_post( $url, array() );
		$body = json_decode(wp_remote_retrieve_body( $responseSending ),true);
		
		$zohomailwoo_refresh_token = $body["refresh_token"];
		$zohomailwoo_access_token = $body["access_token"];
		update_option('zohomailwoo_client_id',base64_encode($zohomailwoo_client_id), false);
		update_option('zohomailwoo_client_secret',base64_encode($zohomailwoo_client_secret), false);
		update_option('zohomailwoo_refresh_token',base64_encode($zohomailwoo_refresh_token), false);
		update_option('zohomailwoo_access_token',base64_encode($zohomailwoo_access_token), false);
		update_option('zohomailwoo_domain_name',$zohomailwoo_domain_name, false);
		update_option('zohomailwoo_timestamp',time(), false);
		flush_rewrite_rules();
		if(get_option('zohomailwoo_mail_accid') != null){
			$zohomailAPI = new ZohoMailWoo_Api();
			
            $emailDetail = $zohomailAPI->getZohoMailAccountDetails();
            $emailIdList = $emailDetail["sendmail_details"];

			$zohomailwoo_mail_accid = get_option('zohomailwoo_mail_accid');
            $zohomailwoo_from_email_id = get_option('zohomailwoo_from_email_id');
			$isAccountAvailable =false;

            if(strcmp($emailDetail["account_id"],$zohomailwoo_mail_accid) == 0)
            {
                $isAccountAvailable = true;
            }
            
			if(!$isAccountAvailable)
			{
				delete_option('zohomailwoo_mail_accid');
				
				delete_option('zohomailwoo_from_name');
                delete_option("zohomailwoo_is_configured");
                foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue) {
                    delete_option('zohomailwoo_from_'.$zohomailTriggerKey);
                }
				
			}
			
		}
		?>
         <head> <meta http-equiv="refresh" content="0; url=<?php echo esc_url($completeRedirectUrl);?>"/> </head>
		 
         <?php
		return;
	}
	$is_mail_configured =false;
$is_account_configured =false;
if(!empty(get_option('zohomailwoo_refresh_token')))
{
		
		$zohomailAPI = new ZohoMailWoo_Api();
		$emailDetail = $zohomailAPI->getZohoMailAccountDetails();
        $emailIdList = $emailDetail["sendmail_details"];
        
        if(!empty($emailDetail["error"])) {
            $is_account_configured = false;
        }else {
            $is_account_configured =true;
            
            if(!empty(get_option('zohomailwoo_is_configured'))){
                $is_mail_configured = true;
            }
        }

		
       
}
	?>

    <div class="zmail-logo">

        <img src="<?php echo sanitize_text_field(plugin_dir_url( __FILE__ ). '../assets/images/icon.png'); ?>" width="40px"/>
        <div>
			<h2>Zoho Mail</h2>
            <span>by Zoho Mail</span>
        </div>
    </div>
	<div class="zmail-settings">
    <div class="zmail-main">
		<div purpose="accordion-box" id="zmail_account_setup_ab" is_configured="<?php echo esc_attr($is_account_configured);?>" class="accordion-body   <?php if(!$is_account_configured){?>zmail-accordion-active<?php }else{?> zmail-accordion-inactive<?php } ?>">
        <button purpose="configure-accordion" class="zmailaccordion__trigger <?php if(!$is_account_configured){?>zmailaccordion__trigger--expanded<?php }else{?> zmailaccordion__trigger--configured<?php } ?>">
            <span class="zmailaccordion__title">
			<?php echo esc_html__( 'Account Configuration' ,'zoho-mail-for-woocommerce');?>
            </span>
			<?php if($is_account_configured){?>
			<span class="zmailsetup-status zmailsetup-status--completed">
                <i class="zmaili-circle-check"></i>
                Authorised
            </span>
			<?php } ?>
            <i class="zmaili-angle-down"></i>
        </button>
        <form method="post" action="<?php echo esc_attr(sanitize_text_field($_SERVER["REQUEST_URI"])); ?>" class="zmailaccordion__content">
			<?php wp_nonce_field('zohomailwoo_settings_nonce'); ?>
            <span class="zmailsetup-step-desc">
				<?php echo esc_html__( 'Configure your Zoho Mail account in this plugin to send emails from WooCommerce transactional emails. To generate client ID and client secret, click' ,'zoho-mail-for-woocommerce');?>
                 <a href="javascript:void(0)" purpose="zmail_generate_client">here</a>.
                <span>
                    <button class="zmailbtn zmailbtn--flat zmailbtn--sm">
                        <span class="zmailbtn__text "><?php echo esc_html__( ' Help doc' ,'zoho-mail-for-woocommerce');?></span>
                    </button>
                </span>
            </span>
            <div class="zmailform-element">
                <label class="zmailinput-label"><?php echo esc_html__( 'Where is your account hosted?' ,'zoho-mail-for-woocommerce');?></label>
                <div class="zmailtext zmailselect">
                    <div class="zmailtext-field-wrapper">
                        
                        <div class="zmailtext__adorn "  style="width:100%;"><select class="form--input form--input--select" name="zohomailwoo_domain_name" <?php if($is_account_configured){?> disabled <?php }?>>
                        <option value="zoho.com" <?php if(get_option('zohomailwoo_domain_name') == "com") {?> selected="true"<?php } ?>>mail.zoho.com</option>
                        <option value="zoho.eu" <?php if(get_option('zohomailwoo_domain_name') == "eu") {?> selected="true"<?php } ?>>mail.zoho.eu</option>
                        <option value="zoho.in" <?php if(get_option('zohomailwoo_domain_name') == "in") {?> selected="true"<?php }?>>mail.zoho.in</option>
                        <option value="zoho.com.cn" <?php if(get_option('zohomailwoo_domain_name') == "com.cn") {?>selected="true"<?php }?>>mail.zoho.com.cn</option>
                        <option value="zoho.com.au" <?php if(get_option('zohomailwoo_domain_name') == "com.au"){?>selected="true"<?php }?>>mail.zoho.com.au</option>
						<option value="zoho.jp" <?php if(get_option('zohomailwoo_domain_name') == "com.au"){?>selected="true"<?php }?>>mail.zoho.jp</option>
						<option value="zohocloud.ca" <?php if(get_option('zohomailwoo_domain_name') == "com.au"){?>selected="true"<?php }?>>mail.zohocloud.ca</option>
						<option value="zoho.sa" <?php if(get_option('zohomailwoo_domain_name') == "com.au"){?>selected="true"<?php }?>>mail.zoho.sa</option>
                    </select></div>
                    </div>
                </div>
            </div>
            <div class="zmailform-element">
                <label class="zmailinput-label">
                    <?php echo esc_html__( 'Client ID' ,'zoho-mail-for-woocommerce');?>
                </label>
                <div class="zmailtext">
                    <div class="zmailtext-field-wrapper">
						<input class="zmailtext__box" type="text" value="<?php echo esc_attr(base64_decode(get_option('zohomailwoo_client_id'))) ?>" name="zohomailwoo_client_id" <?php if($is_account_configured){?> disabled <?php }?> placeholder="<?php echo esc_attr(__( 'Enter valid client ID' ,'zoho-mail-for-woocommerce'));?>"  id="zohomailwoo_client_id" required/>
                        
                    </div>
                </div>
            </div>
            <div class="zmailform-element">
                <label class="zmailinput-label">
                    <?php echo esc_html__( 'Client Secret' ,'zoho-mail-for-woocommerce');?>
                </label>
                <div class="zmailtext">
                    <div class="zmailtext-field-wrapper">
						<input type="password" value="<?php echo esc_attr(base64_decode(get_option('zohomailwoo_client_secret'))) ?>" name="zohomailwoo_client_secret" class="zmailtext__box" id="zohomailwoo_client_secret" <?php if($is_account_configured){?> disabled <?php }?> placeholder="<?php echo esc_attr__( 'Enter valid client secret' ,'zoho-mail-for-woocommerce');?>"  required/>
                        
                    </div>
                </div>
            </div>
            <div class="zmailform-element">
                <label class="zmailinput-label">
                   <?php echo esc_html__( 'Authorization redirect URL' ,'zoho-mail-for-woocommerce');?>
                </label>
                <div class="zmailtext">
                    <div class="zmailtext-field-wrapper">
						<input type="text" id="zmailwoo_authorization_uri" readonly="readonly" name="zohomailwoo_authorization_uri" class="zmailtext__box" value="<?php echo esc_url(admin_url().'admin.php?page=zohomail-config-woo'); ?>" class="regular-text" readonly="readonly" required/>
                        
                    </div>
                </div>
				<i class="form__row-info"><?php echo esc_html__( 'Copy this URL into Redirect URI field of your Client Id creation' ,'zoho-mail-for-woocommerce');?> 
				<a href="javascript:"  class="tib-copy" purpose="copyredirecturi" ><?php echo esc_html__( 'Copy text' ,'zoho-mail-for-woocommerce');?></a>
                </i>
            </div>
			
            <span class=" <?php if($is_account_configured){?> zmail-dispNone <?php }?>">
                <input type="submit"  name="zohomailwoo_submit" class="zmailbtn" value="Configure"/>
                    
            </span>
				
            <span class="zmailsetup-step-desc <?php if(!$is_account_configured){?> zmail-dispNone <?php }?>">
                <?php echo esc_html__( 'To modify this data and re-authorize,' ,'zoho-mail-for-woocommerce');?>
                <button class="zmailbtn zmailbtn--flat zmailbtn--sm" purpose="reauthorize">
                    <span class="zmailbtn__text "><?php echo esc_html__( 'Edit' ,'zoho-mail-for-woocommerce');?></span>
                </button>
            </span>
				
        </form>
        </div>
		<div purpose="accordion-box" id="zmail_send_mail_config_ab" is_configured="<?php echo esc_attr($is_account_configured && $is_mail_configured);?>" class="accordion-body <?php if( $is_account_configured){ ?> zmail-accordion-active<?php }else{?>zmail-accordion-inactive zmail-accordion-disabled<?php } ?>" >
		<button purpose="configure-accordion" class="zmailaccordion__trigger zmailaccordion__trigger--expanded">
            <span class="zmailaccordion__title">
                <?php echo esc_html__( 'Email Configuration' ,'zoho-mail-for-woocommerce');?>
            </span>
			<span class="zmailsetup-status zmailsetup-status--completed <?php if(!$is_mail_configured){?>zmail-dispNone<?php }?>">
                <i class="zmaili-circle-check"></i>
                Confgiured
            </span>
			<i class="zmaili-angle-down"></i>
        </button>
		<?php 
			if($is_account_configured)
			{
		?>
		<form id="mailconfigform" method="post" action="<?php echo esc_attr(sanitize_text_field($_SERVER["REQUEST_URI"])); ?>"  class="zmailaccordion__content">
			<?php wp_nonce_field('zohomailwoo_email_submit_nonce'); ?>
            <span class="zmailsetup-step-desc">
               <?php echo esc_html__( 'Configure your emails for each of the WooCommerce transaction emails.' ,'zoho-mail-for-woocommerce');?>
            </span>
			
			<div class="zmailform-element">
                
                <label class="zmailinput-label">
                    <?php echo esc_html__( 'From Name' ,'zoho-mail-for-woocommerce');?>
                </label>
                <div class="zmailtext">
                    <div class="zmailtext-field-wrapper">
                        <input type="text" name="zohomailwoo_from_name" id="zohomailwoo_from_name" <?php if($is_mail_configured){?>disabled="disabled" <?php }?> class="zmailtext__box" value="<?php echo esc_attr(get_option("zohomailwoo_from_name"));?>"/>
                    </div>
                </div>
            </div>
			<?php
            foreach(ZohoMailWoo_Helper::$zohomailWCTriggerMapping as $zohomailTriggerKey => $zohomailTriggerValue)
            {
                $zohomailwoo_from_email_id = get_option("zohomailwoo_from_".$zohomailTriggerKey);
            ?>
            <div class="zmailform-element" purpose="zmail-email-form">
                <label class="zmailinput-label"><?php echo esc_html__( $zohomailTriggerValue['label'] ,'zoho-mail-for-woocommerce');?></label>
                <div class="zmailtext zmailselect">
                    <div class="zmailtext-field-wrapper">
                        <select id="zohomailwoo_from_<?php echo esc_attr($zohomailTriggerKey);?>" id="zohomailwoo_from_<?php echo esc_attr($zohomailTriggerKey);?>" <?php if($is_mail_configured){?>disabled="disabled" <?php }?> value="<?php echo esc_attr(get_option('zohomailwoo_from_'.$zohomailTriggerKey)) ?>">
						    <option value="0" selected="selected">Choose</option>
						    <?php
							    foreach($emailIdList as $fromAddr)
							    {
						    ?>
							    <option value="<?php echo esc_attr($fromAddr);?>" <?php if(strcmp($fromAddr,$zohomailwoo_from_email_id) == 0) { ?> selected="true" <?php }?>><?php echo esc_html($fromAddr);?></option>
						    <?php
							    }
						    ?>
				
			            </select>
						
                    </div>
                </div>
                <p class="zmailinput-helptext zmailinput-helptext--error zmail-dispNone" purpose="email-error-text" style="display: block;"> </p>
            </div>
            <?php
            }
            ?>
			
			
			
            <div class="zmailbtn-block">
                <button class="zmailbtn  <?php if($is_mail_configured){?> zmail-dispNone <?php }?>"  id="zohomail_save_config"   name="zohomailwoo_email_submit"><span class="zmSpinLoader"></span><?php echo esc_html('Save');?></button>
                <button class="zmailbtn  <?php if(!$is_mail_configured){?> zmail-dispNone <?php }?>"  id="zohomail_test"   name="zohomailwoo_test"><span class="zmSpinLoader"></span><?php echo esc_html('Test');?></button>
            </div>
            <span purpose="zmail_modify_config" class="zmailsetup-step-desc  <?php if(!$is_mail_configured){?> zmail-dispNone <?php }?> ">
                To modify email configuration,                 
                <input type="button" class="zmailbtn zmailbtn--flat zmailbtn--sm" purpose="reconfigure" value="click here.">
            </span>
       
        </form>
		 <?php
		 }
		 ?>
		 </div>
		</div>
	<div class="zmailpopover" style="display:none" id="zmailalert">
        <header class="zmailpopover__header">
            <h3 purpose="alertheader"></h3>
            <div class="zmailpopover__header__actions">
                <button class="zmailbtn zmailbtn--default zmailbtn--sm" purpose="clsbtn">
                    <i class="zmaili-close"></i>
                </button>
            </div>
        </header>
        <div class="zmailpopover__content" purpose="alertcontent">
            
        </div>
    </div>
    </div>
