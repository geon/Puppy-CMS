<?php



require_once('action_demagicquotes.php');






// Purpose: To handle setup, validation and rendering of forms.
// Usage: Derive from it and implement the abstract part. Then Execute() and check for valid postback.
abstract class wwFormBase{
  function __construct($UniqueID = NULL){
    $this->UniqueID = is_null($UniqueID) ? ((substr(microtime(), 2)+0).__LINE__) : $UniqueID;
    $this->Elements = array();

    $this->IsServerSideInvalidated = false;
    $this->ServerSideErrorMessage = '';

    $this->Populate();
  }

  function Execute(){
    if($this->IsValidPostBack()){
      $this->Process();
    }
  }

  function Render(){
    // Main form part.
    print('<form enctype="multipart/form-data" method="post" id="wwForm_'.$this->UniqueID.'" onsubmit="JavaScript:return Validate_wwForm_'.$this->UniqueID.'();">');

      // Hidden field to intify the form at postback.
      print('<input type="hidden" name="wwFormClassName" value="'.get_class($this).'">');
  
      $this->RenderElements();

    print('</form>');

    $this->RenderValidationScript();
  }

  protected function RenderElements(){
    // Print all elements.
    foreach($this->Elements as $Element){
      if($Element instanceof wwHidden){
        $Element->RenderInput();
      }else if($Element instanceof wwValidationScript){
        // Nothing
		}else{
        if($Element instanceof wwCheckBox){
          $Element->RenderInput();
          $Element->RenderLabel();
        }else{
          print('<b>');
          $Element->RenderLabel();
          print('</b><br>');
          $Element->RenderInput();
        }
        if($this->IsPostBack() && !$Element->IsValid())
          print(' <b>Error: </b>'.$Element->GetErrorMessage());
        print('<br>');
      }
    }

    // Print server-side error message.
    if($this->IsServerSideInvalidated && $this->ServerSideErrorMessage != '')
      print('<p id="'.$this->UniqueID.'_ErrorMessage"><b>Error: </b>'.$this->ServerSideErrorMessage.'</p><script defer language="JavaScript">document.getElementById("'.$this->UniqueID.'_ErrorMessage").style.display = "none"; alert("'.addslashes($this->ServerSideErrorMessage).'");</script>');
  }

  protected function RenderValidationScript(){
    // Generate validation javascript.
    print('<script language="JavaScript">function Validate_wwForm_'.$this->UniqueID.'(){var TheForm = document.getElementById("wwForm_'.$this->UniqueID.'"); ');
    foreach($this->Elements as $Element)
      $Element->RenderValidationScript();
    print(' return true;}</script>');
  }



  // Populate and process the form as defined by the user.
  protected abstract function Populate();
  protected abstract function Process();

  // Gather all the data posted by the user.
  protected function GetReply(){
    $Reply = array();
    foreach($this->Elements as $Element)
      $Reply[$Element->Name] = $Element->GetReply();
    return $Reply;
  }

  // Determine if this form was posted back.
  function IsPostBack(){
    if(isset($_POST['wwFormClassName']) && $_POST['wwFormClassName'] == get_class($this)) return true;
    else return false;
  }

  function IsValidPostBack(){
    if(!$this->IsPostBack()) return false;

    if($this->IsServerSideInvalidated) return false;

    $Valid = count($this->Elements) > 0;
    foreach($this->Elements as $Element)
      $Valid = $Valid && $Element->IsValid();

    return $Valid;
  }

  function ServerSideInvalidate($ErrorMessage){
    $this->IsServerSideInvalidated = true;
    $this->ServerSideErrorMessage .= ' '.$ErrorMessage;
  }
}

// Form element class.
class wwHidden{
  function __construct($Name, $Value){
    $this->Name = $Name;
    $this->Value = $Value;
  }

  function RenderInput(){
    print('<input type="hidden" name="'.$this->Name.'" value="'.$this->Value.'">');
  }

  function RenderValidationScript(){
  }

  function IsValid(){
    return true;
  }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $_POST[$this->Name] : NULL;
  }
}

class wwSubmitButton{
  function __construct($Name, $Label, $Warning=''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Warning = $Warning;
  }

  function RenderLabel(){
  }

  function RenderInput(){
    print('<input class="wwSubmitButton" type="submit" name="'.$this->Name.'" value="'.$this->Label.'"'.($this->Warning ? ' onclick="JavaScript: return confirm(\''.addslashes($this->Warning).'\')"' : '').'>');
  }

  function RenderValidationScript(){
  }

  function IsValid(){
    return true;
  }

  function GetErrorMessage(){
    return '';
  }

  function GetReply(){
    return (bool) isset($_POST[$this->Name]);
  }

  public $Name;
  public $Label;
}

class wwImageButton extends wwSubmitButton{
  function __construct($Name, $ImageLabelURL, $Warning=''){
    $this->Name = $Name;
    $this->Label = $ImageLabelURL;
    $this->Warning = $Warning;
  }

  function RenderInput(){
    print('<input style="width: auto;" type="image" name="'.$this->Name.'" src="'.$this->Label.'">');
  }
}

class wwText{
  function __construct($Name, $Label, $Multiline=false, $PreSetValue='', $ValidationExpression = '.*', $ErrorMessage = '', $HideText = false){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Multiline = $Multiline;
    $this->ValidationExpression = $ValidationExpression;
    $this->ErrorMessage = $ErrorMessage;
    $this->PreSetValue = $PreSetValue;
    $this->HideText = $HideText;
  }

  function RenderLabel(){
    print($this->Label);
  }

  function RenderInput(){
    if($this->Multiline)
      print('<textarea class="wwText" name="'.$this->Name.'">'.($this->IsValid() ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'</textarea>');
    else if($this->HideText == false)
      print('<input class="wwText" type="text" name="'.$this->Name.'" value="'.($this->IsValid() ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'">');
    else
      print('<input type="password" name="'.$this->Name.'" value="'.($this->IsValid() ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'">');
  }

  function RenderValidationScript(){
    print('if(! /'.$this->ValidationExpression.'/.test(TheForm.'.$this->Name.'.value)){alert("'.addslashes($this->ErrorMessage).'"); TheForm.'.$this->Name.'.focus(); return false;}');
  }

  function IsValid(){
    return isset($_POST[$this->Name]) && preg_match('/'.$this->ValidationExpression.'/', $_POST[$this->Name]);
  }

  function GetErrorMessage(){
    return $this->ErrorMessage;
  }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $_POST[$this->Name] : '';
  }

  public $Name;
  public $Label;
  public $Multiline;

  public $ValidationExpression;
  public $ErrorMessage;
  public $PreSetValue;

  public $HideText;
}

class wwEmail extends wwText{
  function __construct($Name, $Label, $PreSetValue='', $ErrorMessage = 'Please enter a valid e-mail address.'){
    parent::__construct($Name, $Label, false, $PreSetValue, '^[a-zA-Z0-9._%-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$', $ErrorMessage, false);
  }
}

// Securely handles password verification.
// Passwords are not sent in plain-text, but as a irreversible time- and IP-dependent
// sha1 scrambled string. If Javascript is not enabled, the browser will fallback
// to plain text.
//
// If the form needs a correction, the same scrambled string will be sent back, and the
// "password" will be set to a dummy string. The password will time out in 10 minutes
// (by default) and will need to be retyped after that.
//
// For a combined UserName/PassWord login, at the first Populate() when the username
// is not known, you should use a plain wwText with HideText for password. Later, after the
// UserName/PassWord is supplied, Populate() with wwPassWord, using the same Name
// parameter and with the proper password. NEVER TRY TO VALIDATE PASSWORDS WITH wwText!
class wwPassWord{
  function __construct($Name, $Label, $PassWord, $TimeOut = 10, $MatchErrorMessage = 'Invalid password. Please try again.', $TimeOutErrorMessage = 'Password timed out. Please enter your password again.'){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->PassWord = $PassWord;
    $this->TimeOut = $TimeOut;
    $this->MatchErrorMessage = $MatchErrorMessage;
    $this->TimeOutErrorMessage = $TimeOutErrorMessage;

    $this->Challenge = $this->MakeChallenge();
  }

  function MakeChallenge($MinutesAgo = 0){
    return $_SERVER['REMOTE_ADDR'] . ' ' . date('Y-m-d H:') . ((date('i') + 60-$MinutesAgo)%60);
  }

  function MakeScrambled($MinutesAgo = 0){
    return sha1($this->MakeChallenge($MinutesAgo) . $this->PassWord);
  }

  function RenderLabel(){
    print($this->Label);
  }

  function RenderInput(){
    print('<input type="hidden" id="'.$this->Name.'_TempHash" name="'.$this->Name.'_TempHash" value="'.($this->IsValid() ? $this->MakeScrambled() : '').'">');
    print('<input type="password" name="'.$this->Name.'" value="'.($this->IsValid() ? preg_replace('/./', 'x', $this->PassWord) : '').'">');

    // Clear the preset scrambled password after the timeout. (JavaScript validation will notify the user.)
    if($this->IsValid())
      print('<script language="JavaScript">function '.$this->Name.'ClearPresets(){document.getElementById("'.$this->Name.'_TempHash").value="TimedOut";} setTimeout("'.$this->Name.'ClearPresets()", 60000*'.$this->TimeOut.');</script>');
  }

  function RenderValidationScript(){

    print(' 	 
      // You are welcome to re-use these (sha-1) scripts [without any warranty express or implied] provided you retain my copyright notice and when possible a link to my website.
      // If you have any queries or find any problems, please contact me.
      // 2002-2005 Chris Veness, scripts@movable-type.co.uk , http://www.movable-type.co.uk/scripts/SHA-1.html
      function sha1Hash(msg){ var K = [0x5a827999, 0x6ed9eba1, 0x8f1bbcdc, 0xca62c1d6]; msg += String.fromCharCode(0x80); var l = Math.ceil(msg.length/4) + 2; var N = Math.ceil(l/16); var M = new Array(N); for (var i=0; i<N; i++){ M[i] = new Array(16); for (var j=0; j<16; j++) { M[i][j] = (msg.charCodeAt(i*64+j*4)<<24) | (msg.charCodeAt(i*64+j*4+1)<<16) | (msg.charCodeAt(i*64+j*4+2)<<8) | (msg.charCodeAt(i*64+j*4+3)); }} M[N-1][14] = ((msg.length-1) >>> 30) * 8; M[N-1][15] = ((msg.length-1)*8) & 0xffffffff; var H0 = 0x67452301; var H1 = 0xefcdab89; var H2 = 0x98badcfe; var H3 = 0x10325476; var H4 = 0xc3d2e1f0; var W = new Array(80); var a, b, c, d, e; for (var i=0; i<N; i++) {for (var t=0;  t<16; t++) W[t] = M[i][t]; for (var t=16; t<80; t++) W[t] = ROTL(W[t-3] ^ W[t-8] ^ W[t-14] ^ W[t-16], 1); a = H0; b = H1; c = H2; d = H3; e = H4; for (var t=0; t<80; t++) { var s = Math.floor(t/20); var T = (ROTL(a,5) + f(s,b,c,d) + e + K[s] + W[t]) & 0xffffffff; e = d; d = c; c = ROTL(b, 30); b = a; a = T;} H0 = (H0+a) & 0xffffffff; H1 = (H1+b) & 0xffffffff; H2 = (H2+c) & 0xffffffff; H3 = (H3+d) & 0xffffffff; H4 = (H4+e) & 0xffffffff;} return H0.toHexStr() + H1.toHexStr() + H2.toHexStr() + H3.toHexStr() + H4.toHexStr(); }
      function f(s, x, y, z){ switch (s) { case 0: return (x & y) ^ (~x & z); case 1: return x ^ y ^ z; case 2: return (x & y) ^ (x & z) ^ (y & z); case 3: return x ^ y ^ z; }}
      function ROTL(x, n){return (x<<n) | (x>>>(32-n));}
      Number.prototype.toHexStr = function(){var s="", v; for (var i=7; i>=0; i--) { v = (this>>>(i*4)) & 0xf; s += v.toString(16); }return s;}      // Test for match.

      // Check for normal mismatch.
      if(TheForm.'.$this->Name.'_TempHash.value == "" && sha1Hash("ClientSideMatch" + TheForm.'.$this->Name.'.value) != "'.sha1('ClientSideMatch' . $this->PassWord).'"){
        alert("'.addslashes($this->MatchErrorMessage).'");
        TheForm.'.$this->Name.'.focus();
        return false;
      }

      // Check for timeout of autocompleted scrambled password.
      if(TheForm.'.$this->Name.'_TempHash.value == "TimedOut" && TheForm.'.$this->Name.'.value == "'.preg_replace('/./', 'x', $this->PassWord).'"){
        alert("'.addslashes($this->TimeOutErrorMessage).'");
        TheForm.'.$this->Name.'_TempHash.value = "";
        TheForm.'.$this->Name.'.value = "";
        TheForm.'.$this->Name.'.focus();
        return false;
      }
    ');
  }

  function IsValid(){
    // Check if the scrambled password matches.
    if(isset($_POST[$this->Name.'_TempHash'])){
      for($i=0; $i<$this->TimeOut; $i++)
        if($_POST[$this->Name.'_TempHash'] == $this->MakeScrambled($i)) return true;
    }

    // Check if the password was supplied in plaintext.
    if(isset($_POST[$this->Name]) && $_POST[$this->Name] == $this->PassWord) return true;

    return false;
  }

  function GetErrorMessage(){
    if(isset($_POST[$this->Name]) && $_POST[$this->Name] != $this->PassWord && $_POST[$this->Name] != preg_replace('/./', 'x', $this->PassWord))
      return $this->MatchErrorMessage;
    else
      return $this->TimeOutErrorMessage;
  }

  function GetReply(){
    return $this->IsValid();
  }
}


// $Options = array(array('Title'=>string, 'Value'=>mixed) [, ...])
class wwSelectBox{
  function __construct($Name, $Label, $Options, $PreSetValue=NULL){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Options = $Options;
    $this->PreSetValue = $PreSetValue;
  }

  function RenderLabel(){
    print($this->Label);
  }

  function RenderInput(){
    $SelectedIndex = 0;
    if(isset($_POST[$this->Name]) && $this->IsValid()){
      // Fill in old value for postback...
      foreach($this->Options as $Index => $Option)
        if((string) $Option['Value'] == $_POST[$this->Name]){
          $SelectedIndex = $Index;
          break;
        }
    }else{
      // ...or set default value.
      foreach($this->Options as $Index => $Option)
        if($Option['Value'] == $this->PreSetValue){
          $SelectedIndex = $Index;
          break;
        }
    }

    print('<select name="'.$this->Name.'">');

    foreach($this->Options as $Index => $Option)
      print('<option'.($Index == $SelectedIndex ? ' selected' : '').' value="'.$Option['Value'].'">'.$Option['Title']);

    print('</select>');
  }

  function RenderValidationScript(){
  }

  function IsValid(){
    if(isset($_POST[$this->Name]))
      foreach($this->Options as $Option)
        if($Option['Value'] == $_POST[$this->Name])
          return true;

    return false;
  }

  function GetErrorMessage(){
    return 'SelectBox out of range.';
  }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $_POST[$this->Name] : '';
  }

  public $Name;
  public $Label;
  public $Options;
  public $PreSetValue;
}

class wwFileUpload{
  function __construct($Name, $Label, $ValidationExpression = '.', $ErrorMessage = ''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->ValidationExpression = $ValidationExpression;
    $this->ErrorMessage = $ErrorMessage;
  }

  function RenderLabel(){
    print($this->Label);
  }

  function RenderInput(){
    print('<input type="file" name="'.$this->Name.'">');
  }

  function RenderValidationScript(){
    print('if(! /'.$this->ValidationExpression.'/.test(TheForm.'.$this->Name.'.value)){alert("'.addslashes($this->ErrorMessage).'"); return false;}');
  }

  function IsValid(){
    return (($_FILES[$this->Name]['name'] == '') || is_uploaded_file($_FILES[$this->Name]['tmp_name'])) && preg_match('/'.$this->ValidationExpression.'/', $_FILES[$this->Name]['name']);
  }

  function GetErrorMessage(){
    return $this->ErrorMessage;
  }

  function GetReply(){
    return isset($_FILES[$this->Name]) ? $_FILES[$this->Name] : false;
  }

  public $Name;
  public $Label;
  public $ValidationExpression;
  public $ErrorMessage;
}

class wwCheckBox{
  function __construct($Name, $Label, $PreChecked = false, $Required = false, $ErrorMessage = ''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->PreChecked = $PreChecked;
    $this->Required = $Required;
    $this->ErrorMessage = $ErrorMessage;
  }

  function RenderLabel(){
    print($this->Label);
  }

  function RenderInput(){
    print('<input style="width:auto;" type="checkbox" name="'.$this->Name.'"'.(($this->PreChecked || $this->GetReply()) ? ' checked' : '').'>');
  }

  function RenderValidationScript(){
    if($this->Required)
      print('if(!TheForm.'.$this->Name.'.checked){alert("'.addslashes($this->ErrorMessage).'"); TheForm.'.$this->Name.'.focus(); return false;}');
  }

  function IsValid(){
    return $this->Required ? $this->GetReply() : true;
  }

  function GetErrorMessage(){
    return '';
  }

  function GetReply(){
    return isset($_POST[$this->Name]) ? true : false;
  }

  public $Name;
  public $Label;
  public $PreChecked;
}

class wwValidationScript{
  function __construct($ValidationScript = ''){
    $this->ValidationScript = $ValidationScript;
    $this->Name = 0;
  }

  function RenderLabel(){
  }

  function RenderInput(){
  }

  function RenderValidationScript(){
    print($this->ValidationScript);
  }

  function IsValid(){
    return true;
  }

  function GetErrorMessage(){
    return '';
  }

  function GetReply(){
    return '';
  }

  public $Name;
  public $ValidationScript;
}












/*
   A (Root)      A is the root node (main menu ?). Under that, there's the list B=C=D,
   |             which acts as a single node, defining 3 separate, cooporating values.
  [B=C=D]-G      Under this node, there is nodes E, F & G. They are attached to diffrent
   |   |         parts of the node [A=B=C].
   E   F         
                 * The URL of A will be A.
                 * The URL of B, C & D will be ABCD.
                 * The URL of E will be ABE.
                 * The URL of F or G will be ABCDF or ABCDG, respectively.

                 The structure is basically a tree, where nodes might be a doubly linked list.
                 You should never try to put a cooporating node after a node that allready
                 has a cooporating node, since you can't build doubly linked trees.
                     
  Purpose:
    To handle URL data. Any class that wish to affect the URL should derive from this.
  Usage:
    Derive from it and implement the abstract validation and identification.
    The top Parent should supply NULL as it's parent.
*/

abstract class wwURLAffector{
  function __construct($Name, $Previous=NULL, $IsCooperative=false){
    $this->Name = $Name;
    $this->Previous = $Previous;
    $this->Cooperative = NULL;
  
    if(!is_null($this->Previous) && $IsCooperative)
      $this->Previous->Cooperative = $this;
  }

  // Construct the complete (relative) URL.
  function GetURL($ForceValue = NULL, $HTMLEntities=true){
    // Set the base document URL.
    $URL = '?';

    // Add all necessary arguments before $this.
    foreach($this->GetPreviousArgumentList() as $Argument)
      $URL .= ($Argument ? ($Argument.'&') : '');

    // Add the argument of $this, using the forced value, if any.
    $Argument = $this->GetUrlArgument($ForceValue);
      $URL .= ($Argument ? ($Argument.'&') : '');

    // Add all cooperating arguments.
    foreach($this->GetCooperativeArgumentList() as $Argument)
      $URL .= ($Argument ? ($Argument.'&') : '');

    // Trim the excess '&' and convert to HTML entities for standard compliance.
    return $HTMLEntities ? htmlentities(rtrim($URL, '&'), ENT_QUOTES, 'UTF-8') : rtrim($URL, '&');
  }

  // Get the argument of the currently used instance, optionally forcing the value.
  abstract protected function GetURLArgument($ForceValue = NULL);

  // Make a list of all arguments above $this.
  private function GetPreviousArgumentList(){
    if(is_null($this->Previous)) return array();
    return array_merge($this->Previous->GetPreviousArgumentList(), array($this->Previous->GetURLArgument()));;
  }

  // Make a list of all arguments below $this.
  private function GetCooperativeArgumentList(){
    if(is_null($this->Cooperative)) return array();
    return array_merge($this->Cooperative->GetCooperativeArgumentList(), array($this->Cooperative->GetURLArgument()));;
  }

  protected $Previous;
  protected $Cooperative;
  protected $Name;
}








/*
  Purpose:
    A value-link, attached to the chain of URL-affectors.
  
  Usage:
    Supply name and value to define the name in the URL and the value you want
    back. (The value will not be shown to the public.) Supply Previous to tell
    the link where to attach itself.

    When IsActive() returns true, the link has been followed.

    The link can't be rendered. You just get the URL and print it yourself.
*/
class wwValueLink extends wwURLAffector{      
  function GetValue(){
    return isset($_GET[$this->Name]) ? $_GET[$this->Name] : NULL;
  }

  protected function GetURLArgument($ForceValue = NULL){
    // The link is valid if the argument is set. No value is needed in the URL.
    return (isset($ForceValue) || isset($_GET[$this->Name])) ? ($this->Name.'='.(isset($ForceValue) ? $ForceValue : $_GET[$this->Name])) : '';
  }
}







/*
  Purpose:
    To handle setup, validation and rendering of menus.
  
  Usage:
    Derive from it and implement the abstract part. Don't forget to set
    the $this->Name in the constructor before calling the parent's constructor.
    
    $this->Options should be of the format array(array('Title'=>'foo', 'Value'=>'bar') [, ...]).
    
    Use GetSelectedValue(), or get the value manually by using
    GetSelectedIndex() to index options returned by GetOptions().
    
    As with any URLAffector, use GetURL() to get the URL. Obviously.
*/
abstract class wwMenuBase extends wwURLAffector{
  function __construct($Name, $Previous, $IsCooperative=false, $DefaultToFirst=false){
    parent::__construct($Name, $Previous, $IsCooperative);

    $this->DefaultToFirst = $DefaultToFirst;

    // Fill the menu as defined by the user.    
    $this->Options = array();
    $this->SetOptions();
  }

  function IsValid(){
    // Make sure the argument is set.
    if(isset($_GET[$this->Name]) && $_GET[$this->Name]!='')
      // Check if the argument is listed in the options list.
      foreach($this->Options as $Index => $Option)
        if(($_GET[$this->Name]) === (string)$Index) return true;

    // ...not one of the available options.
    return false;
  }

  function GetOptions(){
    return $this->Options;
  }

  function GetSelectedIndex(){
    reset($this->Options);
    return $this->IsValid() ? ($_GET[$this->Name]) : (($this->DefaultToFirst && count($this->Options)) ? key($this->Options) : NULL);
  }

  function GetSelectedValue(){
    $SelectedIndex = $this->GetSelectedIndex();
    return (is_null($SelectedIndex)) ? NULL : $this->Options[$SelectedIndex]['Value'];
  }

  function GetSelectedOption(){
    $SelectedIndex = $this->GetSelectedIndex();
    return (is_null($SelectedIndex)) ? NULL : $this->Options[$SelectedIndex];
  }

  function Render(){
    print("\n".'<ul id="'.$this->Name.'" class="wwMenuBase">');
    $First = true;
    $SelectedIndex = $this->GetSelectedIndex();
    foreach($this->Options as $Index => $Option){
      // Define some special cases. (CSS classes)
      $ClassList = '';
      if(((string)$Index) === ((string)$SelectedIndex)) $ClassList = 'Current ';
      if($First){
        $ClassList .= 'First';
        $First = false;
      }

      // Print the links.
      print("\n".'<li'.($ClassList ? (' class="'.$ClassList.'"') : '').'><a href="'.$this->GetURL($Index).'">'.htmlentities($Option['Title'], ENT_QUOTES, 'UTF-8').'</a></li>');
    }
    print("\n".'</ul>');
  }

  protected abstract function SetOptions();
      
  protected function GetURLArgument($ForceValue = NULL){
    $Index = (is_null($ForceValue) ? $this->GetSelectedIndex() : $ForceValue);
    return is_null($Index) ? '' : ($this->Name.'='.$Index);
  }

  protected $Options;
}









/*
  Purpose:
    To handle paging of large data sets. Garantuied to give a sensible and proper
    paging offset regardless of user input.
  
  Usage:
    Supply it with $NumItems and $PageSize. Then GetOffSet() to know where to start
    outputting your data. Render() will show a basic paging menu.
*/
class wwPager extends wwURLAffector{
  function __construct($Name, $Previous, $IsCooperative, $NumItems, $PageSize){
    parent::__construct($Name, $Previous, $IsCooperative);

    $this->NumItems = $NumItems;
    $this->PageSize = $PageSize;
  }

  function GetOffSet(){
    // Clip argument to [0, NumItems).
    return isset($_GET[$this->Name]) ? (floor(max(min(($_GET[$this->Name] +0), max($this->NumItems-1, 0)), 0)/$this->PageSize)*$this->PageSize) : 0;
  }

  function Render(){
    if($this->NumItems > $this->PageSize){
  
      print('<ul class="wwPager">');
  
      // Print link to previous page. (Looping)
      $OffSet = $this->GetOffSet()-$this->PageSize;
      print('<li class="First"><a href="'.$this->GetURL($OffSet >= 0 ? $OffSet : (floor($this->NumItems/$this->PageSize)*$this->PageSize)).'">&laquo;</a></li>');
  
      // Print numbered direct links.
      for($i=0; $i<$this->NumItems; $i+=$this->PageSize){
        print('<li'.($i == $this->GetOffSet() ? ' class="Current"' : '').'><a href="'.$this->GetURL($i).'">'.floor($i/$this->PageSize +1).'</a></li>');
      }
  
      // Print link to next page. (Looping)
      $OffSet = $this->GetOffSet()+$this->PageSize;
      print('<li><a href="'.$this->GetURL($OffSet < $this->NumItems ? $OffSet : 0).'">&raquo;</a></li>');
  
      print('</ul>');
    }
  }
      
  protected function GetURLArgument($ForceValue = NULL){
    return $this->Name.'='.(is_null($ForceValue) ? $this->GetOffSet() : $ForceValue);
  }

  protected $Options;
}










?>
