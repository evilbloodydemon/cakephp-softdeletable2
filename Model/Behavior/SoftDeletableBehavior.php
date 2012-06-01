<?php

/**
 * SoftDeletable Behavior class file.
 * Based on the SoftDeletable Behavior by Mariano Iglesias
 * http://cake-syrup.sourceforge.net/ingredients/soft-deletable-behavior/
 *
 * @filesource
 * @author Mariano Iglesias
 * @author Igor Fomin (evilbloodydemon@gmail.com)
 * @link http://github.com/evilbloodydemon/cakephp-softdeletable2
 * @license	http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Model behavior to support soft deleting records.
 */
class SoftDeletableBehavior extends ModelBehavior {
	/**
	 * Initiate behaviour for the model using settings.
	 *
	 * @param object $Model Model using the behaviour
	 * @param array $settings Settings to override for model.
	 * @access public
	 */
	function setup(&$Model, $settings = array()) {
		$default = array('field' => 'deleted', 'field_date' => 'deleted_date', 'delete' => true, 'find' => true);

		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $default;
		}

		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], ife(is_array($settings), $settings, array()));
	}

	/**
	 * Run before a model is deleted, used to do a soft delete when needed.
	 *
	 * @param object $Model Model about to be deleted
	 * @param boolean $cascade If true records that depend on this record will also be deleted
	 * @return boolean Set to true to continue with delete, false otherwise
	 * @access public
	 */
	function beforeDelete(&$Model, $cascade = true) {
		if ($this->settings[$Model->alias]['delete'] && $Model->hasField($this->settings[$Model->alias]['field'])) {
			$attributes = $this->settings[$Model->alias];
			$id = $Model->id;

			$data = array($Model->alias => array(
				$attributes['field'] => 1
			));

			if (isset($attributes['field_date']) && $Model->hasField($attributes['field_date'])) {
				if($Model->getColumnType($attributes['field_date']) == 'integer') {
					$date = time();
				} else {
					$date = date('Y-m-d H:i:s');
				}
				$data[$Model->alias][$attributes['field_date']] = $date;
			}

			foreach(array_merge(array_keys($data[$Model->alias]), array('field', 'field_date', 'find', 'delete')) as $field) {
				unset($attributes[$field]);
			}

			if (!empty($attributes)) {
				$data[$Model->alias] = array_merge($data[$Model->alias], $attributes);
			}

			$Model->id = $id;
			if (!empty($Model->belongsTo)) {
				$keys = $Model->find('first', array('fields' => $Model->__collectForeignKeys()));
			}

			$deleted = $Model->save($data, false, array_keys($data[$Model->alias]));

			if ($deleted) {
				if($cascade) {
					$Model->_deleteDependent($id, $cascade);
					$Model->_deleteLinks($id);
				}
				if (!empty($Model->belongsTo)) {
					$Model->updateCounterCache($keys[$Model->alias]);
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Permanently deletes a record.
	 *
	 * @param object $Model Model from where the method is being executed.
	 * @param mixed $id ID of the soft-deleted record.
	 * @param boolean $cascade Also delete dependent records
	 * @return boolean Result of the operation.
	 * @access public
	 */
	function hardDelete(&$Model, $id, $cascade = true) {
		$onFind = $this->settings[$Model->alias]['find'];
		$onDelete = $this->settings[$Model->alias]['delete'];
		$this->enableSoftDeletable($Model, false);

		$deleted = $Model->delete($id, $cascade);

		$this->enableSoftDeletable($Model, 'delete', $onDelete);
		$this->enableSoftDeletable($Model, 'find', $onFind);

		return $deleted;
	}

	/**
	 * Permanently deletes all records that were soft deleted.
	 *
	 * @param object $Model Model from where the method is being executed.
	 * @param boolean $cascade Also delete dependent records
	 * @return boolean Result of the operation.
	 * @access public
	 */
	function purge(&$Model, $cascade = true) {
		$purged = false;

		if ($Model->hasField($this->settings[$Model->alias]['field'])) {
			$onFind = $this->settings[$Model->alias]['find'];
			$onDelete = $this->settings[$Model->alias]['delete'];
			$this->enableSoftDeletable($Model, false);

			$purged = $Model->deleteAll(array($this->settings[$Model->alias]['field'] => '1'), $cascade);

			$this->enableSoftDeletable($Model, 'delete', $onDelete);
			$this->enableSoftDeletable($Model, 'find', $onFind);
		}

		return $purged;
	}

	/**
	 * Restores a soft deleted record, and optionally change other fields.
	 *
	 * @param object $Model Model from where the method is being executed.
	 * @param mixed $id ID of the soft-deleted record.
	 * @param $attributes Other fields to change (in the form of field => value)
	 * @return boolean Result of the operation.
	 * @access public
	 */
	function undelete(&$Model, $id = null, $attributes = array()) {
		if ($Model->hasField($this->settings[$Model->alias]['field'])) {
			if (empty($id)) {
				$id = $Model->id;
			}

			$data = array($Model->alias => array(
				$Model->primaryKey => $id,
				$this->settings[$Model->alias]['field'] => '0'
			));

			if (isset($this->settings[$Model->alias]['field_date']) && $Model->hasField($this->settings[$Model->alias]['field_date'])) {
				$data[$Model->alias][$this->settings[$Model->alias]['field_date']] = null;
			}

			if (!empty($attributes)) {
				$data[$Model->alias] = array_merge($data[$Model->alias], $attributes);
			}

			$onFind = $this->settings[$Model->alias]['find'];
			$onDelete = $this->settings[$Model->alias]['delete'];
			$this->enableSoftDeletable($Model, false);

			$Model->id = $id;
			$result = $Model->save($data, false, array_keys($data[$Model->alias]));

			$this->enableSoftDeletable($Model, 'find', $onFind);
			$this->enableSoftDeletable($Model, 'delete', $onDelete);

			return ($result !== false);
		}

		return false;
	}

	/**
	 * Set if the beforeFind() or beforeDelete() should be overriden for specific model.
	 *
	 * @param object $Model Model about to be deleted.
	 * @param mixed $methods If string, method (find / delete) to enable on, if array array of method names, if boolean, enable it for find method
	 * @param boolean $enable If specified method should be overriden.
	 * @access public
	 */
	function enableSoftDeletable(&$Model, $methods, $enable = true) {
		if (is_bool($methods)) {
			$enable = $methods;
			$methods = array('find', 'delete');
		}

		if (!is_array($methods)) {
			$methods = array($methods);
		}

		foreach($methods as $method) {
			$this->settings[$Model->alias][$method] = $enable;
		}
	}

	/**
	 * Run before a model is about to be find, used only fetch for non-deleted records.
	 *
	 * @param object $Model Model about to be deleted.
	 * @param array $queryData Data used to execute this query, i.e. conditions, order, etc.
	 * @return mixed Set to false to abort find operation, or return an array with data used to execute query
	 * @access public
	 */
	function beforeFind(&$Model, $queryData) {
		if ($this->settings[$Model->alias]['find'] && $Model->hasField($this->settings[$Model->alias]['field'])) {
			if (empty($queryData['conditions'])) {
				$queryData['conditions'] = array();
			}
			$queryData['conditions'][$Model->alias . '.' . $this->settings[$Model->alias]['field'] . ' !='] = '1';
		}

		return $queryData;
	}

	/**
	 * Run before a model is saved, used to disable beforeFind() override.
	 *
	 * @param object $Model Model about to be saved.
	 * @return boolean True if the operation should continue, false if it should abort
	 * @access public
	 */
	function beforeSave(&$Model) {
		if ($this->settings[$Model->alias]['find']) {
			if (!isset($this->__backAttributes)) {
				$this->__backAttributes = array($Model->alias => array());
			} else if (!isset($this->__backAttributes[$Model->alias])) {
				$this->__backAttributes[$Model->alias] = array();
			}

			$this->__backAttributes[$Model->alias]['find'] = $this->settings[$Model->alias]['find'];
			$this->__backAttributes[$Model->alias]['delete'] = $this->settings[$Model->alias]['delete'];
			$this->enableSoftDeletable($Model, false);
		}

		return true;
	}

	/**
	 * Run after a model has been saved, used to enable beforeFind() override.
	 *
	 * @param object $Model Model just saved.
	 * @param boolean $created True if this save created a new record
	 * @access public
	 */
	function afterSave(&$Model, $created) {
		if (isset($this->__backAttributes[$Model->alias]['find'])) {
			$this->enableSoftDeletable($Model, 'find', $this->__backAttributes[$Model->alias]['find']);
			$this->enableSoftDeletable($Model, 'delete', $this->__backAttributes[$Model->alias]['delete']);
			unset($this->__backAttributes[$Model->alias]['find']);
			unset($this->__backAttributes[$Model->alias]['delete']);
		}
	}
}
?>