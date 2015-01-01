<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

	$data     = array();
	$response = array();
	$levels   = array();
	$labels   = array();

	$start  = $this->input->getString('start', date('Y-m-d', strtotime('last monday')));
	$jStart = new JDate($start);
	$start  = date('Y-m-d', strtotime('last monday', $jStart->toUnix()));
	$jStart = new JDate($start);

	$end     = date('Y-m-d' , strtotime('+16 weeks', $jStart->toUnix()));
	$jEnd    = new JDate($end);
	$endUnix = $jEnd->toUnix();

	$subs = F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
				->expires_from($start)
				->expires_to($end)
				->groupbyweek(1)
				->nojoins(1)
				->paystate('C')
				->getList(true);

	$titles = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
				->createIdLookup();

	// Get involved levels (aka series)
	foreach($subs as $sub)
	{
		if(!isset($levels[$sub->akeebasubs_level_id]))
		{
			$levels[$sub->akeebasubs_level_id] = 1;
		}
	}

	// Inject a dummy level, so I won't have errors on empty sets
	if(!$levels)
	{
		$levels[0] = 1;
		$response['hideLegend'] = true;
	}

	// Let's create empty weeks, for next 16 weeks, so I can have a nice chart
	$loopWeek = $jStart->toUnix();
	$response['labels'][] = date('Y-m-d', $loopWeek);

	foreach($levels as $serie => $dummy)
	{
		if(!isset($data[$serie][date('Y-m-d', $loopWeek)]))
		{
			$data[$serie][date('Y-m-d', $loopWeek)] = array(date('Y-m-d', $loopWeek), 0);
		}
	}


	while($loopWeek <= $endUnix)
	{
		$loopWeek = strtotime('+1 week', $loopWeek);
		$date     = date('Y-m-d', $loopWeek);
		$response['labels'][] = $date;

		// Initialize the array with empty data, so I can have same length array for stacking
		foreach($levels as $serie => $dummy)
		{
			if(!isset($data[$serie][$date]))
			{
				$data[$serie][$date] = array($date, 0);
			}
		}
	}

	foreach($subs as $sub)
	{
		$year = substr($sub->yearweek, 0, 4);
		$week = substr($sub->yearweek, -2, 2);
		$date = date('Y-m-d', strtotime($year.'W'.$week));

		$data[$sub->akeebasubs_level_id][$date] = array($date, (int) $sub->subs);
	}

	ksort($data);
	ksort($levels);

	if($subs)
	{
		foreach($levels as $level => $dummy)
		{
			$response['seriesLabel'][] = array('label' => $titles[$level]->title);
		}
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
