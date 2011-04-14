<?php
/**
 * @package		akeebasubs
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('');

/**
 * AMBRA.Subscriptions to Akeeba Subscriptions converter
 * 
 * Losely based on NinjaBoard's Kunena importer. Thank you, Stian, for your awesome code!
 *
 * @author Nicholas K. Dionysopoulos
 */
class ComAkeebasubsDatabaseConvertersAmbrasubs extends ComAkeebasubsDatabaseConvertersAbstract
{
	/**
	 * This converter is able to run in steps
	 *
	 * @var boolean
	 */
	public $splittable = true;

	public function convert()
	{
		$tables = array(
			array(
				'name'		=>	'levels',
				'options'	=>	array(
					'name' => 'ambrasubs_types',
					'identity_column' => 'id'
				),
				'query'	=> KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'id',
						'title',
						'LOWER(title) AS slug',
						'description',
						'img AS image',
						'period AS duration',
						'value AS price',
						'articleid',
						'published AS enabled',
						'ordering',
					))
			),
			array(
				'name'		=> 'subscriptions',
				'options'	=> array(
					'name' => 'ambrasubs_users2types',
					'identity_column' => 'u2tid'
				),
				'query'	=> KFactory::tmp('lib.koowa.database.query')
					->select(array(
						'tbl.u2tid',
						'tbl.userid AS user_id',
						'tbl.typeid AS akeebasubs_level_id',
						'p.created_datetime AS publish_up',
						'IF(tbl.expires_datetime > \'2038-01-01\', \'NADA\', tbl.expires_datetime) AS publish_down',
						'tbl.notes AS notes',
						'tbl.status AS enabled',
						'IF(p.payment_type IS NULL, \'None\', p.payment_type) AS processor',
						'IF(p.payment_id IS NULL, \'Import\', p.payment_id) AS processor_key',
						'IF(p.payment_status = 1, \'C\', IF(p.payment_status IS NULL, \'C\', \'X\')) AS state',
						'IF(p.payment_amount IS NULL, 0, p.payment_amount) AS net_amount',
						'0 AS tax_amount',
						'IF(p.payment_amount IS NULL, 0, p.payment_amount) AS gross_amount',
						'IF(p.payment_datetime IS NULL, \'0000-00-00 00:00:00\', p.payment_datetime) AS created_on',
						'\'\' AS params',
						'tbl.flag_contact AS contact_flag',
						'tbl.contact_datetime AS first_contact',
						'tbl.contact_datetime AS second_contact'
					))
					->join('left', 'ambrasubs_payments AS p', 'p.id = tbl.paymentid')
			)
		);
		
		//This returns false if the import is big enough to be done in steps.
		//So we need to stop the importing in this step, in order for it to initiate
		if($this->importData($tables) === false) return $this;
		
		// Post-proc the subscription levels, merging the Joomla! articles to the ordertext and
		// canceltext fields. Also take care of the image field.
		if(isset($this->data['levels'])) {
			foreach($this->data['levels'] as $id => $level) {
				$articleText = '<p></p>';
				$articleid = (int)($level['articleid']);
				if($articleid > 0) {
					$article = KFactory::tmp('admin::com.default.database.table.content', array(
						'name'=>'content','identity_column'=>'id'
					))->select(KFactory::tmp('lib.koowa.database.query')
						->select(array('introtext','fulltext'))
						->where('id','=',$articleid), KDatabase::FETCH_ROW)
					->getItem();
					if($article instanceof KDatabaseRowInterface) {
						$articleText = $article->introtext . "\n" . $article->fulltext;
					}
				}
				$this->data['levels'][$id]['ordertext'] = $articleText;
				$this->data['levels'][$id]['canceltext'] = $articleText;
				
				$img = $level['img'];
				$img = ltrim($img,'/');
				if(substr($img,0,15) == 'images/stories/') {
					$this->data['levels'][$id]['img'] = substr($img,15);
				}
			}
		}
		
		if(isset($this->data['subscriptions'])) {
			jimport('joomla.utilities.date');
			$jNow = new JDate();
			$tsNow = $jNow->toUnix();
			foreach($this->data['subscriptions'] as $id => $subscription) {
				// Fixes subscriptions without an attached payment
				if(empty($subscription['publish_up'])) {
					$this->data['subscriptions'][$id]['publish_up'] = '2010-01-01 00:00:00';
				}
				// Fixes subscriptions w/out an expiration date, or with an expiration date set after UNIX' End Of Time.
				$year = empty($subscription['publish_down']) ? 1970 : substr($subscription['publish_down'],0,4);
				if(($year < 2000) || ($year>2038)) {
					$this->data['subscriptions'][$id]['publish_down'] = '2038-01-01 00:00:00';
				}
				// If the subscription is expired, mark it as if the user is already contacted
				$jExp = new JDate($this->data['subscriptions'][$id]['publish_down']);
				if($jExp->toUnix() < $tsNow) {
					$this->data['subscriptions'][$id]['contact_flag'] = 2;
					$this->data['subscriptions'][$id]['first_contact'] = $jNow->toMySQL();
					$this->data['subscriptions'][$id]['second_contact'] = $jNow->toMySQL();
				}
			}
		}
		
		parent::convert();
		
		return $this;
	}
	
	public function canConvert()
	{
		if(!JComponentHelper::getComponent( 'com_ambrasubs', true )->enabled) return false;

		return true;
	}
}