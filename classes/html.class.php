<?php
class html_base
{
  private $base_folder;
  private $language;
  private $values = array();
  private $default_values = array();
  public function __construct($base_folder)
  {
    $this->base_folder = $base_folder;
    
    $this->_assignArray('C_',$_COOKIE);
    $this->_assignArray('S_',$_SESSION);
    $this->default_values = $this->values;
    $this->_clear();
  }
  public function load($file,$replace=NULL)
  {
    /*$f = fopen($this->base_folder . '/' . $file, "r");
    $s = fread($f,filesize($this->base_folder . '/' . $file));
    fclose($f);*/ 
    $s = file_get_contents($this->base_folder . '/' . $file) ;

    $this->_assignArray('',$replace);
    $this->_assignMerge(); 

    while (strpos($s,'{')!=false)
    {
      $first = strpos($s,'{');
      $next = strpos($s,'}');
      $command = substr($s,$first + 1,$next - $first - 1);
      if (!isset($replace[$command]))
        $s = str_replace('{'.$command.'}','',$s);
      else
        $s = str_replace('{'.$command.'}',$replace[$command],$s);
    }
    
    return $s;
  }
  public function assign_array($array)
  {
    $this->_assignArray('',$array);
    $this->default_values = array_merge($this->default_values,$this->values);
    $this->_clear();
  }
  public function assign_value($name,$value)
  {
    $this->_assign($name,$value);
    $this->default_values = array_merge($this->default_values,$this->values);
    $this->_clear();
  }
  public function assign_language($language)
  {
    $this->language = $language;
    
    $this->_clear();
    $this->_assignArray('_',$language);
    $this->default_values = array_merge($this->default_values,$this->values);
    $this->_clear();
    
  }
  private function _assignArray($prefix,$array)
  {
    foreach($array as $key=>$value)
    {
      if (is_array($value))
        $this->_assignArray($prefix.$key.'.',$value);
      else
        $this->_assign($prefix.$key,$value);  
    }
  }
  private function _assign($variable,$value)
  {                                                      
    $this->values['$'.$variable.'$'] = $value;
  }
  private function _clear()
  {
    $values = array();
  }
  private function _assignMerge()
  {
    $this->values = array_merge($this->default_values,$this->values);
  }
}

?>