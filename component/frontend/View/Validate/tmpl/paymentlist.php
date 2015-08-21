<?php

use Akeeba\Subscriptions\Admin\Helper\Select;

echo Select::paymentmethods(
    'paymentmethod',
    $this->input->getString('paymentmethod', ''),
    array(
        'id' 		=> 'paymentmethod',
        'level_id' 	=> $this->input->getInt('id', 0),
        'country'  	=> $this->input->getString('country', '')
    )
);