<?php

function generateRandomString($length = 15) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}





$FOLDER = 'game_files/';
$r =  generateRandomString();
$newgame = $r . '.json';

$template_file = 'game_template.json';
$template_handler = fopen($template_file, 'r') or die('Cannot find template ' . $template_file );
$template = fread($template_handler, filesize($template_file));
$handle = fopen($FOLDER . $newgame, 'w') or die('Cannot open file:  '. $newgame);

if (isset($_GET['sides'])){
	$sides = (int) $_GET['sides'];
	$data = json_decode($template, true);
	$data['size'] = $sides;
}

fwrite($handle, json_encode($data));
fclose($handle);
fclose($template_handler);

//      /dir/../dir
preg_match('/(\/.*)\/.*?\.php/' , $_SERVER['PHP_SELF'], $path);
//echo $path[1];

/*header("Location: http://" . $_SERVER['HTTP_HOST'] . $path[1] . '/' . $FOLDER . $newgame);
exit;
*/

echo $r;

?>