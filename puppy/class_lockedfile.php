<?php

class cLockedFile{
  // Operate on the file $FileName. Wait patiently for $Timeout seconds, after which we ignore the lock.
  function __construct($FileName, $Timeout=10){
    $this->FileName = $FileName;
    $this->Timeout = $Timeout*1000000;

    // A microsecond time stamp uniquely identifies each instance of the class.
    $this->Timestamp = microtime();
  }

  function Lock(){
    // Try over and over, until we conform a successful lock.
    while(!$this->IsOwner()){
      // Wait until the file is not locked. Check every 1/10th of a second, during max $Timeout seconds.
      for($i=0; $i<$this->Timeout/100000; $i++)
        if($this->GetLockTime()){
          usleep(100000);
        }else
          break;

      // Do the actual locking: write the microsecond timestamp to a ".lock" file.
      @file_put_contents($this->FileName.'.lock', $this->Timestamp);

      // Give other threads a chance to overwrite the file if they are very close in time.
      usleep(100000);
    }
  }

  function IsOwner(){
    // We own the file if the microsecond time stamp is identical to what we wrote to it earlier.
    return $this->Timestamp == $this->GetLockTime();
  }

  function GetLockTime(){
    // If the file contains a unix time stamp, younger than the max_execution_time, the file is still locked by a running script.
    if(is_file($this->FileName.'.lock')){
      @list($foo, $UnixTime) = explode(' ', file_get_contents($this->FileName.'.lock'));
      if($UnixTime && ($UnixTime + get_cfg_var('max_execution_time')) > time())
        return $foo.' '.$UnixTime;
    }

    return false;
  }

  function Unlock(){
    if($this->IsOwner()){
      unlink($this->FileName.'.lock');
    }
  }

  function Read(){
    // Start the locking here, releasing in Write().
    $this->Lock();
    return unserialize(file_get_contents($this->FileName));
  }

  function Write($Data){
    // Prepare the data.
    $TempName = tempnam('.', $this->FileName.'.temp.');
    $Serialized = serialize($Data);
    file_put_contents($TempName, $Serialized);

    // If the lock was stolen, wait until it is free again.
    $this->Lock();

    // Attempt atomic update, with non-atomic fallback.
    if(@ !rename($TempName, $this->FileName)){
      @unlink($this->FileName);
      if(@ !rename($TempName, $this->FileName)){
        // Clean up if even the non-atomic update fails. (If another thread renamed simultaniously.)
        unlink($TempName);
      }
    }

    // Release lock aquired in Read().
    $this->Unlock();
  }
}

?>
