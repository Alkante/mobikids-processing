<?php

/**********************************************************************/
/*  Log class                                                     */
/**********************************************************************/
class AlkFileLogger 
{
  
  protected $_logFilePath = "";
  
  /**
   * Constructeur par défaut
   * @param logFilePath chemin vers le fichier de log
   */ 
  public function __construct($logFilePath)
  {
    $this->_logFilePath = $logFilePath;
  }
  
  function write($msg) {
    $logfile = fopen($this->_logFilePath, 'a+');
    if ($logfile) {
      if (!is_array($msg)) {
        $msg = array(0=> $msg);
      }
      for ($i=0;$i<count($msg);$i++) {
      	if (flock($logfile, LOCK_EX)) { // do an exclusive lock
      		fwrite($logfile, $msg[$i] . "\n");
      		flock($logfile, LOCK_UN); // release the lock
      	}        
      }
      fclose($logfile);
    }
  }

  function writeBanner() {
    $banner = str_pad("", 80, "#");
    $this->write($banner);
  }
  
  function writeDate() {
    $dateLine = str_pad("## " . date("D j M G:i:s T Y") . " " , 80, "#");
    $this->write($dateLine);
  }
  
  function writeDateMessage($msg){
  	$dateLine = "[".date("D j M G:i:s T Y")."]";
  	$this->write($dateLine." ".$msg);
  	echo $dateLine." ".$msg."\n";
  }
  
  function writeMessage($msg,$queuename,$status){    
    $logfile = fopen($this->_logFilePath, 'a+');
    if ($logfile) {
      switch ($status){
        case 1:
          $status_txt = "message sent";
          break;
        case 2:
          $status_txt = "message received";
          break;
        default:
          $status_txt = "message";
          break;
      }

      $dateLine = "[".date("D j M G:i:s T Y")."]";
      $line = "$dateLine queue $queuename $status_txt: $msg\n";
      fwrite($logfile, $line);
      fclose($logfile);
    }
  }
}

/**********************************************************************/
/*  time watch functions                                              */
/**********************************************************************/
set_time_limit(0);
$time_start = microtime(true);

function reset_time() {
    global $time_start;
    $time_start = microtime(true);
}

function time_elapsed() {
    global $time_start;
    
    $time_now = microtime(true);
    $elapsed = $time_now - $time_start;
    $secs = floor($elapsed);
    $microsecs = $elapsed * 1000000;

    $bit = array(
        'y' => $secs / 31556926 % 12,
        'w' => $secs / 604800 % 52,
        'd' => $secs / 86400 % 7,
        'h' => $secs / 3600 % 24,
        'm' => $secs / 60 % 60,
        's' => $secs % 60,
        "ms" => $microsecs / 1000
        );
    $ret = array();
    foreach($bit as $k => $v)
        if($v > 0)$ret[] = $v . $k;

    return join(' ', $ret);
}






?>