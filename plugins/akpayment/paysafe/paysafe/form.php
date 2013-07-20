<?php defined('_JEXEC') or die(); ?>

<iframe src="<?php echo $data->url->customer ?>?mid=<?php echo $data->mid ?>&mtid=<?php echo urlencode($data->mtid) ?>&amount=<?php echo urlencode($subscription->gross_amount) ?>&currency=<?php echo urlencode($data->currency) ?>&subId=<?php echo urlencode($data->subId) ?>"
		height="700" width="600"></iframe>