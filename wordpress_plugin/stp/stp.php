<?php

/*
Plugin Name:  CSSB
Plugin URI : http://4X5RY.com
Description: This is a cs 1.6 live scoreboard
Version:0.0.0.0
Author: 4X5RY
Author URI: http://4X5RY.com
License: license go brrrrr
Text Domain: CSSB
*/


add_shortcode("menu", "addMenu");
function addMenu(){
	return include __DIR__.'/index.php';

}
?>
