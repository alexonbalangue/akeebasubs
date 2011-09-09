<?php defined('KOOWA') or die(); ?>

<!--
<script src="media://com_akeebasubs/js/akeebajq.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/autosubmit.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/frontend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
 -->

<?=KFactory::get('com://site/akeebasubs.model.configs')->getConfig()->stepsbar ? @template('com://site/akeebasubs.view.level.steps',array('step' => 'payment')) : ''?>

<?=$form?>