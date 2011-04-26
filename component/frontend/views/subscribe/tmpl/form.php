<?php defined('KOOWA') or die(); ?>

<!--
<script src="media://com_akeebasubs/js/jquery.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/autosubmit.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
 -->

<?=KFactory::get('site::com.akeebasubs.model.configs')->getConfig()->stepsbar ? @template('steps',array('step' => 'payment')) : ''?>

<?=$form?>