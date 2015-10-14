<?php
/**
 * @author bronco@warriordudimanche.com
 * @copyright open source and free to adapt (keep me aware !)
 * @version 0.1
 *   auto_form.php is a little script to auto create a form and 
 *   its content only with an array. 
 *	 It can create text inputs radiobuttons, select lists, passwords inputs. 
 * 	 All the generated form's elements can be reached by classes 
 *	 and ids with css or jquery.
 *
 *	 It's possible to configure auto_form to add some features
 * 	 like placeholders, labels etc.
*/


/* this is an example array
$config=array(
	'use_a_boolean_value'=>true,
	'allow_user_to_config'=>false,
	'adresse_du_site'=>'www.warriordudimanche.net',
	'use_a_choice'=>'choice one',// current value: other values are defined below
	'use_a_radiobutton_choice'=>'choice one',// current value: other values are defined below
	'my_password'=>'password',
	'confirm_password'=>'',
	
);*/
//then render_form($config);


/* #####################################################################
   # auto_form config                                                  #
   #####################################################################
   
*/

// here are the basic parameters
$autoform_config=array(
	'use_labels'=>true,
	'use_placeholder'=>true,
	'method'=>'post',
	'action'=>'config_page.php',
	'form_name'=>'autoform',
	'form_id'=>'autoform',
	'form_class'=>'autoform',
	'enctype'=>'',
	'submit_button_label'=>'Save changes',
	'reset_button_label'
);

foreach (glob('design/*') as $skin){
	$skin=basename($skin);if ($skin!='index.html'){$skins[]=$skin;}
}
$autoform_config['skin']=$skins;
$autoform_config['default_data_folder']=array('private','public');

/* #####################################################################
   # the render function                                               #
   #####################################################################
   You can call it from anywhere in the page and render more than one form
   just call render_form($data_array)to create a brand new form with differents
   names, classes, ids etc.
*/
function render_form($var){
	global $autoform_config;$all_keys='';
	$id=$class=$enctype=$reset='';
	if ($autoform_config['form_id']){$id=' id="'.$autoform_config['form_id'].'" ';}
	if ($autoform_config['form_class']){$class=' class="'.$autoform_config['form_class'].'" ';}
	if ($autoform_config['enctype']){$enctype=' enctype="'.$autoform_config['enctype'].'" ';}
	if (isset($autoform_config['reset_button_label'])){$reset="<input type='reset' value='".$autoform_config['reset_button_label'].'"/>';}
	
	echo '<form name="'.$autoform_config['form_name']."\" $id $class $enctype method=\"".$autoform_config['method']."\" action=\"".$autoform_config['action']."\">\n ";
		foreach($var as $key=>$value){
			$all_keys.=$key.' | ';
			$txt=str_replace('_',' ',$key);
			$label="<label class='$key' for='$key'>$txt</label>";
			$idclasname="name='$key' id='$key' class='$key'";
			//
			echo '<li>';
			if (is_bool($value)){ 
				// oh, a checkbox !
				if ($value==true){$checked=' checked ';}else{$checked='';}
				echo $label;
				echo "<input $idclasname type='checkbox' $checked value=true />";
			}
			else{
				if (!$autoform_config['use_labels']){$label='';}
				if (isset($autoform_config[$key])&&is_array($autoform_config[$key])){
					// lists of choices
					if (isset($autoform_config[$key]['type'])&&$autoform_config[$key]['type']=='radio'){
						unset($autoform_config[$key]['type']);
						
						// oh, a radiobutton list !
						echo $txt.'<br/>';
						echo "<ul>\n";
						foreach ($autoform_config[$key] as $chkey=>$choice){
							if ($choice==$value){$checked='checked';}else{$checked='';}
								echo "<li><label for='$choice$key'> $choice </label><input name='$key' type='radio' value='$choice' $checked id='$choice$key'/></li>\n";
							}
						echo "</ul>\n";
						
					}else{
						// oh, a select input !
						echo $label;
						echo "<select $idclasname text='$value'>\n";						
						foreach ($autoform_config[$key] as $choice){
							if ($choice==$value){$checked='selected';}else{$checked='';}
							echo "<option $checked value='$choice'>$choice</option>\n";
						}
						echo "</select>\n";
					}
				}else if (isset($autoform_config[$key]) && $autoform_config[$key]=='pass'){
					//oh, a password input !
					echo $label;
					echo "<input type='password' $idclasname value='$value' />\n"; 
				
				}else{
					// ok, so that's a text input...
					echo $label;
					if ($autoform_config['use_placeholder']){$placeholder=" placeholder='$txt'";}else{$placeholder='';}
					echo "<input type='text' $idclasname value='$value' $placeholder/>\n"; 				
				}
		
			}
			echo "</li>\n";
		}
	echo "<input type='hidden' name='all_keys' value='$all_keys'/>\n";// this line to prevent unchecked boxes to desapear.
	echo '<input type="submit" value="'.$autoform_config['submit_button_label']."\"/> $reset \n </form>";
}
include('auto_restrict.php');
include('config.php');
unset($GLOBAL['private_data_folder']);
unset($GLOBAL['public_data_folder']);
$GLOBAL['default_data_folder']=basename($GLOBAL['default_data_folder']);

$message='';
if ($_POST){
	$auto_form['filename']='config.php';
	$auto_form['filecontent']="<?php \n /* The configuration generated with auto_form*/\n\n";
	$auto_form['variable_name']='$GLOBAL';
	$all_keys=explode(' | ',$_POST['all_keys']);
	unset($all_keys[count($all_keys)-1]);
	$postdata=array_map('strip_tags',$_POST);
	foreach ($all_keys as $key){
		if (!isset($postdata[$key])){$postdata[$key]=false;} // avoid unchecked boxes to desapear from config file
		if ($postdata[$key]===true){$postdata[$key]='true';} else
		if ($postdata[$key]===false){$postdata[$key]='false';}
		if ($postdata[$key]=='true'||$postdata[$key]=='false'){ //bool
			$auto_form['filecontent'].=$auto_form['variable_name']."['$key']=".$postdata[$key].";\n";
		}else{// not bool
			$auto_form['filecontent'].=$auto_form['variable_name']."['$key']='".$postdata[$key]."';\n";
		}
		
	}
	$auto_form['filecontent'].="\n?>";

	file_put_contents($auto_form['filename'],$auto_form['filecontent']);
	if ($postdata['data_folder']!=$GLOBAL['data_folder']){ rename ($GLOBAL['data_folder'],$postdata['data_folder']);}
	$message=' saved.';
	unset($postdata['all_keys']);
	$GLOBAL=$postdata;
}
?>

<!DOCTYPE html>
<html>
	<head>		
		<meta charset="utf-8" /></head>
		<title>Configuration</title>	
		<link rel="stylesheet" type="text/css" href="design/<?php echo $GLOBAL['skin']; ?>/style.css"/>
		<link rel="shortcut icon" type="/image/png" href="design/<?php echo $GLOBAL['skin']; ?>/favicon2.png">
		<!--[if IE]><script> document.createElement("article");document.createElement("aside");document.createElement("section");document.createElement("footer");</script> <![endif]-->
	</head>
<body class="config">
	<header><a href="index.php"><img src="design/<?php echo $GLOBAL['skin']; ?>/logo2.png"/></a>
		<nav>
			<h1>Configuration <?php echo $message;?></h1>
		</nav>
	</header>
	<aside>
<?php 
	render_form($GLOBAL);
?>
	</aside>
</body>
</html>