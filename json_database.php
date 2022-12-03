<?php




class DatabaseAlreadyExists extends Exception {}
class DatabaseDosentExists extends Exception {}
class DatabaseNotConnected extends Exception {}
class TableAlreadyExists extends Exception {}
class TableDosentExists extends Exception {}

interface Database {
	public function create_database(string $db_name);
	public function connect_database(string $db_name);
	public function create_table(string $table);
	public function insert(string $table, $obj_or_array);
	public function remove(string $table, object $condition);
	public function update(string $table, object $update, object $condition);
	public function get_rows(string $table, object $condition = null);
	public function get_row(string $table, object $condition = null);
}

class JsonDatabase implements Database {
	protected ?string $current_database = null;
	
	// the object can create and interact with several databases.
	public function create_database(string $db_name) {
		$this->validate_database_free($db_name);
		$this->current_database = $db_name;
		// a database is a folder
		mkdir($this->current_database);
	}
	// only one database is connected at a time	
	public function connect_database(string $db_name) {
		$this->validate_database_exists($db_name);
		$this->current_database = $db_name;
	}
	
	public function create_table(string $table) {
		$this->validate_table_free($table);
		$file = fopen($this->tbl_path($table),"w");
		// the table is an array of rows.
		fwrite($file, "[\n]");
		fclose($flile);
	}


	public function insert(string $table, $obj_or_array) {
		$obj_table = $this->get_table($table);
		if (is_array($obj_or_array)) {
			foreach($obj_or_array as $row) {
				array_push($obj_table,$row);
			}
		}
		else {
			array_push($obj_table,$obj_or_array);
		}
		$this->save_table($table, $obj_table);
	}

	// this method will return a sub array containing only the rows which much the condition
	// to much the condition a row must contain the property in the condition with the value stated
	public function get_rows(string $table, object $condition = null) {
		$obj_table = $this->get_table($table);
		if(is_null($condition)) {
			return $obj_table;
		}
		$selected_table = array_filter($obj_table, function($row) use ($condition) {
			return $this->is_row_condition($row, $condition);
		});
		return $selected_table;
	}

	// this function will return the first instance of a row that fits the conditions.
	// it was required by the assignment
	public function get_row(string $table, object $condition = null) {
		$multiple_rows = $this->get_rows($table,$condition);
		if (count($multiple_rows) == 0) {
			return null;
		}
		return array_pop($multiple_rows);
	}

	// remove any row in table which contains all of the proporties of condition with an equal value.
	// use an empty condition to clear the entire array
	public function remove(string $table, object $condition) {
		$obj_table = $this->get_table($table);
		$removed_table = array_filter($obj_table, function($row) use ($condition) {
			if (is_null($row)) {
				return false;
			}
			return  ! $this->is_row_condition($row, $condition);
		});
		$this->save_table($table, $removed_table);
	}

	// update/create the columns in every row which much the condition
	// use an empty condition to update the entire array.
	public function update(string $table, object $update, object $condition) {
		$obj_table = $this->get_table($table);
		foreach($obj_table as $index => $row) {
			if ($this->is_row_condition($row,$condition)) {
				foreach(get_object_vars($update) as $column => $value) {
					$obj_table[$index]->$column = $value;
				}
			}
		}
		$this->save_table($table, $obj_table);
	}

	// HELPER METHODS

	protected function get_table(string $table) {
		$this->validate_table_exists($table);
		// here we can introduce some efficiency by caching our tables in memory
		// rather then reading them out of the file each time
		$json_table = file_get_contents($this->tbl_path($table));
		$table_obj =json_decode($json_table, false);
		return $table_obj;
	}

	protected function save_table(string $table_name, array $table_object) {
		$file = fopen($this->tbl_path($table_name),"w");
		// we use array_values in order to reindex the array.
		fwrite($file, json_encode(array_values($table_object)));
		fclose($file);
	}

	// check if a row much a condition
	protected function is_row_condition(object $row, ?object $condition) {
		if (is_null($condition)) {
			return true;
		}
		foreach(get_object_vars($condition) as $column => $value) {
			if(!property_exists($row,$column)) {
				return false;
			}
			if($row->$column != $value) {
				return false;
			}
		}
		return true;
	}

	// produce the path to the table json file.
	protected function tbl_path(string $table) {
		return $this->current_database . "/" . $table . ".json";
	}

	// VALIDATION METHODS
	// an error is thrown when the validation fails
	
	protected function validate_database_free(string $db) {
		if (is_dir($db)) {
			echo "Error: A database with the name $db already exists\n";
			throw new DatabaseAlreadyExists();
		}
	}
	protected function validate_database_exists(string $db) {
		if (!is_dir($db)) {
			echo "Error: There is not database with the name $db\n";
			throw new DatabaseDosentExists;
		}
	}
	protected function validate_database_connected() {
		if (is_null($this->current_database)) {
			echo "Error: No database is connected.\n";
			throw new DatabaseNotConnected();
		}
		$this->validate_database_exists($this->current_database);
	}
	protected function validate_table_free(string $table) {
		$this->validate_database_connected();
		if (file_exists($this->tbl_path($table))) {
			echo "Error: A table with the name $table already exists\n";
			throw new TableAlreadyExists();
		}
	}
	protected function validate_table_exists(string $table) {
		$this->validate_database_connected();
		if (!file_exists($this->tbl_path($table))) {
			echo "Error: There is no table $table\n";
			throw new TableAlreadyExists();
		}
	}
}

class JsonDatabaseCopy extends JsonDatabase {
	// the functionality required for part 2 of the assignment.
	// I am doing this assignment on saturday so I cannot ask for clarification about this part of the assignment.
	// since getting partial data from a table is already possible using the get_rows method of JsonDatabase.
	// I decided to make a function for conditionaly copying a table inside the database.
	public function copyPartialTable(string $old_table, string $new_table, object $condition) {
		$this->create_table($new_table);
		$partial_table = $this->get_rows($old_table,$condition);
		$this->insert($new_table, $partial_table);
		return $partial_table;
	}
	// and a human readable print of a partial array
	public function printPartialTable(string $table, $condition) {
		print_r($this->get_rows($table,$condition));
	}
}



?>
