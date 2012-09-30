<?php
// This software is released under the MIT license. http://www.opensource.org/licenses/mit-license.php
// Avaliable from http://github.com/geon/wwForm


require_once('action_demagicquotes.php');




// Purpose: To handle setup, validation and rendering of forms.
// Usage: Derive from it and implement the abstract part. Then Execute() and check for valid postback.
abstract class wwFormBase{
  function __construct($ID = NULL){
    // If no unique ID is specified, generate sometihng that's atleast useful for DOM scripting.
    $this->UniqueID = is_null($ID) ? ('wwForm_'.(substr(microtime(), 2)+0).__LINE__) : $ID;

    $this->ServerSideErrorMessage = NULL;

    $this->Elements = array();
    $this->Populate();
  }

  function Execute(){
    if($this->IsValidPostBack()){
      $this->Process();

      // The form might now contains old values! Repopulate to make sure we get a fresh set of fields.
      $this->Elements = array();
      $this->Populate();
    }
  }

  function Render(){
    // Main form part.
    print("\n".'<form class="wwForm" enctype="multipart/form-data" method="post" action="" id="'.$this->UniqueID.'" onsubmit="JavaScript:return Validate_'.preg_replace('/\W/', '_', $this->UniqueID).'();">');
      // Hidden field to identify the form at postback. (The "div"-tag is necesary for validation.)
      print('<div><input type="hidden" name="wwFormClassName" value="'.get_class($this).'" /></div>');

      // Obviously...
      $this->RenderElements();

      // Print server-side error message.
      $this->RenderErrorMessage();
    print('</form>'."\n");

    // Client-side validation  for comfort.
    $this->RenderValidationScript();
  }

  protected function RenderElements(){
    // Print all elements.
    print('<ul class="InputList">'."\n");
    $IsPostBack = $this->IsPostBack();
    foreach($this->Elements as $Element)
      $Element->Render($IsPostBack);
    print("\n".'</ul>');
  }

  protected function RenderErrorMessage(){
    if(isset($this->ServerSideErrorMessage))
      print('<p class="ErrorMessage" id="'.$this->UniqueID.'_ErrorMessage">'.$this->ServerSideErrorMessage.'</p><script defer type="text/javascript">'."\n//<![CDATA[\n".'document.getElementById("'.$this->UniqueID.'_ErrorMessage").style.display = "none"; setTimeout(function(){alert("'.addslashes($this->ServerSideErrorMessage).'");}, 0);'."\n//]]>\n".'</script>');
  }

  protected function RenderValidationScript(){
    // Generate validation javascript.
    print('<script type="text/javascript">'."\n//<![CDATA[\n".'function Validate_'.preg_replace('/\W/', '_', $this->UniqueID).'(){var TheForm = document.getElementById("'.$this->UniqueID.'");'."\n");
    foreach($this->Elements as $Element){
      $Element->RenderValidationScript(); print("\n");
    }
    print('return true;}'."\n//]]>\n".'</script>'."\n");
  }



  // Populate and process the form as defined by the user.
  protected abstract function Populate();
  protected abstract function Process();

  // Gather all the data posted by the user.
  protected function GetReply(){
    $Reply = array();
    foreach($this->Elements as $Element)
      $Reply[$Element->GetName()] = $Element->GetReply();
    return $Reply;
  }

  // Determine if this form was posted back.
  function IsPostBack(){
    if(isset($_POST['wwFormClassName']) && $_POST['wwFormClassName'] == get_class($this)) return true;
    else return false;
  }

  // Determine if there was a valid form posted back.
  function IsValidPostBack(){
    // Not postback.
    if(!$this->IsPostBack()) return false;

    // Invalidated server-side.
    if(isset($this->ServerSideErrorMessage)) return false;

    // Check each element.
    foreach($this->Elements as $Element)
      if(!$Element->IsValid())
        return false;

    // No errors found!
    return true;
  }

  // Some validation cannot be done client-side. This is useful for server-side validation in the "Process()" function.
  function ServerSideInvalidate($ErrorMessage){
    $this->ServerSideErrorMessage .= ' '.$ErrorMessage;
  }
}

abstract class wwFormElementBase{
  abstract function IsValid();
  protected abstract function RenderInput($IsPostBack);

  function Render($IsPostBack){
    print('<li class="Input '.get_class($this).' '.$this->GetName().'"><label>');
    $this->RenderLabel();
    print('</label>');
    $this->RenderInput($IsPostBack);
    if($IsPostBack && !$this->IsValid())
    $this->RenderErrorMessage();
    print('</li>'."\n");
  }

  function GetName(){ return $this->Name; }
  function RenderValidationScript(){ /* Nothing */ }

  protected function GetErrorMessage(){ return ''; }
  protected function RenderLabel(){ print($this->Label); }

  private function RenderErrorMessage(){ print('<p class="ErrorMessage">'.$this->GetErrorMessage().'</p>'); }    
}

// Form element class.
class wwHidden extends wwFormElementBase{
  function __construct($Name, $Value){
    $this->Name = $Name;
    $this->Value = $Value;
  }

  function Render($IsPostBack){
    $this->RenderInput($IsPostBack);
    print('<input type="hidden" name="'.$this->Name.'" value="'.$this->Value.'" />'."\n");
  }

  function RenderInput($IsPostBack){}
  function RenderLabel(){}

  function IsValid(){ return true; }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $_POST[$this->Name] : NULL;
  }
}

class wwSubmitButton extends wwFormElementBase{
  function __construct($Name, $Label, $Warning=''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Warning = $Warning;
  }

  function Render(){
    print('<li class="Input '.get_class($this).' '.$this->GetName().'">');
    print('<button type="submit" name="'.$this->Name.'" value="1"'.($this->Warning ? ' onclick="JavaScript: return confirm(\''.addslashes($this->Warning).'\')"' : '').'><span>'.$this->Label.'</span></button>');
    print('</li>');
  }

  function RenderLabel(){}
  function RenderInput($IsPostBack){}

  function IsValid(){ return true; }
  function GetReply(){ return isset($_POST[$this->Name]); }
}

class wwText extends wwFormElementBase{
  function __construct($Name, $Label, $Multiline=false, $PreSetValue='', $ValidationExpression='.*', $ErrorMessage='', $HideText=false, $ClearHiddenOnPostBack=false){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Multiline = $Multiline;
    $this->ValidationExpression = $ValidationExpression;
    $this->ErrorMessage = $ErrorMessage;
    $this->PreSetValue = $PreSetValue;
    $this->HideText = $HideText;
    $this->ClearHiddenOnPostBack = $ClearHiddenOnPostBack;
  }

  function RenderInput($IsPostBack){
    if($this->Multiline)
      print('<textarea name="'.$this->Name.'" cols="30" rows="10">'.($IsPostBack ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'</textarea>');
    else if($this->HideText == false)
      print('<input type="text" name="'.$this->Name.'" value="'.($IsPostBack ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'" />');
    else
      print('<input type="password" name="'.$this->Name.'" value="'.(($IsPostBack && !$this->ClearHiddenOnPostBack) ? (htmlentities($this->GetReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->PreSetValue, ENT_QUOTES, 'UTF-8'))).'" />');
  }

  function RenderValidationScript(){
    if($this->ValidationExpression != '.*')
      print('if(! /'.$this->ValidationExpression.'/.test(TheForm.'.$this->Name.'.value)){alert("'.addslashes($this->ErrorMessage).'"); TheForm.'.$this->Name.'.focus(); return false;}');
  }

  function IsValid(){
    return isset($_POST[$this->Name]) && preg_match('/'.$this->ValidationExpression.'/', $_POST[$this->Name]);
  }

  function GetErrorMessage(){ return $this->ErrorMessage; }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $_POST[$this->Name] : NULL;
  }
}

class wwEmail extends wwText{
  function __construct($Name, $Label, $PreSetValue = '', $ErrorMessage = 'Please enter a valid e-mail address.', $Required = false){
    parent::__construct($Name, $Label, false, $PreSetValue, '^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})'.($Required ? '' : '?').'$', $ErrorMessage);
  }
}

class wwNumeric extends wwText{
  function __construct($Name, $Label, $PreSetValue = '', $ErrorMessage = 'Please enter a numeric value.', $Required = false){
    $this->Required = $Required;
    parent::__construct($Name, $Label, false, $PreSetValue, '^(\d+([\.,:]\d+)?)'.($Required ? '' : '?').'$', $ErrorMessage, false);
  }
  function GetReply(){    
    return (isset($_POST[$this->Name]) && $_POST[$this->Name] != '') ? (str_replace(array(',', ':'), '.', trim($_POST[$this->Name]))) : NULL;
  }
}

class wwDate extends wwText{
  function __construct($Name, $Label, $PreSetValue = '', $ErrorMessage = 'Please enter a date in the format YYY-MM-DD.', $Required = false){
    $this->Required = $Required;
    parent::__construct($Name, $Label, false, $PreSetValue, '^(\d{4}-\d{2}-\d{2})'.($Required ? '' : '?').'$', $ErrorMessage, false);
  }
  function IsValid(){    
    // Quick check of the format.
    if(!isset($_POST[$this->Name]) || !preg_match('/'.$this->ValidationExpression.'/', $_POST[$this->Name]))
      return false;
      
    // Real validation. No Feb 31:st in my database!
    $Date = $_POST[$this->Name];
    $Year = substr($Date, 0, 4);
    $Month = substr($Date, 5, 2);
    $Day = substr($Date, 8, 2);
    return checkdate($Month, $Day, $Year);
  }
}

class wwHTTPURL extends wwText{
  function __construct($Name, $Label, $PreSetValue = '', $ErrorMessage = 'Please enter a valid web page address.', $Required = false){
    $this->Required = $Required;
    parent::__construct($Name, $Label, false, $PreSetValue, '^(((http|https):\/\/)?(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)?'.($Required ? '' : '?').'$', $ErrorMessage, false);
  }
  function IsValid(){
    // When not required, missing or empty fields are allways valid. (Not that there would be a sensible reason for it to be missing...)
    if(!isset($_POST[$this->Name]) || !$_POST[$this->Name]) return !$this->Required; 

    // Check the regexp.
    if(!parent::IsValid()) return false;

    // Non-technical user might skip the http://-part
    $URL = $_POST[$this->Name];
    if(substr($URL, 0, 4) != 'http') $URL = 'http://'.$URL;

    if(function_exists('curl_init')){
      // Snipped from http://www.jellyandcustard.com/2006/05/31/determining-if-a-url-exists-with-curl/    
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $URL);
      curl_setopt($ch, CURLOPT_HEADER, true);
      curl_setopt($ch, CURLOPT_NOBODY, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
      $Header = curl_exec($ch);
      curl_close($ch);
      if($Header){
        preg_match_all("/HTTP\/1\.[1|0]\s(\d{3})/",$Header,$Statii);
        $Status = end($Statii[1]);
        if($Status=='200')
          return true;
      }
      return false;
    }

    return true;
  }
  function GetReply(){
    if(!isset($_POST[$this->Name])) return '';

    // Non-technical user might skip the http://-part
    $URL = $_POST[$this->Name];
    if(substr($URL, 0, 7) != 'http://') $URL = 'http://'.$URL;
    return $URL;
  }
}

// $Options = array(array('Title'=>string, 'Value'=>mixed) [, ...])
class wwSelectBox extends wwFormElementBase{
  function __construct($Name, $Label, $Options, $PreSetValue = NULL){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Options = $Options;
    $this->PreSetValue = $PreSetValue;
  }

  function RenderInput($IsPostBack){
    $SelectedIndex = 0;
    if($IsPostBack && $this->IsValid()){
      // Fill in old value for postback...
      $SelectedIndex = $_POST[$this->Name];
    }else{
      // ...or set default value.
      foreach($this->Options as $Index => $Option)
        if($Option['Value'] === $this->PreSetValue){
          $SelectedIndex = $Index;
          break;
        }
    }

    print('<select name="'.$this->Name.'">'."\n");
    foreach($this->Options as $Index => $Option)
      print('<option'.($Index == $SelectedIndex ? ' selected' : '').' value="'.$Index.'">'.$Option['Title'].'</option>'."\n");
    print('</select>');
  }

  function IsValid(){
    return isset($_POST[$this->Name]) && isset($this->Options[$_POST[$this->Name]]);
  }

  function GetErrorMessage(){
    return 'SelectBox out of range.';
  }

  function GetReply(){
    return isset($_POST[$this->Name]) ? $this->Options[$_POST[$this->Name]]['Value'] : NULL;
  }
}




// $Options = array(array('Title'=>string, 'Value'=>mixed, 'PreSelected'=>bool) [, ...])
class wwCheckBoxList extends wwFormElementBase{
  function __construct($Name, $Label, $Options){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->Options = $Options;
  }

  function RenderInput($IsPostBack){
    print('<ul>'."\n");

    $SelectedIndex = 0;
    
    $Unique = rand();

    foreach($this->Options as $Index => $Option)
      print('<li><input id="CheckBox_'.$Unique.'_'.$Index.'" type="checkbox" name="'.$this->Name.'[]" value="'.$Index.'"'.
        (($IsPostBack && $this->IsValid())
          // Fill in old value from postback...
          ? (isset($_POST[$this->Name][$Index]) ? ' checked' : '')
          // ...or set default value.
          : ($Option['PreSelected'] ? ' checked' : '')
        )
        .' /><label for="CheckBox_'.$Unique.'_'.$Index.'">'.$Option['Title'].'</label></li>'."\n");

    print('</ul>');
  }

  function IsValid(){
    // Make sure the returned alternative is a valid choise.
    if(isset($_POST[$this->Name]))
      foreach($_POST[$this->Name] as $Reply)
        if(!isset($this->Options[$Reply]))
          return false;

    return true;
  }

  function GetErrorMessage(){
    return 'Unknown checked value.';
  }

  function GetReply(){
    $SelectedValues = array();

    // Collect all values from the reply and return them in an array.
    if(isset($_POST[$this->Name]))
      foreach($_POST[$this->Name] as $SelectedIndex)
        $SelectedValues[] = $this->Options[$SelectedIndex]['Value'];

    return $SelectedValues;
  }

  public $Name;
  public $Label;
  public $Options;
}



class wwFileUpload extends wwFormElementBase{
  function __construct($Name, $Label, $ValidationExpression = '.', $ErrorMessage = ''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->ValidationExpression = $ValidationExpression;
    $this->ErrorMessage = $ErrorMessage;
  }

  function RenderInput($IsPostBack){
    print('<input type="file" name="'.$this->Name.'" onchange="JavaScript:if(! /'.$this->ValidationExpression.'/.test(this.value)){alert(\''.addslashes($this->ErrorMessage).'\'); this.value=\'\';}" />');
  }

  function RenderValidationScript(){
    print('if(! /'.$this->ValidationExpression.'/.test(TheForm.'.$this->Name.'.value)){alert("'.addslashes($this->ErrorMessage).'"); return false;}');
  }

  function IsValid(){
    return (($_FILES[$this->Name]['name'] == '') || is_uploaded_file($_FILES[$this->Name]['tmp_name'])) && preg_match('/'.$this->ValidationExpression.'/', $_FILES[$this->Name]['name']);
  }

  function GetErrorMessage(){
    $Error = $_FILES[$this->Name]['error'];
    if($Error)
      return 'Technical error'.(($Error == 1) ? (': Max file size ('.ini_get('upload_max_filesize').') exceeded.') : '.');
    else
      return $this->ErrorMessage;
  }

  function GetReply(){
    return (isset($_FILES[$this->Name]) && is_uploaded_file($_FILES[$this->Name]['tmp_name'])) ? $_FILES[$this->Name] : NULL;
  }
}

class wwIconified extends wwFormElementBase{
  function __construct($Name, $Label, $FileTypes){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->FileTypes = $FileTypes;
  }

  function RenderInput($IsPostBack){
    print('<input class="wwIconified" type="file" name="'.$this->Name.'[]" />');
  }


  function IsValid(){
    $AllFine = true;
    foreach($_FILES[$this->Name]['name'] as $Index => $Foo)
      if(($_FILES[$this->Name]['name'][$Index] != '') && !is_uploaded_file($_FILES[$this->Name]['tmp_name'][$Index])){
        $AllFine = false;
        break;
      }

    return $AllFine;
  }

  function GetReply(){
    if(!$this->IsValid()) return NULL;

    // Transpose the array and erase empty rows.
    $Files = array();
    foreach($_FILES[$this->Name]['name'] as $Index => $File)
      if($_FILES[$this->Name]['size'][$Index] != '')
        $Files[$Index] = array('name'=>$_FILES[$this->Name]['name'][$Index], 'tmp_name'=>$_FILES[$this->Name]['tmp_name'][$Index]);

    return $Files;
  }
}

class wwCheckBox extends wwFormElementBase{
  function __construct($Name, $Label, $PreChecked = false, $Required = false, $ErrorMessage = ''){
    $this->Name = $Name;
    $this->Label = $Label;
    $this->PreChecked = $PreChecked;
    $this->Required = $Required;
    $this->ErrorMessage = $ErrorMessage;
  }

  function Render($IsPostBack){
    print('<li class="Input '.get_class($this).' '.$this->GetName().'">');
    $this->RenderInput($IsPostBack);
    print('<label>');
    $this->RenderLabel();
    print('</label>');
    if($IsPostBack && !$this->IsValid())
    $this->RenderErrorMessage();
    print('</li>'."\n");
  }

  function RenderInput($IsPostBack){
    print('<input type="checkbox" name="'.$this->Name.'"'.(($this->PreChecked || $this->GetReply()) ? ' checked="checked"' : '').' />');
  }

  function RenderValidationScript(){
    if($this->Required)
      print('if(!TheForm.'.$this->Name.'.checked){alert("'.addslashes($this->ErrorMessage).'"); TheForm.'.$this->Name.'.focus(); return false;}');
  }

  function IsValid(){
    return $this->Required ? $this->GetReply() : true;
  }

  function GetErrorMessage(){ return $this->ErrorMessage; }

  function GetReply(){ return isset($_POST[$this->Name]) ? true : false; }
}






?>
