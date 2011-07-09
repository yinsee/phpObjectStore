<?php
/*
ObjectStore is a flat file value-key pair database for PHP, inspired by MongoDB PHP class.
It is suitable to be used in simple CMS websites that does not require full-featured database.

Copyriht (C) 2011 "Daddycat" Tan Yin See -- yinsee@wsatp.com 

Latest Source: http://github.com/yinsee/phpObjectStore

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// define where to store the database
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
	
	function __construct($name, ObjectStore $parent=NULL)
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
		@unlink(OBJECT_STORE_DB_FOLDER.$this->path);
		unset($this->data);
		unset($this->unique_keys);	
		$this->data = array();
		$this->unique_keys = array('id');
	}
	
	// set unique keys
	// only support single key at the moment
	function unique(array $key)
	{
		if (!in_array($key, $this->unique_keys)) $this->unique_keys[] = $key;
	}
	
	protected function _save()
	{
		if (empty($this->path)) return;
		
		// save $data to file as serialized
		file_put_contents(OBJECT_STORE_DB_FOLDER.$this->path, serialize($this->data));
	}
	
	private function _new_id()
	{
		return md5(rand()).'#'.time();
	}
	
	// add record 
	// todo: unique key checks, replace if $or_replace = true
	function insert(array $newdata, $or_replace=false)
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
	// todo: unique key checks
	function update($id, array $newdata, $insert = true)
	{
		$this->data[$id] = array_merge_recursive($this->data[$id], $newdata);
		if ($this->autosave) $this->_save();
	}
	
	// replace existing record if found
	// if not found, add new records if $insert = true
	// todo: unique key checks
	function set($id, array $newdata, $insert = true)
	{
		$this->data[$id] = $newdata;
		if ($this->autosave) $this->_save();
	}
	
	// search existing match by id or sets, and return array of matches
	// return empty array if no match
	// sort by $sort field
	// only return first entry if $multple=false
	// todo: sort
	// todo: regex search
	function find($id_or_keys, $multiple = true, $sort = false)
	{
		if (!is_array($id_or_keys))
		{
			return $this->data[$id_or_keys];
		}
		
		$return = array();
		foreach($this->data as $row)
		{
			if (empty($id_or_keys)) 
			{
				$return = $row;
			}
			else
			{
				$match = true;
				foreach($id_or_keys as $k=>$v)
				{
					// compare each key-value, if not match then break with match=false
					if (!isset($row[$k]) || $row[$k]!=$v) {
						$match = false;
						break;
					}
				}
				// if matched, store to $return
				if ($match && $multiple) $return[] = $row;
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