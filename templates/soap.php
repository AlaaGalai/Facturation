<?php
$user = "prolawyer@avocats-ch.ch";
$password = "Wztqtz4&";

$url = "http://www.e-service.admin.ch/ws-zefix-1.7/ZefixService";
$url = "http://test-e-service.fenceit.ch/ws-zefix-1.7/ZefixService?wsdl";
$critere = "CH24130133257";
$critere = "nestle";
$xmlns = "xmlns=\"http://www.e-service.admin.ch/zefix/2015-06-26\"";
$searchTag  = "searchByNameRequest";
// $searchTag  = "getByCHidFullRequest";
// $searchTag  = "getByCHidDetailledRequest";
$active     = "true";
$maxSize    = "100";
$activeTag  = "      <active>$active</active>\n";
$maxSizeTag = "      <maxSize>$maxSize</maxSize>\n";

switch($searchTag)
{
case "searchByNameRequest":
	$searchCrit = "name";
	break;
case "getByCHidFullRequest":
	$searchCrit = "chid";
	break;
case "GetByCHidDetailledRequest":
	$searchCrit = "chid";
	break;
}

$soap_request  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$soap_request .= "<Envelope xmlns=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
$soap_request .= "   <Body>\n";
$soap_request .= "    <$searchTag $xmlns>\n";
$soap_request .= "      <$searchCrit>$critere</$searchCrit>\n";
$soap_request .= "      <active>$active</active>\n";
$soap_request .= "      <maxSize>$maxSize</maxSize>\n";
$soap_request .= "    </$searchTag>\n";
$soap_request .= "  </Body>\n";
$soap_request .= "</Envelope>";

$soapAction = "http://soap.zefix.admin.ch/SearchByName";

$dom = new DOMDocument();
$xml = $soap_request;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
// End initial block

$dom->loadXML($xml);
$output = $dom->saveXML();
echo $output;


$soapParam = array(
	"login" => $user,
	"password" => $password
);
$client = new SoapClient($url, $soapParam);
//$services = $client->__getFunctions ();
$response = $client->__doRequest($soap_request, $url, $soapAction, 1);

$dom->loadXML($response);
$output = $dom->saveXML();
echo $output;

?>
