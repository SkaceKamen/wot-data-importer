<?php
include_once 'classes/mysql.class.php';
include_once 'config.php';
$mysql = new mysqler(MYSQL_HOSTNAME,MYSQL_PORT,MYSQL_USERNAME,MYSQL_PASSWORD,MYSQL_DATABASE) or die('Could not connect to the database server.');

?>