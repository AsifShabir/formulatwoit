<?php
//**********************************************************
// AUTHOR..........: ALBERT ROIG.
// CREATION DATA...: 06/03/2017.
// NOTES...........: Example of obtaining the label.
//**********************************************************
?>

<form action="" method="post">
<br><b>INTRODUZCA ESTA INFORMACIÓN:</b><br><br>
UidCliente....: <input type="text" name="UidCliente" size="60" value=""> ............... (Dato proporcionado por GLS)<br><br>
Referencia....: <input type="text" name="Referencia" size="40" value=""> ............... (La referencia del cliente)<br><br>
Tipo Etiqueta: <select name="Tipo">
	<option value="PDF">PDF</option>
	<option value="ZPL">ZPL</option>
	<option value="JPG">JPG</option>
	<option value="PNG">PNG</option>
	<option value="EPL">EPL</option>
	<option value="DPL">DPL</option>
	<option value="XML">XML</option>
	<option value="EPL">EPL_SEPARADO</option>
	<option value="EPL">PDF_SEPARADO</option>
</select>
<br><br>
<input type="submit" value="Submit">
</form>

<?php

$url = "https://wsclientes.asmred.com/b2b.asmx";

// FUNCIONA SOLO CON ENVIOS NO ENTREGADOS.
// XML NO RETORNA NADA.

$UidCliente = $_POST['UidCliente'];
$Referencia = $_POST['Referencia'];
$Tipo       = $_POST['Tipo'];


//2024 Formamos nuestro request
$XML='<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:asm="http://www.asmred.com/">
<soap:Header/>
<soap:Body>
   <asm:EtiquetaEnvioV2>
      <!--Optional:-->
      <uidcliente>'.$UidCliente.'</uidcliente>
      <asm:codigo>'.$Referencia.'</asm:codigo>
      <asm:tipoEtiqueta>'.strtoupper($Tipo).'</asm:tipoEtiqueta>
   </asm:EtiquetaEnvioV2>
</soap:Body>
</soap:Envelope>';



$ch = curl_init();

curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
curl_setopt($ch, CURLOPT_HEADER, FALSE);
curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POSTFIELDS, $XML);
// Parametros adicionales del setopt que pueden ser de ayuda
// curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/soap+xml; charset=UTF-8; SOAPAction: http://www.asmred.com/EtiquetaEnvioV2"));
curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml; charset=UTF-8"));

echo "<br>WS PETICION DE ETIQUETA<br>".$XML."<br>";

$postResult = curl_exec($ch);

if (curl_errno($ch)) {
	echo 'No se pudo llamar al WS de GLS<br>';
}

//echo 'postResult value: '.$postResult;
//$result = strpos($postResult, '<base64Binary>');
libxml_use_internal_errors(true);
$xml = simplexml_load_string($postResult);


//Validamos si lo recibido es un XML
if($xml === false){
	echo '<font size="5">No se ha retornado ninguna etiqueta.</font>';
}
else {

	$xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
    $xml->registerXPathNamespace('asm', 'http://www.asmred.com/');

    $result = $xml->xpath('//soap:Body/asm:EtiquetaEnvioV2Response/asm:EtiquetaEnvioV2Result/Etiquetas/Etiqueta');

	if ($result === false) {
		echo "Error en la consulta XPath<br>";
	} elseif (empty($result)) {
		echo "No se encontraron etiquetas<br>";
	} else {
		for ($i = 0; $i < count($result); $i++) {
			echo "Etiqueta encontrada: <br>" .(string)$result[$i] . "<br>";
		}
	}

}


?>