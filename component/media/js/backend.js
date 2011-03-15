/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

var akeebasubs_ri_offset = 0;
var akeebasubs_ri_total = 0;
var akeebasubs_ri_done = 0;

function akeebasubs_refresh_integrations()
{
	akeebasubs_ri_start();
}

function akeebasubs_ri_start()
{
	akeebasubs_ri_offset = 0;
	akeebasubs_ri_total = 1;
	akeebasubs_ri_done = 0;
	
	(function($) {
		$('#asriPercent').text('0');
		$('#asriSpinner').show();
		$.blockUI({message: $('#refreshMessage'), fadeOut: 2000});
		akeebasubs_ri_step();
	})(akeeba.jQuery);
}

function akeebasubs_ri_step()
{
	(function($) {
		$.ajax({
			type: 'GET',
			url: 'index.php?option=com_akeebasubs&view=subrefreshes&format=json',
			data: {
				'forceoffset'		: akeebasubs_ri_offset,
				'limit'				: 250
			},
			dataType: 'json',
			success: function(msg, textStatus, xhr) {
				akeebasubs_ri_total = msg.total;
				akeebasubs_ri_done += msg.processed;
				akeebasubs_ri_offset += msg.processed;
				
				var percentage = 0;
				if(akeebasubs_ri_total > 0) {
					percentage = 100 * akeebasubs_ri_done / akeebasubs_ri_total;
				}
				$('#asriPercent').text(parseInt(percentage + ' '));
				
				if(akeebasubs_ri_done == akeebasubs_ri_total) $('#asriSpinner').hide();
				
				if(msg.processed == 0) {
					$.unblockUI();
				} else {
					akeebasubs_ri_step();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				$.unblockUI();
			}
		});		
		
	})(akeeba.jQuery);
}