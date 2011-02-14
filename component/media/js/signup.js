/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

var akeeba_subscriptions_ajax = '';

var european_union_countries = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GB', 'GR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'];

/**
 * Called whenever we need to do a composite validation / price calculation run
 */
function doValidation(e)
{
	(function($) {
		// Necessary when the country changes!
		showHideVATFields();
		showHideStateField();
	
		var isBusiness = $('#isbusiness1').is(':checked') ? 1 : 0;
		
		var data = {
			'option':		'com_akeebasubs',
			'view':			'levels',
			'action':		'validate',
			
			'username':		$('#username').val(),
			'password':		$('#password').val(),
			'password2':	$('#password2').val(),
			'name':			$('#name').val(),
			'email':		$('#email').val(),
			'address':		$('#address').val(),
			'address2':		$('#address2').val(),
			'country':		$('select[name$="country"]').val(),
			'state':		$('select[name$="state"]').val(),
			'city':			$('#city').val(),
			'zip':			$('#zip').val(),
			'isbusiness':	isBusiness,
			'businessname':	$('#businessname'),
			'occupation':	$('#occupation'),
			'vat':			$('#vat'),
			'coupon':		$('#coupon')
		};
		
		$.ajax('index.php',{
			'async':		false,
			'cache':		false,
			'data':			data,
			'dataType':		'json',
			'timeout':		10000,
			'type':			'post',
			'success':		function(msg, textStatus, xhr) {
				alert('success');
			},
			'error':		function(xhr, textStatus, errorThrown) {
				alert('error');
			}
		});
		
		$.post(akeeba_subscriptions_ajax, data, function(msg){
			alert('success');
		});
		// TODO Do an AJAX request and get the info back ;)
	})(akeeba.jQuery);
}

/**
 * Only runs the validation if it is a business registration. Also shows/hides relevant fields.
 */
function doBusinessValidation(e)
{
	(function($) {
		var isBusiness = $('#isbusiness1').is(':checked');
		if(isBusiness) {
			// Show the business fields
			$('#businessfields').show();
			
			// Do I have to show the VAT field?
			showHideVATFields();
			
			doValidation(e);
		} else {
			$('#businessfields').hide();
		}
	})(akeeba.jQuery);
}

function showHideVATFields()
{
	(function($) {
		var isBusiness = $('#isbusiness1').is(':checked');
		if(isBusiness) {
			// Do I have to show the VAT field?
			var country = $('select[name$="country"]').val();
			var isEUCountry = false;
			for (key in european_union_countries) {
	            if (european_union_countries[key] == country) {
	                isEUCountry = true;
	            }
	        }
			if(isEUCountry) {
				$('#vatfields').show();
				if(country == 'GR') {
					$('#vatcountry').text('EL');
				} else {
					$('#vatcountry').text(country)
				}
			} else {
				$('#vatfields').hide();
				$('#vatcountry').text('');
			}
		}
	})(akeeba.jQuery);
}

function showHideStateField()
{
	(function($) {
		// Do I have to show the VAT field?
		var country = $('select[name$="country"]').val();
		if( (country=='US') || (country=='CA') ) {
			$('#stateField').show();
		} else {
			$('select[name$="state"]').val('');
			$('#stateField').hide();
		}
	})(akeeba.jQuery);
}

(function($) {
	$(document).ready(function(){
		doBusinessValidation();
		showHideStateField();
		$('select[name$="country"]').change(doValidation);
		$('select[name$="state"]').change(doValidation);
		$('select[name$="address1"]').blur(doValidation);
		$('select[name$="address2"]').blur(doValidation);
		$('select[name$="city"]').blur(doValidation);
		$('select[name$="zip"]').blur(doValidation);
		$('select[name$="vat"]').blur(doBusinessValidation);
		$('#isbusiness1').click(doBusinessValidation);
		$('#isbusiness0').click(doBusinessValidation);
		$('#validateCoupon').blur(doValidation);
	});
})(akeeba.jQuery);