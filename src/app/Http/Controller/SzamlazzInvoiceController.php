<?php

namespace Gbl\Szamlazz\App\Http\Controller;

use App\Http\Controllers\Controller;
use Exception;
use SimpleXMLElement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\BadResponseException;

class SzamlazzInvoiceController extends Controller
{
    /**
     * @var string \Illuminate\Config\Repository|mixed
     */
    private $agentKey;
    /**
     * @var string
     */
    private $errorDate;
    /**
     * @var string
     */
    private $errorMessage;
    /**
     * @var string
     */
    private $errorCode;

    /**
     * SzamlazzInvoiceController constructor.
     */
    public function __construct()
    {
        $this->agentKey = config('gbl.agent_key');
    }

    /**
     * Create Invoice
     *
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function createInvoice(Request $request, Client $client): JsonResponse
    {
        // TODO validáció még szükséges lesz hozzá
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamla'));
        $this->multidimensionalArrayToXml($request->all(), $xml);
        $responseXml = $this->sendXmlAsFileWithGuzzle($xml->asXML(), 'xmlagentxmlfile', $client);

        return response()->json([
                'status' => 'OK',
                'message' => 'Gratulálunk a számla elkészült.',
                'data' => [
                    'szamlaszam' => $responseXml['szlahu_szamlaszam'][0],
                    'vevoinfourl' => urldecode($responseXml['szlahu_vevoifiokurl'][0]),
                ]
            ]
        );
    }

    /**
     * Reverse Invoice Create
     *
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function reverseInvoice(Request $request, Client $client): JsonResponse
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlast'));
        $this->multidimensionalArrayToXml($request->all(), $xml);
        $responseXml = $this->sendXmlAsFileWithGuzzle($xml->asXML(), 'szamla_agent_st', $client);

        return response()->json([
                'status' => 'OK',
                'message' => 'Gratulálunk a sztornó számla elkészült.',
                'data' => [
                    'szamlaszam' => $responseXml['szlahu_szamlaszam'][0],
                    'vevoinfourl' => urldecode($responseXml['szlahu_vevoifiokurl'][0]),
                ]
            ]
        );
    }

    /**
     * Kifizetés létrehozása
     *
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function registerCreditEntries(Request $request, Client $client)
    {
        // TODO Élesben tesztelni kell a szamlazz.hu teszt fiókkal nem lehet.
        // itt maximon 5 jóváírás lehet akkor azokon foreachelni kell majd és úgy berakni.
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlakifiz'));
        $this->multidimensionalArrayToXml($request->all(), $xml);
        $responseXml = $this->sendXmlAsFileWithGuzzle($xml->asXML(), 'szamla_agent_kifiz', $client);

        return response()->json([
                'status' => 'OK',
                'message' => 'Gratulálunk a kifizetéshez.',
                'data' => [
                    'szamlaszam' => $responseXml['szlahu_szamlaszam'][0],
                    'vevoinfourl' => urldecode($responseXml['szlahu_vevoifiokurl'][0]),
                ]
            ]
        );
    }

    /**
     * Invoice query to PDF file
     *
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function queryInvoicePdf(Request $request, Client $client): JsonResponse
    {
        // TODO validációnál nem fogadunk el a valaszVerzio-ba csak 1-es értéket !!!
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlapdf'));
        $this->arrayToXml($request->all(), $xml);
        $responseXml = $this->sendXmlAsFileWithGuzzle($xml->asXML(), 'szamla_agent_pdf', $client, true);

        $name = date('Y-m-d') . ' ' . $xml->szamlaszam . ' invoice.pdf';
        Storage::disk('public')->put($name,$responseXml);
        return response()->json([
                'status' => 'OK',
                'message' => 'Sikeres számlaletöltés',
                'data' => [
                    'szamlaszam' => $xml->szamlaszam,
                ]
            ]
        );
    }

    /**
     * Invoice query and JSON response
     *
     * @param Request $request
     * @param Client $client
     * @return JsonResponse
     */
    public function queryIncoiceXml(Request $request, Client $client): JsonResponse
    {
        // TODO Validátiónál majd nem fogadhatjuk el a
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlaxml'));
        $this->arrayToXml($request->all(), $xml);
        $responseXml = $this->sendXmlAsFileWithGuzzle($xml->asXML(), 'szamla_agent_xml', $client,true);
        $response = $this->xmlToJson($responseXml);

        return response()->json($response);
    }

    /**
     * Pro Forma Invoice
     *
     * @param Request $request
     * @param Client $client
     */
    public function deletingProFormaInvoice(Request $request, Client $client)
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamladbkdel'));

        //beallitasok
        $beallitasok = $xml->addChild('beallitasok');

        $beallitasok->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $beallitasok->addChild('jelszo', config('gbl.szamlazz_password'));
        $beallitasok->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));
        //fejlec
        $fejlec = $xml->addChild('fejlec');
        // TODO lehetséges nem egy számla, hanem egy rendelésszám alapján történő stornó. Még ilyet nem csináltam a szamlazz-on tesztelni kell!!!
//        if($request->szamlaszam){
        $fejlec->addChild('szamlaszam', 'GBL-2020-1');
//        }
//        else {
//            $fejlec->addChild('rendelesszam','XXX');
//        }
        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_dijbekero_torlese', $client);
        dd($responseXml);
    }

    /**
     * Guzzle segítségével leposztoljuk a fájl formában az xml-t
     *
     * @param $xml //A korábban összeállított xml fájl
     * @param $action //Az egyedi action-ok segítségével tudja a számlázz.hu api hogy a beküldött xml-el mit szeretnél.
     * @param $client //Guzzle client
     * @param bool $body //Kapcsoló ennek ettől függ hogy a visszatérési érték a response headerje vagy body-ja legyen
     * @return mixed    //array vagy string(pdf) tér most még vissza amíg nincs meg mit kell az adatokból.
     */
    private function sendXmlAsFileWithGuzzle($xml, $action, $client, $body = false)
    {
        $uri = 'https://www.szamlazz.hu/szamla/?action=' . $action;
        $guzzleRequest = new GuzzleRequest('POST', $uri, ['Content-Type' => 'application/xml'], $xml);
        try {
            $response = $client->send($guzzleRequest);
            if(isset($response->getHeaders()['szlahu_error'])) {
                $this->errorDate = $response->getHeader('Date')[0];
                $this->errorCode = $response->getHeader('szlahu_error_code')[0];
                $this->errorMessage = urldecode($response->getHeader('szlahu_error')[0]);
                throw new Exception('Hiba nézd meg a log fiájlban!');
            }
        }
        catch(BadResponseException $e) {
            echo 'Uh oh! ' . $e->getMessage();
            echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . PHP_EOL;
            echo 'HTTP request: ' . $e->getRequest() . PHP_EOL;
            echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . PHP_EOL;
            echo 'HTTP response: ' . $e->getResponse() . PHP_EOL;
            exit();
        }
        catch(Exception $e) {
            $xml = simplexml_load_string($xml);
            if(isset($xml->fejlec->szamlaszam)) {
                Log::channel('szamlazz')->alert($this->errorDate . ' számlaszám: ' . $xml->fejlec->szamlaszam . PHP_EOL . ' hibakód: ' . $this->errorCode . ', ' . $this->errorMessage);
            }
            Log::channel('szamlazz')->alert(PHP_EOL . ' hibakód: ' . $this->errorCode . ', ' . $this->errorMessage);
            exit();
        }
        if($body) {
            return $response->getBody()->getContents();
        }
        return $response->getHeaders();
    }

    /**
     * Az XML Skeleton
     *
     * @param $xmlType //XML kerethez és attribútumokhoz szükséges adat
     * @return bool|string
     */
    private function xmlSkeleton($xmlType)
    {
        // Generate invoice
        if($xmlType === 'xmlszamla') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamla 
                        xmlns="http://www.szamlazz.hu/xmlszamla"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamla  http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamla .xsd"> 
                </xmlszamla>';
        }
        // Reverse invoice
        elseif($xmlType === 'xmlszamlast') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlast 
                        xmlns="http://www.szamlazz.hu/xmlszamlast"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlast http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlast.xsd"> 
                </xmlszamlast>';
        }
        // Register credit entry
        elseif($xmlType === 'xmlszamlakifiz') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlakifiz 
                        xmlns="http://www.szamlazz.hu/xmlszamlakifiz"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlakifiz http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlakifiz.xsd"> 
                </xmlszamlakifiz>';
        }
        // Query invoice pdf
        elseif($xmlType === 'xmlszamlapdf') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlapdf 
                        xmlns="http://www.szamlazz.hu/xmlszamlapdf"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlapdf http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlapdf.xsd"> 
                </xmlszamlapdf>';
        }
        // Query invoice xml
        elseif($xmlType === 'xmlszamlaxml') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlaxml 
                        xmlns="http://www.szamlazz.hu/xmlszamlaxml"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlaxml http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlaxml.xsd"> 
                </xmlszamlaxml>';
        }
        // Delete Pro Forma Invoices
        elseif($xmlType === 'xmlszamladbkdel') {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamladbkdel 
                        xmlns="http://www.szamlazz.hu/xmlszamladbkdel"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamladbkdel http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamladbkdel.xsd"> 
                </xmlszamladbkdel>';
        }
        else {
            return false;
        }
    }

    /**
     * XML to JSON
     *
     * @param string $xml
     * @return Array
     */
    private function xmlToJson ($xml): Array
    {
        $xmlObject = simplexml_load_string($xml);
        $jsonString = json_encode($xmlObject);
        $jsonArray = json_decode($jsonString, true);
        return $jsonArray;
    }

    /**
     * Array to XML
     *
     * @param $array
     * @param $skeleton
     */
    private function arrayToXml($array, $skeleton): void
    {
        $skeleton->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $skeleton->addChild('jelszo', config('gbl.szamlazz_password'));
        $skeleton->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));
        foreach($array as $key => $value) {
            $skeleton->addChild("$key", htmlspecialchars("$value"));
        }
    }

    /**
     * Multidimensional Arrays to XML
     *
     * @param $array
     * @param $skeleton
     */
    private function multidimensionalArrayToXml($array, $skeleton): void
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)) {
                    $subnode = $skeleton->addChild("$key");
                    if($key === 'beallitasok') {
                        $subnode->addChild('felhasznalo', config('gbl.szamlazz_user'));
                        $subnode->addChild('jelszo', config('gbl.szamlazz_password'));
                        $subnode->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));
                    }
                    $this->multidimensionalArrayToXml($value, $subnode);
                }
                else {
                    $subnode = $skeleton->addChild("item$key");
                    $this->multidimensionalArrayToXml($value, $subnode);
                }
            }
            else {
                $skeleton->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}
