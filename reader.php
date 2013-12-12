<?php
// By Flowlance (danny@leetcake.net)
date_default_timezone_set("Europe/Amsterdam"); // Your server's timezone

$settings = array(
    'DBCONN'  => 'C:\\inetpub\\xomnhala\\config.php', // Path to db connection file
    'TABLE'   => 'UniqueKills',                       // Unique Ranking table name
    'LOGTYPE' => 'EVALOGS',                           // "EVALOGS" or "FATALLOGS"
    'LOGPATH' => 'C:\\Server\\evaLog\\',              // Path to log files
    'DONEDIR' => 'finished\\'                         // Proceeded/Scanned logs will be moved here
);

include($settings['DBCONN']);

class UniqueReader extends mssql {
    var $table;
    var $logptrn;
    var $fileptrn;
    var $path;
    var $finished;
    var $logfiles;
    
    public function setVar($settings) {
        $this->table    = $settings['TABLE'];
        $this->logfiles = $settings['LOGTYPE'];
        $this->path     = $settings['LOGPATH'];
        $this->finished = $settings['DONEDIR'];
        
        if($this->logfiles == "EVALOGS") {
            $this->logptrn  = "/\[([0-9_\-]{1,})\].*\[([a-zA-Z0-9_]{1,})\].*\[([a-zA-Z0-9_]{1,})\]/siU";
            $this->fileptrn = "/^[0-9\-]{1,}\_uniquekills.txt$/";
        } else {
            $this->logptrn = "/([0-9_\-]{1,}\t[0-9_:]{1,})\t.*Unique\sMonster\sKilled!\sUNIQUE\[([a-zA-Z0-9_]{1,})\].*\sby\s\[([a-zA-Z0-9_]{1,})\]/siU";
            $this->fileptrn = "/^[0-9\-]{1,}\_FatalLog.txt$/";
        }
    }
    
    public function ts($datestr) {
        if($this->logfiles == "EVALOGS") {
            $split = split("_", $datestr);
            $date  = $split[0] . " " . str_replace("-", ":", $split[1]);
        } else {
            $date = str_replace("\t", " ", $datestr);
        }
        
        return strtotime($date);
    }
    
    public function match($search) {
        if(preg_match_all($this->logptrn, $search, $m)) {
            return $m;
        } else {
            return false;
        }
    }
    
    public function isToday($filename) {
        $split = split("_", $filename);
        $date  = $this->logfiles == "EVALOGS" ? date("j-n-Y") : date("Y-m-d");
        
        if($date==$split[0]) {
            return true;
        }
    }
    
    public function move($v) {
        if(!$this->isToday($v)) {
            if(copy($this->path . $v,$this->path . $this->finished . $v)) {
                unlink($this->path . $v);
            }
        }
    }
    
    public function folder_check() {
        if(!file_exists($this->path . $this->finished)) {
            mkdir($this->path . $this->finished);
        }
    }
    
    public function scan() {
        $this->folder_check();
        $files = scandir($this->path);

        foreach($files as $v) {
            if(preg_match($this->fileptrn, $v)) {
                $file = fopen($this->path . $v, "r");
                
                while($line = fgets($file)) {
                    $cont = $this->match($line);
                    if($cont!=false) {
                        $exists = $this->get_num("SHARD", "SELECT * FROM ".$this->table." WHERE CharName16='".$this->safe($cont[3][0])."' AND Monster='".$this->safe($cont[2][0])."' AND Timestamp='".$this->ts($cont[1][0])."'");
                        if($exists==0) {
                            $params = array($this->safe($cont[3][0]), $this->safe($cont[2][0]), $this->ts($cont[1][0]));
                            $this->query("SHARD", "INSERT INTO ".$this->table." (CharName16, Monster, Timestamp) VALUES (?, ?, ?)", $params);
                        }
                    }
                }
                
                fclose($file);
                $this->move($v);
            }
        }
    }
}

$reader = new UniqueReader();
$reader->setVar($settings);
$reader->scan();
?>