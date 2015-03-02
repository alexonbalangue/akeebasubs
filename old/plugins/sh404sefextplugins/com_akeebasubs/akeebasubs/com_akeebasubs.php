<?php
defined( '_JEXEC' ) or die( 'Direct Access to this location is not allowed.' );

/**
 * Incoming var from the include
 * @var     string  $option     Component name, ie com_foobar
 * @var     int     $id         Id of the current record
 */

// ------------------  standard plugin initialize function - don't change ---------------------------
global $sh_LANG;
$sefConfig = & shRouter::shGetConfig();
$shLangName = '';
$shLangIso = '';
$title = array();
$shItemidString = '';
$dosef = shInitializePlugin( $lang, $shLangName, $shLangIso, $option);
if ($dosef == false) return;
// ------------------  standard plugin initialize function - don't change ---------------------------

// ------------------  load language file - adjust as needed ----------------------------------------
//$shLangIso = shLoadPluginLanguage( 'com_XXXXX', $shLangIso, '_SEF_SAMPLE_TEXT_STRING');
// ------------------  load language file - adjust as needed ----------------------------------------


if (!function_exists( 'shAkeebasubsMenuName'))
{
    function shAkeebasubsMenuName($task, $Itemid, $option, $shLangName)
    {
        $sefConfig           = &shRouter::shGetConfig();
        $shArsDownloadName = shGetComponentPrefix($option);

        if( empty($shArsDownloadName) ) $shArsDownloadName = getMenuTitle($option, $task, $Itemid, null, $shLangName);
        if( empty($shArsDownloadName) || $shArsDownloadName == '/' ) $shArsDownloadName = 'AkeebaReleaseSystem';

        return str_replace( '.', $sefConfig->replacement, $shArsDownloadName );
    }
}

// remove common URL from GET vars list, so that they don't show up as query string in the URL
shRemoveFromGETVarsList('option');
shRemoveFromGETVarsList('lang');
shRemoveFromGETVarsList('view');
shRemoveFromGETVarsList('format');

if( isset($Itemid) ) shRemoveFromGETVarsList('Itemid');

global $shGETVars;

$task    = isset($task) ? @$task : null;
$Itemid  = isset($Itemid) ? @$Itemid : null;
$title[] = shAkeebasubsMenuName($task, $Itemid, $option, $shLangName);

// Default view
$default = 'levels';

// We need to find out if the menu item link has a view param
if ($Itemid)
{
    $menu = JFactory::getApplication()->getMenu()->getItem($Itemid);
    if (!is_object($menu))
    {
        $menuquery = array();
    }
    else
    {
        parse_str(str_replace('index.php?', '', $menu->link), $menuquery); // remove "index.php?" and parse
    }
}
else
{
    $menuquery = array();
}

// Add the view
$newView = $view ? $view : (array_key_exists('view', $menuquery) ? $menuquery['view'] : $default);

if ($newView == 'level')
{
    $newView = 'new';
}
elseif ($newView == 'message')
{
    if (!array_key_exists('layout', $shGETVars))
    {
        $shGETVars['layout'] = 'order';
    }

    if ($shGETVars['layout'] == 'order')
    {
        $newView = 'thankyou';
    }
    else
    {
        $newView = 'cancelled';
    }

    shRemoveFromGETVarsList('layout');
}
elseif ($newView == 'userinfo')
{
    if (!array_key_exists('layout', $shGETVars))
    {
        shRemoveFromGETVarsList('layout');
    }
}
$title[] = $newView;
shRemoveFromGETVarsList('view');
shRemoveFromGETVarsList('layout');

// Add the slug
if ($newView != 'userinfo')
{
    if (array_key_exists('slug', $shGETVars) && (F0FInflector::isSingular($title[1]) || ($title[1] == 'new')))
    {
        $title[2] = $shGETVars['slug'];
        shRemoveFromGETVarsList('slug');
    }
    elseif (array_key_exists('id', $shGETVars) && ($title[1] == 'subscription'))
    {
        $title[2] = $shGETVars['id'];
        shRemoveFromGETVarsList('id');
    }
}

$title[] = '/';
// ------------------  standard plugin finalize function - don't change ---------------------------
if ($dosef){
   $string = shFinalizePlugin( $string, $title, $shAppendString, $shItemidString,
      (isset($limit) ? @$limit : null), (isset($limitstart) ? @$limitstart : null),
      (isset($shLangName) ? @$shLangName : null));
}
// ------------------  standard plugin finalize function - don't change ---------------------------
