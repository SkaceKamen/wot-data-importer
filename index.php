<pre>
<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

set_time_limit(1000);

require 'config.php';
require 'classes/wot.xml.class.php';
require 'classes/debug.class.php';
require 'classes/wot_import.class.php';
require 'db_class.php';
require 'read_mo.php';

define('LEVEL_MAIN', 0);
define('LEVEL_INFO', 1);
define('LEVEL_ERRORS', 2);

Debug::$renderers[LEVEL_INFO] = function($tag, $text) {
    echo "<div style='font-size: 10px'>{$tag}: {$text}</div>";
};
Debug::$renderers[LEVEL_ERRORS] = function($tag, $text) {
	echo "<div style='font-color: #ff2222'>{$tag}: {$text}</div>";
};

function shelltype($string)
{
	switch($string)
	{
		case 'ARMOR_PIERCING': return 'ap'; break;
		case 'ARMOR_PIERCING_CR': return 'apcr'; break;
		case 'HIGH_EXPLOSIVE': return 'he'; break;
		case 'HOLLOW_CHARGE': return 'heat'; break;
	}
	return '';
}

function tank_check_unlocks($parent_id, $unlock, $nation)
{
	//Debug::log('tank_check_unlocks BEGIN for $parent_id = '.$parent_id.', $unlock = '.$unlock.', $nation = '.$nation.'.', LEVEL_INFO);
	global $mysql, $version_id;
	if (isset($unlock->vehicle))
	{
		$vars = $unlock->children();
		foreach($vars as $key=>$data)
		{
			if ($key == 'vehicle')
			{
				if (!is_array($data))
					$data = array($data);
				foreach($data as $tank)
				{
					$node = $nation . '-' . $tank;       
					$cost = @$tank->cost;
					// Todo: Migrate to stored procedure
					$tank_id = $mysql->query("SELECT wot_tanks_id FROM wot_tanks WHERE name_node = '$node' AND wot_version_id = '$version_id'");
					$tank_id = $mysql->row($tank_id);
					$tank_id = $tank_id['wot_tanks_id'];
					if ($tank_id == null)
					{
						Debug::log('<b>ERROR!</b> Failed to seach child ' . print_r($node, true), LEVEL_ERRORS);
					}
					else
					{   
						// Todo: Migrate to stored procedure
						$mysql->insertOrUpdate('wot_tanks_parents', "wot_tanks_id = {$tank_id} AND parent_id = {$parent_id}", array('wot_tanks_id' => $tank_id, 'parent_id' => $parent_id, 'cost' => $cost));
					}
				}
			}
		}
	}
	//Debug::log('tank_check_unlocks END for $parent_id = '.$parent_id.', $unlock = '.$unlock.', $nation = '.$nation.'.',LEVEL_INFO);
}

$update = true;

if (isset($_GET['update']))
{
	$update = (bool)$_GET['update'];
}

if (!file_exists(WOT_PATH)) 
{
	die('Path ' . WOT_PATH . ' is not valid');
}

if (isset($_GET['version'])) 
{
	$version = mysql_real_escape_string($_GET['version']);
} 
else 
{
	$version = WOT_PATH . 'version.xml';
	if (!file_exists($version)) 
	{
		die("Failed to find path to {$version}");
	}
	$version = simplexml_load_file($version);
	$version = trim((string)$version->version);
}

Debug::log('Version: ' . $version, LEVEL_MAIN);

// Todo: Migrate to stored procedure
$exists = $mysql->query("SELECT id, version FROM wot_versions WHERE version = '{$version}' OR id = '{$version}'");
$exists = $mysql->row($exists);

$new_tanks = array();

function decodeDirectory($path, $target, $base = null)
{
	if (is_null($base)) {
		$base = $path;
	}
	
	$handle = opendir($path);
	
	while(($file = readdir($handle)) !== false) {
		if ($file == '.' || $file == '..')
			continue;
		$file = $path . '/' . $file;
		if (!file_exists($file)) {
			Debug::log("Problem: {$path} {$target} {$base} {$file}", LEVEL_ERRORS);
		}
		
		if (is_dir($file)) {
			decodeDirectory($file, $target, $base);
		} else {
			$path_target = $target . substr($path, strlen($base)) . '/';
			if (!file_exists($path_target)) {
				mkdir($path_target, 777, true);
			}
			
			$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
			$name = pathinfo($file, PATHINFO_BASENAME);
			if ($ext == 'xml') {
				WotXML::decodePackedFile($file, $name, $path_target . $name);
			}
		}
	}
	
}



if (!$exists) 
{
	Debug::log('New version detected', LEVEL_MAIN);

	// Todo: Migrate to stored procedure
	$mysql->insert('wot_versions', array('version' => $version, 'published' => time()));

	$version_id = $mysql->getLastUpdatedId();
	
	WotXML::init();
	
	decodeDirectory(WOT_PATH . 'res/scripts/item_defs/vehicles/', 'versions/' . $version . '/');
	
	foreach(WotXML::$failed as $fail) 
	{
		Debug::log("{$fail['filename']} failed: " . $fail->getMessage(), LEVEL_ERRORS);
	}
	
	Debug::log('XMLs parased', LEVEL_INFO);
	
	if (!file_exists('texts/' . $version))
	{
		mkdir('texts/' . $version);
	}
	
	$folder = WOT_PATH . 'res/text/LC_MESSAGES/';
	$folder_handle = opendir($folder);
	
	while(($file = readdir($folder_handle)) !== false)
	{
		if ($file == '.' || $file == '..' || is_dir($folder . $file))
		{
			continue;
		}
		
		$name = pathinfo($file, PATHINFO_FILENAME);
		if (substr($name, strlen($name) - 8) == 'vehicles' || $name == 'artefacts')
		{
			copy($folder . $file, 'texts/' . $version . '/' . $file);
		}
	}
	
	Debug::log('Translations copied', LEVEL_INFO);
} 
else 
{
	$version_id = $exists['id'];
	$version = $exists['version'];
}
$vehicles_path = 'versions/' . $version . '/';

if ($update)
{
	Debug::log("Update mode detected", LEVEL_MAIN);
	
	$translation = array();
	
	$translate_dir = 'texts/' . $version . '/';
	$dir = opendir($translate_dir);
	while($file = readdir($dir))
	{
		if ($file!='.' && $file!='..')
		{
			$ex = explode('.',$file);
			$ex = $ex[0];
			
			$translation[$ex] = read_mo($translate_dir . "//" . $file);  
			
		}
	}
}

function TranslateToLocal($key)
{
	global $translation;
	global $mysql;
	if (substr($key,0,1) == '#')
	{
		$key = substr($key,1,strlen($key));
		
		$file = substr($key,0,strpos($key,':'));
		$_key = substr($key,strpos($key,':')+1,strlen($key));
		
		if (isset($translation[$file][$_key]))
			return $translation[$file][$_key];
		else
		{
			Debug::log('<p><b>Translate error</b> '.$key.'</p>', LEVEL_ERRORS);
			
			return $key;
		}
	} else {
		return $key;
	}
}

$mysql->allowUpdate = $update;

//Load equipment
$equipment = simplexml_load_file($vehicles_path . '/common/optional_devices.xml');
foreach($equipment->children() as $node => $item) {
	$icon = (string)$item->icon;
	$icon = explode(' ', $icon);
	$icon = $icon[0];
	$icon = str_replace('../maps/icons/artefact/', '', $icon);
	
	$price = 0;
	$price_gold = 0;
	if (isset($item->price->gold))
		$price_gold = (int)$item->price;
	else
		$price = (int)$item->price;
	
	$weight = 0;
	if (isset($item->script->weight))
		$weight = (int)$item->script->weight;
	
	$include = '';
	$exclude = '';
	
	$inc = 'include';
	if (isset($item->vehicleFilter->$inc->vehicle->tags))
		$include = (string)$item->vehicleFilter->$inc->vehicle->tags;
	if (isset($item->vehicleFilter->exclude->vehicle->tags))
		$exclude = (string)$item->vehicleFilter->exclude->vehicle->tags;
	
	//remove tabs
	$include = preg_replace('/\s+/', ' ', $include);
	$exclude = preg_replace('/\s+/', ' ', $exclude);
	
	$data = array(
		'wot_version_id' => $version_id,
		'name' => TranslateToLocal((string)$item->userString),
		'name_node' => $node,
		'description' => TranslateToLocal((string)$item->description),
		'icon' => $icon,
		'price' => $price,
		'price_gold' => $price_gold,
		'removable' => (mb_strtolower(((string)$item->removable)) == mb_strtolower('true') ? 1 : 0),
		'weight' => $weight,
		'vehicle_tags_include' => $mysql->quoteString($include),
		'vehicle_tags_exclude' => $mysql->quoteString($exclude)
	);
	
	Debug::log("<b>Prepare</b> {$node} " . print_r($data, true), LEVEL_INFO);
	
	$equipment_id = $mysql->insertOrUpdate('wot_equipment',"name_node = '$node' AND wot_version_id = '$version_id'",$data);

	if ($equipment_id) {
		Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
	} else {
		$equipment_id = $mysql->query("SELECT wot_equipment_id FROM wot_equipment WHERE name_node = '$node' AND wot_version_id = '$version_id'");
		$equipment_id = $mysql->row($equipment_id);
		$equipment_id = $equipment_id['wot_equipment_id'];
		Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
	}
	
	$break = false;
	//Debug::log("<b>Begin child loop for</b> {$node} " , LEVEL_INFO);
	foreach($item->script->children() as $param_node => $param_value) {
		if ($param_node == 'weight')
			continue;
		if ($param_node == 'attribute' || $param_node == 'value' || $param_node == 'factor') {
			$data = array(
				'wot_equipment_id' => $equipment_id,
				'param' => (string)$item->script->attribute,
				'value' => isset($item->script->value) ? (string)$item->script->value : (string)$item->script->factor
			);
			$break = true;
		} else {
			$data = array(
				'wot_equipment_id' => $equipment_id,
				'param' => (string)$param_node,
				'value' => (string)$param_value
			);
		}
		if (strpos($data['param'], '/'))
			$data['param'] = substr($data['param'], strpos($data['param'], '/')+1);
		
		if ($mysql->insertOrUpdate('wot_equipment_params',"wot_equipment_id = '$equipment_id' AND param = '{$data['param']}'",$data)) {
			Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
		} else {
			Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
		}
		if ($break)
			break;
	}
	//Debug::log("<b>End child loop for</b> {$node} " , LEVEL_INFO);
}

$base_dir = $vehicles_path;
$dir_nations = opendir($vehicles_path);

$global_tags = array();

while($nation = readdir($dir_nations))
{
	if ($nation!='.' && $nation!='..' && $nation != 'common')
	{
		$file = $base_dir . "/" . $nation . "/list.xml";
		

		Debug::log('<hr><h3>Tanks</h3>');
		/**
		 * Dynamic functions for handling the data mapping for each type
		 * 
		 **/

		$shellsData = function($shell,$node,$version_id)
		{
			
			$price = 0;
			$price_gold = 0;
			if (isset($shell->price->gold))
			{
			    $price_gold = (int)$shell->price;
			}
			else
			{
			    $price = (int)$shell->price;
			}
			
			return array(
			          'wot_version_id' => $version_id,
			          'id' => (int)$shell->id,
			          'name_node' => $node,
			          'name' => TranslateToLocal((string)$shell->userString),
			          'price' => (int)$price,
			          'price_gold' => (int)$price_gold,
			          'shell_type' => shelltype((string)$shell->kind),
			          'shell_caliber' => (int)$shell->caliber,
			          'shell_damage_armor' => (int)$shell->damage->armor,
			          'shell_damage_device' => (int)$shell->damage->devices,
			          'shell_explosion_radius' => (float)$shell->explosionRadius,
			          'shell_tracer' => (bool)$shell->isTracer  
			        );
		};

		$radio_data = function($radioContent, $version_id)
		{
			return array(
			  'wot_version_id' => $version_id,
			  'name' => TranslateToLocal((string)$radioContent->userString),
			  'level' => (int)$radioContent->level,
			  'price' => (float)$radioContent->price,
			  'weight' => (float)$radioContent->weight,
			  'health' => (int)$radioContent->maxHealth,
			  'health_regen' => (int)$radioContent->maxRegenHealth,
			  'repair' => (float)$radioContent->repairCost,
			  'radio_distance' => (int)$radioContent->distance
			);
		};
		
		/**
		READ TANKS
		 **/         
		Debug::log("Reading $nation ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		$tanks = $list->children();
		foreach($tanks as $node=>$tank)
		{
			$node = $nation . '-' . $node;
			$tags = preg_split('/\s+/',$tank->tags);
			
			switch($tags[0])
			{
				case 'lightTank': $class = 'light'; break;
				case 'mediumTank': $class = 'medium'; break;
				case 'heavyTank': $class = 'heavy'; break;
				case 'AT-SPG': $class = 'td'; break;
				case 'SPG': $class = 'spg'; break;
			}
			
			$secret = 0;
			$igr = 0;
			foreach($tags as $tag) {
				if ($tag == 'secret')
					$secret = 1;
				if ($tag == 'premiumIGR')
					$igr = 1;
				if (!isset($global_tags[$tag]))
					$global_tags[$tag] = true;
			}
			
			$price = 0;
			$price_gold = 0;
			if (isset($tank->price->gold))
				$price_gold = (int)$tank->price;
			else
				$price = (int)$tank->price;
			
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$tank->id,
			  'name_node' => $node,
			  'name' => TranslateToLocal((string)$tank->userString),
			  'nation' => $nation,
			  'class' => $class,
			  'price' => (int)$price,
			  'price_gold' => (int)$price_gold,
			  'tags' => implode(' ', $tags),
			  'secret' => $secret,
			  'igr' => $igr,
			  'level' => (int)$tank->level,
			  'crew' => -1); // Default the crew which will get set later
			
			if ($mysql->insertOrUpdate('wot_tanks',"name_node = '$node' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
				$new_tanks[] = $node;
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} 
		}
		
		Debug::log('<hr><h3>Radios</h3>');
		
		/**
		READ RADIOS
		 **/
		$table = 'wot_items_radios';
		
		$file = $base_dir . "/" . $nation . "/components/radios.xml";
		Debug::log("Reading $nation\\radios ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$radios_ids = $list->ids->children();
		
		foreach($radios_ids as $node=>$content)
		{
			
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$content,
			  'name_node' => $node
			);
			if ($mysql->insertOrUpdate($table, "name_node = '$node' AND wot_version_id = '$version_id'", $data))
			{
				Debug::log("<b>Insert ids</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update ids</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}

			$content = 'default';
		} 
		
		$radios = $list->shared->children();
		foreach($radios as $node=>$content)
		{
			$node = $nation . '-' . $node;

			$data = $radio_data($content,$version_id);

			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert shared</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update shared</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}      
			$content = 'default';
		}	

		Debug::log('<hr><h3>Chassis</h3>');
		
		/**
		CHASSIS
		 **/    
		
		$table = 'wot_items_chassis';
		
		$file = $base_dir . "/" . $nation . "/components/chassis.xml";
		Debug::log("Reading $nation\\chassis ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$ids = $list->ids->children();
		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$content,
			  'name_node' => $node
			);
			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} 
		}
		
		Debug::log('<hr><h3>Turrets</h3>');
		
		/**
		TURRETS
		 **/    
		
		$table = 'wot_items_turrets';
		
		$file = $base_dir . "/" . $nation . "/components/turrets.xml";
		Debug::log("Reading $nation/turrets ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$ids = $list->ids->children();
		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$content,
			  'name_node' => $node
			);
			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} 
		}
		
		Debug::log('<hr><h3>Shells</h3>');
		
		/**
		SHELLS
		 **/
		$table = 'wot_items_shells';
		
		$file = $base_dir . "/" . $nation . "/components/shells.xml";
		Debug::log("Reading $nation/shells ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$items = $list->children();
		foreach($items as $node=>$content)
		{
			if ($node != 'icons')
			{
				$node = $nation . '-' . $node;

				$data = $shellsData($content, $node, $version_id);
				
				if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
				{
					Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} else {
					Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} 
			}
		}
		
		Debug::log('<hr><h3>Guns</h3>');
		
		/**
		READ GUNS
		 **/
		$table = 'wot_items_guns';
		
		$file = $base_dir . "/" . $nation . "/components/guns.xml";
		Debug::log("Reading $nation/guns ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$ids = $list->ids->children();
		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$content,
			  'name_node' => $node,
			  'gun_max_ammo' => 0
			);
			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}   
		} 
		
		$items = $list->shared->children();
		foreach($items as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'name' => TranslateToLocal((string)$content->userString),
			  'name_node' => $node,
			  'level' => (int)$content->level,
			  'price' => (int)$content->price,
			  'weight' => (float)$content->weight,
			  'health' => (int)$content->maxHealth,
			  'health_regen' => (int)$content->maxRegenHealth,
			  'repair' => (float)$content->repairCost,
			  'gun_max_ammo' => (int)$content->maxAmmo,
			  'gun_impulse' => (float)$content->impulse,
			  'gun_recoil_amplitude' => (float)$content->recoil->amplitude,
			  'gun_recoil_backoffTime' => (float)$content->recoil->backoffTime,
			  'gun_recoil_returnTime' => (float)$content->recoil->returnTime,
			  'gun_pitch_limits' => (string)$content->pitchLimits,
			  'gun_rotation_speed' => (int)$content->rotationSpeed,
			  'gun_reload_time' => (float)$content->reloadTime,
			  'gun_aiming_time' => (float)$content->aimingTime,
			  'gun_clip_count' => (int)$content->clip->count,
			  'gun_clip_rate' => (int)$content->clip->rate,
			  'gun_burst_count' => (int)$content->burst->count,
			  'gun_burst_rate' => (int)$content->burst->rate,
			  'gun_dispersion_radius' => (float)$content->shotDispersionRadius,
			  'gun_dispersion_turret_rotation' => (float)$content->shotDispersionFactors->turretRotation,
			  'gun_dispersion_after_shot' => (float)$content->shotDispersionFactors->afterShot,
			  'gun_dispersion_damaged' => (float)$content->shotDispersionFactors->whileGunDamaged,
			  'turret_yaw_limits' => (string)$content->turretYawLimits
			);
			$gun_id = $mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data); 
			if ($gun_id)
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				$gun_id = $mysql->query("SELECT wot_items_guns_id FROM $table WHERE name_node = '$node' AND wot_version_id = '$version_id'");
				$gun_id = $mysql->row($gun_id);
				$gun_id = $gun_id['wot_items_guns_id'];
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}
			
			$shells = $content->shots->children();
			foreach($shells as $s_node=>$s_content)
			{
				$s_node = $nation . '-' . $s_node;
				$shell_id = $mysql->query("SELECT wot_items_shells_id FROM wot_items_shells WHERE name_node = '$s_node' AND wot_version_id = '$version_id'");
				$shell_id = $mysql->row($shell_id);
				$shell_id = $shell_id['wot_items_shells_id'];
				
				if (!$shell_id) {
					Debug::log("\t<b>Error</b> Failed to find shell {$node2}", LEVEL_ERRORS);
				} else {
					$data = array(
					  'wot_items_guns_id' => $gun_id,
					  'wot_items_shells_id' => $shell_id,
					  'shell_default_portion' => (float)$s_content->defaultPortion,
					  'shell_speed' => (int)$s_content->speed,
					  'shell_max_distance' => (int)$s_content->maxDistance,
					  'shell_piercing_power' => (string)$s_content->piercingPower
					);
					if ($mysql->insertOrUpdate('wot_items_shells_guns',"wot_items_guns_id = '$gun_id' AND wot_items_shells_id = '$shell_id'",$data))
					{
						Debug::log("\t<b>Insert</b> {$node}::{$s_node} FK", LEVEL_INFO);
					} else {
						Debug::log("\t<b>Update</b> {$node}::{$s_node} FK", LEVEL_INFO);
					} 
				}
			}            
		}
		
		Debug::log('<hr><h3>Engines</h3>');
		
		/**
		READ ENGINES
		 **/
		$table = 'wot_items_engines';
		
		$file = $base_dir . "/" . $nation . "/components/engines.xml";
		Debug::log("Reading $nation/engines ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		if ($list)
		{
			$ids = $list->ids->children();
			foreach($ids as $node=>$content)
			{
				$node = $nation . '-' . $node;
				$data = array(
				  'wot_version_id' => $version_id,
				  'id' => (int)$content,
				  'name_node' => $node
				);
				if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
				{
					Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} else {
					Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
				}   
			} 
			
			$items = $list->shared->children();
			foreach($items as $node=>$content)
			{
				$node = $nation . '-' . $node;
				$data = array(
				  'wot_version_id' => $version_id,
				  'name' => TranslateToLocal((string)$content->userString),
				  'name_node' => $node,
				  'level' => (int)$content->level,
				  'price' => (int)$content->price,
				  'weight' => (float)$content->weight,
				  'health' => (int)$content->maxHealth,
				  'health_regen' => (int)$content->maxRegenHealth,
				  'repair' => (float)$content->repairCost,
				  'engine_power' => (int)$content->power,
				  'engine_fire_chance' => (float)$content->fireStartingChance
				);
				if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
				{
					Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} else {
					Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
				}           
			}
		}
		
		Debug::log('<hr><h3>Fuel tanks</h3>');
		
		/**
		READ FUEL TANKS
		 **/
		$table = 'wot_items_tanks';
		
		$file = $base_dir . "/" . $nation . "/components/fueltanks.xml";
		Debug::log("Reading $nation/fuelTanks ($file)", LEVEL_INFO);
		$list = simplexml_load_file($file);
		
		$ids = $list->ids->children();
		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'id' => (int)$content,
			  'name_node' => $node
			);
			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND nation = '$nation' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}   
		} 
		
		$items = $list->shared->children();
		foreach($items as $node=>$content)
		{
			$node = $nation . '-' . $node;
			$data = array(
			  'wot_version_id' => $version_id,
			  'name' => TranslateToLocal((string)$content->userString),
			  'name_node' => $node,
			  'nation' => $nation,
			  'price' => (int)$content->price,
			  'weight' => (float)$content->weight,
			  'health' => (int)$content->maxHealth,
			  'health_regen' => (int)$content->maxRegenHealth,
			  'repair' => (float)$content->repairCost
			);
			if ($mysql->insertOrUpdate($table,"name_node = '$node' AND nation = '$nation' AND wot_version_id = '$version_id'",$data))
			{
				Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
			} else {
				Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
			}           
		}
		
		Debug::log('<hr><h3>Tanks</h3>');
		
		/**
		LOAD TANKS
		 **/
		
		$no_tanks = array('list.xml','components','customization.xml');
		
		$dir = opendir($base_dir . "/" . $nation);
		while($file = readdir($dir))
		{
			if ($file!='.' && $file!='..' && !in_array($file,$no_tanks))
			{
				Debug::log("Reading <i>$file</i>", LEVEL_INFO);
				
				$str = file_get_contents($base_dir . "/" . $nation . "/" . $file);
				$str = str_replace("shared", "", $str);
				
				$f = fopen($base_dir . "/" . $nation . "/" . $file, 'w');
				fwrite($f,$str);
				fclose($f);
				
				$list = simplexml_load_file($base_dir . "/" . $nation . "/" . $file);
				
				$armor = array();
				foreach($list->hull->armor->children() as $node=>$content)
				{
					$node = substr($node, strpos($node,'_')+1,strlen($node));
					$armor[$node] = (int)$content;
					if ((bool)@$content->noDamage) {
						$armor[$node] .= '(true)';
					}
				}
				
				$armor_primary = '';
				foreach(explode(' ',(string)$list->hull->primaryArmor) as $node)
				{
					$node = substr($node, strpos($node,'_')+1,strlen($node));
					$armor_primary .= $armor[$node] . ' ';
				}
				$armor_primary = trim($armor_primary); 
				
				$node = substr($file,0,strpos($file,'.'));
				$node = $nation . '-' . $node;
				
				$data = array(
				  'wot_version_id' => $version_id,
				  'name_node' => $node,
				  'health' => (int)$list->hull->maxHealth,
				  'speed_forward' => (int)$list->speedLimits->forward,
				  'speed_backward' => (int)$list->speedLimits->backward,
				  'repair' => (int)$list->repairCost,
				  'weight' => (float)$list->hull->weight,
				  'armor' => implode(' ',$armor),
				  'armor_primary' => $armor_primary,
				  'ammo_health' => (int)$list->hull->ammoBayHealth->maxHealth,
				  'ammo_health_regen' => (int)$list->hull->ammoBayHealth->maxRegenHealth,
				  'ammo_repair' => (float)$list->hull->ammoBayHealth->repairCost,
				  'crew' => count($list->crew->children())
				);
				
				$tank_id = $mysql->insertOrUpdate('wot_tanks',"name_node = '$node' AND wot_version_id = '$version_id'",$data);
				if ($tank_id)
				{
					Debug::log("<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} else {
					$tank_id = $mysql->query("SELECT wot_tanks_id FROM wot_tanks WHERE name_node = '$node' AND wot_version_id = '$version_id'");
					$tank_id = $mysql->row($tank_id);
					$tank_id = $tank_id['wot_tanks_id'];
					Debug::log("<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
				} 
				
				$table = 'wot_items_chassis';
				$items = $list->chassis->children();
				foreach($items as $node=>$content)
				{
					$node = $nation . '-' . $node;
					
					$data = array(
					  'wot_version_id' => $version_id,
					  'wot_tanks_id' => (int)$tank_id,
					  'name' => TranslateToLocal((string)$content->userString),
					  'name_node' => $node,
					  'level' => (int)$content->level,
					  'price' => (int)$content->price,
					  'weight' => (float)$content->weight,
					  'health' => (int)$content->maxHealth,
					  'health_regen' => (int)$content->maxRegenHealth,
					  'repair' => (float)$content->repairCost,
					  'chassis_armor_left' => (int)$content->armor->leftTrack,
					  'chassis_armor_right' => (int)$content->armor->rightTrack,
					  'chassis_climb_edge' => (int)$content->maxClimbAngle,
					  'chassis_load' => (int)$content->maxLoad,
					  'chassis_brake' => (int)$content->brakeForce,
					  'chassis_rotation_speed' => (int)$content->rotationSpeed,
					  'chassis_bulk_health' => (int)$content->bulkHealthFactor,
					  'chassis_terrain_resistance' => (string)$content->terrainResistance,
					  'chassis_gun_dispersion_movement' => (float)$content->shotDispersionFactors->vehicleMovement,
					  'chassis_gun_dispersion_rotation' => (float)$content->shotDispersionFactors->vehicleRotation
					);
					if ($mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data))
					{
						Debug::log("\t<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
					} else {
						Debug::log("\t<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
					}
					
					tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);       
				}
				
				$table = 'wot_items_turrets';
				$items = $list->turrets0->children();

				foreach($items as $node=>$content)
				{
					$node = $nation . '-' . $node;
					
					$armor = array();
					
					foreach($content->armor->children() as $_node=>$_content)
					{
						$_node = substr($_node, strpos($_node,'_')+1,strlen($_node));
						$armor[$_node] = (int)$_content;
						if ((bool)@$_content->noDamage) {
							$armor[$_node] .= '(true)';
						}
					}
					
					$armor_primary = '';
					
					foreach(explode(' ',(string)$content->primaryArmor) as $_node)
					{
						if ($_node == '')
						{
							continue;
						}

						$_node = substr($_node, strpos($_node,'_')+1,strlen($_node));

						if (array_key_exists($_node,$armor)== false)
						{
							continue;
						}
						
						$armor_primary .= $armor[$_node] . ' ';
					}
					$armor_primary = trim($armor_primary); 
					
					$data = array(
					  'wot_version_id' => $version_id,
					  'wot_tanks_id' => (int)$tank_id,
					  'name' => TranslateToLocal((string)$content->userString),
					  'name_node' => $node,
					  'level' => (int)$content->level,
					  'price' => (int)$content->price,
					  'weight' => (float)$content->weight,
					  'health' => (int)$content->maxHealth,
					  'turret_yaw_limits' => (string)$content->yawLimits,
					  'turret_armor' => implode(' ',$armor),
					  'turret_armor_primary' => $armor_primary,
					  'turret_rotation_speed' => (int)$content->rotationSpeed,
					  'turret_rotator_health' => (int)$content->turretRotatorHealth->maxHealth,
					  'turret_rotator_health_regen' => (int)$content->turretRotatorHealth->maxRegenHealth,
					  'turret_rotator_repair' => (float)$content->turretRotatorHealth->repairCost,
					  'turret_vision_radius' => (int)$content->circularVisionRadius,
					  'turret_scope_health' => (int)$content->surveyingDeviceHealth->maxHealth,
					  'turret_scope_health_regen' => (int)$content->surveyingDeviceHealth->maxRegenHealth,
					  'turret_scope_repair' => (float)$content->surveyingDeviceHealth->repairCost
					);
					$turret_id = $mysql->insertOrUpdate($table,"name_node = '$node' AND wot_version_id = '$version_id'",$data);
					if ($turret_id)
					{
						Debug::log("\t<b>Insert</b> {$node} " . print_r($data, true), LEVEL_INFO);
					} else {
						$turret_id = $mysql->query("SELECT wot_items_turrets_id FROM wot_items_turrets WHERE name_node = '$node' AND wot_version_id = '$version_id'");
						$turret_id = $mysql->row($turret_id);
						$turret_id = $turret_id['wot_items_turrets_id'];
						Debug::log("\t<b>Update</b> {$node} " . print_r($data, true), LEVEL_INFO);
					}
					
					$items2 = $content->guns->children();
					foreach($items2 as $node2=>$content2)
					{
						$node2 = $nation . '-' . $node2;
						
						$gun_id = $mysql->query("SELECT wot_items_guns_id, gun_reload_time, gun_aiming_time, gun_max_ammo, turret_yaw_limits FROM wot_items_guns WHERE name_node = '$node2' AND wot_version_id = '$version_id'");
						$gun_data = $mysql->row($gun_id);
						$gun_id = $gun_data['wot_items_guns_id'];
						
						if (!$gun_id) {
							Debug::log("\t<b>Error</b> Failed to find gun {$node2}", LEVEL_ERRORS);
						} else {
							$gun_armor = 0;
							$armor = array();
							foreach($content2->armor->children() as $n=>$c)
							{
								if ($n!='gun')
								{
									$n = substr($n, strpos('_',$n)+1,strlen($n));
									$armor[$n] = (int)$c;
									if ((bool)@$c->noDamage) {
										$armor[$n] .= '(true)';
									}
								} else {
									$gun_armor = (int)$c;
								}
							}
							
							$data = array(
							  'wot_items_guns_id' => $gun_id,
							  'wot_items_turrets_id' => $turret_id,
							  'gun_armor' => implode(' ',$armor),
							  'gun_armor_gun' => (int)$gun_armor,
							  'gun_max_ammo' => (isset($content2->maxAmmo) ? (int)$content2->maxAmmo : $gun_data['gun_max_ammo']),
							  'gun_aiming_time' => (isset($content2->aimingTime) ? (float)$content2->aimingTime : $gun_data['gun_aiming_time']),
							  'gun_reload_time' => (isset($content2->reloadTime) ? (float)$content2->reloadTime : $gun_data['gun_reload_time']),
							  'turret_yaw_limits' => (isset($content2->turretYawLimits) ? (string)$content2->turretYawLimits : $gun_data['turret_yaw_limits']),
							);
							
							if (isset($content2->pitchLimits))
							{
								$data['gun_pitch_limits'] = (string)$content2->pitchLimits;
							}
							
							if (isset($content2->clip))
							{
								$data['gun_clip_count'] = (int)$content2->clip->count;
								$data['gun_clip_rate'] = (int)$content2->clip->rate;
							}

							if ($mysql->insertOrUpdate('wot_items_guns_turrets',"wot_items_guns_id = '$gun_id' AND wot_items_turrets_id = '$turret_id'",$data))
							{
								Debug::log("\t<b>Insert</b> FK {$node}({$turret_id})::{$node2}({$gun_id})", LEVEL_INFO);
							} else {
								Debug::log("\t<b>Update</b> FK {$node}({$turret_id})::{$node2}({$gun_id})", LEVEL_INFO);
							}
						}
						
						tank_check_unlocks((int)$tank_id, $content2->unlocks, $nation);  
					}
					tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);          
				}
				
				$items = $list->engines->children();
				foreach($items as $node => $content)
				{
					$node = $nation . '-' . $node;
					$engine_id = $mysql->getSingleRow("SELECT wot_items_engines_id FROM wot_items_engines WHERE name_node = '$node' AND wot_version_id = '$version_id'");
					$engine_id = $engine_id[0];
					if ($engine_id)
					{
						$data = array(
						  'wot_tanks_id' => $tank_id,
						  'wot_items_engines_id' => $engine_id
						);
						if ($mysql->insertOrUpdate('wot_items_engines_tanks',"wot_tanks_id = '$tank_id' AND wot_items_engines_id = '$engine_id'",$data))
						{
							Debug::log("\t<b>Insert</b> FK {$node}", LEVEL_INFO);
						} else {
							Debug::log("\t<b>Update</b> FK {$node}", LEVEL_INFO);
						}
					} else {
						Debug::log("\t<b>Error</b> Failed to find engine {$node}", LEVEL_ERRORS);
					}
					
					tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);    
				}
				
				$items = $list->radios->children();
				foreach($items as $node=>$content)
				{
					$node = $nation . '-' . $node;
					$radio_id = $mysql->getSingleRow("SELECT wot_items_radios_id FROM wot_items_radios WHERE name_node = '$node' AND wot_version_id = '$version_id'");
					$radio_id = $radio_id[0];
					
					if (!$radio_id) {
						Debug::log("\t<b>Error</b> Failed to find radio {$node}", LEVEL_ERRORS);
					} else {
						$data = array(
						  'wot_tanks_id' => $tank_id,
						  'wot_items_radios_id' => $radio_id
						);
						if ($mysql->insertOrUpdate('wot_items_radios_tanks',"wot_tanks_id = '$tank_id' AND wot_items_radios_id = '$radio_id'",$data))
						{
							Debug::log("\t<b>Insert FK</b> {$node} " . print_r($data, true), LEVEL_INFO);
						} else {
							Debug::log("\t<b>Update FK</b> {$node} " . print_r($data, true), LEVEL_INFO);
						} 
					}
					
					tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);   
				}
				
				$fuels = $list->fuelTanks->children();
				foreach($fuels as $node=>$content)
				{
					$node = $nation . '-' . $node;
					$fuel_id = $mysql->getSingleRow("SELECT wot_items_tanks_id FROM wot_items_tanks WHERE name_node = '$node' AND nation = '$nation' AND wot_version_id = '$version_id'");
					$fuel_id = $fuel_id[0];
					
					$data = array('default_tank' => $fuel_id);
					$mysql->update('wot_tanks',"wot_tanks_id = $tank_id",$data);
				}
			}
		}
		
		Debug::log('<hr>Done');
		Debug::log('<hr>New tanks<hr>');
		foreach($new_tanks as $tank) {
			Debug::log($tank);
		}
	}
}

Debug::log('<hr>Done all');
Debug::log('<hr>TAGS<hr>');
foreach($global_tags as $tag => $dump) {
	Debug::log($tag);
}

?>
</pre>
