<?php

namespace App\Utils;

use App\Contact;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class GlsUtil extends Util
{
    // protected $client;

    // public function __construct()
    // {
    //     $this->client = new Client([
    //         'base_uri' => 'https://api.gls.com/', // Replace with the actual GLS API base URL
    //         'headers' => [
    //             'Authorization' => 'Bearer ' . env('GLS_API_TOKEN'), // Use your actual API token
    //             'Content-Type' => 'application/json',
    //         ],
    //     ]);
    // }

    // public function createShippingLabel($order)
    // {
    //     if ($order->contact_id != null) {
    //         $recipent = $order->contact;
    //     }
    //     if ($order->opening_stock_product_id != null) {
    //         $product = $order->product;
    //     }
    //     $response = $this->client->post('/shipping/labels', [
    //         'json' => [
    //             'sender' => [
    //                 'name' => 'Your Company Name',
    //                 'address' => 'Your Company Address',
    //                 'city' => 'Your City',
    //                 'postcode' => 'Your Postcode',
    //                 'country' => 'Your Country',
    //             ],
    //             'recipient' => [
    //                 'name' => $recipent->name,
    //                 'address' => $recipent->address_line_1,
    //                 'city' => $recipent->city,
    //                 'state' => $recipent->state,
    //                 'postcode' => $recipent->zip_code,
    //                 'country' => $recipent->country,
    //             ],
    //             'parcels' => [
    //                 [
    //                     'weight' => $product->weight ?? '0',
    //                     'package_type' => 'box', // e.g., parcel, box, etc.
    //                 ],
    //             ],
    //         ],
    //     ]);

    //     dd($response);
    //     return json_decode($response->getBody()->getContents(), true);
    // }


    // $payload = [
    //     'UserName' => '2080060960',
    //     'Password' => 'API1234',
    //     'Customerid' => '2080060960',
    //     'Contactid' => '208a144Uoo',
    //     'ShipmentDate' => now()->format('Ymd'),
    //     'Reference' => 'Customer reference',
    //     'Addresses' => [
    //         'Delivery' => [
    //             'Name1' => 'Navn1',
    //             'Street1' => 'Street',
    //             'CountryNum' => '276',
    //             'ZipCode' => '10082',
    //             'City' => 'Berlin',
    //         ]
    //     ],
    //     'Parcels' => [
    //         [
    //             'Weight' => 2.5,
    //         ]
    //     ]
    // ];
    // $response = Http::withHeaders([
    //     'Accept' => 'application/json',
    // ])->post($this->apiUrl, $payload);


    public function createShippingLabel($order)
    {
        

        $weight = 0;
        foreach($order->sell_lines as $sell_line){
            $weight += $sell_line->product->weight;
        }
        $contact = Contact::find($order->contact_id);
        $shipping_address = ($contact->address_line_1 != "") ? $contact->address_line_1 : $order->shipping_address;

        

        $consigneename = $contact->name;
        $country = $contact->country;
        if($country == "Spain" || $country == "España"){
            $country = "ES";
        }
        if($country == "Portugal" || $country == "Portugal"){
            $country = "PT";
        }
        $city = $contact->city;
        $state = $contact->state;
        $zipcode = $contact->zip_code;
        $phone = $contact->mobile;
        $email = $contact->email;

        if(strlen($shipping_address) < 3){
            $shipping_address = $shipping_address.' '.$city.' '.$country;
        }



        $gls_servicio = request()->input('servicio') ? request()->input('servicio') :  'eurobusinessparcel';



        if($country == 'ES'){
            $gls_servicio = 'gls24';
        }

        if ($gls_servicio == 'gls10') {
            $servicio = 1;
            $horario = 0;
        } elseif ($gls_servicio == 'gls14') {
            $servicio = 1;
            $horario = 2;
        } else if ($gls_servicio == 'gls24') {
            $servicio = 1;
            $horario = 3;
        } else if ($gls_servicio == 'buspar') {
            $servicio = 96;
            $horario = 18;
        } else if ($gls_servicio == 'economy') {
            $servicio = 37;
            $horario = 18;
        } else if ($gls_servicio == 'eurobusinessparcel') {
            $servicio = 74;
            $horario = 3;
        } else if ($gls_servicio == 'parcelshopgls') {
            $servicio = 1;
            $horario = 19;
        } else if ($gls_servicio == 'shopdelint') {
            $servicio = 74;
            $horario = 19;
        }
        //Dani 02/01/2016
        if ($weight < 1) {
            $weight = 1;
        }

        //Dani 02/01/2017 Si el servicio es EBP y el peso es inferior a 3, entonces lo cambio a SEBP
        if ($servicio == 74 && $weight < 3) {
            $servicio = 76;
        }

        //dd($order,$contact);
        //$uidCliente="6BAB7A53-3B6D-4D5A-9450-702D2FAC0B11"; // Testing
        $uidCliente = "90302e52-b545-40c3-8ece-b18115f8995c";
        //$uidCliente = "15F9A8B5-82AC-4094-99F7-9FD58FD43E9E";

        $URL= "https://wsclientes.asmred.com/b2b.asmx?wsdl";
        //10eac1d187f1bbff87835cbba105ea0260bfc547bea87c0c92b4b861d62984477abd67477d07f74947317ec773a65c65bd7585fe2ef843800f1f000af555396d
        $envio = array();
        $envio["fecha"] = date("d/m/Y");
        $envio["servicio"] = $servicio;
        $envio["horario"] = $horario;
        $envio["bultos"] = "1";
        $envio["peso"] = $weight;
        $envio["reem"] = "0";
        $envio["nombreOrg"] = "FORMULATWOIT S.L";
        $envio["direccionOrg"] = "P.I EL PLANO, CA NAVE 12";
        $envio["poblacionOrg"] = "ZARAGOZA";
        $envio["codPaisOrg"] = 34;
        $envio["cpOrg"] = "50430";
        $envio["nombreDst"] = $consigneename;
        $envio["direccionDst"] = $shipping_address;
        $envio["poblacionDst"] = $city;
        $envio["codPaisDst"] = $country;
        $envio["cpDst"] = $zipcode;
        $envio["tfnoDst"] = $phone;
        $envio["emailDst"] = $email;
        $envio["observaciones"] = "transport notes";
        $envio["nif"] = "11223344F";
        $envio["portes"] = "P";
        $envio["RefC"] = rand(0000000,9999999).$order->id;

        //dd($envio);

        $XML= '<?xml version="1.0" encoding="utf-8"?>
                <soap12:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">
                <soap12:Body>
                <GrabaServicios  xmlns="http://www.asmred.com/">
                <docIn>
                    <Servicios uidcliente="' . $uidCliente . '" xmlns="http://www.asmred.com/">
                    <Envio>
                    <Fecha>' . $envio["fecha"] . '</Fecha>
                    <Servicio>' . $envio["servicio"] . '</Servicio>
                    <Horario>' . $envio["horario"] . '</Horario>
                    <Bultos>' . $envio["bultos"] . '</Bultos>
                    <Peso>' . $envio["peso"] . '</Peso>
                    <Portes>' . $envio["portes"] . '</Portes>
                    <Importes>
                        <Reembolso>'. $envio["reem"] .'</Reembolso>
                    </Importes>
                    <Remite>
                        <Nombre>' . $envio["nombreOrg"] . '</Nombre>
                        <Direccion>' . $envio["direccionOrg"] . '</Direccion>
                        <Poblacion>' . $envio["poblacionOrg"] . '</Poblacion>
                        <Pais>' . $envio["codPaisOrg"] . '</Pais>
                        <CP>' . $envio["cpOrg"] . '</CP>
                    </Remite>
                    <Destinatario>
                        <Nombre>' . $envio["nombreDst"] . '</Nombre>
                        <Direccion>' . $envio["direccionDst"] . '</Direccion>
                        <Poblacion>' . $envio["poblacionDst"] . '</Poblacion>
                        <Pais>' . $envio["codPaisDst"]. '</Pais>
                        <CP>' . $envio["cpDst"] . '</CP>
                        <Telefono>' . $envio["tfnoDst"] . '</Telefono>
                        <Movil>' . $envio["tfnoDst"] . '</Movil>
                        <Email>' . $envio["emailDst"] . '</Email>
                        <Observaciones>' . $envio["observaciones"] . '</Observaciones>
                    </Destinatario>
                    <Referencias>
                        <Referencia tipo="C">' . $envio["RefC"] . '</Referencia>
                    </Referencias>
                    </Envio>
                    </Servicios>
                    </docIn>
                </GrabaServicios>
                </soap12:Body>
                </soap12:Envelope>';
        //echo $XML;
        //exit();
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_URL, $URL );
        curl_setopt($ch, CURLOPT_POSTFIELDS, $XML );
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml; charset=UTF-8"));

        //echo 'xml: ' . $XML . '<br><br>';
        //exit();
        $postResult = curl_exec($ch);
        curl_close($ch);
        //dd($postResult);
        //exit();
        $xml = simplexml_load_string($postResult, null, 0, "http://www.w3.org/2003/05/soap-envelope");
        
        $xml->registerXPathNamespace('asm', 'http://www.asmred.com/');
        
        $arr = $xml->xpath("//asm:GrabaServiciosResponse/asm:GrabaServiciosResult");
        
        $ret = $arr[0]->xpath("//Servicios/Envio");
        $errors = $ret[0]->xpath("//Servicios/Envio/Errores");
        if(isset($errors[0])){
            $error =(string) $errors[0]->Error;
            if(strpos($error,'-82')){
                $error = '-82: EuroBusiness shipments. Wrong zipcode /wrong country code. Error in zip code or its format, and maybe, a bad combination of city and zip code.';
            }
            if(strpos($error,'-81')){
                $error = '-81: EuroBusiness shipments. A wrong format is transmitted in field.';
            }
            if(strpos($error,'-52')){
                $error = '-52: Error, EuroEstandar/EBP service: reported a country that is not included on the service (<Destinatario>.<Pais>).';
            }
            if($error != ""){
                session()->put(['error'=>$error]);
                return [
                    'errors' => $error
                ];
            }
        }
        //return $ret[0];
        $return = $ret[0]->xpath("//Servicios/Envio/Resultado/@return");
        
        $referenciaN = $arr[0]->xpath('//Servicios/Envio/Referencias/Referencia[@tipo="N"]');
        if (is_array($referenciaN) && isset($referenciaN[0])) $referenciaN = (string)$referenciaN[0];

        $cb = $ret[0]->xpath("//Servicios/Envio/@codbarras");                    

        $codBarras = $cb[0]["codbarras"];


        //echo "CodeBaras=>".$codBarras;
        
        $uid = $ret[0]->xpath("//Servicios/Envio/@uid");

        $uid = $uid[0]["uid"];
        //echo "uid=>".$uid;
        //exit();
        if(!empty($codBarras)){
            $glsLabel = $this->generateLabelviaCodbarras($codBarras,$uid,$order);
            //dd($glsLabel);
            return $glsLabel;
        }
        exit();

        //echo 'postResult: ' . $postResult . '<br><br>';
        

        $xml = simplexml_load_string($postResult, NULL, NULL, "http://http://www.w3.org/2003/05/soap-envelope");
        $xml->registerXPathNamespace('asm', 'http://www.asmred.com/');
        $arr = $xml->xpath("//asm:GrabaServiciosResponse/asm:GrabaServiciosResult");
        $ret = $arr[0]->xpath("//Servicios/Envio");

        $return = $ret[0]->xpath("//Servicios/Envio/Resultado/@return");
        //echo 'Return: ' . $return[0] . '<br/><br/>';

        $cb = $ret[0]->xpath("//Servicios/Envio/@codbarras");
        //print_r($cb);
        //echo 'Codigo barras: ' . isset($cb[0]["codbarras"]) ? $cb[0]["codbarras"] : '';

        $uid = $ret[0]->xpath("//Servicios/Envio/@uid");
        //echo '<br/>uid: ' . $uid[0]["uid"];
       // exit();
        if(isset($cb[0]["codbarras"])){
            $glsLabel = $this->generateLabelviaCodbarras($cb[0]["codbarras"],$uid[0]["uid"],$order);
            //dd($glsLabel);
            return $glsLabel;
        }
        

    }


    public function generateLabelviaCodbarras($codbarras,$uid,$order){
        $url = "https://wsclientes.asmred.com/b2b.asmx";

        // FUNCIONA SOLO CON ENVIOS NO ENTREGADOS.
        // XML NO RETORNA NADA.
        // $xmlObject = simplexml_load_string($codbarras);
        // $xmlObject2 = simplexml_load_string($uid);

        // if ($xmlObject instanceof SimpleXMLElement) {
        //     $codbarras = $xmlObject[0];
        // }
        // if ($xmlObject2 instanceof SimpleXMLElement) {
        //     $uid = $xmlObject2[0];
        // } 
        //print_r($codbarras);
        //print_r($uid);
        $UidCliente = $uid;
        $Referencia = $codbarras;
        $Tipo       = 'PDF';


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

        //echo "<br>WS PETICION DE ETIQUETA<br>".$XML."<br>";

        $postResult = curl_exec($ch);

        if (curl_errno($ch)) {
            //echo 'No se pudo llamar al WS de GLS<br>';
        }

        //echo 'postResult value: '.$postResult;
        //$result = strpos($postResult, '<base64Binary>');
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($postResult);

        //dd($xml);
        //Validamos si lo recibido es un XML
        if($xml === false){
            //echo '<font size="5">No se ha retornado ninguna etiqueta.</font>';
        }
        else {

            $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
            $xml->registerXPathNamespace('asm', 'http://www.asmred.com/');

            $result = $xml->xpath('//soap:Body/asm:EtiquetaEnvioV2Response/asm:EtiquetaEnvioV2Result/Etiquetas/Etiqueta');
            $pdfData = '';
            if ($result === false) {
                return "Error en la consulta XPath<br>";
            } elseif (empty($result)) {
                return "No se encontraron etiquetas<br>";
            } else {
                for ($i = 0; $i < count($result); $i++) {
                    $pdfData.=(string)$result[$i];
                }

            }

            $decodedPdfData = base64_decode($pdfData);
            //echo $decodedPdfData;
            // Specify the path where the PDF should be saved
            $folderName = storage_path('app/public/shipping_labels/documents_'.$order->id);
            $filename = 'shipping-label.pdf';
            $pdfFilePath = storage_path('app/public/shipping_labels/documents_'.$order->id.'/'.$filename);
            
            // Ensure the directory exists
            if (!file_exists(dirname($pdfFilePath))) {
                mkdir(dirname($pdfFilePath), 0755, true);
            }

            // Save the PDF file to the specified path
            file_put_contents($pdfFilePath, $decodedPdfData);

            $fileurl = Storage::url($folderName . '/' . $filename);
            $fileurl = asset('/storage/shipping_labels/documents_'.$order->id.'/'.$filename);
            //dd($filename,$fileurl,$pdfFilePath);
            //dd($pdfFilePath);
            // You can save the file path to your database if needed
            // Or return the file path as needed
            
            return [
                'url' => $fileurl,
                'codeBarassa' => $codbarras,
                'uid' => $uid,
            ];
        }

    }





}
