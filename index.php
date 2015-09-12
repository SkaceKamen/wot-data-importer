<pre>
<?php
    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
    error_reporting(-1);

    set_time_limit(1000);

    require_once 'config.php';
    //require_once 'classes/wot.xml.class.php';
    require_once 'classes/debug.class.php';
    require_once 'classes/wot_import.class.php';
    //require_once 'read_mo.php';

    define('LEVEL_MAIN', 0);
    define('LEVEL_INFO', 1);
    define('LEVEL_ERRORS', 2);

    Debug::$renderers[LEVEL_INFO] = function($tag, $text) {
        echo "<div style='font-size: 10px'>{$tag}: {$text}</div>";
    };
    Debug::$renderers[LEVEL_ERRORS] = function($tag, $text) {
        echo "<div style='font-color: #ff2222'>{$tag}: {$text}</div>";
    };

    //$update = true;

    //if (isset($_GET['update']))
    //{
    //    $update = (bool)$_GET['update'];
    //}

    $importClass = new wot_import(WOT_PATH);
    $importClass->uploadMeta();
    
    //if ($update)
    //{
    //    Debug::log("Update mode detected", LEVEL_MAIN);
	
    //    $translation = array();
	
    //    $translate_dir = 'texts/' . $version . '/';
    //    $dir = opendir($translate_dir);
    //    while($file = readdir($dir))
    //    {
    //        if ($file!='.' && $file!='..')
    //        {
    //            $ex = explode('.',$file);
    //            $ex = $ex[0];
			
    //            $translation[$ex] = read_mo($translate_dir . "//" . $file);  
			
    //        }
    //    }
    //}



?>
</pre>
