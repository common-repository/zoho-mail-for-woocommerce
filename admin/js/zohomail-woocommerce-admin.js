(function($) {
    'use strict';

    $(window).load(function() {
		$('[purpose="configure-accordion"]').click(function(e){
			var $self = $(this);
			$("#zmailwooalert").remove();
			var $activeForm = $self.parent().siblings(".accordion-body");
			if(!$self.closest('[purpose=accordion-box]').hasClass('zmail-accordion-disabled')){
				$.each($activeForm,function(index,obj){
					if($(obj).attr("is_configured") === 1){
						$(obj).find('[purpose=configure-accordion]').addClass("zmailaccordion__trigger--configured");
					}
				});
				
				$self.removeClass("zmailaccordion__trigger--collapsed  zmailaccordion__trigger--configured").addClass("zmailaccordion__trigger--expanded");
				$self.parent().siblings(".accordion-body").removeClass("zmail-accordion-active").addClass("zmail-accordion-inactive");
				$self.parent(".accordion-body").removeClass("zmail-accordion-inactive").addClass("zmail-accordion-active");
			}
			
		});
		$('[purpose="zmail-woo-mail-config"]').click(function(e){
			var $self = $(this);
			
			if($self.is(":checked"))
			{
				$self.closest(".zmailform-element-block--column").removeClass("zmail-checkbox-inactive").addClass("zmail-checkbox-active");
			}
			else
			{
				$self.closest(".zmailform-element-block--column").removeClass("zmail-checkbox-active").addClass("zmail-checkbox-inactive");
			}
			
			
		});
        $('[purpose=zmail_template_validate]').click(function(e) {
			e.preventDefault();
			$("#zmailwooalert").remove();
			var $templateForm = $(this).closest('[purpose=zmail-template-form]');
			var $input = $templateForm.find(".template_key")[0];
			$self = $(this);

            var templatename = $($input).attr("template_key");
			var templatekey = $($input).val();
            var data = {
                action: "zmailmail_validate_template",
                template_name: templatename,
				template_key:  templatekey 
            }
			data['_wpnonce']=$("#zmail_email_config_ab").find("[name=_wpnonce]").val();
			data['_wp_http_referer']=$("#zmail_email_config_ab").find("[name=_wp_http_referer]").val();
			var $self = $(this);

            $.post(ajaxurl, data).done(function(data) {
				data = $.parseJSON(data);
				if(data.result == "warning")
				{
					var $zmailalert = $("#zmailalert").clone();
					$zmailalert.attr("id","zmailwooalert");
					if(Object.keys(data.required).length >0){
						$zmailalert.find("[purpose=alertcontent]").html(data.errorMsg);
						$("#zmail_email_config_ab").append($zmailalert);
						$("#zmailwooalert").offset($self.position());
						$("#zmailwooalert").find("[purpose=clsbtn]").on('click', function () {
							$zmailalert.slideUp('fast', function () {
								$zmailalert.remove();
							});
						});
						var $varContent = $("<b>");
						
						$.each( data.required, function( key, value ) {
							if(typeof(value) === "string"){
								$varContent.append("{{"+value+"}}<br/>");
							}
							if(typeof(value) === "object"){
								$varContent.append("{{#"+key+"}}<br/>");
								$.each( value, function( objkey, objvalue ){
									$varContent.append(" <span class='zmail-tab-space'> {{this."+objvalue+"}}</span><br/>");
								});
								$varContent.append("{{/"+key+"}}<br/>");
							}
						});
						$("#zmailwooalert").find(".zmailpopover__content").append($varContent);
						$zmailalert.show();
					}
					
				}
				else if(data.result !== "success")
				{
					var error = '<span>'+data.errorMsg+'<span>';
					
					$templateForm.find('[purpose=template-error-text]').html(error).show().addClass("zmailinput-helptext--error");
					
					
				}
				else{
					var error = '<span>Saved successfully<span>';
					$templateForm.find('[purpose=template-error-text]').html(error).show().addClass("zmailinput-helptext--success").removeClass("zmailinput-helptext--error");
					
				}
				
				 
                
            });
        });
		$('#zohomail_save_config').click(function(e) {
			e.preventDefault();
			$("[purpose=email-error-text]").hide();
			var $self= $(this);
			if($self.hasClass("zmLoading")){
				return;
			}
			$self.addClass("zmLoading");
			
			var zmailFromName = $("#zohomailwoo_from_name").val();
			var $zmail_wc_triggers = ["New_Order","Cancelled_Order","Failed_Order","Customer_On_Hold_Order","Customer_Processing_Order","Customer_Completed_Order","Customer_Refunded_Order","Customer_Invoice","Customer_Note","Customer_Reset_Password","Customer_New_Account"];
			var $hasError = false;
			$.each($zmail_wc_triggers,function(index,trigger){
				var zmailFromAddress = $("#zohomailwoo_from_"+trigger).val();
				if(!zmailValidateEmail(zmailFromAddress)) {
					var $emailForm = $("#zohomailwoo_from_"+trigger).closest('[purpose=zmail-email-form]');
					var error = '<span>Invalid email<span>';
					$emailForm.find('[purpose=email-error-text]').html(error).show();
					$hasError = true;
				}
			});
			if($hasError){
				$self.removeClass("zmLoading");
				return;
			}

            var data = {
                action: "zohomailwoo_save_mailaccount",
				zohomailwoo_from_name: zmailFromName
            }
			$.each($zmail_wc_triggers,function(index,trigger){
				data['zohomailwoo_from_'+trigger] = $("#zohomailwoo_from_"+trigger).val();
			});
			data['_wpnonce']=$("#mailconfigform").find("[name=_wpnonce]").val();
			data['_wp_http_referer']=$("#mailconfigform").find("[name=_wp_http_referer]").val();
			
		
            $.post(ajaxurl, data).done(function(data) {
				data = $.parseJSON(data);
				
				$self.removeClass("zmLoading");
				if(data.result === "failure"){
					if(data.errorMsg){
						var notice = $('<div class="notice notice-error is-dismissible"><p>' + data.errorMsg + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
					}
					if(data.failed_categories) {
						$.each(data.failed_categories,function(index,category){
							var $emailForm = $("#zohomailwoo_from_"+category.type).closest('[purpose=zmail-email-form]');
							var error = '<span>'+category.errorMsg+'<span>';
							$emailForm.find('[purpose=email-error-text]').html(error).show();
							$("#zohomailwoo_from_"+category.type).focus();
						});
						var notice = $('<div class="notice notice-error is-dismissible"><p>Some error occured, please check in form</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
					}
					$('#wpbody-content').prepend(notice);
						$(".wp-toolbar").scrollTop(0);
						notice.on('click', '.notice-dismiss', function () {
							notice.slideUp('fast', function () {
								notice.remove();
							});
						});
					

				}
				else{
					var notice = $('<div class="notice is-dismissible"><p>Plugin configured successfully</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
					$('#wpbody-content').prepend(notice);
					$.each($zmail_wc_triggers,function(index,item){
						$("#zohomailwoo_from_"+item).attr("disabled","disabled");
					});
					$("#zohomailwoo_from_name").attr("disabled","disabled");
			
					$('#zohomail_test').removeClass('zmail-dispNone');
					$('#zohomail_save_config').addClass('zmail-dispNone');
					notice.on('click', '.notice-dismiss', function () {
						notice.slideUp('fast', function () {
								notice.remove();
							});
						});
					$(".wp-toolbar").scrollTop(0);
				}
                
            });
        });
		$('[purpose=mail_content_confiugration] [type=radio]').click(function(){
			var $self = $(this);
			var $mail_type = $self.attr("mail_type")
			if($mail_type && $mail_type === "woo_mail"){
				var cnfrm = confirm('Are you sure?');
				if(cnfrm != true)
				{
					return false;
				}
				else {
					var $input = $(this).parent('[purpose=zmail-template-form]').find(".template_key")[0];
					var templatename = $($input).attr("template_name");
					var $temp_config = $self.closest("[purpose='mail_template_config']");
					var data = {
						action: "zmailmail_reset_template",
						template_name:  $temp_config.attr("template_name")
						}
					data['_wpnonce']=$("#zmail_email_config_ab").find("[name=_wpnonce]").val();
					data['_wp_http_referer']=$("#zmail_email_config_ab").find("[name=_wp_http_referer]").val();
					$self.closest('[purpose=mail_template_config]').find('[purpose=zmail-template-form]').hide();
					$.post(ajaxurl, data).done(function(data) {
						
					});
				}
			}
			else{
				$self.closest('[purpose=mail_template_config]').find('[purpose=zmail-template-form]').show();
			}
			
		});
		$('[purpose=reauthorize]').click(function(e) {
			e.preventDefault();
			$("[name=zohomailwoo_domain_name]").removeAttr("disabled");
			$("#zohomailwoo_client_id").removeAttr("disabled");
			$("#zohomailwoo_client_secret").removeAttr("disabled");
			$("[name=zohomailwoo_submit]").parent().removeClass("zmail-dispNone");
			$(this).addClass("zmail-dispNone");
        });
		
		function zmailValidateEmail(email) {
			var regex = /^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        if(!regex.test(email)) {
           return false;
        }else{
           return true;
        }
      }
	  $('[purpose=reconfigure]').click(function(e) {
		e.preventDefault();
		$("#zohomailwoo_from_email_id").removeAttr("disabled");
		$("#zohomailwoo_from_name").removeAttr("disabled");
		var $zmail_wc_triggers = ["New_Order","Cancelled_Order","Failed_Order","Customer_On_Hold_Order","Customer_Processing_Order","Customer_Completed_Order","Customer_Refunded_Order","Customer_Invoice","Customer_Note","Customer_Reset_Password","Customer_New_Account"];
		$.each($zmail_wc_triggers,function(index,item){
			$("#zohomailwoo_from_"+item).removeAttr("disabled");
		});
		$('#zohomail_test').addClass('zmail-dispNone');
		$('#zohomail_save_config').removeClass('zmail-dispNone');
	});
      $('[purpose=copyredirecturi]').click(function(e) {
            var copyText = document.getElementById('zmailwoo_authorization_uri');
            		copyText.select();
            		copyText.setSelectionRange(0, copyText.value.length);
            		document.execCommand('copy');
      });
	  $("[purpose=zmail_generate_client]").click(function(){
		var $domain = $("[name=zohomailwoo_domain_name]").val();
		var $url = 'https://api-console.'+ $domain + '/add#web';
		window.open($url, '_blank').focus();
	  });
	  $('#zohomail_test').click(function(e) {
		e.preventDefault();
		var data = {
			action: "zohomail_test_woo",
			
		}
		if($(this).hasClass("zmLoading")){
			return;
		}
		$(this).addClass("zmLoading");
		var $self = $(this);
		data['_wpnonce']=$("#mailconfigform").find("[name=_wpnonce]").val();
		data['_wp_http_referer']=$("#mailconfigform").find("[name=_wp_http_referer]").val();
		$.post(ajaxurl, data).done(function(data) {
			data = $.parseJSON(data);
			$self.removeClass("zmLoading");
			if(data.result === "failure"){
				
				if(data.errorMsg){
					var notice = $('<div class="notice notice-error is-dismissible"><p>' + data.errorMsg + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
				}
				if(data.failed_categories) {
					$.each(data.failed_categories,function(index,category){
						var $emailForm = $("#zohomailwoo_from_"+category.type).closest('[purpose=zmail-email-form]');
						var error = '<span>'+category.errorMsg+'<span>';
						$emailForm.find('[purpose=email-error-text]').html(error).show();
						$("#zohomailwoo_from_"+category.type).focus();
					});
					var notice = $('<div class="notice notice-error is-dismissible"><p>Some error occured, please check in form</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
				}
				$('#wpbody-content').prepend(notice);
					$(".wp-toolbar").scrollTop(0);
					notice.on('click', '.notice-dismiss', function () {
						notice.slideUp('fast', function () {
							notice.remove();
						});
					});

			}
			else{
				var notice = $('<div class="notice is-dismissible"><p>Plugin configured successfully</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>');
				$('#wpbody-content').prepend(notice);
				notice.on('click', '.notice-dismiss', function () {
					notice.slideUp('fast', function () {
							notice.remove();
						});
					});
				$(".wp-toolbar").scrollTop(0);
			}
			
		});
		
	  });

    });
	
	
})(jQuery);