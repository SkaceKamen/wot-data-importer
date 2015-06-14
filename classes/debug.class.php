<?php
class Debug
{
	public static $renderers = array();
	public static $messages = array();
	public static $tag = 0;
	public static function log($text, $level = 0)
	{
		if (!isset(static::$messages[$level]))
			static::$messages[$level] = array();
		static::$messages[$level][] = $text;
		if (!isset(static::$renderers[$level]))
			echo (++static::$tag) . ': ' . $text . PHP_EOL;
		else {
			$function = static::$renderers[$level];
			$function(static::$tag++, $text);
		}
	}
}