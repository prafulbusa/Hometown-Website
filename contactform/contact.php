<?php
session_start();
error_reporting(E_ERROR | E_PARSE);

header('Content-type: text/html; charset=utf-8');


#########################################################################
#	Kontaktformular.com         					                                #
#	http://www.kontaktformular.com        						                    #
#	All rights by KnotheMedia.de                                    			#
#-----------------------------------------------------------------------#
#	I-Net: http://www.knothemedia.de                            					#
#########################################################################
// It´s not allowed to remove the copyright notice!


  $script_root = substr(__FILE__, 0,
                        strrpos(__FILE__,
                                DIRECTORY_SEPARATOR)
                       ).DIRECTORY_SEPARATOR;

require_once $script_root.'upload.php';

$remote = getenv("REMOTE_ADDR");

function encrypt($string, $key) {
$result = '';
for($i=0; $i<strlen($string); $i++) {
   $char = substr($string, $i, 1);
   $keychar = substr($key, ($i % strlen($key))-1, 1);
   $char = chr(ord($char)+ord($keychar));
   $result.=$char;
}
return base64_encode($result);
}
$sicherheits_eingabe = encrypt($_POST["securitycode"], "8h384ls94");
$sicherheits_eingabe = str_replace("=", "", $sicherheits_eingabe);

@require('config.php');

if ($_POST['delete'])
{
unset($_POST);
}

// take over the data from the formular
if ($_POST["mt-mk"]) {

// variables of the data fields
   $name      = $_POST["name"];
   $email      = $_POST["email"];
   $phonenumber = $_POST["phonenumber"];
   $place   = $_POST["place"];
   $subject   = $_POST["subject"];
   $message   = $_POST["message"];
   $securitycode   = $_POST["securitycode"];
   $date = date("d.m.Y | H:i");
   $ip = $_SERVER['REMOTE_ADDR']; 
   $UserAgent = $_SERVER["HTTP_USER_AGENT"];
   $host = getHostByAddr($remote);


// examination of the data fields
$name = stripslashes($name);
$email = stripslashes($email);
$subject = stripslashes($subject);
$message = stripslashes($message);
 

if(!$name) {
 
 $fehler['name'] = "<font color=#cc3333>Please enter your <strong>name</strong>.<br /></font>";
 
}


if (!preg_match("/^[0-9a-zA-ZÄÜÖ_.-]+@[0-9a-z.-]+\.[a-z]{2,6}$/", $email)) {
   $fehler['email'] = "<font color=#cc3333>Please enter a <strong>e-mail-address</strong>.\n<br /></font>";
}

 
if(!$subject) {
 
 $fehler['subject'] = '<font color=#cc3333>Please enter a <strong>subject</strong>.<br /></font>';
 
 
}
 
if(!$message) {
 
 $fehler['message'] = '<font color=#cc3333>Please enter a <strong>message</strong>.<br /></font>';
 
 
}

if($sicherheits_eingabe != $_SESSION['captcha_spam']){
unset($_SESSION['captcha_spam']);
   $fehler['captcha'] = "<font color=#cc3333>You entered a wrong <strong>code</strong>.<br /></font>";
   }

    if (!isset($fehler) || count($fehler) == 0) {
      $error             = false;
      $errorMessage      = '';
      $uploadErrors      = array();
      $uploadedFiles     = array();
      $totalUploadSize   = 0;

      if ($cfg['UPLOAD_ACTIVE'] && in_array($_SERVER['REMOTE_ADDR'], $cfg['BLACKLIST_IP']) === true) {
          $error = true;
          $fehler['upload'] = '<font color=#990000>You have no authorization to upload files.<br /></font>';
      }

      if (!$error) {
          for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
              if ($_FILES['f']['error'][$i] == UPLOAD_ERR_NO_FILE) {
                  continue;
              }

              $extension = explode('.', $_FILES['f']['name'][$i]);
              $extension = strtolower($extension[count($extension)-1]);
              $totalUploadSize += $_FILES['f']['size'][$i];

              if ($_FILES['f']['error'][$i] != UPLOAD_ERR_OK) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  switch ($_FILES['f']['error'][$i]) {
                      case UPLOAD_ERR_INI_SIZE :
                          $uploadErrors[$j]['error'] = 'the file is too big (PHP-Ini directive).';
                      break;
                      case UPLOAD_ERR_FORM_SIZE :
                          $uploadErrors[$j]['error'] = 'the file is too big (MAX_FILE_SIZE in HTML-Formular).';
                      break;
                      case UPLOAD_ERR_PARTIAL :
						  if ($cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'the file has been uploaded partially.';
						  } else {
							  $uploadErrors[$j]['error'] = 'the file has been sent partially.';
					  	  }
                      break;
                      case UPLOAD_ERR_NO_TMP_DIR :
                          $uploadErrors[$j]['error'] = 'No temporarily folder has been found.';
                      break;
                      case UPLOAD_ERR_CANT_WRITE :
                          $uploadErrors[$j]['error'] = 'error during saving the file.';
                      break;
                      case UPLOAD_ERR_EXTENSION  :
                          $uploadErrors[$j]['error'] = 'unknown error due to an extension.';
                      break;
                      default :
						  if ($cfg['UPLOAD_ACTIVE']) {
                          	  $uploadErrors[$j]['error'] = 'unknown error on uploading.';
						  } else {
							  $uploadErrors[$j]['error'] = 'unknown error on sending the email attachments.';
						  }
                  }

                  $j++;
                  $error = true;
              }
              else if ($totalUploadSize > $cfg['MAX_ATTACHMENT_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'maximum upload reached ('.$cfg['MAX_ATTACHMENT_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              else if ($_FILES['f']['size'][$i] > $cfg['MAX_FILE_SIZE']*1024) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'The file is too big (max. '.$cfg['MAX_FILE_SIZE'].' KB).';
                  $j++;
                  $error = true;
              }
              else if (!empty($cfg['BLACKLIST_EXT']) && strpos($cfg['BLACKLIST_EXT'], $extension) !== false) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'the file extension is not permitted.';
                  $j++;
                  $error = true;
              }
              else if (preg_match("=^[\\:*?<>|/]+$=", $_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'invalid symbols in the file name (\/:*?<>|).';
                  $j++;
                  $error = true;
              }
              else if ($cfg['UPLOAD_ACTIVE'] && file_exists($cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i])) {
                  $uploadErrors[$j]['name'] = $_FILES['f']['name'][$i];
                  $uploadErrors[$j]['error'] = 'the file already exist.';
                  $j++;
                  $error = true;
              }
              else {
				  if ($cfg['UPLOAD_ACTIVE']) {
                     move_uploaded_file($_FILES['f']['tmp_name'][$i], $cfg['UPLOAD_FOLDER'].'/'.$_FILES['f']['name'][$i]);	
				  }
                  $uploadedFiles[] = $_FILES['f']['name'][$i];
              }
          }
      }

      if ($error) {
          $errorMessage = 'following errors occured when sending the contact formular:'."\n";
          if (count($uploadErrors) > 0) {
              foreach ($uploadErrors as $err) {
                  $tmp .= '<strong>'.$err['name']."</strong><br/>\n- ".$err['error']."<br/><br/>\n";
              }
              $tmp = "<br/><br/>\n".$tmp;
          }
          $errorMessage .= $tmp.'';
          $fehler['upload'] = $errorMessage;
      }
  }


// if no error, an email will be sent
   if (!isset($fehler))
   {

// header of the email
   $recipient = "".$empfaenger."";   
   $subject = "".$_POST["subject"]."";
   //$mailheaders = "From: \"".stripslashes($_POST["vorname"])." ".stripslashes($_POST["name"])."\" <".$_POST["email"].">\n";
	//$mailheaders .= "Reply-To: <".$_POST["email"].">\n";
	//$mailheaders .= "X-Mailer: PHP/" . phpversion() . "\n";
	$mailheader_betreff = "=?UTF-8?B?".base64_encode($subject)."?=";
	$mailheaders   = array();
	$mailheaders[] = "MIME-Version: 1.0";
	$mailheaders[] = "Content-type: text/plain; charset=utf-8";
	$mailheaders[] = "From: =?UTF-8?B?".base64_encode(stripslashes($_POST["name"]))."?= <".$_POST["email"].">";
	$mailheaders[] = "Reply-To: <".$_POST["email"].">";
	$mailheaders[] = "Subject: ".$mailheader_betreff;
	$mailheaders[] = "X-Mailer: PHP/".phpversion();		


// display of the email
   $msg  = "The following has been sent by the demo contact form style 1:\n" . "-------------------------------------------------------------------------\n\n";
   $msg .= "Name: " . $name . "\n";
   $msg .= "E-Mail: " . $email . "\n\n";
   $msg .= "Phone Number: " . $phonenumber . "\n";
   $msg .= "Place: " . $place . "\n";
   $msg .= "\nSubject: " . $subject . "\n";
   $msg .= "Message:\n" . $_POST['message'] = preg_replace("/\r\r|\r\n|\n\r|\n\n/","\n",$_POST['message']) . "\n\n";
   "-------------------------------------------------------------------------\n\n";
 if (count($uploadedFiles) > 0) {
	   if ($cfg['UPLOAD_ACTIVE']) {
       	   $msg .= 'The following files have been uploaded:'."\n";
	       foreach ($uploadedFiles as $file) {
	           $msg .= ' - '.$cfg['DOWNLOAD_URL'].'/'.$cfg['UPLOAD_FOLDER'].'/'.$file."\n";
	       }
	   } else {
		   $msg .= 'The following files have been attached:'."\n";
		   foreach ($uploadedFiles as $file) {
	           $msg .= ' - '.$file."\n";
	       }
	   }
   }
   $msg .= "\n\nIP address: " . $ip . "\n";
  
  
  //$mailheaders = "From: \"".stripslashes($_POST["vorname"])." ".stripslashes($_POST["name"])."\" <".$_POST["email"].">\n";
	//$mailheaders .= "Reply-To: <".$_POST["email"].">\n";
	//$mailheaders .= "X-Mailer: PHP/" . phpversion() . "\n";
	$mailheader_betreff = "=?UTF-8?B?".base64_encode($subject)."?=";
	$mailheaders   = array();
	
	
	// Arrange the email attachments
	// is only necessary if no upload is desired
	if (!$cfg['UPLOAD_ACTIVE'] && count($uploadedFiles) > 0) {
		$attachments = array();
		for ($i = 0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
		   	if ($_FILES['f']['name'][$i] == UPLOAD_ERR_NO_FILE) {
				continue;
			}
			$attachments[] = $_FILES['f']['tmp_name'][$i];
		}
		$boundary = md5(uniqid(rand(), true));
		
		
		
		// Header
		$mailheaders[] = "MIME-Version: 1.0";
		$mailheaders[] = "Content-type: multipart/mixed; boundary=\"".$boundary."\"";
		$mailheaders[] = "From: =?UTF-8?B?".base64_encode(stripslashes($_POST["name"]))."?= <".$_POST["email"].">";
		$mailheaders[] = "Reply-To: <".$_POST["email"].">";
		$mailheaders[] = "Subject: ".$mailheader_betreff;
		$mailheaders[] = "X-Mailer: PHP/".phpversion();	
		
		// Message
		$mailheaders[] = "--".$boundary;
		$mailheaders[] = "Content-type: text/plain; charset=utf-8";
		$mailheaders[] = "Content-Transfer-Encoding: 8bit";
		$mailheaders[] = "The following has been sent by the contact form:\n";
		$mailheaders[] = $msg;
		$mailheaders[] = "";
		
		// Attachment
		for ($i = 0; $i < count($uploadedFiles); $i++) {
			$file = fopen($attachments[$i],"r");
			$content = fread($file,filesize($attachments[$i]));
			fclose($file);
			$encodedfile = chunk_split(base64_encode($content));
			$mailheaders[] = "--".$boundary;
			$mailheaders[] = "Content-Disposition: attachment; filename=\"".$uploadedFiles[$i]."\"";		
			$mailheaders[] = "Content-Type: application/octet-stream; name=\"".$uploadedFiles[$i]."\"";
			$mailheaders[] = "Content-Transfer-Encoding: base64";
			$mailheaders[] = "";
			$mailheaders[] = $encodedfile;
		}
		$mailheaders[] = "--".$boundary."--";
	}
	else{
		$mailheaders[] = "MIME-Version: 1.0";
		$mailheaders[] = "Content-type: text/plain; charset=utf-8";
		$mailheaders[] = "From: =?UTF-8?B?".base64_encode(stripslashes($_POST["name"]))."?= <".$_POST["email"].">";
		$mailheaders[] = "Reply-To: <".$_POST["email"].">";
		$mailheaders[] = "Subject: ".$mailheader_betreff;
		$mailheaders[] = "X-Mailer: PHP/".phpversion();		
	}


   // Thank you E-Mail 
   $dsubject = "Your request"; // Subject of the message
   
   //$dmailheaders = "From: ".$ihrname." <".$recipient.">\n";
	//$dmailheaders .= "Reply-To: <".$recipient.">\n";
	$dmailheader_dsubject = "=?UTF-8?B?".base64_encode($dsubject)."?=";
  $dmailheaders   = array();
	$dmailheaders[] = "MIME-Version: 1.0";
	$dmailheaders[] = "Content-type: text/plain; charset=utf-8";
	$dmailheaders[] = "From: =?UTF-8?B?".base64_encode($ihrname)."?= <".$recipient.">";
	$dmailheaders[] = "Reply-To: <".$recipient.">";
	$dmailheaders[] = "Subject: ".$dmailheader_dsubject;
	$dmailheaders[] = "X-Mailer: PHP/".phpversion();	
  $dmsg  = "Thank you very much for your e-mail. We will reply as fast as we can.\n\n";
  $dmsg .= "Summary: \n" .
  "-------------------------------------------------------------------------\n\n";
  $dmsg .= "Name: " . $name . "\n";
  $dmsg .= "E-Mail: " . $email . "\n\n";
  $dmsg .= "Phone Number: " . $phonenumber . "\n";
  $dmsg .= "Place: " . $place . "\n";
  $dmsg .= "\nSubject: " . $subject . "\n";
  $dmsg .= "Message:\n" . str_replace("\r", "", $message) . "\n\n";
   
   if (count($uploadedFiles) > 0) {
       $dmsg .= 'You have assigned the following files:'."\n";
       foreach ($uploadedFiles as $file) {
           $dmsg .= ' - '.$file."\n";
       }
   }
   $dmsg = strip_tags ($dmsg);


//if (mail($recipient,$betreff,$msg,$mailheaders)) {
//mail($email, $dsubject, $dmsg, $dmailheaders);
if (mail($recipient, $mailheader_betreff, $msg, implode("\n", $mailheaders))) {
mail($email, $dmailheader_dsubject, $dmsg, implode("\n", $dmailheaders));

// thank you page, if email has been sent
echo "<META HTTP-EQUIV=\"refresh\" content=\"0;URL=".$danke."\">";
exit;
 
}
}
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de-DE" lang="de-DE">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<meta name="language" 			content="de"/>
<meta name="description"      content="kontaktformular.com"/>
<meta name="revisit"          content="After 7 days"/>
<meta name="robots"           content="INDEX,FOLLOW"/>
<meta http-equiv="Content-Style-Type" content="text/css" />   
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<title>kontaktformular.com</title>
<link href="style-contact-form.css" rel="stylesheet" type="text/css" />

</head>

<body id="Kontaktformularseite">

<div class="kontaktformular">
<form action="<?php $_SERVER['PHP_SELF'];?>" method="post" enctype="multipart/form-data">
<p><input style="width:0px; height:0px; visibility:hidden;" type="hidden" name="action" value="smail" /></p>
<p><input style="width:0px; height:0px; visibility:hidden;" type="hidden" name="content" value="formular"/></p>

<fieldset class="kontaktdaten">
    <legend>contact data</legend>
	<table>
		<tr>
			<td class="label"><label>Name: <span class="pflichtfeld">*</span></label></td>
			<td class="field"> <?php if ($fehler["name"] != "") { echo $fehler["name"]; } ?><input type="text" name="name" maxlength="25" id="textfield" value="<?php echo $_POST[name]; ?>" size="20" <?php if ($fehler["name"] != "") { echo 'class="errordesignfields"'; } ?>/></td>
		
		</tr>
		<tr>
			<td class="label"><label>E-Mail: <span class="pflichtfeld">*</span></label></td>
			<td class="field"><?php if ($fehler["email"] != "") { echo $fehler["email"]; } ?><input type="text" name="email" maxlength="200" value="<?php echo $_POST[email]; ?>" size="20" <?php if ($fehler["email"] != "") { echo 'class="errordesignfields"'; } ?>/></td>
		</tr>
		<tr>
			<td class="label"><label>Phone Number: </label></td>
			<td class="field"><input type="text" name="phonenumber" maxlength="150" value="<?php echo $_POST[phonenumber]; ?>" size="20"/></td>
			<td class="error"></td>
		</tr>
		<tr>
			<td class="label"><label>Place: </label></td>
			<td class="field"><input type="text" name="place" maxlength="150" value="<?php echo $_POST[place]; ?>" size="20"/></td>
			<td class="error"></td>
		</tr>
		
				
		
		
	</table>
 </fieldset>

 <fieldset class="anfrage">
    <legend>request</legend>
	<table>
		<tr>
			<td class="label"><label>Subject: <span class="pflichtfeld">*</span></label></td>
			<td class="field"><?php if ($fehler["subject"] != "") { echo $fehler["subject"]; } ?><input type="text" name="subject" maxlength="150" value="<?php echo $_POST[subject]; ?>" size="20" <?php if ($fehler["subject"] != "") { echo 'class="errordesignfields"'; } ?>/></td>
		</tr>
		<tr>	
    		<td class="label"><label>Message: <span class="pflichtfeld">*</span></label></td>
    		<td class="field"><?php if ($fehler["message"] != "") { echo $fehler["message"]; } ?><textarea name="message" cols="30" rows="8" <?php if ($fehler["message"] != "") { echo 'class="errordesignfields"'; } ?>><?php echo $_POST[message]; ?></textarea></td>
			</tr>
	</table>
 </fieldset>
 
 <?php
      for ($i=0; $i < $cfg['NUM_ATTACHMENT_FIELDS']; $i++) {
          echo '<fieldset class="upload">';
		  echo '<legend>Attachment</legend>';
		  echo '<label>File</label><input type="file" size="12" name="f[]" /><br />';
		  echo '</fieldset>';
      }
?>

 <fieldset class="captcha">
	<legend>spam-protection</legend>
	<table>
		<tr>
			<td class="label"><label>Security Code </label></td>
			<td><img src="captcha/captcha.php" alt="Security-Code" title="kontaktformular.com-sicherheitscode" id="captcha" /></td>
			<td><a href="javascript:void(0);" onclick="javascript:document.getElementById('captcha').src='captcha/captcha.php?'+Math.random();cursor:pointer;"><span class="captchareload"><img src="icon-kf.gif" alt="reload security code" title="reload picture" /></span></a></td>
			<td class="error"></td>
		</tr>
		<tr>
			<td class="label"><label>Please enter: <span class="pflichtfeld">*</span></label></td>
			<td class="field" colspan="2"><?php if ($fehler["captcha"] != "") { echo $fehler["captcha"]; } ?><input type="text" name="securitycode" maxlength="150" value="" size="20" <?php if ($fehler["captcha"] != "") { echo 'class="errordesignfields"'; } ?>/></td>
		</tr>
	</table>
 </fieldset>


 <fieldset class="buttons">
   <legend>your action</legend>
<br />
   <div style="text-align:center">Advice: Fields with <span class="pflichtfeld">*</span> have to be filled.<br /><br />
   

   <input type="submit" name="mt-mk" value="Send" onclick="tescht();"/>
   <input type="submit" name="delete" value="Delete" />
</div>

</fieldset>
</form> <div style="font-size:11px; text-align:center"><!-- It´s not allowed to remove the copyright notice! --><strong><a href="http://www.kontaktformular.com/en" title="Contact Form">Contact Form</a></strong>: <a href="http://www.kontaktformular.com/en">&copy; kontaktformular.com</a> | <a href="http://www.knothemedia.de/backlinks-kaufen.html">Backlinks</a></div></div>

</body>
</html>