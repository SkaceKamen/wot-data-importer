<?php
class mysqler
{
  public $connected;
  public $allowUpdate = true;
  public function __construct($db_host=NULL,$db_user=NULL,$db_pass=NULL,$db_name=NULL,$db_charset=NULL)
  {
    if (isset($db_host))  $this->connect($db_host,$db_user,$db_pass,$db_name,$db_charset);
  }
  public function show_error($text)
  {
    echo "<div class='error'><h3>Chyba databáze</h3>$text</div>";
    /*$f = fopen("error_log","a");
    fwrite($f,$text."\r\n");
    fclose($f);*/
  }
  public function connect($db_host,$db_user,$db_pass,$db_name,$db_charset=NULL)
  {
    if ($db_charset==NULL) $db_charset = "utf8";
    if (MySQL_Connect($db_host, $db_user, $db_pass))
    {
      if (MySQL_Select_DB($db_name))
      {
        $this->connected = 1;
        if ($this->query("SET CHARACTER SET $db_charset") == 0)
          $this->show_error("Nepodařilo se deklarovat kódování.");
      } else  {
        $this->show_error("Chyba při výběru databáze");
        return 0;
      }
    }
    else
    {
      $this->show_error("Chyba při připojování k mysql databázi.");
      return 0;
    }
  }
  public function query($query)
  {
    if ($this->connected)
    {
      $ret = MySQL_Query($query);
      if ($ret)
        return $ret;
      else
      {
        $this->show_error("Chyba při vykonávání příkazu SQL.<br><div class='sql'>$query</div><div class='sql_chyba'>".mysql_error().'</div>');
        die();
        return 0;
      }
    } else {
      $this->show_error("Připojení ještě nebylo provedeno.");
      return 0;
    }
  }
  public function row($queryid)
  {
    if ($this->connected)
    {
      $ret = MySQL_Fetch_Array($queryid);
      if ($ret)
        return $ret;
      else
        return 0;
    } else {
      $this->show_error("Připojení ještě nebylo provedeno.");
      return 0;
    }
  }
  public function count($queryid)
  {
    if ($this->connected)
    {
      $ret = MySQL_Num_Rows($queryid);
      if ($ret)
        return $ret;
      else
        return 0;
    } else {
      $this->show_error("Připojení ještě nebylo provedeno.");
      return 0;
    }
  }
  public function insert($table, $data)
  {
    return $this->query("INSERT INTO $table (" . implode(',',array_keys($data)) . ") VALUES ('" . implode("','",$data) . "')");
  }
  public function update($table, $where, $data)
  {
    $values = array();
    foreach($data as $key=>$value)
      $values[] = "$key = '$value'";

    //echo "<p>UPDATE $table SET " . implode(',',$values) . " WHERE " . $where . "</p>";

    return $this->query("UPDATE $table SET " . implode(',',$values) . " WHERE " . $where);
  }
  
  public function insertOrUpdate($table, $where, $data)
  {
    if ($this->count($this->query("SELECT * FROM $table WHERE $where")) == 0)
    {
        $this->insert($table, $data);
        return mysql_insert_id();
    } else {
        if ($this->allowUpdate)
          $this->update($table, $where,$data);
        return false;
    }
  }
  
  public function getSingleRow($query)
  {
    $r = $this->row($this->query($query));
    return $r;
  }
  
  public function __destruct()
  {
    MySQL_Close();
  }
}
?>