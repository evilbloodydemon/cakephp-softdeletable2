<?php
/* SVN FILE: $Id$ */
/**
 * Fixture for test case in SoftDeletableBehavior.
 *
 * @filesource
 * @author Mariano Iglesias
 * @author Igor Fomin (evilbloodydemon@gmail.com)
 * @link http://github.com/evilbloodydemon/cakephp-softdeletable2
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * A fixture for a testing model
 */
class DeletableArticleFixture extends CakeTestFixture
{
	public $name = 'DeletableArticle';

	public $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => false),
		'body' => 'text',
		'published' => array('type' => 'integer', 'default' => '0', 'null' => false),
		'deleted' => array('type' => 'integer', 'default' => '0'),
		'deleted_date' => 'datetime',
		'deleted_date_int' => array('type' => 'integer', 'default' => '0'),
		'created' => 'datetime',
		'updated' => 'datetime',
		'deletable_comment_count' => array('type' => 'integer', 'default' => '0'),
	);

	public $records = array(
		array (
			'id' => 1,
			'title' =>
			'First Article',
			'body' => 'First Article Body',
			'published' => '1',
			'deleted' => '0',
			'deleted_date' => null,
			'deleted_date_int' => 0,
			'created' => '2007-03-18 10:39:23',
			'updated' => '2007-03-18 10:41:31',
			'deletable_comment_count' => 0,
		),
		array (
			'id' => 2,
			'title' => 'Second Article',
			'body' => 'Second Article Body',
			'published' => '1',
			'deleted' => '0',
			'deleted_date' => null,
			'deleted_date_int' => 0,
			'created' => '2007-03-18 10:41:23',
			'updated' => '2007-03-18 10:43:31',
			'deletable_comment_count' => 0,
		),
		array (
			'id' => 3,
			'title' => 'Third Article',
			'body' => 'Third Article Body',
			'published' => '1',
			'deleted' => '0',
			'deleted_date' => null,
			'deleted_date_int' => 0,
			'created' => '2007-03-18 10:43:23',
			'updated' => '2007-03-18 10:45:31',
			'deletable_comment_count' => 0,
		),
	);
}
