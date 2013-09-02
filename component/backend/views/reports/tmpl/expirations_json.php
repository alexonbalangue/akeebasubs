<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

	$data     = array();
	$response = array();
	$levels   = array();

	$start = $this->input->getString('start', date('Y-m-d', strtotime('-2 months', strtotime('last monday'))));
	$jStart = new JDate($start);
	$end   = date('Y-m-d' , strtotime('+4 months', $jStart->toUnix()));
	//$end   = $this->input->getString('end', date('Y-m-d' , strtotime('+2 months', strtotime('last monday'))));

	$subs = FOFModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->expires_from($start)
				->expires_to($end)
				->groupbyweek(1)
				->nojoins(1)
				->getList(true);

	$titles = FOFModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->createIdLookup();

	// Get involved levels (aka series)
	foreach($subs as $sub)
	{
		if(!isset($levels[$sub->akeebasubs_level_id]))
		{
			$levels[$sub->akeebasubs_level_id] = 1;
		}
	}

	foreach($subs as $sub)
	{
		$time = mktime(0, 0, 0, 1, 1, substr($sub->yearweek, 0, 4));
		$t    = date('Y-m-d', $time);
		$date = date('Y-m-d', strtotime('+'.substr($sub->yearweek, -2, 2).' weeks', $time));

		// Initialize the array with empty data, so I can have same length array for stacking
		foreach($levels as $serie => $dummy)
		{
			if(!isset($data[$serie][$date]))
			{
				$data[$serie][$date] = array($date, 0);
			}
		}

		$data[$sub->akeebasubs_level_id][$date] = array($date, (int) $sub->subs);
	}

	ksort($data);
	ksort($levels);

	foreach($levels as $level => $dummy)
	{
		$response['seriesLabel'][] = array('label' => $titles[$level]->title);
	}

	// jqplot doesn't like associative arrays
	foreach($data as $series)
	{
		$temp = array();

		foreach($series as $serie)
		{
			$temp[] = $serie;
		}

		$response['data'][] = $temp;
	}

	echo json_encode(($response));
