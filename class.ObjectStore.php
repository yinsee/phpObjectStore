<?php
/*
ObjectStore is a flat file value-key pair database for PHP

Usage:
$db = new ObjectStore('name');
$db->insert(array newdata);
$db->insert(array newdata);
$db->insert(array newdata);

*/

// where to store the database
define('OBJECT_STORE_DB_FOLDER', dirname(__file__).'/db/');

// try to create folder for database
if (!is_dir(OBJECT_STORE_DB_FOLDER)) 
{
	if (!@mkdir(OBJECT_STORE_DB_FOLDER))
	{
		die("Please create database folder at ".OBJECT_STORE_DB_FOLDER." with permission 0777");
	}
}

class ObjectStore {
	// store the data
	var $data = array();
	// store the unique keys
	var $unique_keys = array('id');
	// whether we should auto save upon update / set (more file access, possible performance issue?)
	var $autosave = false;
	// objectstore path for internal reference
	private $path;
	
	function __construct($name, $parent=NULL)
	{
		if (empty($name)) {
			trigger_error(__class__."(\$name) is required");
			exit;
		}
		
		if (isset($parent) && $parent->path!='')
			$this->path = $parent->path.".".$name;
		else
			$this->path = $name;
		
		// load data if found
		if (file_exists(OBJECT_STORE_DB_FOLDER.$this->path))
		{
			$this->data = unserialize(file_get_contents(OBJECT_STORE_DB_FOLDER.$this->path));
		}
	}
	
	function __get($prop)
	{
		if (!isset($this->$prop))
		{
			$this->$prop = new ObjectStore($prop, $this);
		}
		return $this->$prop;
	}
	
	function __destruct()
	{
		if (!$this->autosave) $this->_save();
	}
	
	// clear all and remove the data file
	function drop()
	{
		unlink(OBJECT_STORE_DB_FOLDER.$this->path);
		unset($this->data);
		unset($this->unique_keys);	
		$this->data = array();
		$this->unique_keys = array('id');
	}
	
	// set unique keys
	// only support single key at the moment
	// todo: not working yet
	function unique(array $key)
	{
		if (!in_array($key, $this->unique_keys)) $this->unique_keys[] = $key;
	}
	
	protected function _save()
	{
		if (empty($this->path)) return;
		
		// save $data to file as serialized
		file_put_contents(OBJECT_STORE_DB_FOLDER.$this->path, serialize($this->data));
		print "Saved ($this->path)";
	}
	
	private function _new_id()
	{
		return md5(rand()).'#'.time();
	}
	
	// add record 
	// todo: if unique key clashes, replace if $or_replace = true
	function insert($newdata, $or_replace=false)
	{
		$newid = $this->_new_id();
/*		if (isset($newid) && !$or_replace)
		{
			trigger_error("Record with id $newid already exists");
			return $newid;
		}
*/
		$this->data[$newid] = $newdata;
		if ($this->autosave) $this->_save();		
		return $newid;
	}
	
	
	// update existing record if found
	// if not found, add new records if $insert = true
	function update($id, $newdata, $insert = true)
	{
		$this->data[$id] = array_merge_recursive($this->data[$id], $newdata);
		if ($this->autosave) $this->_save();
	}
	
	// replace existing record if found
	// if not found, add new records if $insert = true
	function set($id, $newdata, $insert = true)
	{
		$this->data[$id] = $newdata;
		if ($this->autosave) $this->_save();
	}
	
	// search existing match and return array of matches
	// return empty array if no match
	// sort by $sort field
	// only return first entry if $multple=false
	function find($keys, $sort = false, $multiple = true)
	{
		$return = array();
		foreach($this->data as $row)
		{
			if (empty($keys)) 
			{
				$return = $row;
			}
			else
			{
				$match = true;
				foreach($keys as $k=>$v)
				{
					// compare each key-value, if not match then break with match=false
					if (!isset($row[$k]) || $row[$k]!=$v) {
						$match = false;
						break;
					}
				}
				// if matched, store to $return
				if ($match && $multpile) $return[] = $row;
				elseif ($match) $return = $row;
			}
			// once found, return immediately if not mutliple
			if (!$multiple && !empty($return)) break;
		}
		return $return;
	}
	
	// delete existing data by id
	// return no of records deleted
	function delete($id)
	{
		unset($this->data[$id]);
		if ($this->autosave) $this->_save();
	}
}
?>	