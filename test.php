<?
require("class.objectstore.php");
error_reporting(E_ALL);
ini_set('display_errors',1);

$a = new ObjectStore();
$a->b3->drop();
$id = $a->b3->insert(array('test'=>999, 'z'=>array('ab'=>'cd', 'ef'=>'fg')));
$id = $a->b3->insert(array('test'=>123, 'b'=>array('c'=>'d', 'e'=>'f')));
$a->b3->update($id, array('test'=>1234, 'b'=>array('g'=>'h')));

print "<pre>";
print_r($a);
print "</pre>";

		
?>