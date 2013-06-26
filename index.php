<?php
if (isset($_GET['public'])){$public=true;$bodyclass='publicpage';}else{$bodyclass='';$public=false;include 'auto_restrict.php';}
function aff($a,$stop=true){echo 'Arret a la ligne '.__LINE__.' du fichier '.__FILE__.'<pre>';var_dump($a);echo '</pre>';if ($stop){exit();}}
function BodyClasses($add=''){$regex='#(msie)[/ ]([0-9])+|(firefox)/([0-9])+|(chrome)/([0-9])+|(opera)/([0-9]+)|(safari)/([0-9]+)|(android)|(iphone)|(ipad)|(blackberry)|(Windows Phone)|(symbian)|(mobile)|(bada])#i';@preg_match($regex,$_SERVER['HTTP_USER_AGENT'],$resultat);return ' class="'.$add.' '.@preg_replace('#([a-zA-Z ]+)[ /]([0-9]+)#','$1 $1$2',$resultat[0]).' '.basename($_SERVER['PHP_SELF'],'.php').'" ';}
function title2filename($chaine){$a=array(' ',':','|','#','/','\\','$','*','?','&','<','>');return substr(stripAccents(str_replace($a,'_',$chaine)),0,30);}
function stripAccents($string){	$a=explode(' ','à á â ã ä ç è é ê ë ì í î ï ñ ò ó ô õ ö ù ú û ü ý ÿ À Á Â Ã Ä Ç È É Ê Ë Ì Í Î Ï Ñ Ò Ó Ô Õ Ö Ù Ú Û Ü Ý');$b=explode(' ','a a a a a c e e e e i i i i n o o o o o u u u u y y A A A A A C E E E E I I I I N O O O O O U U U U Y');return str_replace($a,$b,$string);}
if(isset($_GET['publicget'])||isset($_GET['privateget'])){$bodyclass.=' iframe';}
$bodyclass=bodyclasses($bodyclass);$target='';

/* (webpage retriever by Timo Van Neerden; http://lehollandaisvolant.net/contact December 2012)
 * last updated : December, 10th, 2012
 *
 * This piece of software is under the WTF Public Licence. 
 * Everyone is permitted to copy and distribute verbatim or modified 
 * copies of this program, under the following terms of the WFTPL :
 *
 *            DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE 
 *   TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION 
 *
 *  0. You just DO WHAT THE FUCK YOU WANT TO.
 *
 */

/* all the enhancements (logo, css, session lock, bookmarklet) are from Bronco (www.warriordudimanche.net) 
* and are based on the same licence ;)
* thanks a lot to Timo for his great job on this app ;) */

// PHP 5.1.2 minimum required

error_reporting(-1);
date_default_timezone_set('UTC');
// CONFIGURABLE OPTIONS
// change those directories'names for more security
$GLOBALS['data_folder'] = 'mypersonaldata'; 
$GLOBALS['private_data_folder'] = $GLOBALS['data_folder'].'/private';
$GLOBALS['public_data_folder'] =  $GLOBALS['data_folder'].'/public';
$GLOBALS['default_data_folder'] =  $GLOBALS['public_data_folder'];
$GLOBAL['version']='1.2';
$GLOBALS['message'] = 'Votre webliothèque perso';
$GLOBALS['public_title'] = 'Voici la webliothèque publique de Bronco';
$bookmarklet='<a title="Drag this link to your shortcut bar" href=\'javascript:javascript:(function(){var url = location.href;window.open("http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?q="+ encodeURIComponent(url),"_blank","menubar=yes,height=600,width=1000,toolbar=yes,scrollbars=yes,status=yes");})();\' >Bookmarklet</a>';
$column_width='width:47%';
if ($public){$bookmarklet='';$column_width='width:97%';}
if (!creer_dossier($GLOBALS['data_folder'], TRUE)) { die('Cant create '.$GLOBALS['data_folder'].' folder.'); }
if (!creer_dossier($GLOBALS['data_folder'].'/zipversions', TRUE)) { die('Cant create '.$GLOBALS['data_folder'].'/zipversions'.' folder.'); }
if (!creer_dossier($GLOBALS['private_data_folder'], TRUE)) { die('Cant create '.$GLOBALS['private_data_folder'].' folder.'); }
if (!creer_dossier($GLOBALS['public_data_folder'], TRUE)) { die('Cant create '.$GLOBALS['public_data_folder'].' folder.'); }

//
// BEGIN SCRIPT
//

#TODO
/*
- remplacer les liens relatifs par les liens absolus (ne chercher que les liens relatifs, uri)
- gestion des pages DL (classement ?)
- gestion de la taille max des fichiers à télélcharger 


*/

// init
// url not yet retrieved
$GLOBALS['done']['d'] = FALSE;

if (!$public){
	// Get URL to save.
	if (!empty($_GET['q'])) {


		$url = htmlspecialchars($_GET['q']);
		if (strpos($url, '://') === false) {
			$url = 'http://'.$url;
		}
		$GLOBALS['url'] = $url;
		$url_p = url_parts();

		// retrieve the file main HTML file

		$GLOBALS['main_page_data'] = get_external_file($GLOBALS['url'], 6);


		if ($GLOBALS['main_page_data'] === FALSE) {
			die('error retrieving external main page');
		}

		else {
			// crée le nouveau dossier basé sur le TS.
			$new_folder = date('Y-m-d-H-i-s');
			if (!creer_dossier($GLOBALS['default_data_folder'].'/'.$new_folder) === TRUE ) {
				die('error creating data folder');
			}
			else {
				$GLOBALS['target_folder'] = $GLOBALS['default_data_folder'].'/'.$new_folder;
			}
			$liste_css = array();
			// parse le fichier principal à la recherche de données à télécharger
			$files = list_retrievable_data($GLOBALS['url'], $GLOBALS['main_page_data']);
			// les récupère et les enregistre.
			//echo '<pre>';print_r($files);die();
			foreach ($files as $i => $file) {
				if ($data = get_external_file($file['url_fichier'], 3) and ($data !== FALSE) ) {
					// CSS files need to be parsed aswell
					if ($file['type'] == 'css') {
						$liste_css[] = $file;
					}
					else {
						file_put_contents($GLOBALS['target_folder'].'/'.$file['nom_destination'], $data);
					}
				}
			}
			// remplace juste les liens <a href=""> relatifs vers des liens absolus
			absolutes_links($GLOBALS['main_page_data']);

			// enregistre le fichier HTML principal
			file_put_contents($GLOBALS['target_folder'].'/'.'index.html', $GLOBALS['main_page_data']);

			// récupère le titre de la page
			// cherche le charset spécifié dans le code HTML.
			// récupère la balise méta tout entière, dans $meta
			preg_match('#<meta .*charset=.*>#Usi', $GLOBALS['main_page_data'], $meta);

			// si la balise a été trouvée, on tente d’isoler l’encodage.
			if (!empty($meta[0])) {
				// récupère juste l’encodage utilisé, dans $enc
				preg_match('#charset="?(.*)"#si', $meta[0], $enc);
				// regarde si le charset a été trouvé, sinon le fixe à UTF-8
				$html_charset = (!empty($enc[1])) ? strtolower($enc[1]) : 'utf-8';
			} else { $html_charset = 'utf-8'; }
			// récupère le titre, dans le tableau $titles, rempli par preg_match()
			preg_match('#<title>(.*)</title>#Usi', $GLOBALS['main_page_data'], $titles);
			if (!empty($titles[1])) {
				$html_title = trim($titles[1]);
				// ré-encode le titre en UTF-8 en fonction de son encodage.
				$title = ($html_charset == 'iso-8859-1') ? utf8_encode($html_title) : $html_title;
			// si pas de titre : on utilise l’URL.
			} else {
				$title = $url;
			}


			// récupère, parse, modifie & enregistre les fichier CSS (et les fichiés liés)
			$n = 0;
			$count = count($liste_css);
			while ( $n < $count and $n <300) { // no more than 300 ext files.
				$i = $n;
				$file = $liste_css[$i];
				if ($data = get_external_file($file['url_fichier'], 3) and ($data !== FALSE) ) {
					if (preg_match('#(css|php|txt|html|xml|js)#', $file['url_fichier']) ) {
						$matches_url = array();
						preg_match_all('#url\s{0,}\(("|\')?([^\'")]{1,})(\'|")?\)#i', $data, $matches_url, PREG_SET_ORDER);
						$matches_url2 = array();
						preg_match_all("#@import\s*(?:\"([^\">]*)\"?|'([^'>]*)'?)([^;]*)(;|$)#i", $data, $matches_url2, PREG_SET_ORDER);


						$matches_url = array_merge($matches_url2, $matches_url);
				

						// pour chaque URL/URI
						foreach ($matches_url as $j => $valuej) {

							if (preg_match('#^data:#', $matches_url[$j][2])) break; // if BASE64 data, dont download.

							// get the filenam (basename)
							$nom_fichier = (preg_match('#^(ht|f)tps?://#', $matches_url[$j][2])) ? pathinfo(parse_url($matches_url[$j][2], PHP_URL_PATH), PATHINFO_BASENAME) : pathinfo($matches_url[$j][2], PATHINFO_BASENAME);

							// get the URL. For URIs, uses the GLOBALS[url] tu make the URL
							// the files in CSS are relative to the CSS !
							if (preg_match('#^https?://#', $matches_url[$j][2])) {
								$url_fichier = $matches_url[$j][2];
							}
							// abs url w/o protocole
							elseif (preg_match('#^//#', $matches_url[$j][2])) {
								$url_fichier = $url_p['s'].':'.$matches_url[$j][2];
							}
							// rel url
							elseif (preg_match('#^/#', $matches_url[$j][2])) {
								$url_fichier = $url_p['s'].'://'.$url_p['h'].$matches_url[$j][2];
							}

							else {
								$endstr = ($w = strpos($file['url_fichier'], '?')) ? $w : strlen($file['url_fichier']);
								$url_fichier = substr(substr($file['url_fichier'], 0, $endstr), 0, -strlen($file['nom_fich_origine'])).$matches_url[$j][2];
							}
							// new rand name, for local storage.
							$nouveau_nom = rand_new_name($nom_fichier);
							//echo '<pre>'.$nouveau_nom."\n";
							$add = TRUE;

							// avoids downloading the same file twice. (yes, we re-use the same $retrievable ($files), why not ?)
							foreach ($files as $key => $item) {
								if ($item['url_fichier'] == $url_fichier) {
									$nouveau_nom = $item['nom_destination'];
									$add = FALSE;
									break;
								}
							}

							// if we do download, add it to the array.
							if ($add === TRUE) {
								$files_n = array(
									'url_origine' => $matches_url[$j][2],
									'url_fichier' => $url_fichier,
									'nom_fich_origine' => $nom_fichier,
									'nom_destination' => $nouveau_nom
									);
								$files[] = $files_n;
								$liste_css[] = $files_n;
							}

							// replace url in CSS $data
							$data = str_replace($matches_url[$j][2], $nouveau_nom, $data);
							// echo $nouveau_nom."<br>\n";

							if (!preg_match('#(css|php|txt|html)#', $file['url_fichier']) ) {
								if (FALSE !== ($f = get_external_file($url_fichier, 3)) ) {
									file_put_contents($GLOBALS['target_folder'].'/'.$nouveau_nom, $f);
								}
							}
						}
					}

					// don't forget to save data
					file_put_contents($GLOBALS['target_folder'].'/'.$file['nom_destination'], $data);
				}
				$n++;
				$count = count($liste_css);
			}
			// enregistre un fichier d’informations concernant la page (date, url, titre)
			$info  = '';
			$info .= 'URL="'.$GLOBALS['url'].'"'."\n";
			$info .= 'TITLE="'.$title.'"'."\n";
			$info .= 'DATE="'.time().'"'."\n";
			file_put_contents($GLOBALS['target_folder'].'/'.'index.ini', $info);
			$GLOBALS['done']['d'] = 'ajout';			
			$GLOBALS['done']['lien'] = $GLOBALS['target_folder'].'/';	

		}

	}//die;


	// in case of delete an entry
	if (isset($_GET['suppr']) and $torem = $_GET['suppr'] and $torem != '') {
		$torem = htmlspecialchars($_GET['suppr']);
		if (is_dir($_GET['suppr'])){
			$sousliste = scandir($_GET['suppr']); // listage des dossiers de data.
			$nb_sousfichier = count($sousliste);
			for ($j = 0 ; $j < $nb_sousfichier ; $j++) {
				if (!($sousliste[$j] == '..' or $sousliste[$j] == '.')) {
					unlink($_GET['suppr'].'/'.$sousliste[$j]);
				}
			}
			// then the folder itself.
	        if (TRUE === rmdir($_GET['suppr'])) {
					$GLOBALS['done']['d'] = 'remove';			

			}
	    }
	    
	    header("location: index.php");
	}

	// to private
	if (isset($_GET['toprivate']) and $torem = $_GET['toprivate'] and $torem != '') {
		$torem = htmlspecialchars($_GET['toprivate']);
		if (is_dir($GLOBALS['public_data_folder'].'/'.$_GET['toprivate'])){
			rename ($GLOBALS['public_data_folder'].'/'.$_GET['toprivate'],$GLOBALS['private_data_folder'].'/'.$_GET['toprivate']);
			header("location: index.php");
		}
	}
	// to public
	if (isset($_GET['topublic']) and $torem = $_GET['topublic'] and $torem != '') {
		$torem = htmlspecialchars($_GET['topublic']);
		if (is_dir($GLOBALS['private_data_folder'].'/'.$_GET['topublic'])){
			rename ($GLOBALS['private_data_folder'].'/'.$_GET['topublic'],$GLOBALS['public_data_folder'].'/'.$_GET['topublic']);
			header("location: index.php");
		}
	}
	// disconnect
	if (isset($_GET['discotime'])){log_user('disco','');}

	if (isset($_GET['privateget'])&&is_dir($GLOBALS['private_data_folder'].'/'.$_GET['privateget'])){$target=$GLOBALS['private_data_folder'].'/'.$_GET['privateget'];}


	if (isset($_GET['zipprivate'])) { 
		$ini_file = $GLOBALS['private_data_folder'].'/'.$_GET['zipprivate'].'/index.ini';
		if(is_file($ini_file)){$info=parse_ini_file($ini_file);}else{$info['TITLE']='';}
		$origin_folder_path=$GLOBALS['private_data_folder'].'/'.$_GET['zipprivate'];
		$zip_foldername=title2filename($info['TITLE']).'-'.$_GET['zipprivate'];
		$zip_filename=$zip_foldername.'.zip';
		$zip_completepath=$GLOBALS['data_folder'].'/zipversions/'.$zip_filename;
		if (is_file($zip_completepath)){header("location: $zip_completepath");exit();}// il existe déjà, on envoie
		if (is_dir($origin_folder_path)){// sinon on crée le zip si le dossier existe
			include 'zip.php';			
			rename ($origin_folder_path,$zip_foldername); // on le déplace pour éviter de voir la structure de dossiers apparaître dans le zip
			zip($zip_filename,$zip_foldername,$GLOBALS['data_folder'].'/zipversions/');  
			rename ($zip_foldername,$origin_folder_path); // on le remet à sa place
			header('location: '.$GLOBALS['data_folder'].'/zipversions/'.$zip_filename);
		}
	}
}
	// public get 
	//download public zip version
	if (isset($_GET['zippublic'])) {
		$ini_file = $GLOBALS['public_data_folder'].'/'.$_GET['zippublic'].'/index.ini';
		if(is_file($ini_file)){$info=parse_ini_file($ini_file);}else{$info['TITLE']='';}
		$origin_folder_path=$GLOBALS['public_data_folder'].'/'.$_GET['zippublic'];
		$zip_foldername=title2filename($info['TITLE']).'-'.$_GET['zippublic'];
		$zip_filename=$zip_foldername.'.zip';
		$zip_completepath=$GLOBALS['data_folder'].'/zipversions/'.$zip_filename;
		if (is_file($zip_completepath)){header("location: $zip_completepath");exit();}// il existe déjà, on envoie
		if (is_dir($origin_folder_path)){// sinon on crée le zip si le dossier existe
			include 'zip.php';			
			rename ($origin_folder_path,$zip_foldername); // on le déplace pour éviter de voir la structure de dossiers apparaître dans le zip
			zip($zip_filename,$zip_foldername,$GLOBALS['data_folder'].'/zipversions/');  
			rename ($zip_foldername,$origin_folder_path); // on le remet à sa place
			header('location: '.$GLOBALS['data_folder'].'/zipversions/'.$zip_filename);
		}
	}
	if (isset($_GET['publicget'])&&is_dir($GLOBALS['public_data_folder'].'/'.$_GET['publicget'])){$target=$GLOBALS['public_data_folder'].'/'.$_GET['publicget'];}




function url_parts() {
	$url_p['s']    = parse_url($GLOBALS['url'], PHP_URL_SCHEME); $url_p['s']   = (is_null($url_p['s'])) ? '' : $url_p['s'];
	$url_p['h']    = parse_url($GLOBALS['url'], PHP_URL_HOST);   $url_p['h']   = (is_null($url_p['h'])) ? '' : $url_p['h'];
	$url_p['p']    = parse_url($GLOBALS['url'], PHP_URL_PORT);   $url_p['p']   = (is_null($url_p['p'])) ? '' : ':'.$url_p['p'];
	$url_p['pat']  = parse_url($GLOBALS['url'], PHP_URL_PATH);   $url_p['pat'] = (is_null($url_p['pat'])) ? '' : $url_p['pat'];
	$url_p['file'] = pathinfo($url_p['pat'], PATHINFO_BASENAME);
	return $url_p;
}

//
// Gets external file by URL. 
// Make a stream context (better).
//

function get_external_file($url, $timeout) {
	$context = stream_context_create(array('http'=>array('timeout' => $timeout))); // Timeout : time until we stop waiting for the response.
	$data = @file_get_contents($url, false, $context, -1, 4000000); // We download at most 4 Mb from source.
	if (isset($data) and isset($http_response_header) and isset($http_response_header[0]) and (strpos($http_response_header[0], '200 OK') !== FALSE) ) {
		return $data;
	}
	else {
		return FALSE;
	}
}

//
// CREATE FOLDER
//

function creer_dossier($dossier, $indexfile = FALSE) {
	if ( !is_dir($dossier) ) {
		if (mkdir($dossier, 0777, TRUE) === TRUE) {
			chmod($dossier, 0777);
			if ($indexfile == TRUE) touch($dossier.'/index.html'); // make a index.html file : avoid the possibility of listing folder's content
			return TRUE;
		} else {
			return FALSE;
		}
	}
	return TRUE; // if folder already exists
}


//
// PARSE TAGS AND LISTE DOWNLOADABLE CONTENT IN ARRAY
// Also modify html source code to replace absolutes URLs with local URIs.
//

function list_retrievable_data($url, &$data) {
	$url_p = url_parts();

	$retrievable = array();

	// cherche les balises 'link' qui contiennent  un rel="(icon|favicon|stylesheet)" et un href=""
	// (on ne cherche pas uniquement le "href" sinon on se retrouve avec les flux RSS aussi)
	$matches = array();
	preg_match_all('#<\s*link[^>]+rel=["\'][^"\']*(icon|favicon|stylesheet)[^"\']*["\'][^>]*>#Si', $data, $matches, PREG_SET_ORDER);
	// dans les link avec une icone, stylesheet, etc récupère l’url.
	foreach($matches as $i => $key) {
		$type =  (strpos($key[1], 'stylesheet') !== FALSE) ? 'css' : 'other';
		if ( (preg_match_all('#(href|src)=["\']([^"\']*)["\']#i', $matches[$i][0], $matches_attr, PREG_SET_ORDER) === 1) ) {
			$retrievable = add_table_and_replace($data, $retrievable, $matches[$i][0], $matches_attr[0][2], $url_p, $type);
		}
	}

	// recherche les images, scripts, audio & videos HTML5.
	// dans les balises, récupère l’url/uri contenue dans les src="".
	// le fichier sera téléchargé.
	// Le nom du fichier sera modifié pour être unique, et sera aussi modifié dans le code source.
	$matches = array();
	preg_match_all('#<\s*(source|audio|img|script|video)[^>]+src="([^"]*)"[^>]*>#Si', $data, $matches, PREG_SET_ORDER);

	foreach($matches as $i => $key) {
		if (preg_match('#^data:#', $matches[$i][2])) break;
		$retrievable = add_table_and_replace($data, $retrievable, $matches[$i][0], $matches[$i][2], $url_p, 'other');
	}

	// Dans les balises <style>, remplace les url() et src()
	$matches = array();
	preg_match_all('#<\s*style[^>]*>(.*?)<\s*/\s*style[^>]*>#is', $data, $matches, PREG_SET_ORDER);

	// pour chaque élement <style>
	foreach($matches as $i => $value) {
		$matches_url = array();
		preg_match_all('#url\s*\(("|\')?([^\'")]*)(\'|")?\)#i', $matches[$i][1], $matches_url, PREG_SET_ORDER);
		$matches_url2 = array();
		preg_match_all("#@import\s*(\"([^\">]*)\"?|'([^'>]*)'?)([^;]*)(;|$)#i", $matches[$i][1], $matches_url2, PREG_SET_ORDER);
		$matches_url = array_merge($matches_url2, $matches_url);
		//echo '<pre>';print_r($matches_url);die;

		// pour chaque URL/URI
		foreach ($matches_url as $j => $valuej) {
			if (preg_match('#^data:#', $matches_url[$j][2])) break;
			$retrievable = add_table_and_replace($data, $retrievable, $matches[$i][1], $matches_url[$j][2], $url_p, 'other');
		}
	}

	// recherche les url dans les CSS inlines.
	$matches = array();
	// pour chaque élement contenant un style=""
	preg_match_all('#<\s*[^>]+style="([^"]*url\s*\(([^)]*)\)[^"]*)"[^>]*>+#is', $data, $matches, PREG_SET_ORDER);
	foreach($matches as $i => $value) {
		$matches_url = array();

		// pour chaque URL/URI trouvé
		preg_match_all('#url\s*\(("|\')?([^\'")]*)(\'|")?\)#i', $matches[$i][1], $matches_url, PREG_SET_ORDER);

		foreach ($matches_url as $j => $valuej) {
			if (preg_match('#^data:#', $matches_url[$j][2])) break; // if BASE64 data, dont download.
			$retrievable = add_table_and_replace($data, $retrievable, $matches[$i][1], $matches_url[$j][2], $url_p, 'other');
		}
	}
	return $retrievable;
}

function absolutes_links(&$data) {
	$url_p = url_parts();
	// cherche les balises 'a' qui contiennent un href
	$matches = array();
	preg_match_all('#<\s*a[^>]+href=["\'](([^"\']*))["\'][^>]*>#Si', $data, $matches, PREG_SET_ORDER);

	// ne conserve que les liens ne commençant pas par un protocole « protocole:// » ni par une ancre « # »
	foreach($matches as $i => $link) {
		$link[1] = trim($link[1]);
		if (!preg_match('#^(([a-z]+://)|(\#))#', $link[1]) ) {
			$matches[$i][1] = complete_url($link[1]);
			$new_match = str_replace($matches[$i][2], $matches[$i][1], $matches[$i][0]);
			$data = str_replace($matches[$i][0], $new_match, $data);
		}
	}
}

function complete_url($url) {
	$home_p = url_parts();

	$url = trim($url);
	if ($url === '') {
		return '';
	}
//	echo $url."\n\n\n";

	$hash_pos = strrpos($url, '#');
	$fragment = $hash_pos !== false ? '#' . substr($url, $hash_pos) : '';
	$sep_pos  = strpos($url, '://');

	if ($sep_pos === false || $sep_pos > 5) {
		switch ($url{0}) {
			// absolute path w/o HTTP and relatives paths
			case '/':
				$url = substr($url, 0, 2) === '//' ? $home_p['s'] . ':' . $url : $home_p['s'] . '://' . $home_p['h'] . $url;
				break;
			// php query string
			case '?':
				$url = $home_p['h'] . '/' . $home_p['file'] . $url;
				break;
			// html # particule
			case '#':
			// magnet & mailto
			case 'm':
			// javascript
			case 'j':
				break;
			default:
				$url = $home_p['h'] . '/' . $url;
				break;
		}
	}
//	echo $url."\n\n\n";
	return $url;
}

function add_table_and_replace(&$data, $retrievable, &$match1, $match, $url_p, $type) {
	// get the filenam (basename)
	$nom_fichier = (preg_match('#^https?://#', $match)) ? pathinfo(parse_url($match, PHP_URL_PATH), PATHINFO_BASENAME) : pathinfo($match, PATHINFO_BASENAME);
	// get the URL. For relatives URL, uses the GLOBALS[url] tu make the complete URL
	// the files in CSS are relative to the CSS !
	if (preg_match('#^https?://#', $match)) { // url
		$url_fichier = $match;
	}
	elseif (preg_match('#^//#', $match)) { // absolute path w/o HTTP
		$url_fichier = $url_p['s'].':'.$match;
	}
	elseif (preg_match('#^/#', $match)) { // absolute local path
		$url_fichier = $url_p['s'].'://'.$url_p['h'].$match;
	}
	else { // relative local path
		$uuu = (strlen($url_p['file']) == 0 or preg_match('#/$#', $url_p['pat'])) ? $GLOBALS['url'] : substr($GLOBALS['url'], 0, -strlen($url_p['file'])) ;
		$url_fichier = $uuu . substr($match, 0, -strlen($nom_fichier)).$nom_fichier;
	}

	$url_fichier = html_entity_decode(urldecode($url_fichier));
	// new rand name, for local storage.
	$nouveau_nom = rand_new_name($nom_fichier);
	if ($type == 'css') {
		$nouveau_nom = $nouveau_nom.'.css';
	}
	$add = TRUE;

	// avoids downloading the same file twice.
	foreach ($retrievable as $key => $item) {
		if ($item['url_fichier'] == $url_fichier) {
			$nouveau_nom = $item['nom_destination'];
			$add = FALSE;
			break;
		}
	}

	// if we do want to download it, we add to the array.
	if ($add === TRUE) {
		$retrievable[] = array(
			'url_origine' => $match,
			'url_fichier' => $url_fichier,
			'nom_fich_origine' => $nom_fichier,
			'nom_destination' => $nouveau_nom,
			'type' => $type
			);
	}
	// replace the URL with the new filename in the &data.
	$new_match = str_replace($match, $nouveau_nom, $match1);
	$data = str_replace($match1, $new_match, $data);
	$match1 = $new_match;

	return $retrievable;
}
function rand_new_name($name) {
	$name = substr($name, 0, (($w = strpos($name, '?')) ? $w : strlen($name)));
	return 'f_'.str_shuffle('abcd').mt_rand(100, 999).'--'.preg_replace('#[^\w.]#', '_', substr($name, 15)).'.'.pathinfo($name, PATHINFO_EXTENSION);
}


if ($GLOBALS['done']['d'] !== FALSE) {
	switch($GLOBALS['done']['d']) {
		case 'ajout' :
			header('Location: index.php?done='.$GLOBALS['done']['d'].'&lien='.urlencode($GLOBALS['url']).'&loclink='.urlencode($GLOBALS['done']['lien']));
			break;
		case 'remove' :
			header('Location: index.php?done='.$GLOBALS['done']['d']);
			break;
	}
	echo '</div>'."\n";
}

/*
 * Displays main form (page to retrieve)
 *
 */
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" /></head>
		<title>Respawn – a PHP WebPage Saver</title>	
		<link rel="stylesheet" type="text/css" href="design/style.css"/>
		<link rel="shortcut icon" type="/image/png" href="design/favicon2.png">
		<!--[if IE]><script> document.createElement("article");document.createElement("aside");document.createElement("section");document.createElement("footer");</script> <![endif]-->
		
	</head>
<body <?php echo $bodyclass;?>>
	<header><a href="<?php echo $_SERVER['PHP_SELF'];if ($public){echo '?public'; }?>"><img src="design/logo2.png"/></a>
		<nav id="orpx_nav-bar">
		<?php 
			if (!$public){
				echo "\t".'<form method="get" action="'.$_SERVER['PHP_SELF'].'" style="text-align:center">'."\n";
				echo "\t\t".'<input id="____q" type="text" size="70" name="q" value="" placeholder="URL from the page to download" />'."\n";
				echo "\t\t".'<input type="submit" value="Retrieve"/>'."\n";
				echo "\t".'</form>'."\n";
			} else{echo '<p>'.$GLOBALS['message'].'</p>';}
		?>
		</nav>
	</header>
	<aside>
	<?php
	if (!empty($target)){
		echo '<iframe src="'.$target.'" width="100%"></iframe>';
	}else{
		if (!$public){
			if (isset($_GET['done']) and $_GET['done'] !== FALSE) {
				echo '<div id="new-link">'."\n";
				switch($_GET['done']) {
					case 'ajout' :
						echo "\t".'<a target="_blank" href="'.urldecode($_GET['loclink']).'">Retrieved page</a> - (<a href="'.htmlspecialchars(urldecode($_GET['lien'])).'">orig. page</a>)' ."\n";
						break;

					case 'remove' :
						echo "\t".'Page removed'."\n";
						break;
				}
				echo '</div>'."\n";

			}
		}
		// public pages
		echo '<div class="public" style="'.$column_width.'">'."\n";
		$liste_pages = scandir($GLOBALS['public_data_folder']);
		if ( ($nb = count($liste_pages)) != 0 ) {
			echo '<ul id="liste-pages-sauvees">'."\n";

			for ($i = 0 ; $i < $nb ; $i++) {
				// dont list '.' and '..' folders.
				if (is_dir($GLOBALS['public_data_folder'].'/'.$liste_pages[$i]) and ($liste_pages[$i] != '.') and ($liste_pages[$i] != '..')) {
					// each folder should contain such a file "index.ini".
					$ini_file = $GLOBALS['public_data_folder'].'/'.$liste_pages[$i].'/index.ini';
					$favicon = glob($GLOBALS['public_data_folder'].'/'.$liste_pages[$i].'/*favicon.*');

					$favicon = (isset($favicon[0])) ? $favicon[0] : '';
					if ( is_file($ini_file) and is_readable($ini_file) ) {
						$infos = parse_ini_file($ini_file);
					} else {
						$infos = FALSE;
					}
					if (FALSE !== $infos) {
						$titre = $infos['TITLE']; $url = $infos['URL']; $date = date('d/m/Y, H:i:s', $infos['DATE']);
					} else {
						$titre = 'titre'; $url = '#'; $date = 'date inconnue';
					}
					echo "\t".'<li>';
					if (!$public){echo '<a class="suppr" onclick="return window.confirm(\'Sure to remove?\')" href="?suppr='.$GLOBALS['public_data_folder'].'/'.$liste_pages[$i].'" title="suppr"> </a>';}
					echo '<a class="zip" href="?zippublic='.$liste_pages[$i].'"  title="Download zip version"> </a><a class="origine" href="'.$url.'" title="origin"> </a> - <a href="?public&publicget='.$liste_pages[$i].'"><img src="'.$favicon.'"/>'.$titre.'</a> <em> ['.$date.']</em>';
					if (!$public){echo '<a href="?toprivate='.$liste_pages[$i].'" class="toprivate" title="Change to private">&#9654;</a></li>'."\n";}else{echo "</li>\n";}
				}
			}
			echo '</ul>'."\n";
		}
		echo '</div>'."\n";

		if (!$public){ // don't list private pages
		
			echo '<div class="private" style="'.$column_width.'">'."\n";
			$liste_pages = scandir($GLOBALS['private_data_folder']);
			if ( ($nb = count($liste_pages)) != 0 ) {
				echo '<ul id="liste-pages-sauvees">'."\n";

				for ($i = 0 ; $i < $nb ; $i++) {
					// dont list '.' and '..' folders.
					if (is_dir($GLOBALS['private_data_folder'].'/'.$liste_pages[$i]) and ($liste_pages[$i] != '.') and ($liste_pages[$i] != '..')) {
						// each folder should contain such a file "index.ini".
						$ini_file = $GLOBALS['private_data_folder'].'/'.$liste_pages[$i].'/index.ini';
						$favicon=glob($GLOBALS['private_data_folder'].'/'.$liste_pages[$i].'/*favicon.*');
						$favicon = (isset($favicon[0])) ? $favicon[0] : '';
						if ( is_file($ini_file) and is_readable($ini_file) ) {
							$infos = parse_ini_file($ini_file);
						} else {
							$infos = FALSE;
						}
						if (FALSE !== $infos) {
							$titre = $infos['TITLE']; $url = $infos['URL']; $date = date('d/m/Y, H:i:s', $infos['DATE']);
						} else {
							$titre = 'titre'; $url = '#'; $date = 'date inconnue';
						}
						echo "\t".'<li><a class="suppr" onclick="return window.confirm(\'Sure to remove?\')" href="?suppr='.$GLOBALS['private_data_folder'].'/'.$liste_pages[$i].'" title="suppr"> </a><a class="zip" href="?zipprivate='.$liste_pages[$i].'"  title="Download zip version"> </a><a class="origine" href="'.$url.'" title="origin"> </a> - <a href="?privateget='.$liste_pages[$i].'"><img src="'.$favicon.'"/>'.$titre.'</a> <em> ['.$date.']</em><a href="?topublic='.$liste_pages[$i].'" class="topublic" title="Change to public">&#9664;</a></li>'."\n";
					}
				}
				echo '</ul>'."\n";
			}
			echo '</div>'."\n";
		}
	}
?>
	</aside>
<footer>
		<p style='float:left'>
			<?php if (!$public){echo '<a href="?discotime">Disconnect</a>';}?>
			<?php if (!$public){echo $bookmarklet;} ?></p><p><a title='from TiMo' href='http://lehollandaisvolant.net/index.php?mode=links&id=20121211195941'>Respawn</a> (bronco edition v<?php echo $GLOBAL['version'];?>) - <?php $GLOBALS['message'];?> - <a href='?public'>Public page link</a>
			<script id='flattrbtn'>(function(i){var f,s=document.getElementById(i);f=document.createElement('iframe');f.src='//api.flattr.com/button/view/?uid=Broncowdd&button=compact&url='+encodeURIComponent(document.URL);f.title='Flattr';f.height=20;f.width=110;f.style.borderWidth=0;s.parentNode.insertBefore(f,s);})('flattrbtn');</script>
		</p>
</footer>
</body>
</html>
