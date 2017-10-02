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
    
    define('LEVEL_MAIN',0);
    define('LEVEL_MINIMUM', 1);
    define('LEVEL_NORMAL', 2);
    define('LEVEL_DETAILED', 3);
    define('LEVEL_DIAGNOSTIC', 4);

    define('LEVEL_ERRORS', 5);

    $logLevel = 0;

    if (LOG_LEVEL == 'Minimal'){
        $logLevel = 1;
    }else if(LOG_LEVEL == 'Normal'){
        $logLevel = 2;
    }else if (LOG_LEVEL == 'Detailed'){
        $logLevel = 3;
    }else if (LOG_LEVEL == 'Diagnostic'){
        $logLevel = 4;
    }
   
    Debug::$renderers[LEVEL_MAIN] = function($tag, $text) {
            echo "<div style='font-size: 14px'>{$tag}: {$text}</div>";
        };

    if ($logLevel >= LEVEL_MINIMUM){
        Debug::$renderers[LEVEL_MINIMUM] = function($tag, $text) {
            echo "<div style='font-size: 12px'>{$tag}: {$text}</div>";
        };
    }
    
    if ($logLevel >= LEVEL_NORMAL){
        Debug::$renderers[LEVEL_NORMAL] = function($tag, $text) {
            echo "<div style='font-size: 10px'>{$tag}: {$text}</div>";
        };
    }
    
    if ($logLevel >= LEVEL_DETAILED){
        Debug::$renderers[LEVEL_DETAILED] = function($tag, $text) {
           echo "<div style='font-size: 8px'>{$tag}: {$text}</div>";
       };
    }
    if ($logLevel >=LEVEL_DIAGNOSTIC){
        Debug::$renderers[LEVEL_DIAGNOSTIC] = function($tag, $text) {
               echo "<div style='font-size: 6px'>{$tag}: {$text}</div>";
           };
    }
    // Error should always display
    Debug::$renderers[LEVEL_ERRORS] = function($tag, $text) {
        echo "<div style='font-color: #ff2222'>{$tag}: {$text}</div>";
    };
     
    $importClass = new wot_import(WOT_PATH);
    $importClass->uploadMeta();
    

?>
</pre>
