<?php
/* SVN FILE: $Id$ */

/**
 * Test cases for SoftDeletable Behavior, which are basically testing methods to test several
 * aspects of slug functionality.
 *
 * Go to the SoftDeletable Behavior page at Cake Syrup to learn more about it:
 *
 * http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 *
 * @filesource
 * @author Mariano Iglesias
 * @link http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 * @version	$Revision$
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 * @package app.tests
 * @subpackage app.tests.cases.behaviors
 */

App::import('Core', 'ConnectionManager');

/**
 * Base model that to load SoftDeletable behavior on every test model.
 *
 * @package app.tests
 * @subpackage app.tests.cases.behaviors
 */
class SoftDeletableTestModel extends CakeTestModel {
	/**
	 * Behaviors for this model
	 *
	 * @var array
	 * @access public
	 */
	var $actsAs = array('SoftDeletable.SoftDeletable');
}

/**
 * Model used in test case.
 *
 * @package	app.tests
 * @subpackage app.tests.cases.behaviors
 */
class DeletableArticle extends SoftDeletableTestModel {
	/**
	 * Name for this model
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'DeletableArticle';

	/**
	 * hasMany releations for this model
	 *
	 * @var array
	 * @access public
	 */
	var $hasMany = array('DeletableComment' => array('dependent' => true));
}

/**
 * Model used in test case.
 *
 * @package	app.tests
 * @subpackage app.tests.cases.behaviors
 */
class DeletableComment extends SoftDeletableTestModel {
	/**
	 * Name for this model
	 *
	 * @var string
	 * @access public
	 */
	var $name = 'DeletableComment';

	/**
	 * belongsTo releations for this model
	 *
	 * @var array
	 * @access public
	 */
	var $belongsTo = array('DeletableArticle');
}

/**
 * Test case for SoftDeletable Behavior
 *
 * @package app.tests
 * @subpackage app.tests.cases.models
 */
class SoftDeletableTestCase extends CakeTestCase {
	/**
	 * Fixtures associated with this test case
	 *
	 * @var array
	 * @access public
	 */
	var $fixtures = array('plugin.soft_deletable.deletable_article', 'plugin.soft_deletable.deletable_comment');

	/**
	 * Method executed before each test
	 *
	 * @access public
	 */
	function startTest() {
		$this->DeletableArticle =& new DeletableArticle();
	}

	/**
	 * Method executed after each test
	 *
	 * @access public
	 */
	function endTest() {
		unset($this->DeletableArticle);
		ClassRegistry::flush();
	}

	/**
	 * Test beforeFind callback
	 *
	 * @access public
	 */
	function testBeforeFind() {
		$Db =& ConnectionManager::getDataSource($this->DeletableArticle->useDbConfig);
		$SoftDeletable =& new SoftDeletableBehavior();
		$SoftDeletable->setup($this->DeletableArticle);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array());
		$expected = array('conditions' => array('DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.deleted' => 0)));
		$expected = array('conditions' => array('DeletableArticle.deleted' => 0));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.deleted' => array(0, 1))));
		$expected = array('conditions' => array('DeletableArticle.deleted' => array(0, 1)));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'))));
		$expected = array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'DeletableArticle.deleted !=' => '1'));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'deleted' => 1)));
		$expected = array('conditions' => array('DeletableArticle.id' => '> 0', 'or' => array('DeletableArticle.title' => 'Title', 'DeletableArticle.id' => '5'), 'deleted' => 1));
		$this->assertEqual($result, $expected);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'id=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+id\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => '1=1 LEFT JOIN table ON (table.column=DeletableArticle.id)'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+1\s*=\s*1\s+LEFT JOIN table ON ' . preg_quote('(table.column=DeletableArticle.id)') . '$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'deleted=1'));
		$this->assertPattern('/^' . preg_quote('deleted') . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'deleted  = 1'));
		$this->assertPattern('/^' . preg_quote('deleted') . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => $Db->name('deleted') . '=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('deleted')) . '\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'id > 0 AND deleted =1'));
		$this->assertPattern('/^id > 0 AND deleted\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'mydeleted=1'));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+mydeleted\s*=\s*1$/', $result['conditions']);

		$result = $SoftDeletable->beforeFind($this->DeletableArticle, array('conditions' => 'title = \'record is not deleted\''));
		$this->assertPattern('/^' . preg_quote($Db->name('DeletableArticle') . '.' . $Db->name('deleted')) . '\s*!=\s*1\s+AND\s+title\s*=\s*\'' . preg_quote('record is not deleted') . '\'$/', $result['conditions']);

		unset($SoftDeletable);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testFind() {
		$this->DeletableArticle->delete(2);
		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => 0), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => 1), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => array('DeletableArticle.deleted' => array(0, 1)), 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}


	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testFindStringConditions() {
		$Db =& ConnectionManager::getDataSource($this->DeletableArticle->useDbConfig);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'id > 0 AND title LIKE ' . $Db->value('%ir%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(2);
		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND deleted=0', 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'DeletableArticle.deleted = 0 AND title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND deleted=1', 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'DeletableArticle.deleted = 1 AND title LIKE ' . $Db->value('%Article%'), 'fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%Article%') . ' AND (deleted=0 OR deleted = 1)', 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('conditions' => 'title LIKE ' . $Db->value('%ir%') . ' AND DeletableArticle.deleted IN (0,1)', 'fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testSoftDelete() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test hard delete
	 *
	 * @access public
	 */
	function testHardDelete() {
		$result = $this->DeletableArticle->hardDelete(2);
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test soft delete
	 *
	 * @access public
	 */
	function testPurge() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);

		$this->DeletableArticle->delete(3);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article', 'deleted' => 1
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article', 'deleted' => 1
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);

		$result = $this->DeletableArticle->purge();
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->enableSoftDeletable(false);
		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title', 'deleted')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->enableSoftDeletable(true);
	}

	/**
	 * Test undelete
	 *
	 * @access public
	 */
	function testUndelete() {
		$this->DeletableArticle->delete(2);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$result = $this->DeletableArticle->undelete(2);
		$this->assertTrue($result);

		$this->DeletableArticle->unbindModel(array('hasMany' => array('DeletableComment')));
		$result = $this->DeletableArticle->find('all', array('fields' => array('id', 'title')));
		$expected = array(
			array('DeletableArticle' => array(
				'id' => 1, 'title' => 'First Article'
			)),
			array('DeletableArticle' => array(
				'id' => 2, 'title' => 'Second Article'
			)),
			array('DeletableArticle' => array(
				'id' => 3, 'title' => 'Third Article'
			))
		);
		$this->assertEqual($result, $expected);
	}

	/**
	 * Test recursivity when soft deleting records
	 *
	 * @access public
	 */
	function testRecursive() {
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->delete(2);

		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article'
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article'
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article'
			))
		);
		$this->assertEqual($result, $expected);

		$this->DeletableArticle->DeletableComment->enableSoftDeletable(false);
		$result = $this->DeletableArticle->DeletableComment->find('all', array('fields' => array('id', 'comment', 'deleted')));
		$expected = array(
			array('DeletableComment' => array(
				'id' => 1, 'comment' => 'First Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 2, 'comment' => 'Second Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 3, 'comment' => 'Third Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 4, 'comment' => 'Fourth Comment for First Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 5, 'comment' => 'First Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 6, 'comment' => 'Second Comment for Second Article', 'deleted' => 1
			)),
			array('DeletableComment' => array(
				'id' => 7, 'comment' => 'First Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 8, 'comment' => 'Second Comment for Third Article', 'deleted' => 0
			)),
			array('DeletableComment' => array(
				'id' => 9, 'comment' => 'Third Comment for Third Article', 'deleted' => 0
			))
		);
		$this->assertEqual($result, $expected);
		$this->DeletableArticle->DeletableComment->enableSoftDeletable(true);
	}
}

?>