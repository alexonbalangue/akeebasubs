/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2012 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

var european_union_countries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];
var akeebasubs_business_state = '';
var akeebasubs_isbusiness = false;
var akeebasubs_blocked_gui = false;
var akeebasubs_run_validation_after_unblock = false;
var akeebasubs_cached_response = false;
var akeebasubs_valid_form = true;
var akeebasubs_personalinfo = true;
var akeebasubs_validation_fetch_queue = [];
var akeebasubs_validation_queue = [];
var akeebasubs_level_id = 0;
var akeebasubs_submit_after_validation = false;

function cacheSubmitAction(e)
{
	(function($) {
		e.preventDefault();
		akeebasubs_submit_after_validation = true;
		$('#subscribenow').attr('disabled','disabled');
	})(akeeba.jQuery);
}

function blockInterface()
{
	(function($) {
		$('#subscribenow').click(cacheSubmitAction);
		$('#ui-disable-spinner').css('display','inline-block');
		//$('#subscribenow').attr('disabled','disabled');
		akeebasubs_blocked_gui = true;
	})(akeeba.jQuery);
}

function enableInterface()
{
	(function($) {
		$('#subscribenow').unbind('click');
		$('#ui-disable-spinner').css('display','none');
		$('#subscribenow').removeAttr('disabled');
		akeebasubs_blocked_gui = false;
		if(akeebasubs_run_validation_after_unblock) {
			akeebasubs_run_validation_after_unblock = false;
			validateBusiness();
		} else {
			if(akeebasubs_submit_after_validation) {
				akeebasubs_submit_after_validation = false;
				setTimeout("(function($) {$('#subscribenow').click()})(akeeba.jQuery);", 100);
			}
		}
	})(akeeba.jQuery);
}

/**
 * Runs a form validation with the server and returns validation and, optionally,
 * price analysis information. If a callback_function is specified, it will be called
 * if the form is valid.
 * @param callback_function
 * @return
 */
function validateForm(callback_function)
{
	if(akeebasubs_blocked_gui) {
		akeebasubs_run_validation_after_unblock = true;
		return;
	}

	(function($) {
		if(akeebasubs_personalinfo) {
			var data = {
				'action'	:	'read',
				'id'		:	akeebasubs_level_id,
				'username'	:	$('#username').val(),
				'name'		:	$('#name').val(),
				'email'		:	$('#email').val(),
				'email2'	:	$('#email2').val(),
				'address1'	:	$('#address1').val(),
				'address2'	:	$('#address2').val(),
				'country'	:	$('select[name$="country"]').val(),
				'state'		:	$('select[name$="state"]').val(),
				'city'		:	$('#city').val(),
				'zip'		:	$('#zip').val(),
				'isbusiness':	$('#isbusiness1').is(':checked') ? 1 : 0,
				'businessname':	$('#businessname').val(),
				'occupation':	$('#occupation').val(),
				'vatnumber'	:	$('#vatnumber').val(),
				'coupon'	:	($("#coupon").length > 0) ? $('#coupon').val() : '',
				'custom'	:	{}
			};
		} else {
			var data = {
				'action'	:	'read',
				'id'		:	akeebasubs_level_id,
				'username'	:	$('#username').val(),
				'name'		:	$('#name').val(),
				'email'		:	$('#email').val(),
				'email2'	:	$('#email2').val(),
				'coupon'	:	($("#coupon").length > 0) ? $('#coupon').val() : '',
				'custom'	:	{}
			};
		}
		
		if($('#password')) {
			data.password = $('#password').val();
			data.password2 = $('#password2').val();
		}
		
		// Fetch the custom fields
		$.each(akeebasubs_validation_fetch_queue, function(index, function_name){
			var result = function_name();
			if( (result !== null) && (typeof result == 'object') ) {
				// Merge the result with the data object
				$.extend(data.custom, result);
			}
		});
		
		blockInterface();
		
		$.ajax({
			type: 'POST',
			url: akeebasubs_validate_url+'?option=com_akeebasubs&view=validate&format=json',
			data: data,
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				if(msg.validation) {
					msg.validation.custom_validation = msg.custom_validation;
					msg.validation.custom_valid = msg.custom_valid;
					applyValidation(msg.validation, callback_function);
				}
				if(msg.price) applyPrice(msg.price);
				enableInterface();
			},
			error: function(jqXHR, textStatus, errorThrown) {
				enableInterface();
			}
		});		
		
	})(akeeba.jQuery);
}

/**
 * Validates the password fields
 * @return
 */
function validatePassword()
{
	(function($) {
		if(!$('#password')) return;
		var password = $('#password').val();
		var password2 = $('#password2').val();
		
		$('#password_invalid').css('display','none');
		$('#password2_invalid').css('display','none');
		
		if(password == '') {
			$('#password_invalid').css('display','inline-block');
			akeebasubs_valid_form = false;
		} else {
			if(password2 != password) {
				$('#password2_invalid').css('display','inline-block');
				akeebasubs_valid_form = false;
			}
		}		
	})(akeeba.jQuery);
}

/**
 * Validates the (real) name
 * @return
 */
function validateName()
{
	(function($) {
		$('#name_empty').css('display','none');
		var name = $('#name').val();
		var invalidName = false;
		if(name == '') {
			invalidName = true;
		} else {
			name = ltrim(rtrim(name, " "), " ");
			var nameParts = name.split(' ');
			if(nameParts.length < 2) invalidName = true;
		}
		
		if(invalidName) {
			$('#name_empty').css('display','inline-block');
			akeebasubs_valid_form = false;
			return;
		}
	})(akeeba.jQuery);
}

/**
 * DHTML email validation script. Courtesy of SmartWebby.com (http://www.smartwebby.com/dhtml/)
 */

function echeck(str) {
	var at="@";
	var dot=".";
	var lat=str.indexOf(at);
	var lstr=str.length;
	var ldot=str.indexOf(dot);
	if (str.indexOf(at)==-1){
	   return false;
	}

	if (str.indexOf(at)==-1 || str.indexOf(at)==0 || str.indexOf(at)==lstr){
	   return false;
	}

	if (str.indexOf(dot)==-1 || str.indexOf(dot)==0 || str.indexOf(dot)==lstr){
	    return false;
	}

	 if (str.indexOf(at,(lat+1))!=-1){
	    return false;
	 }

	 if (str.substring(lat-1,lat)==dot || str.substring(lat+1,lat+2)==dot){
	    return false;
	 }

	 if (str.indexOf(dot,(lat+2))==-1){
	    return false;
	 }
	
	 if (str.indexOf(" ")!=-1){
	    return false;
	 }

	 return true;				
}

/**
 * Validates the email address
 * @return
 */
function validateEmail()
{
	(function($) {
		$('#email_empty').css('display','none');
		$('#email_invalid').css('display','none');
		$('#email2_invalid').css('display','none');
		var email = $('#email').val();
		var email2 = $('#email2').val();
		if(email == '') {
			$('#email_empty').css('display','inline-block');
			akeebasubs_valid_form = false;
			return;
		} else if(!echeck(email)) {
			$('#email_invalid').css('display','inline-block');
			akeebasubs_valid_form = false;
			return;
		} else {
			validateForm();
		}
	})(akeeba.jQuery);
}

function validateAddress()
{
	if(!akeebasubs_personalinfo) return;

	(function($) {
		var address = $('#address1').val();
		var country = $('select[name$="country"]').val();
		var state = $('select[name$="state"]').val();
		var city = $('#city').val();
		var zip = $('#zip').val();
		
		var hasErrors = false;
		
		if(address == '') {
			$('#address1_empty').css('display','inline-block');
			hasErrors = true;
		} else {
			$('#address1_empty').css('display','none');
		}

		if(country == '') {
			$('#country_empty').css('display','inline-block');
			hasErrors = true;
		} else {
			$('#country_empty').css('display','none');
			// If that's an EU country, show and update the VAT field
			if($('#vatfields')) {
				$('#vatfields').css('display','none');
				for(key in european_union_countries) {
					if(european_union_countries[key] == country) {
						$('#vatfields').css('display','block');
						var ccode = country;
						if(ccode == 'GR') {ccode = 'EL';}
						$('#vatcountry').text(ccode);
					}
				}
			}
		}
		
		/*
		if( (country == 'US') || (country == 'CA') ) {
			$('#stateField').css('display','block');
			if(state == '') {
				$('#state_empty').css('display','inline-block');
				hasErrors = true;
			} else {
				$('#state_empty').css('display','none');
			}
		} else {
			$('#stateField').css('display','none');
		}
		*/
	
		if(city == '') {
			$('#city_empty').css('display','inline-block');
			hasErrors = true;
		} else {
			$('#city_empty').css('display','none');
		}

		if(zip == '') {
			$('#zip_empty').css('display','inline-block');
			hasErrors = true;
		} else {
			$('#zip_empty').css('display','none');
		}
		
		if(hasErrors) {
			akeebasubs_valid_form = false;
			return;
		}
		
	})(akeeba.jQuery);
}

/**
 * Validates the business registration information and runs a price fetch
 * @return
 */
function validateBusiness()
{
	(function($) {
		if(akeebasubs_personalinfo) {
			// Do I have to show the business fields?
			if($('#isbusiness1').is(':checked')) {
				$('#businessfields').show();
			} else {
				$('#businessfields').hide();
				// If it's not a business validation, chain an address validation
				if(akeebasubs_blocked_gui) {
					akeebasubs_run_validation_after_unblock = true;
					return;
				} else {
					akeebasubs_valid_form = true;
					validateForm();
				}
				return;
			}
		} else {
			if(akeebasubs_blocked_gui) {
				akeebasubs_run_validation_after_unblock = true;
				return;
			} else {
				akeebasubs_valid_form = true;
				validateForm();
			}
			return;
		}
		
		// Do I have to show VAT fields?
		var country = $('select[name$="country"]').val();
		$('#vatfields').css('display','none');
		for(key in european_union_countries) {
			if(european_union_countries[key] == country) {
				$('#vatfields').css('display','block');
				var ccode = country;
				if(ccode == 'GR') {ccode = 'EL';}
				$('#vatcountry').text(ccode);
			}
		}
		
		if(akeebasubs_personalinfo) {
			// Make sure we don't do business validation / price check unless something's changed
			var vatnumber = '';
			if($('#vatnumber')) vatnumber = $('#vatnumber').val();
			
			var data = {
				country: $('select[name$="country"]').val(),
				state: $('select[name$="state"]').val(),
				city: $('#city').val(),
				zip: $('#zip').val(),
				isbusiness: $('#isbusiness1').is(':checked') ? 1 : 0,
				businessname: $('#businessname').val(),
				occupation: $('#occupation').val(),
				vatnumber: vatnumber,
				coupon: ($("#coupon").length > 0) ? $('#coupon').val() : ''
			};
		} else {
			var data = {
				coupon: ($("#coupon").length > 0) ? $('#coupon').val() : ''
			};
		}
		
		var hash = '';
		for(key in data) {
			hash += '|' + key + '|' + data[key];
		}
		hash += '|';
		
		if(akeebasubs_business_state == hash) {
			if(akeebasubs_isbusiness) return;
			akeebasubs_isbusiness = true;
		}
		
		akeebasubs_business_state = hash;

		validateForm();
	})(akeeba.jQuery);
}

function validateIsNotBusiness(e) {
	(function($) {
		$('#businessfields').hide();
		akeebasubs_cached_response.businessname = true;
		akeebasubs_cached_response.novatrequired = true;
		applyValidation(akeebasubs_cached_response);
		akeebasubs_isbusiness = false;
	})(akeeba.jQuery);
}

function applyValidation(response, callback)
{
	akeebasubs_cached_response = response;

	(function($) {
		akeebasubs_valid_form = true;
		if($('#username_valid')) {
			if(response.username) {
				$('#username_valid').css('display','inline-block');
				$('#username_invalid').css('display','none');
			} else {
				if( !$('#username').attr('disabled') || $('#username').attr('disabled') != 'disabled' ) {
					akeebasubs_valid_form = false;
				}
				
				$('#username_valid').css('display','none');
				$('#username_invalid').css('display','none');
				
				if($('#username').val() != '') {
					$('#username_invalid').css('display','inline-block');
				}
			}
		}
		
		if(response.name) {
			$('#name_empty').css('display','none');
		} else {
			akeebasubs_valid_form = false;
			$('#name_empty').css('display','inline-block');
		}
		
		if(response.email) {
			$('#email_invalid').css('display','none');
		} else {
			akeebasubs_valid_form = false;
			$('#email_invalid').css('display','inline-block');
		}
		
		if(response.email2) {
			$('#email2_invalid').css('display','none');
		} else {
			akeebasubs_valid_form = false;
			$('#email2_invalid').css('display','inline-block');
		}
		
		if(akeebasubs_personalinfo) {
			if(response.address1) {
				$('#address1_empty').css('display','none');
			} else {
				akeebasubs_valid_form = false;
				$('#address1_empty').css('display','inline-block');
			}
			
			if(response.country) {
				$('#country_empty').css('display','none');
			} else {
				akeebasubs_valid_form = false;
				$('#country_empty').css('display','inline-block');
			}
			
			if(response.state) {
				$('#state_empty').css('display','none');
			} else {
				if($('#state_empty').css('display') != 'none') {
					akeebasubs_valid_form = false;
				}
				$('#state_empty').css('display','inline-block');
			}
				
			if(response.city) {
				$('#city_empty').css('display','none');
			} else {
				akeebasubs_valid_form = false;
				$('#city_empty').css('display','inline-block');
			}
			
			if(response.zip) {
				$('#zip_empty').css('display','none');
			} else {
				akeebasubs_valid_form = false;
				$('#zip_empty').css('display','inline-block');
			}
	
			if(response.businessname) {
				$('#businessname_empty').css('display','none');
			} else {
				if($('#isbusiness1').is(':checked')) {
					akeebasubs_valid_form = false;
				}
				$('#businessname_empty').css('display','inline-block');
			}
			
			if(response.vatnumber && ($('#vatfields').css('display') != 'none')) {
				$('#vat-status-invalid').css('display','none');
				$('#vat-status-valid').css('display','inline-block');
			} else {
				$('#vat-status-invalid').css('display','inline-block');
				$('#vat-status-valid').css('display','none');
			}
			
			if(response.novatrequired) {
				$('#vat-status-invalid').css('display','none');
				$('#vat-status-valid').css('display','none');
			}
		}
		
		// Finally, apply the custom validation
		$.each(akeebasubs_validation_queue, function(index, function_name){
			var isValid = function_name(response);
			akeebasubs_valid_form = akeebasubs_valid_form & isValid;
		});
	})(akeeba.jQuery);
}

function applyPrice(response)
{
	(function($) {
		if($('#akeebasubs-sum-net').length > 0) {
			$('#akeebasubs-sum-net').text(response.net);
			$('#akeebasubs-sum-discount').text(response.discount);
			$('#akeebasubs-sum-vat').text(response.tax);
			$('#akeebasubs-sum-total').text(response.gross);

			if(response.gross <= 0) {
				$('#paymentmethod-container').css('display','none');
			} else {
				$('#paymentmethod-container').css('display','inline');
			}
		}
	})(akeeba.jQuery);
}

/**
 * Adds a function to the validation fetch queue
 */
function addToValidationFetchQueue(myfunction)
{
	if(typeof myfunction != 'function') return false;
	akeebasubs_validation_fetch_queue.push(myfunction);
}

/**
 * Adds a function to the validation queue
 */
function addToValidationQueue(myfunction)
{
	if(typeof myfunction != 'function') return false;
	akeebasubs_validation_queue.push(myfunction);
}


(function($) {
	$(document).ready(function(){
		$('#username').blur(validateForm);
		if($('#password')) {
			$('#password').blur(validatePassword);
			$('#password2').blur(validatePassword);
		}
		$('#name').blur(validateName);
		$('#email').blur(validateEmail);
		$('#email2').blur(validateEmail);
		if(akeebasubs_personalinfo) {
			$('select[name$="country"]').change(validateBusiness);
			$('select[name$="state"]').change(validateBusiness);
			$('#address1').blur(validateAddress);
			$('#city').blur(validateBusiness);
			$('#zip').blur(validateBusiness);
			$('#businessname').blur(validateBusiness);
			$('#isbusiness0').click(validateIsNotBusiness);
			$('#isbusiness1').click(validateBusiness);
			$('#vatnumber').blur(validateBusiness);
		}
		if($('#coupon').length > 0) {
			$('#coupon').blur(validateBusiness);
		}
		// Attach onBlur events to custom fields
		$('#signupForm *[name]').filter(function(index){
			return $(this).attr('name').substr(0, 7) == 'custom[';
		}).blur(validateForm);
		
		// Workaround for RocketTheme's fancy option hider
		var rokkedLabel = $('label[for="isbusiness1"]');
		if(rokkedLabel) {
			rokkedLabel.removeClass('rokradios');
			$('#isbusiness0').attr('style','');
		}
		
		if(akeebasubs_personalinfo) {
			if($('#isbusiness1').is(':checked')) {
				$('#isbusiness1').click();
			} else {
				$('#isbusiness0').click();
			}
		}
		
		// Disable form submit when ENTER is hit in the coupon field
		$('input#coupon').keypress(function(e){
			if ( e.which == 13 ) {
				validateForm();
				return false;
			}
		});
	});
})(akeeba.jQuery);

/**
(function($) {
})(akeeba.jQuery);
/**/

function rtrim (str, charlist) {
    charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '\\$1');
    var re = new RegExp('[' + charlist + ']+$', 'g');
    return (str + '').replace(re, '');
}

function ltrim (str, charlist) {
    charlist = !charlist ? ' \\s\u00A0' : (charlist + '').replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
    var re = new RegExp('^[' + charlist + ']+', 'g');
    return (str + '').replace(re, '');
}