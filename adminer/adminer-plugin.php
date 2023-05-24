<?php

/** Adminer customization allowing usage of plugins
* @link https://www.adminer.org/plugins/#use
* @author Jakub Vrana, https://www.vrana.cz/
* @license https://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
* @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License, version 2 (one or other)
*/
class AdminerPlugin extends Adminer {
	protected $plugins;
	
	function __construct($plugins) {
		$this->plugins = $plugins;
	}
	
	function _applyPlugin($function, $args) {
		foreach ($this->plugins as $plugin) {
			if (method_exists($plugin, $function)) {
                $return = $plugin->$function(...$args);
				if ($return !== null) {
					return $return;
				}
			}
		}
        return parent::$function(...$args);
	}
	
	function _appendPlugin($function, $args) {
        $return = parent::$function(...$args);
		foreach ($this->plugins as $plugin) {
			if (method_exists($plugin, $function)) {
                $value = $plugin->$function(...$args);
				if ($value) {
					$return += $value;
				}
			}
		}
		return $return;
	}
	
	// appendPlugin
	
	function dumpFormat() {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}
	
	function dumpOutput() {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}

	function editRowPrint($table, $fields, $row, $update) {
	}

	function editFunctions($field) {
		$args = func_get_args();
		return $this->_appendPlugin(__FUNCTION__, $args);
	}

	// applyPlugin
	
	function name() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function credentials() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function connectSsl() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function permanentLogin($create = false) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function bruteForceKey() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function serverName($server) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function database() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function schemas() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function databases($flush = true) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function queryTimeout() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function headers() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function csp() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function head() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function css() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function loginForm() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function loginFormField($name, $heading, $value) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function login($login, $password) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tableName($tableStatus) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function fieldName($field, $order = 0) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLinks($tableStatus, $set = "") {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function foreignKeys($table) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function backwardKeys($table, $tableName) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function backwardKeysPrint($backwardKeys, $row) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectQuery($query, $start, $failed = false) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function sqlCommandQuery($query) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function rowDescription($table) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function rowDescriptions($rows, $foreignKeys) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLink($val, $field) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectVal($val, $link, $field, $original) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function editVal($val, $field) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tableStructurePrint($fields) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tableIndexesPrint($indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectColumnsPrint($select, $columns) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectSearchPrint($where, $columns, $indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectOrderPrint($order, $columns, $indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLimitPrint($limit) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLengthPrint($text_length) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectActionPrint($indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectCommandPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectImportPrint() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectEmailPrint($emailFields, $columns) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectColumnsProcess($columns, $indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectSearchProcess($fields, $indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectOrderProcess($fields, $indexes) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLimitProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectLengthProcess() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectEmailProcess($where, $foreignKeys) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function selectQueryBuild($select, $where, $group, $order, $limit, $page) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function messageQuery($query, $time, $failed = false) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function editInput($table, $field, $attrs, $value) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function editHint($table, $field, $value) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function processInput($field, $value, $function = "") {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpDatabase($db) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpTable($table, $style, $is_view = 0) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpData($table, $style, $query) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpFilename($identifier) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function dumpHeaders($identifier, $multi_table = false) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function importServerPath() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function homepage() {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function navigation($missing) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function databasesPrint($missing) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

	function tablesPrint($tables) {
		$args = func_get_args();
		return $this->_applyPlugin(__FUNCTION__, $args);
	}

}
