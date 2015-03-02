<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die;

$data = array();
$response = array();
$levelsWithExpirations = array();
$labels = array();

$start = $this->input->getString('start', gmdate('Y-m-d', strtotime('last monday')));
$jStart = new JDate($start);
$start = gmdate('Y-m-d', strtotime('last monday', $jStart->toUnix()));
$jStart = new JDate($start);

$end = gmdate('Y-m-d', strtotime('+16 weeks', $jStart->toUnix()));
$jEnd = new JDate($end);
$endUnix = $jEnd->toUnix();

$subscriptionsExpiringInDateRange = F0FModel::getTmpInstance('Subscriptions', 'AkeebasubsModel')
	->expires_from($start)
	->expires_to($end)
	->groupbyweek(1)
	->nojoins(1)
	->paystate('C')
	->getList(true);

$allSubscriptionLevelsDetails = F0FModel::getTmpInstance('Levels', 'AkeebasubsModel')
	->createIdLookup();

// Get involved levels (aka series)
foreach ($subscriptionsExpiringInDateRange as $sub)
{
	if (!in_array($sub->akeebasubs_level_id, $levelsWithExpirations))
	{
		$levelsWithExpirations[] = $sub->akeebasubs_level_id;
	}
}

// Inject a dummy level, so I won't have errors on empty sets
if (empty($levelsWithExpirations))
{
	$levelsWithExpirations[] = 0;
	$response['hideLegend'] = true;
}

// Let's create empty weeks, for next 16 weeks, so I can have a nice chart
$loopWeek = $jStart->toUnix();
$startingWeekLabel = gmdate('Y-m-d', $loopWeek);
$response['labels'][] = $startingWeekLabel;

// Handle starting week
foreach ($levelsWithExpirations as $levelID)
{
	if (!isset($data[$levelID][$startingWeekLabel]))
	{
		$data[$levelID][$startingWeekLabel] = array($startingWeekLabel, 0);
	}
}

// Handle every other week
while ($loopWeek <= $endUnix)
{
	$loopWeek = strtotime('+1 week', $loopWeek);
	$weekLabel = gmdate('Y-m-d', $loopWeek);
	$response['labels'][] = $weekLabel;

	// Initialize the array with empty data, so I can have same length array for stacking
	foreach ($levelsWithExpirations as $levelID)
	{
		if (!isset($data[$levelID][$weekLabel]))
		{
			$data[$levelID][$weekLabel] = array($weekLabel, 0);
		}
	}
}

if (count($subscriptionsExpiringInDateRange))
{
	foreach ($subscriptionsExpiringInDateRange as $sub)
	{
		$year = substr($sub->yearweek, 0, 4);
		$week = substr($sub->yearweek, -2, 2);
		$weekLabel = gmdate('Y-m-d', strtotime($year . 'W' . $week));

		$data[$sub->akeebasubs_level_id][$weekLabel] = array($weekLabel, (int)$sub->subs);
	}

	ksort($data);
	asort($levelsWithExpirations);

	foreach ($levelsWithExpirations as $level)
	{
		$response['seriesLabel'][] = array('label' => $allSubscriptionLevelsDetails[$level]->title);
	}
}

// jqplot doesn't like associative arrays
foreach ($data as $allSeries)
{
	$temp = array();

	foreach ($allSeries as $dataForThisSeries)
	{
		$temp[] = $dataForThisSeries;
	}

	$response['data'][] = $temp;
}

echo json_encode($response);
