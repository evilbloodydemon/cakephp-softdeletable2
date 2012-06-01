<?php
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
class DeletableCommentFixture extends CakeTestFixture
{
	var $name = 'DeletableComment';

	var $fields = array(
		'id' => array('type' => 'integer', 'key' => 'primary', 'extra'=> 'auto_increment'),
		'deletable_article_id' => array('type' => 'integer', 'null'=>false),
		'comment' => 'text',
		'deleted' => array('type' => 'integer', 'default' => '0'),
		'created' => 'datetime',
		'updated' => 'datetime'
	);

	var $records = array(
		array('id' => 1, 'deletable_article_id' => 1, 'comment' => 'First Comment for First Article', 'deleted' => '0', 'created' => '2007-03-18 10:45:23', 'updated' => '2007-03-18 10:47:31'),
		array('id' => 2, 'deletable_article_id' => 1, 'comment' => 'Second Comment for First Article', 'deleted' => '0', 'created' => '2007-03-18 10:47:23', 'updated' => '2007-03-18 10:49:31'),
		array('id' => 3, 'deletable_article_id' => 1, 'comment' => 'Third Comment for First Article', 'deleted' => '0', 'created' => '2007-03-18 10:49:23', 'updated' => '2007-03-18 10:51:31'),
		array('id' => 4, 'deletable_article_id' => 1, 'comment' => 'Fourth Comment for First Article', 'deleted' => '0', 'created' => '2007-03-18 10:51:23', 'updated' => '2007-03-18 10:53:31'),
		array('id' => 5, 'deletable_article_id' => 2, 'comment' => 'First Comment for Second Article', 'deleted' => '0', 'created' => '2007-03-18 10:53:23', 'updated' => '2007-03-18 10:55:31'),
		array('id' => 6, 'deletable_article_id' => 2, 'comment' => 'Second Comment for Second Article', 'deleted' => '0', 'created' => '2007-03-18 10:55:23', 'updated' => '2007-03-18 10:57:31'),
		array('id' => 7, 'deletable_article_id' => 3, 'comment' => 'First Comment for Third Article', 'deleted' => '0', 'created' => '2007-03-18 10:57:23', 'updated' => '2007-03-18 10:59:31'),
		array('id' => 8, 'deletable_article_id' => 3, 'comment' => 'Second Comment for Third Article', 'deleted' => '0', 'created' => '2007-03-18 10:59:23', 'updated' => '2007-03-18 11:01:31'),
		array('id' => 9, 'deletable_article_id' => 3, 'comment' => 'Third Comment for Third Article', 'deleted' => '0', 'created' => '2007-03-18 11:01:23', 'updated' => '2007-03-18 11:03:31')
	);
}

?>