/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

var european_union_countries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];
var akeebasubs_business_state = '';
var akeebasubs_blocked_gui = false;
var akeebasubs_run_validation_after_unblock = false;

function blockInterface()
{
	(function($) {
		$('#ui-disable-spinner').css('display','inline-block');
		$('#subscribenow').attr('disabled','disabled');
		akeebasubs_blocked_gui = true;
	})(akeeba.jQuery);
}

function enableInterface()
{
	(function($) {
		$('#ui-disable-spinner').css('display','none');
		$('#subscribenow').removeAttr('disabled');
		akeebasubs_blocked_gui = false;
		if(akeebasubs_run_validation_after_unblock) {
			akeebasubs_run_validation_after_unblock = false;
			validateBusiness();
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
		var data = {
			// -- component parameters
			'option'	:	'com_akeebasubs',
			'view'		:	'subscribe',
			'action'	:	'validate',
			'format'	:	'json',
			// -- data
			'id'		:	akeebasubs_level_id,
			'username'	:	$('#username').val(),
			'name'		:	$('#name').val(),
			'email'		:	$('#email').val(),
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
			'coupon'	:	$('#coupon').val()
		};
		
		if($('#password')) {
			data.password = $('#password').val();
			data.password2 = $('#password2').val();
		}
		
		blockInterface();
		
		$.ajax({
			type: 'POST',
			url: akeebasubs_validate_url+'?option=com_akeebasubs&view=subscribe&action=validate&format=json',
			data: data,
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				if(msg.validation) applyValidation(msg.validation, callback_function);
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
			$('#password_invalid').css('display','inline');
		} else {
			if(password2 != password) {
				$('#password2_invalid').css('display','inline');
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
		if(name == '') {
			$('#name_empty').css('display','inline');
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
		var email = $('#email').val();
		if(email == '') {
			$('#email_empty').css('display','inline');
			return;
		} else if(!echeck(email)) {
			$('#email_invalid').css('display','inline');
			return;
		}
	})(akeeba.jQuery);
}

function validateAddress()
{
	(function($) {
		var address = $('#address1').val();
		var country = $('select[name$="country"]').val();
		var state = $('select[name$="state"]').val();
		var city = $('#city').val();
		var zip = $('#zip').val();
		
		var hasErrors = false;
		
		if(address == '') {
			$('#address1_empty').css('display','inline');
			hasErrors = true;
		} else {
			$('#address1_empty').css('display','none');
		}

		if(country == '') {
			$('#country_empty').css('display','inline');
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
						if(ccode == 'GR') { ccode = 'EL'; }
						$('#vatcountry').text(ccode);
					}
				}
			}
		}
		
		if( (country == 'US') || (country == 'CA') ) {
			$('#stateField').css('display','block');
			if(state == '') {
				$('#state_empty').css('display','inline');
				hasErrors = true;
			} else {
				$('#state_empty').css('display','none');
			}
		} else {
			$('#stateField').css('display','none');
		}
	
		if(state == '') {
			$('#state_empty').css('display','inline');
			hasErrors = true;
		} else {
			$('#state_empty').css('display','none');
		}

		if(zip == '') {
			$('#zip_empty').css('display','inline');
			hasErrors = true;
		} else {
			$('#zip_empty').css('display','none');
		}
		
		if(hasErrors) {
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
		// Do I have to show the business fields?
		if($('#isbusiness1').is(':checked')) {
			$('#businessfields').show();
		} else {
			$('#businessfields').hide();
		}
		
		// Do I have to show VAT fields?
		var country = $('select[name$="country"]').val();
		$('#vatfields').css('display','none');
		for(key in european_union_countries) {
			if(european_union_countries[key] == country) {
				$('#vatfields').css('display','block');
				var ccode = country;
				if(ccode == 'GR') { ccode = 'EL'; }
				$('#vatcountry').text(ccode);
			}
		}
		
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
			coupon: $('#coupon').val()
		};
		var hash = '';
		for(key in data) {
			hash += '|' + key + '|' + data[key];
		}
		hash += '|';
		
		if(akeebasubs_business_state == hash) return;
		
		akeebasubs_business_state = hash;

		validateForm();
	})(akeeba.jQuery);
}

function applyValidation(response, callback)
{
	(function($) {
		if($('#username_valid')) {
			if(response.username) {
				$('#username_valid').css('display','inline');
				$('#username_invalid').css('display','none');
			} else {
				$('#username_valid').css('display','none');
				$('#username_invalid').css('display','inline');
			}
		}
		
		if(response.name) {
			$('#name_empty').css('display','none');
		} else {
			$('#name_empty').css('display','inline');
		}
		
		if(response.email) {
			$('#email_invalid').css('display','none');
		} else {
			$('#email_invalid').css('display','inline');
		}
		
		if(response.address1) {
			$('#address1_empty').css('display','none');
		} else {
			$('#address1_empty').css('display','inline');
		}
		
		if(response.country) {
			$('#country_empty').css('display','none');
		} else {
			$('#country_empty').css('display','inline');
		}
		
		if(response.state) {
			$('#state_empty').css('display','none');
		} else {
			$('#state_empty').css('display','inline');
		}
			
		if(response.city) {
			$('#city_empty').css('display','none');
		} else {
			$('#city_empty').css('display','inline');
		}
		
		if(response.zip) {
			$('#zip_empty').css('display','none');
		} else {
			$('#zip_empty').css('display','inline');
		}

		if(response.businessname) {
			$('#businessname_empty').css('display','none');
		} else {
			$('#businessname_empty').css('display','inline');
		}
		
		if(response.occupation) {
			$('#occupation_empty').css('display','none');
		} else {
			$('#occupation_empty').css('display','inline');
		}
		
		if(response.vatnumber && ($('#vatfields').css('display') != 'none')) {
			$('#vat-status-invalid').css('display','none');
			$('#vat-status-valid').css('display','inline');
		} else {
			$('#vat-status-invalid').css('display','inline');
			$('#vat-status-valid').css('display','none');
		}
	})(akeeba.jQuery);
}

function applyPrice(response)
{
	(function($) {
		$('#akeebasubs-sum-net').text(response.net);
		$('#akeebasubs-sum-discount').text(response.discount);
		$('#akeebasubs-sum-vat').text(response.tax);
		$('#akeebasubs-sum-total').text(response.gross);
		
		if(response.gross <= 0) {
			$('#paymentmethod-container').css('display','none');
		} else {
			$('#paymentmethod-container').css('display','inline');
		}
	})(akeeba.jQuery);
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
		$('select[name$="country"]').change(validateAddress);
		$('select[name$="country"]').change(validateBusiness);
		$('select[name$="state"]').change(validateAddress);
		$('select[name$="state"]').change(validateBusiness);
		$('#address1').blur(validateAddress);
		$('#city').blur(validateAddress);
		$('#city').blur(validateBusiness);
		$('#zip').blur(validateAddress);
		$('#zip').blur(validateBusiness);
		$('#businessname').blur(validateBusiness);
		$('#isbusiness0').click(validateBusiness);
		$('#isbusiness1').click(validateBusiness);
		$('#businessname').blur(validateBusiness);
		$('#occupation').blur(validateBusiness);
		$('#vatnumber').blur(validateBusiness);
		if($('#coupon')) {
			$('#coupon').blur(validateBusiness);
		}
	});
})(akeeba.jQuery);

/**
(function($) {
})(akeeba.jQuery);
/**/