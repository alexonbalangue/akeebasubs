<?php if (!defined('ALLOW_PAGSEGURO_CONFIG')) { die('No direct script access allowed'); }
/*
************************************************************************
PagSeguro Config File
************************************************************************
*/

$PagSeguroConfig = array();

$PagSeguroConfig['environment'] = Array();
$PagSeguroConfig['environment']['environment'] = "production";

$PagSeguroConfig['credentials'] = Array();
$PagSeguroConfig['credentials']['email'] = "your@email.com";
$PagSeguroConfig['credentials']['token'] = "your_token_here";

$PagSeguroConfig['application'] = Array();
$PagSeguroConfig['application']['charset'] = "UTF-8"; // UTF-8, ISO-8859-1

$PagSeguroConfig['log'] = Array();
$PagSeguroConfig['log']['active'] = TRUE;
if(version_compare(JVERSION, '3.0', 'ge')) {
	$PagSeguroConfig['log']['fileLocation'] = JFactory::getConfig()->get('log_path').'/pagseguro.log';
} else {
	$PagSeguroConfig['log']['fileLocation'] = JFactory::getConfig()->getValue('log_path').'/pagseguro.log';
}

?>