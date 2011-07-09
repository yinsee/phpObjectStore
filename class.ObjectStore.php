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


class ObjectStore {
	// objectstore path for internal reference
	private $path;
	// store the data
	private $data = array();
	// store the unique keys
	private $unique_keys = array('id');
	// whether we should auto save upon update / set (more file access, possible performance issue?)
	private $autosave = false;
	// use compression for saving (set level 1-9, 0 for no compression)
	private $gzip = 0;
	// where to store the files
	private $dir;
	// store the options to pass on 
	private $options;

	function __construct($name, array $options=null, ObjectStore $parent=null)
	{
		if (empty($name)) {
			trigger_error(__class__."(\$name) is required");
			exit;
		}
		
		if (isset($parent) && $parent->path!='')
			$this->path = $parent->path.".".$name;
		else
			$this->path = $name;
		
		// process options
		$this->options = $options;
		if (isset($options['gzip'])) $this->gzip = $options['gzip'];
		if (isset($options['autosave'])) $this->autosave = $options['autosave'];
		// define where to store the database
		if (isset($options['dir'])) 
		{
			$this->dir = $options['dir'].'/';
			$this->dir = str_replace('//','/',$this->dir);
		}
		else
		{
			$this->dir = dirname(__file__).'/db/';
		}

		// try to create folder for database
		if (!is_dir($this->dir)) 
		{
			if (!@mkdir($this->dir))
			{
				die("Please create database folder at ".$this->dir." with permission 0777");
			}
		}

		
		// load data if found
		if (file_exists($this->dir.$this->path))
		{
			$data_raw = file_get_contents($this->dir.$this->path);
			if ($this->gzip) $data_raw = gzuncompress($data_raw);
			$this->data = unserialize($data_raw);
		}
	}
	
	function __get($prop)
	{
		if (!isset($this->$prop))
		{
			$this->$prop = new ObjectStore($prop, $this->options, $this);
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
		@unlink($this->dir.$this->path);
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
		$data_raw = serialize($this->data);
		if ($this->gzip) $data_raw = gzcompress($data_raw, $this->gzip);
		file_put_contents($this->dir.$this->path, $data_raw);
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