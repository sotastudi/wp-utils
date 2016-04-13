<?php
// CARREGAR WP-CONFIG.PHP PER AGAFAR LES DADES DE LA BASE DE DADES
// WP-UTILS 2.7 (2015-11-09)
define('DEBUG_WPUTILS', false);
// IMPORTA LES DADES DE CONFIGURACIO DE WP
if(is_file("../wp-config.php")){
	require_once("../wp-config.php");
}else{
	echo "No s'ha trobat l'arxiu de configuració de wordpress.";
	exit;
}
// FORMULARI PER ENTRAR EL DOMINI ANTERIOR PEL NOU
function formulari_trasllat_directori(){
	$directori_wp=get_dades_sql();
	$html='<h1>TRASLLAT DE DIRECTORI</h1>';
	$html.='<form name="form_trasllat_directori_wp" method="post" action="">';
	$html.='<p><strong>Dir Actual:</strong><br /><a href="'.$directori_wp.'">'.$directori_wp.'</a><input type="text" id="directori_actual" name="directori_actual" value="'.$directori_wp.'" size="50" /></p>';
	$html.='<p><strong>Dir Nou: </strong><br /> http://<input type="text" id="directori_nou" name="directori_nou" value="" size="50" /></p>';
	$html.='<p><button id="trasllat_directori_wp" name="trasllat_directori_wp" type="submit">Canviar directori</button></p>';
	$html.='</form>';
	return $html;
}
// RESET WP
function formulari_reset_wp(){
	$domini_wp=get_dades_sql();
	$html='<h1>RESET</h1>';
	$html.='<p>Elimina TOTS els comentaris, entrades, p&agrave;gines i enlla&ccedil;os que venen de mostra per defecte a la instal&middot;laci&oacute; inicial de WordPress i tamb&eacute; modifica alguns camps de la configuraci&oacute;.</p>';
	$html.='<form name="form_reset_wp" method="post" action="">';
	$html.='<p><button id="reset_wp" name="reset_wp" type="submit">Buidar WP</button></p>';
	$html.='</form>';
	return $html;
}
// CONFIGURACIO PER DEFECTE
function formulari_configurar_wp(){
	$domini_wp=get_dades_sql();
	$html='<h1>CONFIGURAR</h1>';
	$html.='<p>Estableix la configuraci&oacute; per defecte.</p>';
	$html.='<form name="form_configurar_wp" method="post" action="">';
	$html.='<p><button id="configurar_wp" name="configurar_wp" type="submit">Configurar WP</button></p>';
	$html.='</form>';
	return $html;
}
// TRASLLAT DE DIRECTORI I DOMINI
function substituir_content(&$item1, $args){
	$cerca=$args['cerca'];
	$canvia=$args['canvia'];
	if(is_string($item1)){
		// ES STRING
		$strpos=strpos($item1, 'a:');
		if($strpos===0){
			//$item1=str_replace($cerca, $canvia, $item1);
			substituir_serialize($cerca, $canvia, $item1);
		}else{
			$item1=str_replace($cerca, $canvia, $item1);
		}
	}else{
		echo '<pre>WARNING ITEM 1:<br />'; 	var_dump($item1); echo '</pre>';
	}
}
function revisar_string($string){
	$search=array("'");
	$replace=array("\'");
	$new_string=str_replace($search, $replace, $string);
	return $new_string;
}
function substituir_array_content(&$item1, $key, $args){
	substituir_content($item1, $args);
}
function seccionar_valores_string($string, $caracter_cerca='s:'){
	$valores=array();
	// echo '<H4>Extraer ultima s</H4>';
	$len_caracter_cerca=strlen($caracter_cerca);
	// BEFORE
	$pos_before=strrpos($string, $caracter_cerca);
	//echo 'Pos : '.$pos;
	$pos_before=$pos_before+$len_caracter_cerca;
	$before=substr($string, 0, $pos_before);
	// MIDDLE + AFTER
	$part_middle=substr($string, $pos_before);
	// echo("<br /><strong>Part Middle</strong>: $part_middle");
	// AFTER
	$pos_after=strrpos($part_middle, ':"');
	$after=substr($part_middle, $pos_after);
	// MIDDLE
	$middle=substr($part_middle, 0, $pos_after);
	$valores['before']=$before;
	$valores['after']=$after;
	$valores['middle']=$middle;
	 //echo '<pre>';print_r($valores);echo '</pre>';
	return $valores;
}
function substituir_serialize($cerca, $canvia, $item1){
	$entrada=$item1;
	// DIFERENCIA
	$cerca_len=strlen($cerca);
	$canvia_len=strlen($canvia);
	$diferencia=$canvia_len-$cerca_len;
	// Cerca el num de coincidencias
	$n_coincidencias=substr_count($entrada, $cerca);
	// ARRAY STRING
	$arr_string=explode($cerca, $entrada);
	$i=0;
	foreach ($arr_string as &$part_string){
		if($i<$n_coincidencias){
			$valores=seccionar_valores_string($part_string);
			$valores['middle']+=$diferencia;
			$part_string=$valores['before'].$valores['middle'].$valores['after'];
		}
		$i++;
	}
	$string=implode($canvia, $arr_string);
	return $string;
}
function update_valor($tabla, $var, $valor, $where=''){
	$sql="UPDATE `$tabla` SET `$var`='".$valor."' $where;";
	return $sql;
}
function detectar_serialize($string, $cerca, $canvia){
	$before_cerca='a:';
	$pos_before_cerca=strpos($string, $before_cerca);
	if($pos_before_cerca===0){
//		echo '<P>STRING SERIALIZE</P>';
		$valor=substituir_serialize($cerca, $canvia, $string);
	}else{
//		echo '<P>STRING NORMAL</P>';
		$valor=str_replace($cerca, $canvia, $string);
	}
	return $valor;
}
// WP OPTIONS
function replace_wpoptions($cerca, $canvia, $table='', $field_id='option_id', $field_value='option_value'){
	global $table_prefix;
	$sql_replace='';
	if($table=='') $table=$table_prefix.'options';
	echo '<h2>OPTIONS</h2>';
	echo '<br><strong>Table</strong>:'.$table;
	echo '<br />Cerca:'.$cerca;	echo '<br />Canvia:'.$canvia;
	//
	// Treure la barra del final
//	$cerca=substr($cerca, 0, -1);
//	$canvia=substr($canvia, 0, -1);
	$sql='SELECT `'.$field_id.'`, `'.$field_value.'` FROM `'.$table.'` WHERE `'.$field_value.'` LIKE \'%'.$cerca.'%\'';
	$connexio_db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if(DEBUG_WPUTILS){
		echo  '<p>REPLACE SQL:'.$sql.'</p>';
		if(mysqli_multi_query($connexio_db, $sql)){
			$resultat=mysqli_store_result($connexio_db);
			echo '<p><strong>ACTUALITZANT</strong>: '.$table.'<p>';
			// echo '<pre>'.$sql.'</pre>';
			while($row=$resultat->fetch_object()){
	//			echo '<H2>ROW:</H2>'; echo '<pre>';	print_r($row);	echo '</pre>';
				$where= ' WHERE `'.$field_id.'`='.$row->$field_id;
				$valor=detectar_serialize($row->$field_value, $cerca, $canvia);
				$nou_valor=revisar_string($valor);
				//echo '<br> Valor:'.$valor.'<br> Nou Valor:'.$nou_valor;
				$valor=$nou_valor;
				$sql_replace.=update_valor($table, $field_value, $valor, $where).'
				';
			}
		}
		echo '<br><strong>SQL REPLACE:</strong><br/>
		'.$sql_replace;
		$sql_replace='';
	}else{
		if(mysqli_multi_query($connexio_db, $sql)){
			$resultat=mysqli_store_result($connexio_db);
			echo '<p><strong>ACTUALITZANT</strong>: '.$table.'<p>';
			// echo '<pre>'.$sql.'</pre>';
			while($row=$resultat->fetch_object()){
	//			echo '<H2>ROW:</H2>'; echo '<pre>';	print_r($row);	echo '</pre>';
				$where= ' WHERE `'.$field_id.'`='.$row->$field_id;
				$valor=detectar_serialize($row->$field_value, $cerca, $canvia);
				$sql_replace.=update_valor($table, $field_value, $valor, $where);
			}
		}
	}
	return $sql_replace;
}
// WP POST
function replace_wppost($cerca, $canvia){
	global $table_prefix;
	echo '<P><strong>ACTUALITZANT:</strong> '.$table_prefix.'post</P>';
	$sql='';
	if(DEBUG_WPUTILS)		echo '<p>CERCA:'.$cerca .' => '.$canvia.'</p>';
	// Canvi de domini
	$sql.="UPDATE `".$table_prefix."posts` SET `guid`=REPLACE(`guid`,'".$cerca."','".$canvia."');";
	// Canvi de la URL del dominio del contenido
	$sql.="UPDATE `".$table_prefix."posts` SET `post_content`=REPLACE(`post_content`,'".$cerca."','".$canvia."');";
	if(DEBUG_WPUTILS)	echo '<BR>WPPOST SQL:'.$sql;
	return $sql;
}
function generar_sql_trasllat_directori($directori_actual, $directori_nou){
	global $table_prefix;
	if($directori_nou!=''){
		$cerca=$directori_actual;
		$canvia=$directori_nou;
		$canvia='http://'.$canvia;
		// WP_POST
		$sql=replace_wppost($cerca, $canvia);
		// WP_POSTMETA
		$sql.=replace_wpoptions($cerca, $canvia, $table_prefix.'postmeta', 'meta_id', 'meta_value');
		// WP_OPTIONS
		$sql.=replace_wpoptions($cerca, $canvia, $table_prefix.'options', 'option_id', 'option_value');
		// WP_ICL_TRANSLATION_STATUS
		$sql.=replace_wpoptions($cerca, $canvia, $table_prefix.'icl_translation_status', 'rid', 'translation_package');
	}
	if(DEBUG_WPUTILS)	echo '<BR>SQL:'.$sql;
	return $sql;
}
// PER A FER UN RESET DE COMENTARIS, LINKS DE LA INSTAL·LACIÓ PER DEFECTE I DEIXAR-HO NET I CONFIGURAT
function generar_sql_reset_wp(){
	global $table_prefix;
	// eliminar tots els comentaris
	$sql="TRUNCATE TABLE `".$table_prefix."comments`;";
	// eliminar tots els enllaços
	$sql.="TRUNCATE TABLE `".$table_prefix."links`;";
	// eliminar totes les entrades "post"
	$sql.="TRUNCATE TABLE `".$table_prefix."postmeta`;";
	$sql.="TRUNCATE TABLE `".$table_prefix."posts`;";
	return $sql;
}
function generar_sql_configurar_wp(){
	global $table_prefix;
	// Zona horaria = 'Madrid'
	$sql="UPDATE `".$table_prefix."options` SET `option_value` = 'Europe/Madrid' WHERE `option_name` LIKE 'timezone_string';";
	// Formato de fecha = dd/mm/aaaa
	$sql.="UPDATE `".$table_prefix."options` SET `option_value` = 'd/m/Y' WHERE `option_name` LIKE 'date_format';";
	// Formato horario = hh:mm
	$sql.="UPDATE `".$table_prefix."options` SET `option_value` = 'H:i' WHERE `option_name` LIKE 'time_format';";
	// Estructura enlace permanente (url amigable)
	$sql.="UPDATE `".$table_prefix."options` SET `option_value`= '/%postname%/' WHERE `option_name` LIKE 'permalink_structure';";
	// Desactivar comentarios por defecto
	$sql.="UPDATE `".$table_prefix."options` SET `option_value`= 'closed' WHERE `option_name` LIKE 'default_comment_status';";
	return $sql;
}
function get_dades_sql($query=''){
	global $table_prefix;
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	/* check connection */
	if ($mysqli->connect_errno) {
	    printf("Conexión fallida: %s\n", $mysqli->connect_error);
	    exit();
	}
	/* Select queries return a resultset */
	if($query=='') $query="SELECT `option_value` FROM `".$table_prefix."options` WHERE `option_name` LIKE 'home'";
	if ($mysqli->multi_query($query)) {
		if($result = $mysqli->store_result()){
			$row = $result->fetch_row();
			$domini_wp=$row[0];
		}
	    $result->close();
	}
	$mysqli->close();
	return $domini_wp;
}
function get_tables_sql(){
	global $table_prefix;
	//	echo '<h1>MOSTRAR TABLAS SQL</H1>';
	// CONEXION
	$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	/* check connection */
	if ($mysqli->connect_errno) {
	    printf("Conexión fallida: %s\n", $mysqli->connect_error);
	    exit();
	}
	// RESULTADOS
	$sql='SHOW TABLES FROM `'.DB_NAME.'`';
//	echo($sql);
	$result= $mysqli->query($sql);
	if(!empty($result) && $result->num_rows>0){
		$ver_tablas = $result->fetch_all(MYSQLI_NUM);
		foreach ($ver_tablas as $tabla){
			$tablas[]=$tabla[0];
		}
		$result->free();
	}
	$mysqli->close();
//	print_r($tablas);
	return $tablas;
}
function dades_configuracio(){
	?><strong>DB_HOST</strong>: <?php echo(DB_HOST)?><br />
	<strong>DB_USER</strong>: <?php echo(DB_USER)?><br />
	<strong>DB_PASSWORD</strong>: <?php echo(DB_PASSWORD)?><br />
	<strong>DB_NAME</strong>: <?php echo(DB_NAME)?><?php
}
function executar_sql($sql){
	if($sql!=''){
		$connexio_db = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
		/* comprobar conexión */
		if (mysqli_connect_errno()) {
		    printf("Conexión fallida: %s\n", mysqli_connect_error());
		    exit();
		}
		// ejecutar consulta
		if(DEBUG_WPUTILS){
			echo 'SQL: '.$sql."<br /><br />";
			echo "<p>SQL ejecutado sin problemas:</p>";
		}else{
			if(mysqli_multi_query($connexio_db, $sql)){
				$resultat=mysqli_store_result($connexio_db);
			}
		}
		mysqli_close($connexio_db);
	}
	unset($_POST);
}
/* HTML */
if(isset($_POST['trasllat_domini_wp'])){
	$sql= generar_sql_trasllat_domini($_POST['domini_actual'], $_POST['domini_nou']);
	$resultat_sql=$sql;
	//$resultat_sql=executar_sql($sql);
}else{
	$resultat_sql='';
}
if(isset($_POST['trasllat_directori_wp'])){
	//print_r($_POST);
	$sql= generar_sql_trasllat_directori($_POST['directori_actual'], $_POST['directori_nou']);
	$resultat_sql=$sql;
	$resultat_sql=executar_sql($sql);
}else{
	$resultat_sql='';
}
if(isset($_POST['reset_wp'])){
	$sql= generar_sql_reset_wp();
	$resultat_sql=executar_sql($sql);
}
if(isset($_POST['configurar_wp'])){
	$sql= generar_sql_configurar_wp();
	$resultat_sql.='<p>WP reconfigurat!</p>'.$sql;
	$resultat_sql.=executar_sql($sql);
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
	<head>
		<title>&Uacute;TILS PER WORDPRESS</title>
		<link rel="stylesheet" type="text/css" href="styles.css" media="screen" />
	</head>
	<body>
		<h1>&Uacute;tils WP. 2.7 per php 5.4</h1>
		<?
			if(DEBUG_WPUTILS){
				echo '<h3>MODO TEST</h3>';
			}else{
				echo '<h3>MODO LIVE</h3>';
			}
			if($resultat_sql!=''){
				echo $resultat_sql;
			}
//			get_tables_sql();
			?><div id="dades_configuracio" class="modul_util"><?php
				dades_configuracio();
			?></div><?
			?><div id="trasllat_directori" class="modul_util"><?php
				echo formulari_trasllat_directori();
			?></div><?
			?><div id="configurar_wp" class="modul_util"><?php
				echo formulari_configurar_wp();
			?></div><?
			?><div id="reset-wp" class="modul_util"><?php
				echo formulari_reset_wp();
			?></div><?
		?>
		<h4>Eliminar el directori "WP-UTILS" una cop acabat tot el proc&eacute;s</h4>
		<p>Un cop hagueu acabat d'utilitzar els &uacute;tils, elimineu la carpeta (/wp-utils/) del servidor.</p>
	</body>
</html>
