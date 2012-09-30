<?php
// This software is released under the MIT license. http://www.opensource.org/licenses/mit-license.php
// Avaliable from http://github.com/geon/wwForm


require_once('action_demagicquotes.php');




// Purpose: To handle setup, validation and rendering of forms.
// Usage: Derive from it and implement the abstract part. Then Execute() and check for valid postback.
abstract class wwFormBase{
	function __construct($ID = NULL) {
		// If no unique ID is specified, generate sometihng that's atleast useful for DOM scripting.
		$this->uniqueID = is_null($ID) ? ('wwForm_'.(substr(microtime(), 2)+0).__LINE__) : $ID;

		$this->serverSideErrorMessage = NULL;

		$this->elements = array();
		$this->populate();
	}

	function execute() {
		if ($this->isValidPostBack()) {
			$this->process();

			// The form might now contains old values! Repopulate to make sure we get a fresh set of fields.
			$this->elements = array();
			$this->populate();
		}
	}

	function render() {
		// Main form part.
		print("\n".'<form class="wwForm" enctype="multipart/form-data" method="post" action="" id="'.$this->uniqueID.'" onsubmit="JavaScript:return Validate_'.preg_replace('/\W/', '_', $this->uniqueID).'();">');
			// Hidden field to identify the form at postback. (The "div"-tag is necesary for validation.)
			print('<div><input type="hidden" name="wwFormClassName" value="'.get_class($this).'" /></div>');

			// Obviously...
			$this->renderElements();

			// Print server-side error message.
			$this->renderErrorMessage();
		print('</form>'."\n");

		// Client-side validation	for comfort.
		$this->renderValidationScript();
	}

	protected function renderElements() {
		// Print all elements.
		print('<ul class="InputList">'."\n");
		$isPostBack = $this->isPostBack();
		foreach ($this->elements as $element)
			$element->Render($isPostBack);
		print("\n".'</ul>');
	}

	protected function renderErrorMessage() {
		if (isset($this->serverSideErrorMessage))
			print('<p class="ErrorMessage" id="'.$this->uniqueID.'_ErrorMessage">'.$this->serverSideErrorMessage.'</p><script defer type="text/javascript">'."\n//<![CDATA[\n".'document.getElementById("'.$this->uniqueID.'_ErrorMessage").style.display = "none"; setTimeout(function() {alert("'.addslashes($this->serverSideErrorMessage).'");}, 0);'."\n//]]>\n".'</script>');
	}

	protected function renderValidationScript() {
		// Generate validation javascript.
		print('<script type="text/javascript">'."\n//<![CDATA[\n".'function Validate_'.preg_replace('/\W/', '_', $this->uniqueID).'() {var TheForm = document.getElementById("'.$this->uniqueID.'");'."\n");
		foreach ($this->elements as $element) {
			$element->RenderValidationScript(); print("\n");
		}
		print('return true;}'."\n//]]>\n".'</script>'."\n");
	}



	// Populate and process the form as defined by the user.
	protected abstract function populate();
	protected abstract function process();

	// Gather all the data posted by the user.
	protected function getReply() {
		$reply = array();
		foreach ($this->elements as $element)
			$reply[$element->getName()] = $element->getReply();
		return $reply;
	}

	// Determine if this form was posted back.
	function isPostBack() {
		if (isset($_POST['wwFormClassName']) && $_POST['wwFormClassName'] == get_class($this)) return true;
		else return false;
	}

	// Determine if there was a valid form posted back.
	function isValidPostBack() {
		// Not postback.
		if (!$this->isPostBack()) return false;

		// Invalidated server-side.
		if (isset($this->serverSideErrorMessage)) return false;

		// Check each element.
		foreach ($this->elements as $element)
			if (!$element->isValid())
				return false;

		// No errors found!
		return true;
	}

	// Some validation cannot be done client-side. This is useful for server-side validation in the "Process()" function.
	function serverSideInvalidate($errorMessage) {
		$this->serverSideErrorMessage .= ' '.$errorMessage;
	}
}

abstract class wwFormElementBase{
	abstract function isValid();
	protected abstract function renderInput($isPostBack);

	function render($isPostBack) {
		print('<li class="Input '.get_class($this).' '.$this->getName().'"><label>');
		$this->renderLabel();
		print('</label>');
		$this->renderInput($isPostBack);
		if ($isPostBack && !$this->isValid())
		$this->renderErrorMessage();
		print('</li>'."\n");
	}

	function getName() { return $this->name; }
	function renderValidationScript() { /* Nothing */ }

	protected function getErrorMessage() { return ''; }
	protected function renderLabel() { print($this->label); }

	private function renderErrorMessage() { print('<p class="ErrorMessage">'.$this->getErrorMessage().'</p>'); }		
}

// Form element class.
class wwHidden extends wwFormElementBase{
	function __construct($name, $value) {
		$this->name = $name;
		$this->value = $value;
	}

	function render($isPostBack) {
		$this->renderInput($isPostBack);
		print('<input type="hidden" name="'.$this->name.'" value="'.$this->value.'" />'."\n");
	}

	function renderInput($isPostBack) {}
	function renderLabel() {}

	function isValid() { return true; }

	function getReply() {
		return isset($_POST[$this->name]) ? $_POST[$this->name] : NULL;
	}
}

class wwSubmitButton extends wwFormElementBase{
	function __construct($name, $label, $warning='') {
		$this->name = $name;
		$this->label = $label;
		$this->warning = $warning;
	}

	function render() {
		print('<li class="Input '.get_class($this).' '.$this->getName().'">');
		print('<button type="submit" name="'.$this->name.'" value="1"'.($this->warning ? ' onclick="JavaScript: return confirm(\''.addslashes($this->warning).'\')"' : '').'><span>'.$this->label.'</span></button>');
		print('</li>');
	}

	function renderLabel() {}
	function renderInput($isPostBack) {}

	function isValid() { return true; }
	function getReply() { return isset($_POST[$this->name]); }
}

class wwText extends wwFormElementBase{
	function __construct($name, $label, $multiline=false, $preSetValue='', $validationExpression='.*', $errorMessage='', $hideText=false, $clearHiddenOnPostBack=false) {
		$this->name = $name;
		$this->label = $label;
		$this->multiline = $multiline;
		$this->validationExpression = $validationExpression;
		$this->errorMessage = $errorMessage;
		$this->preSetValue = $preSetValue;
		$this->hideText = $hideText;
		$this->clearHiddenOnPostBack = $clearHiddenOnPostBack;
	}

	function renderInput($isPostBack) {
		if ($this->multiline)
			print('<textarea name="'.$this->name.'" cols="30" rows="10">'.($isPostBack ? (htmlentities($this->getReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->preSetValue, ENT_QUOTES, 'UTF-8'))).'</textarea>');
		else if ($this->hideText == false)
			print('<input type="text" name="'.$this->name.'" value="'.($isPostBack ? (htmlentities($this->getReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->preSetValue, ENT_QUOTES, 'UTF-8'))).'" />');
		else
			print('<input type="password" name="'.$this->name.'" value="'.(($isPostBack && !$this->clearHiddenOnPostBack) ? (htmlentities($this->getReply(), ENT_QUOTES, 'UTF-8')) : (htmlentities($this->preSetValue, ENT_QUOTES, 'UTF-8'))).'" />');
	}

	function renderValidationScript() {
		if ($this->validationExpression != '.*')
			print('if (! /'.$this->validationExpression.'/.test(TheForm.'.$this->name.'.value)) {alert("'.addslashes($this->errorMessage).'"); TheForm.'.$this->name.'.focus(); return false;}');
	}

	function isValid() {
		return isset($_POST[$this->name]) && preg_match('/'.$this->validationExpression.'/', $_POST[$this->name]);
	}

	function getErrorMessage() { return $this->errorMessage; }

	function getReply() {
		return isset($_POST[$this->name]) ? $_POST[$this->name] : NULL;
	}
}

class wwSimplePasswordCheck extends wwText{
	function __construct($name, $label, $thePassword, $errorMessage = 'Wrong password.') {
		$this->thePassword = $thePassword;
		parent::__construct($name, $label, false, '', '.*', $errorMessage, true, true);
	}
	function isValid() {		
		// Check only on server-side.
		return isset($_POST[$this->name]) && $_POST[$this->name] === $this->thePassword;
	}
}

class wwEmail extends wwText{
	function __construct($name, $label, $preSetValue = '', $errorMessage = 'Please enter a valid e-mail address.', $required = false) {
		parent::__construct($name, $label, false, $preSetValue, '^([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4})'.($required ? '' : '?').'$', $errorMessage);
	}
}

class wwNumeric extends wwText{
	function __construct($name, $label, $preSetValue = '', $errorMessage = 'Please enter a numeric value.', $required = false) {
		$this->required = $required;
		parent::__construct($name, $label, false, $preSetValue, '^(\d+([\.,:]\d+)?)'.($required ? '' : '?').'$', $errorMessage, false);
	}
	function getReply() {		
		return (isset($_POST[$this->name]) && $_POST[$this->name] != '') ? (str_replace(array(',', ':'), '.', trim($_POST[$this->name]))) : NULL;
	}
}

class wwDate extends wwText{
	function __construct($name, $label, $preSetValue = '', $errorMessage = 'Please enter a date in the format YYY-MM-DD.', $required = false) {
		$this->required = $required;
		parent::__construct($name, $label, false, $preSetValue, '^(\d{4}-\d{2}-\d{2})'.($required ? '' : '?').'$', $errorMessage, false);
	}
	function isValid() {		
		// Quick check of the format.
		if (!isset($_POST[$this->name]) || !preg_match('/'.$this->validationExpression.'/', $_POST[$this->name]))
			return false;
			
		// Real validation. No Feb 31:st in my database!
		$date = $_POST[$this->name];
		$year = substr($date, 0, 4);
		$month = substr($date, 5, 2);
		$day = substr($date, 8, 2);
		return checkdate($month, $day, $year);
	}
}

class wwHTTPURL extends wwText{
	function __construct($name, $label, $preSetValue = '', $errorMessage = 'Please enter a valid web page address.', $required = false) {
		$this->required = $required;
		parent::__construct($name, $label, false, $preSetValue, '^(((http|https):\/\/)?(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?)?'.($required ? '' : '?').'$', $errorMessage, false);
	}
	function isValid() {
		// When not required, missing or empty fields are allways valid. (Not that there would be a sensible reason for it to be missing...)
		if (!isset($_POST[$this->name]) || !$_POST[$this->name]) return !$this->required; 

		// Check the regexp.
		if (!parent::IsValid()) return false;

		// Non-technical user might skip the http://-part
		$URL = $_POST[$this->name];
		if (substr($URL, 0, 4) != 'http') $URL = 'http://'.$URL;

		if (function_exists('curl_init')) {
			// Snipped from http://www.jellyandcustard.com/2006/05/31/determining-if-a-url-exists-with-curl/		
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $URL);
			curl_setopt($ch, CURLOPT_HEADER, true);
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
			$header = curl_exec($ch);
			curl_close($ch);
			if ($header) {
				preg_match_all("/HTTP\/1\.[1|0]\s(\d{3})/",$header,$Statii);
				$status = end($Statii[1]);
				if ($status=='200')
					return true;
			}
			return false;
		}

		return true;
	}
	function getReply() {
		if (!isset($_POST[$this->name])) return '';

		// Non-technical user might skip the http://-part
		$URL = $_POST[$this->name];
		if (substr($URL, 0, 7) != 'http://') $URL = 'http://'.$URL;
		return $URL;
	}
}

// $options = array(array('title'=>string, 'value'=>mixed) [, ...])
class wwSelectBox extends wwFormElementBase{
	function __construct($name, $label, $options, $preSetValue = NULL) {
		$this->name = $name;
		$this->label = $label;
		$this->options = $options;
		$this->preSetValue = $preSetValue;
	}

	function renderInput($isPostBack) {
		$selectedIndex = 0;
		if ($isPostBack && $this->isValid()) {
			// Fill in old value for postback...
			$selectedIndex = $_POST[$this->name];
		} else {
			// ...or set default value.
			foreach ($this->options as $index => $option)
				if ($option['value'] === $this->preSetValue) {
					$selectedIndex = $index;
					break;
				}
		}

		print('<select name="'.$this->name.'">'."\n");
		foreach ($this->options as $index => $option)
			print('<option'.($index == $selectedIndex ? ' selected' : '').' value="'.$index.'">'.$option['title'].'</option>'."\n");
		print('</select>');
	}

	function isValid() {
		return isset($_POST[$this->name]) && isset($this->options[$_POST[$this->name]]);
	}

	function getErrorMessage() {
		return 'SelectBox out of range.';
	}

	function getReply() {
		return isset($_POST[$this->name]) ? $this->options[$_POST[$this->name]]['value'] : NULL;
	}
}




// $options = array(array('title'=>string, 'value'=>mixed, 'preSelected'=>bool) [, ...])
class wwCheckBoxList extends wwFormElementBase{
	function __construct($name, $label, $options) {
		$this->name = $name;
		$this->label = $label;
		$this->options = $options;
	}

	function renderInput($isPostBack) {
		print('<ul>'."\n");

		$selectedIndex = 0;
		
		$unique = rand();

		foreach ($this->options as $index => $option)
			print('<li><input id="CheckBox_'.$unique.'_'.$index.'" type="checkbox" name="'.$this->name.'[]" value="'.$index.'"'.
				(($isPostBack && $this->isValid())
					// Fill in old value from postback...
					? (isset($_POST[$this->name][$index]) ? ' checked' : '')
					// ...or set default value.
					: ($option['preSelected'] ? ' checked' : '')
				)
				.' /><label for="CheckBox_'.$unique.'_'.$index.'">'.$option['title'].'</label></li>'."\n");

		print('</ul>');
	}

	function isValid() {
		// Make sure the returned alternative is a valid choise.
		if (isset($_POST[$this->name]))
			foreach ($_POST[$this->name] as $reply)
				if (!isset($this->options[$reply]))
					return false;

		return true;
	}

	function getErrorMessage() {
		return 'Unknown checked value.';
	}

	function getReply() {
		$selectedValues = array();

		// Collect all values from the reply and return them in an array.
		if (isset($_POST[$this->name]))
			foreach ($_POST[$this->name] as $selectedIndex)
				$selectedValues[] = $this->options[$selectedIndex]['value'];

		return $selectedValues;
	}

	public $name;
	public $label;
	public $options;
}



class wwFileUpload extends wwFormElementBase{
	function __construct($name, $label, $validationExpression = '.', $errorMessage = '') {
		$this->name = $name;
		$this->label = $label;
		$this->validationExpression = $validationExpression;
		$this->errorMessage = $errorMessage;
	}

	function renderInput($isPostBack) {
		print('<input type="file" name="'.$this->name.'" onchange="JavaScript:if (! /'.$this->validationExpression.'/.test(this.value)) {alert(\''.addslashes($this->errorMessage).'\'); this.value=\'\';}" />');
	}

	function renderValidationScript() {
		print('if (! /'.$this->validationExpression.'/.test(TheForm.'.$this->name.'.value)) {alert("'.addslashes($this->errorMessage).'"); return false;}');
	}

	function isValid() {
		return (($_FILES[$this->name]['name'] == '') || is_uploaded_file($_FILES[$this->name]['tmp_name'])) && preg_match('/'.$this->validationExpression.'/', $_FILES[$this->name]['name']);
	}

	function getErrorMessage() {
		$error = $_FILES[$this->name]['error'];
		if ($error)
			return 'Technical error'.(($error == 1) ? (': Max file size ('.ini_get('upload_max_filesize').') exceeded.') : '.');
		else
			return $this->errorMessage;
	}

	function getReply() {
		return (isset($_FILES[$this->name]) && is_uploaded_file($_FILES[$this->name]['tmp_name'])) ? $_FILES[$this->name] : NULL;
	}
}

class wwIconified extends wwFormElementBase{
	function __construct($name, $label, $fileTypes) {
		$this->name = $name;
		$this->label = $label;
		$this->fileTypes = $fileTypes;
	}

	function renderInput($isPostBack) {
		print('<input class="wwIconified" type="file" name="'.$this->name.'[]" />');
	}


	function isValid() {
		$allFine = true;
		foreach ($_FILES[$this->name]['name'] as $index => $foo)
			if (($_FILES[$this->name]['name'][$index] != '') && !is_uploaded_file($_FILES[$this->name]['tmp_name'][$index])) {
				$allFine = false;
				break;
			}

		return $allFine;
	}

	function getReply() {
		if (!$this->isValid()) return NULL;

		// Transpose the array and erase empty rows.
		$files = array();
		foreach ($_FILES[$this->name]['name'] as $index => $file)
			if ($_FILES[$this->name]['size'][$index] != '')
				$files[$index] = array('name'=>$_FILES[$this->name]['name'][$index], 'tmp_name'=>$_FILES[$this->name]['tmp_name'][$index]);

		return $files;
	}
}

class wwCheckBox extends wwFormElementBase{
	function __construct($name, $label, $preChecked = false, $required = false, $errorMessage = '') {
		$this->name = $name;
		$this->label = $label;
		$this->preChecked = $preChecked;
		$this->required = $required;
		$this->errorMessage = $errorMessage;
	}

	function render($isPostBack) {
		print('<li class="Input '.get_class($this).' '.$this->getName().'">');
		$this->renderInput($isPostBack);
		print('<label>');
		$this->renderLabel();
		print('</label>');
		if ($isPostBack && !$this->isValid())
		$this->renderErrorMessage();
		print('</li>'."\n");
	}

	function renderInput($isPostBack) {
		print('<input type="checkbox" name="'.$this->name.'"'.(($this->preChecked || $this->getReply()) ? ' checked="checked"' : '').' />');
	}

	function renderValidationScript() {
		if ($this->required)
			print('if (!TheForm.'.$this->name.'.checked) {alert("'.addslashes($this->errorMessage).'"); TheForm.'.$this->name.'.focus(); return false;}');
	}

	function isValid() {
		return $this->required ? $this->getReply() : true;
	}

	function getErrorMessage() { return $this->errorMessage; }

	function getReply() { return isset($_POST[$this->name]) ? true : false; }
}
