<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

	$data  = array();
	$start = $this->input->getString('start', date('Y-m-d', strtotime('-2 months', strtotime('last monday'))));
	$end   = $this->input->getString('end', date('Y-m-d' , strtotime('+2 months', strtotime('last monday'))));

	$subs = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->expires_from($start)
				->expires_to($end)
				->groupbyweek(1)
				->nojoins(1)
				->getList(true);

	foreach($subs as $sub)
	{
		$time = mktime(0, 0, 0, 1, 1, substr($sub->yearweek, 0, 4));
		$t = date('Y-m-d', $time);
		$date = date('Y-m-d', strtotime('+'.substr($sub->yearweek, -2, 2).' weeks', $time));
		$data[] = array($date, $sub->subs);
	}

	//[[["2008-09-30",1], ["2008-09-30 4:00PM",1], ["2008-09-30 4:00PM",1], ["2008-12-30 4:00PM",9], ["2009-01-30 4:00PM",8.2]]]
	echo json_encode(array($data));