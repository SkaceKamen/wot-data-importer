<?php

include_once 'config.php';
require_once 'classes/wot.xml.class.php';
require_once 'classes/debug.class.php';
require_once 'classes/wot_import.class.php';
require_once 'classes/mysql.class.php';
require_once "db_class.php";
require_once "read_mo.php";

//require_once 'read_mo.php';

/**
 * Handles the import of the data from the files into the database.
 *
 * @version 1.0
 * @author stephen
 */
final class wot_import
{

	private $folderlocation;
	private $version;
    private $versionId;

    const VehicleDefinitionFolder = 'res/scripts/item_defs/vehicles/';
    const DecompiledVersionsFolder = 'versions/';
    const TranslationsFolder = 'res/text/LC_MESSAGES/';

	public function __construct($StartinFolderLocation)
	{
		$this->folderlocation = $StartinFolderLocation;
        $this->version = $this->getVersionNumberFromFolder($StartinFolderLocation);

        WotXML::init();
	}

    // BEGIN Import supporting methods
	private function TranslateToLocal($key)
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

	/*
	 * Gets the short name for shell types
	 * 
	 * @param String longName The long name for the shell type
	 */
	private function shelltype($longName)
	{
		switch($longName)
		{
			case 'ARMOR_PIERCING': return 'AP'; 
            case 'ARMOR_PIERCING_CR': return 'APCR';
			case 'HIGH_EXPLOSIVE': return 'HE';
			case 'HOLLOW_CHARGE': return 'HEAT';
		}
		return '';
	}

	
	private function tank_check_unlocks($parent_id, $unlock, $nation)
	{
		//Debug::log('tank_check_unlocks BEGIN for $parent_id = '.$parent_id.', $unlock = '.$unlock.', $nation = '.$nation.'.', LEVEL_NORMAL);
		global $mysql;
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
						$tank_id = $mysql->query("SELECT wot_tanks_id FROM wot_tanks WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
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
		//Debug::log('tank_check_unlocks END for $parent_id = '.$parent_id.', $unlock = '.$unlock.', $nation = '.$nation.'.',LEVEL_NORMAL);
	}
    // END Import supporting methods

    public function uploadMeta()
	{
        global $mysql;

        Debug::log('<hr><h1>Beginning meta update</h1><hr>', LEVEL_MAIN);
        
        $startTime = new DateTime();

		$version = $this->getVersionNumberFromFolder($this->folderlocation);

		if ($this->getVersionExistsInDatabase($version, $this->versionId) == null)
		{
            Debug::log('New version detected', LEVEL_MINIMUM);

            // Todo: Migrate to stored procedure
            $mysql->insert('wot_versions', array('version' => $version, 'published' => time()));

            $this->versionId = $mysql->getLastUpdatedId();
		}
        
        $this->deserializeWoTMetaFiles();
        $this->copyTranslationFiles();
        $this->updateTranslations();
        
        $this->vehicles_path = 'versions/' . $this->version . '/';
        
        $this->ImportEquipment();
        $this->ImportTankInformation();


        Debug::log('<hr><h1>Completed meta upate</h1>', LEVEL_MAIN);
        Debug::log('Meta update took '.$startTime->diff(new DateTime())->format('%I minutes and %S seconds'), LEVEL_MAIN);
                
	}

    // BEGIN Version determining methods
	private function getVersionNumberFromFolder($folderLocation)
	{
		if (!file_exists($folderLocation)) 
		{
			die('Path ' . $folderLocation . ' is not valid');
		}

		if (isset($_GET['version'])) 
		{
			$version = mysql_real_escape_string($_GET['version']);
		} 
		else 
		{
			$version = $folderLocation . 'version.xml';
			if (!file_exists($version)) 
			{
				die("Failed to find path to {$version}");
			}
			$version = simplexml_load_file($version);
			$version = trim((string)$version->version);
		}

        Debug::log('Version: ' . $version, LEVEL_MINIMUM);

		return $version;
	}

	private function getVersionExistsInDatabase($versionNumber, &$versionIdNumber)
	{
        // Todo: Migrate to stored procedure
        global $mysql;

		$exists = $mysql->query("SELECT id, version FROM wot_versions WHERE version = '{$versionNumber}' OR id = '{$versionNumber}'");
        
        if ($mysql->Count($exists) > 0)
        {
            $versionIdNumber = $mysql->row($exists)['id'];
            return  $mysql->row($exists);
        }
        else 
        {
            return null;
        }
		
	}
    // END Version determining methods 
    	
    // BEGIN Meta file modifications
    
	/*
     * Enumerate the directories 
     * 
     * 
     * @param String path Base file path location
     * @param String target  
     */
	private function decodeDirectory($path, $target, $base = null)
	{
		if (is_null($base)) {
			$base = $path;
		}
		
		$handle = opendir($path);
		
		while(($file = readdir($handle)) !== false) {
			if ($file == '.' || $file == '..')
				continue;
			$file = $path . '/' . $file;
			if (!file_exists($file)) 
			{
				Debug::log("Problem: {$path} {$target} {$base} {$file}", LEVEL_ERRORS);
			}
			
			if (is_dir($file)) 
			{
				$this->decodeDirectory($file, $target, $base);
			} 
			else
			{
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

    private function deserializeWoTMetaFiles()
    {
        $this->decodeDirectory($this->folderlocation.self::VehicleDefinitionFolder,self::DecompiledVersionsFolder.$this->version.'/');

        Debug::log('XMLs parased', LEVEL_NORMAL);
        
        if (!file_exists('texts/' .$this->version))
        {
            mkdir('texts/' . $this->version);
        }

    }

    private function copyTranslationFiles(){

        $folder = WOT_PATH . self::TranslationsFolder ;
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
                copy($folder . $file, 'texts/' .$this->version . '/' . $file);
            }
        }
        
        Debug::log('Translations copied', LEVEL_MAIN);
    }
    
    private function updateTranslations()
    {
        global $translation;

        $translation = array();
            
        $translate_dir = 'texts/' . $this->version . '/';

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

    // END Meta file modifications

    // BEGIN Importing methods

    private function ImportEquipment()
    {
		Debug::log('<hr><h3>Common Equipment</h3>', LEVEL_NORMAL);

        global $mysql;

        $equipment = simplexml_load_file($this->vehicles_path . '/common/optional_devices.xml');

        foreach($equipment->children() as $node => $item) {
            $icon = (string)$item->icon;
            $icon = explode(' ', $icon);
            $icon = $icon[0];
            $icon = str_replace('../maps/icons/artefact/', '', $icon);
            
            $price = 0;
            $price_gold = 0;
            if (isset($item->price->gold))
            {
                $price_gold = (int)$item->price;
            }else{
                $price = (int)$item->price;
            }
            
            $weight = 0;
            if (isset($item->script->weight))
                $weight = (int)$item->script->weight;
            
            $include = '';
            $exclude = '';
            
            $inc = 'include';
            if (isset($item->vehicleFilter->$inc->vehicle->tags)){
                $include = (string)$item->vehicleFilter->$inc->vehicle->tags;
            }
            if (isset($item->vehicleFilter->exclude->vehicle->tags)){
                $exclude = (string)$item->vehicleFilter->exclude->vehicle->tags;
            }
            
            //remove tabs
            $include = preg_replace('/\s+/', ' ', $include);
            $exclude = preg_replace('/\s+/', ' ', $exclude);
            
            $data = array(
                'wot_version_id' => $this->versionId,
                'name' => $this->TranslateToLocal((string)$item->userString),
                'name_node' => $node,
                'description' => $this->TranslateToLocal((string)$item->description),
                'icon' => $icon,
                'price' => $price,
                'price_gold' => $price_gold,
                'removable' => (mb_strtolower(((string)$item->removable)) == mb_strtolower('true') ? 1 : 0),
                'weight' => $weight,
                'vehicle_tags_include' => $mysql->quoteString($include),
                'vehicle_tags_exclude' => $mysql->quoteString($exclude)
            );
            
            $this->InsertData('wot_equipment',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);

            $equipment_id = $mysql->query("SELECT wot_equipment_id FROM wot_equipment WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
            $equipment_id = $mysql->row($equipment_id);
            $equipment_id = $equipment_id['wot_equipment_id'];

            $break = false;
            
            foreach($item->script->children() as $param_node => $param_value) {
                if ($param_node == 'weight'){
                    continue;
                }
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
                {
                    $data['param'] = substr($data['param'], strpos($data['param'], '/')+1);
                }
                
                $this->InsertData('wot_equipment_params',$node,"wot_equipment_id = '$equipment_id' AND param = '{$data['param']}'",$data);

                if ($break)
                {
                    break;
                }
            }
            
        }
    }
   
    private function ImportTankInformation(){
        $base_dir = $this->vehicles_path;
        $dir_nations = opendir($this->vehicles_path);

        $global_tags = array();

        while($nation = readdir($dir_nations))
        {
            if ($nation!='.' && $nation!='..' && $nation != 'common')
            {
                $new_tanks = array();
                
                $this->ImportTanks($nation, $new_tanks, $global_tags);

                $this->ImportRadios($nation);

                $this->ImportChassis($nation);
                
                $this->ImportTurrets($nation);
                
                $this->ImportShells($nation);
                
                $this->ImportGuns($nation);
                
                $this->ImportEngines($nation);
                
                $this->ImportFuelTanks($nation);
                
                Debug::log('<hr><h3>Map Components and Tanks</h3>', LEVEL_NORMAL);
                
                /**
                LOAD TANKS
                 **/
                
                $no_tanks = array('list.xml','components','customization.xml');
                
                $dir = opendir($base_dir . "/" . $nation);
                while($file = readdir($dir))
                {
                    if ($file!='.' && $file!='..' && !in_array($file,$no_tanks))
                    {
                        Debug::log("Reading <i>$file</i>", LEVEL_NORMAL);
                        
                        $str = file_get_contents($base_dir . "/" . $nation . "/" . $file);
                        $str = str_replace("shared", "", $str);
                        
                        $f = fopen($base_dir . "/" . $nation . "/" . $file, 'w');
                        fwrite($f,$str);
                        fclose($f);
                        
                        $fileContents = simplexml_load_file($base_dir . "/" . $nation . "/" . $file);
                        
                        $tank_id = $this->ImportTankArmor($file,$nation,$fileContents);
                        $this->ImportChassisData($tank_id,$nation,$fileContents);
                        $this->ImportTurretData($tank_id,$nation,$fileContents);
                        $this->ImportEngineData($tank_id,$nation,$fileContents);
                        $this->ImportRadioData($tank_id,$nation,$fileContents);
                        $this->ImportFuelTankData($tank_id,$nation,$fileContents);

                        
                    }
                }
                
                Debug::log("<hr>$nation tanks done", LEVEL_MAIN);
                Debug::log('<hr>New or updated tanks<hr>', LEVEL_MINIMUM);
                foreach($new_tanks as $tank) {
                    Debug::log($tank, LEVEL_MINIMUM);
                }
            }
        }

        Debug::log('<hr>Completed all nations',LEVEL_MAIN);
        Debug::log('<hr>TAGS<hr>',LEVEL_MINIMUM);
        foreach($global_tags as $tag => $dump) {
            Debug::log($tag, LEVEL_MINIMUM);
        }
    }
    
	private function ImportTanks($nation,&$nationTanks, &$nationTags){
		
        $file = $this->vehicles_path . "/" . $nation . "/list.xml";
		
		Debug::log('<hr><h3>Tanks</h3>', LEVEL_NORMAL);

		Debug::log("Reading $nation ($file)", LEVEL_NORMAL);
		$fileContents = simplexml_load_file($file);
		$tanks = $fileContents->children();

		foreach($tanks as $node=>$tank)
		{
			$node = $nation . '-' . $node;
			$tags = preg_split('/\s+/',$tank->tags);
			$class = '';

			switch($tags[0])
			{
				case 'lightTank': $class = 'light'; break;
				case 'mediumTank': $class = 'medium'; break;
				case 'heavyTank': $class = 'heavy'; break;
				case 'AT-SPG': $class = 'td'; break;
				case 'SPG': $class = 'spg'; break;
			}
			
            if ($class == ''){
                continue;
            }

			$secret = 0;
			$igr = 0;
			foreach($tags as $tag) {
				if ($tag == 'secret')
                {
                    $secret = 1;
                }
				if ($tag == 'premiumIGR')
                {
                    $igr = 1;
                }
				if (!isset($nationTags[$tag]))
                {
                    $nationTags[$tag] = true;
                }
			}
			
			$price = 0;
			$price_gold = 0;

			if (isset($tank->price->gold)){
				$price_gold = (int)$tank->price;
			}
			else{
				$price = (int)$tank->price;
			}
			
			$data = array(
			  'wot_version_id' => $this->versionId,
			  'id' => (int)$tank->id,
			  'name_node' => $node,
			  'name' => $this->TranslateToLocal((string)$tank->userString),
			  'nation' => $nation,
			  'class' => $class,
			  'price' => (int)$price,
			  'price_gold' => (int)$price_gold,
			  'tags' => implode(' ', $tags),
			  'secret' => $secret,
			  'igr' => $igr,
			  'level' => (int)$tank->level,
			  'crew' => -1); // Default the crew which will get set later
			
			if ($this->InsertData('wot_tanks',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data) >= 0){
				$nationTanks[]=$node;
			}
		}
	}

	private function ImportRadios($nation){
		Debug::log('<hr><h3>Radios</h3>', LEVEL_NORMAL);
		
		/**
		READ RADIOS
		 **/
		
		$file = $this->vehicles_path. "/" . $nation . "/components/radios.xml";

		Debug::log("Reading $nation\\radios ($file)", LEVEL_NORMAL);
		$fileContents = simplexml_load_file($file);
		
		$radios_ids = $fileContents->ids->children();
		
		foreach($radios_ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			
			$this->insertData('wot_items_radios', $node, "name_node = '$node' AND wot_version_id = '$this->versionId'", array(
																																  'wot_version_id' => $this->versionId,
																																  'id' => (int)$content,
																																  'name_node' => $node
																																), ' ids');

			$content = 'default';
		} 
		
		$radios = $fileContents->shared->children();

		foreach($radios as $node=>$radioContent)
		{
			$node = $nation . '-' . $node;

			$this->insertData('wot_items_radios',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'", array(
																													  'wot_version_id' => $this->versionId,
																													  'name' => $this->TranslateToLocal((string)$radioContent->userString),
																													  'level' => (int)$radioContent->level,
																													  'price' => (float)$radioContent->price,
																													  'weight' => (float)$radioContent->weight,
																													  'health' => (int)$radioContent->maxHealth,
																													  'health_regen' => (int)$radioContent->maxRegenHealth,
																													  'repair' => (float)$radioContent->repairCost,
																													  'radio_distance' => (int)$radioContent->distance
																													), ' shared');
			$content = 'default';
		}
	}

    private function ImportShells($nation){
        
        Debug::log('<hr><h3>Shells</h3>', LEVEL_NORMAL);

        $file = "$this->vehicles_path/$nation/components/shells.xml";
        
        Debug::log("Reading $nation/shells ($file)", LEVEL_NORMAL);

        foreach(simplexml_load_file($file)->children() as $node=>$content)
        {
            if ($node != 'icons')
            {
                $node = $nation . '-' . $node;

                $price = 0;
                $price_gold = 0;

                if (isset($shell->price->gold))
                {
                    $price_gold = (int)$content->price;
                }
                else
                {
                    $price = (int)$content->price;
                }
            
                $this->insertData('wot_items_shells', $node, "name_node = '$node' AND wot_version_id = '$this->versionId'",	array(
																																	'wot_version_id' => $this->versionId,
																																	'id' => (int)$content->id,
																																	'name_node' => $node,
																																	'name' => $this->TranslateToLocal((string)$content->userString),
																																	'price' => (int)$price,
																																	'price_gold' => (int)$price_gold,
																																	'shell_type' => $this->shelltype((string)$content->kind),
																																	'shell_caliber' => (int)$content->caliber,
																																	'shell_damage_armor' => (int)$content->damage->armor,
																																	'shell_damage_device' => (int)$content->damage->devices,
																																	'shell_explosion_radius' => (float)$content->explosionRadius,
																																	'shell_tracer' => (bool)$content->isTracer  
																																	)) ;
            }
        }
    }

	private function ImportChassis($nation){
		
		Debug::log('<hr><h3>Chassis</h3>', LEVEL_NORMAL);
		
		$file = $this->vehicles_path . "/" . $nation . "/components/chassis.xml";
		
		Debug::log("Reading $nation\\chassis ($file)", LEVEL_NORMAL);
		$fileContents = simplexml_load_file($file);
		
		$ids = $fileContents->ids->children();

		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			
			$this->insertData('wot_items_chassis',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",array(
																															'wot_version_id' => $this->versionId,
																															'id' => (int)$content,
																															'name_node' => $node
																														));

		}
		
	}

	private function ImportTurrets($nation){
		Debug::log('<hr><h3>Turrets</h3>', LEVEL_NORMAL);
		
		$file = $this->vehicles_path. "/" . $nation . "/components/turrets.xml";

		Debug::log("Reading $nation/turrets ($file)", LEVEL_NORMAL);

		$fileContents = simplexml_load_file($file);
		
		$ids = $fileContents->ids->children();

		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			
			$this->insertData('wot_items_turrets',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",array(
																															  'wot_version_id' => $this->versionId,
																															  'id' => (int)$content,
																															  'name_node' => $node
																															));
		}
	}
	
	private function ImportGuns($nation){
		global $mysql;

        Debug::log('<hr><h3>Guns</h3>', LEVEL_NORMAL);
		
		/**
		READ GUNS
		 **/
		
		$file = $this->vehicles_path . "/" . $nation . "/components/guns.xml";

		Debug::log("Reading $nation/guns ($file)", LEVEL_NORMAL);
		$fileContents = simplexml_load_file($file);
		
		$ids = $fileContents->ids->children();

		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			
			$this->insertData('wot_items_guns',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",array(
																														  'wot_version_id' => $this->versionId,
																														  'id' => (int)$content,
																														  'name_node' => $node,
																														  'gun_max_ammo' => 0
																														));
			
		} 
		
		$items = $fileContents->shared->children();

		foreach($items as $node=>$content)
		{
			$node = $nation . '-' . $node;

			$data = array(
			  'wot_version_id' => $this->versionId,
			  'name' => $this->TranslateToLocal((string)$content->userString),
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

			$this->InsertData('wot_items_guns',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);
                        
            $gun_id = $mysql->query("SELECT wot_items_guns_id FROM wot_items_guns WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
            $gun_id = $mysql->row($gun_id);
            $gun_id = $gun_id['wot_items_guns_id'];

			$shells = $content->shots->children();

			foreach($shells as $s_node=>$s_content)
			{
				$s_node = $nation . '-' . $s_node;
				$shell_id = $mysql->query("SELECT wot_items_shells_id FROM wot_items_shells WHERE name_node = '$s_node' AND wot_version_id = '$this->versionId'");
				$shell_id = $mysql->row($shell_id);
				$shell_id = $shell_id['wot_items_shells_id'];
				
				if (!$shell_id) {
					Debug::log("\t<b>Error</b> Failed to find shell {$s_node}", LEVEL_ERRORS);
				} else {
					
					$data = array(
					  'wot_items_guns_id' => $gun_id,
					  'wot_items_shells_id' => $shell_id,
					  'shell_default_portion' => (float)$s_content->defaultPortion,
					  'shell_speed' => (int)$s_content->speed,
					  'shell_max_distance' => (int)$s_content->maxDistance,
					  'shell_piercing_power' => (string)$s_content->piercingPower
					);

					$this->InsertData('wot_items_shells_guns',$s_node,"wot_items_guns_id = '$gun_id' AND wot_items_shells_id = '$shell_id'",$data);
					
				}
			}            
		}
	}

	private function ImportEngines($nation){
		Debug::log('<hr><h3>Engines</h3>', LEVEL_NORMAL);
		
		//$table = 'wot_items_engines';
		
		$file = $this->vehicles_path. "/" . $nation . "/components/engines.xml";
		
		Debug::log("Reading $nation/engines ($file)", LEVEL_NORMAL);

		$fileContents = simplexml_load_file($file);
		
		if ($fileContents)
		{
			$ids = $fileContents->ids->children();
			foreach($ids as $node=>$content)
			{
				$node = $nation . '-' . $node;
				$data = array(
				  'wot_version_id' => $this->versionId,
				  'id' => (int)$content,
				  'name_node' => $node
				);
				
				$this->InsertData('wot_items_engines',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);

			} 
			
			$items = $fileContents->shared->children();
			foreach($items as $node=>$content)
			{
				$node = $nation . '-' . $node;
				$data = array(
				  'wot_version_id' => $this->versionId,
				  'name' => $this->TranslateToLocal((string)$content->userString),
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
				
				$this->InsertData('wot_items_engines',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);

			}
		}
	}

	private function ImportFuelTanks($nation){
		Debug::log('<hr><h3>Fuel tanks</h3>', LEVEL_NORMAL);
		
		$file = $this->vehicles_path . "/" . $nation . "/components/fueltanks.xml";
		Debug::log("Reading $nation/fuelTanks ($file)", LEVEL_NORMAL);
		$fileContents = simplexml_load_file($file);
		
		$ids = $fileContents->ids->children();
		foreach($ids as $node=>$content)
		{
			$node = $nation . '-' . $node;
			
			$this->InsertData('wot_items_tanks',$node,"name_node = '$node' AND nation = '$nation' AND wot_version_id = '$this->versionId'",array(
																																				  'wot_version_id' => $this->versionId,
																																				  'id' => (int)$content,
																																				  'name_node' => $node
																																				));
			
		} 
		
		$items = $fileContents->shared->children();

		foreach($items as $node=>$content)
		{
			$node = $nation . '-' . $node;

			$this->InsertData('wot_items_tanks',$node,"name_node = '$node' AND nation = '$nation' AND wot_version_id = '$this->versionId'", array(
																																					'wot_version_id' => $this->versionId,
																																					'name' => $this->TranslateToLocal((string)$content->userString),
																																					'name_node' => $node,
																																					'nation' => $nation,
																																					'price' => (int)$content->price,
																																					'weight' => (float)$content->weight,
																																					'health' => (int)$content->maxHealth,
																																					'health_regen' => (int)$content->maxRegenHealth,
																																					'repair' => (float)$content->repairCost
																																				));

		}
	}

    private function ImportTankArmor($fileName, $nation, $fileContents){
        global $mysql;

        $armor = array();

        foreach($fileContents->hull->armor->children() as $node=>$content)
        {
            $node = substr($node, strpos($node,'_')+1,strlen($node));
            $armor[$node] = (int)$content;
            if ((bool)@$content->noDamage) {
                $armor[$node] .= '(true)';
            }
        }
                        
        $armor_primary = '';
        foreach(explode(' ',(string)$fileContents->hull->primaryArmor) as $node)
        {
            $node = substr($node, strpos($node,'_')+1,strlen($node));
            $armor_primary .= $armor[$node] . ' ';
        }
        $armor_primary = trim($armor_primary); 
                        
        $node = substr($fileName,0,strpos($fileName,'.'));
        $node = $nation . '-' . $node;
                        
        $data = array(
            'wot_version_id' => $this->versionId,
            'name_node' => $node,
            'health' => (int)$fileContents->hull->maxHealth,
            'speed_forward' => (int)$fileContents->speedLimits->forward,
            'speed_backward' => (int)$fileContents->speedLimits->backward,
            'repair' => (int)$fileContents->repairCost,
            'weight' => (float)$fileContents->hull->weight,
            'armor' => implode(' ',$armor),
            'armor_primary' => $armor_primary,
            'ammo_health' => (int)$fileContents->hull->ammoBayHealth->maxHealth,
            'ammo_health_regen' => (int)$fileContents->hull->ammoBayHealth->maxRegenHealth,
            'ammo_repair' => (float)$fileContents->hull->ammoBayHealth->repairCost,
            'crew' => count($fileContents->crew->children())
        );

        $this->InsertData('wot_tanks',$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);
                        
        $tank_id = $mysql->query("SELECT wot_tanks_id FROM wot_tanks WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
        $tank_id = $mysql->row($tank_id);

        return $tank_id['wot_tanks_id'];
    }

    private function ImportChassisData($tank_id, $nation, $fileContents){
        $table = 'wot_items_chassis';
        $items = $fileContents->chassis->children();
        foreach($items as $node=>$content)
        {
            $node = $nation . '-' . $node;
            
            $data = array(
              'wot_version_id' => $this->versionId,
              'wot_tanks_id' => (int)$tank_id,
              'name' => $this->TranslateToLocal((string)$content->userString),
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
            
            $this->InsertData($table,$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);
            
            $this->tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);       
        }
        
    }

    private function ImportTurretData($tank_id, $nation, $fileContents){
        global $mysql;

        $table = 'wot_items_turrets';
        $items = $fileContents->turrets0->children();

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
              'wot_version_id' => $this->versionId,
              'wot_tanks_id' => (int)$tank_id,
              'name' => $this->TranslateToLocal((string)$content->userString),
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
            $this->InsertData($table,$node,"name_node = '$node' AND wot_version_id = '$this->versionId'",$data);
            
            $turret_id = $mysql->query("SELECT wot_items_turrets_id FROM wot_items_turrets WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
            $turret_id = $mysql->row($turret_id);
            $turret_id = $turret_id['wot_items_turrets_id'];

            // Map guns to turrets
            $items2 = $content->guns->children();
            foreach($items2 as $node2=>$content2)
            {
                $node2 = $nation . '-' . $node2;
                
                $gun_id = $mysql->query("SELECT wot_items_guns_id, gun_reload_time, gun_aiming_time, gun_max_ammo, turret_yaw_limits FROM wot_items_guns WHERE name_node = '$node2' AND wot_version_id = '$this->versionId'");
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

                    $this->InsertData('wot_items_guns_turrets',$node,"wot_items_guns_id = '$gun_id' AND wot_items_turrets_id = '$turret_id'",$data,"({$turret_id})::{$node2}({$gun_id})");

                }
                
                $this->tank_check_unlocks((int)$tank_id, $content2->unlocks, $nation);  
            }
            $this->tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);          
        }
    }

    private function ImportEngineData($tank_id,$nation,$fileContents){
        global $mysql;

        $items = $fileContents->engines->children();
        foreach($items as $node => $content)
        {
            $node = $nation . '-' . $node;
            $engine_id = $mysql->getSingleRow("SELECT wot_items_engines_id FROM wot_items_engines WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
            $engine_id = $engine_id[0];
            if (!$engine_id){
                Debug::log("\t<b>Error</b> Failed to find engine {$node}", LEVEL_ERRORS);
            } else {
                $data = array(
                  'wot_tanks_id' => $tank_id,
                  'wot_items_engines_id' => $engine_id
                );
                
                $this->InsertData('wot_items_engines_tanks',$node,"wot_tanks_id = '$tank_id' AND wot_items_engines_id = '$engine_id'",$data);
            }
            
            $this->tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);    
        }
    }

    private function ImportRadioData($tank_id,$nation,$fileContents){
        global $mysql;

        $items = $fileContents->radios->children();
        foreach($items as $node=>$content)
        {
            $node = $nation . '-' . $node;
            $radio_id = $mysql->getSingleRow("SELECT wot_items_radios_id FROM wot_items_radios WHERE name_node = '$node' AND wot_version_id = '$this->versionId'");
            $radio_id = $radio_id[0];
            
            if (!$radio_id) {
                Debug::log("\t<b>Error</b> Failed to find radio {$node}", LEVEL_ERRORS);
            } else {
                $data = array(
                  'wot_tanks_id' => $tank_id,
                  'wot_items_radios_id' => $radio_id
                );

                $this->InsertData('wot_items_radios_tanks',$node,"wot_tanks_id = '$tank_id' AND wot_items_radios_id = '$radio_id'",$data);

            }
            
            $this->tank_check_unlocks((int)$tank_id, $content->unlocks, $nation);   
        }
    }

    private function ImportFuelTankData($tank_id,$nation,$fileContents){
        global $mysql;
        $fuels = $fileContents->fuelTanks->children();

        foreach($fuels as $node=>$content)
        {
            $node = $nation . '-' . $node;
            $fuel_id = $mysql->getSingleRow("SELECT wot_items_tanks_id FROM wot_items_tanks WHERE name_node = '$node' AND nation = '$nation' AND wot_version_id = '$this->versionId'");
            $fuel_id = $fuel_id[0];
                            
            if (!$fuel_id) {
                Debug::log("\t<b>Error</b> Failed to find fuel tank {$node}", LEVEL_ERRORS);
            } else {
                $data = array('default_tank' => $fuel_id);

                $mysql->update('wot_tanks',"wot_tanks_id = $tank_id",$data);
            }
        }
    }

    private function InsertData($tableName, $node, $filter, $data, $messageSuffix = ''){
        global $mysql;
        
        Debug::log("<b>Prepare</b> {$node} " . print_r($data, true), LEVEL_DETAILED);

		$result = $mysql->insertOrUpdate($tableName,$filter,$data);

        if ($result >= 0)
        {
            Debug::log("<b>Insert$messageSuffix</b> {$node} " . print_r($data, true), LEVEL_DETAILED);
			return $result;
        } else {
            Debug::log("<b>Update$messageSuffix</b> {$node} " . print_r($data, true), LEVEL_DETAILED);
			return -1;
        } 
    }
    // END Importing methods
}

