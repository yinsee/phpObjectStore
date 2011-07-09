<?
/*
test script for phpObjectStore
*/
require("class.objectstore.php");

$db = new ObjectStore('mystore', array('gzip'=>9));
// test drop
$db->products->drop();

// test insert
print "<h1>Test Insert and Update</h1>";
$db->products->insert(array('brand'=>'Dell', 'model'=>'Inspiron 15', 'specs'=>array('resolution'=>'1680x1050', 'weight'=>4.5, 'size'=>15)));
$acer = $db->products->insert(array('brand'=>'Acer', 'model'=>'Timeline 840', 'specs'=>array('resolution'=>'1024x600', 'weight'=>1.5, 'size'=>11)));
$db->products->insert(array('brand'=>'Dell', 'model'=>'Latitude Xinclair', 'specs'=>array('resolution'=>'1440x900', 'weight'=>1.5, 'size'=>13)));
$macair = $db->products->insert(array('brand'=>'Apple', 'model'=>'Macbook Air 11"', 'specs'=>array('resolution'=>'1440x900', 'weight'=>1.2, 'size'=>11)));
$db->products->update($macair, array('options'=>array('256GB SSD', '4GB Ram')));
print "<pre>";
print_r($db->products);
print "</pre>";

// test set
print "<h1>Test Set</h1>";
$db->products->set($acer, array('brand'=>'Acer', 'model'=>'Timeline 360', 'specs'=>array('resolution'=>'1024x600', 'weight'=>1.5, 'size'=>11)));
print "<pre>";
print_r($db->products->find($acer));
print "</pre>";

// test find
print "<h1>Test Find</h1>";
$res = $db->products->find(array('brand'=>'Dell'));
print "<pre>";
print_r($res);
print "</pre>";		
print "<h1>Test Find with Regexp /air/i</h1>";
$res = $db->products->find(array('model'=>new Regexp('/air/i')));
print "<pre>";
print_r($res);
print "</pre>";		



// test delete
print "<h1>Test Delete</h1>";
$db->products->delete($macair);
print "<pre>";
print_r($db->products);
print "</pre>";		
?>