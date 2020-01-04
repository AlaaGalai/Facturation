<?php
$user = "prolawyer@avocats-ch.ch";
$password = "Wztqtz4&";

$url = "http://www.e-service.admin.ch/ws-zefix-1.7/ZefixService";
$url = "http://test-e-service.fenceit.ch/ws-zefix-1.7/ZefixService?wsdl";
$critere = "CH24130133257";
$xmlns = "xmlns=\"http://www.e-service.admin.ch/zefix/2015-06-26\"";

$soap_request  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$soap_request .= "<Envelope xmlns=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
$soap_request .= "   <Body>\n";
// $soap_request .= "    <searchByNameRequest $xmlns>\n";
$soap_request .= "    <getByCHidFullRequest $xmlns>\n";
// $soap_request .= "    <GetByCHidDetailledRequest xmlns=\"http://www.e-service.admin.ch/zefix/2015-06-26\">\n";
$soap_request .= "      <name>$critere</name>\n";
$soap_request .= "      <chid>$critere</chid>\n";
$soap_request .= "      <active>true</active>\n";
$soap_request .= "      <maxSize>2</maxSize>\n";
// $soap_request .= "    </searchByNameRequest>\n";
$soap_request .= "    </getByCHidFullRequest>\n";
// $soap_request .= "    </GetByCHidDetailledRequest>\n";
// $soap_request .= "    </GetByCHidDetailledRequest>\n";
$soap_request .= "  </Body>\n";
$soap_request .= "</Envelope>";

$soapAction = "http://soap.zefix.admin.ch/SearchByName";
$header = array(
    "Content-type: text/xml;charset=\"utf-8\"",
    "Accept: text/xml",
    "Cache-Control: no-cache",
    "Pragma: no-cache",
//     "SOAPAction: \"http://soap.zefix.admin.ch/SearchByName\"",
    "Content-length: ".strlen($soap_request),
  );


$soap_do = curl_init(); 
curl_setopt($soap_do, CURLOPT_URL,            $url );   
curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10); 
curl_setopt($soap_do, CURLOPT_TIMEOUT,        10); 
curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);  
curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false); 
curl_setopt($soap_do, CURLOPT_POST,           true ); 
curl_setopt($soap_do, CURLOPT_POSTFIELDS,    $soap_request); 
curl_setopt($soap_do, CURLOPT_HTTPHEADER,     array('Content-Type: text/xml; charset=utf-8', 'Content-Length: '.strlen($soap_request) )); 
curl_setopt($soap_do, CURLOPT_USERPWD, $user . ":" . $password);

$result = curl_exec($soap_do);
 if($result === false) {
    $err = 'Curl error: ' . curl_error($soap_do);
    curl_close($soap_do);
    print $err;
 } else {
    curl_close($soap_do);
    //print 'Operation completed without any errors';
 }

$dom = new DOMDocument();
$xml = $soap_request;
// Initial block (must before load xml string)
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
// End initial block

$dom->loadXML($xml);
$output = $dom->saveXML();
echo $output;

$dom = new DOMDocument();
$xml = $result;
// Initial block (must before load xml string)
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
// End initial block

$dom->loadXML($xml);
$output = $dom->saveXML();
// echo $output;

$soapParam = array(
"login" => $user,
"password" => $password
);
$client = new SoapClient($url, $soapParam);
$services = $client->__getFunctions ();
// $service = $client->getAllServiceSummaries();
// print_r($service);
$response = $client->__doRequest($soap_request, $url, $soapAction, 1);
print_r($response);
?>
