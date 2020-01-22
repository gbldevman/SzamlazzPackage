<?php

namespace Gbl\Szamlazz\App\Http\Controller;

use App\Http\Controllers\Controller;
use SimpleXMLElement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Exception\BadResponseException;

class SzamlazzController extends Controller
{
    /**
     * @var string \Illuminate\Config\Repository|mixed
     */
    private $agentKey;

    /**
     * SzamlazzController constructor.
     */
    public function __construct()
    {
        $this->agentKey = config('gbl.agent_key');
    }

    /**
     *
     */
    public function createInvoice()
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamla'));

        //beallitasok
        $beallitasok = $xml->addChild('beallitasok');

        $beallitasok->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $beallitasok->addChild('jelszo', config('gbl.szamlazz_password'));
        $beallitasok->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));

        $beallitasok->addChild('eszamla', 'false');
        $beallitasok->addChild('kulcstartojelszo');
        $beallitasok->addChild('szamlaLetoltes', 'true');
        $beallitasok->addChild('valaszVerzio', '2');
        //fejlec
        $fejlec = $xml->addChild('fejlec');
        $fejlec->addChild('keltDatum', '2020-01-20');
        $fejlec->addChild('teljesitesDatum', '2020-01-20');
        $fejlec->addChild('fizetesiHataridoDatum', '2020-01-30');
        $fejlec->addChild('fizmod', 'Átutalás');
        $fejlec->addChild('penznem', 'HUF');
        $fejlec->addChild('szamlaNyelve', 'hu');
        $fejlec->addChild('megjegyzes', 'Számla megjegyzés');
        $fejlec->addChild('arfolyamBank', 'MNB');
        $fejlec->addChild('arfolyam', '0.0');
        $fejlec->addChild('rendelesSzam');
        $fejlec->addChild('dijbekeroSzamlaszam');
        $fejlec->addChild('elolegszamla', 'false');
        $fejlec->addChild('vegszamla', 'false');
        $fejlec->addChild('helyesbitoszamla', 'false');
        $fejlec->addChild('helyesbitettSzamlaszam');
        $fejlec->addChild('dijbekero', 'false');
        $fejlec->addChild('szamlaszamElotag', 'GBL');
        //elado
        $elado = $xml->addChild('elado');
        $elado->addChild('bank', 'CIB');
        $elado->addChild('bankszamlaszam', '11111111-22222222-33333333');
        $elado->addChild('emailReplyto');
        $elado->addChild('emailTargy', 'Számla értesítő');
        $elado->addChild('emailSzoveg', 'mail text');
        //vevo
        $vevo = $xml->addChild('vevo');
        $vevo->addChild('nev', 'Kovacs Bt.');
//        $vevo->addChild('azonosito');
        $vevo->addChild('irsz', '2030');
        $vevo->addChild('telepules', 'Érd');
        $vevo->addChild('cim', 'Tárnoki út 23.');
        $vevo->addChild('email', 'buyer@example.com');
        $vevo->addChild('sendEmail', 'false');
        $vevo->addChild('adoszam', '12345678-1-42');
        $vevo->addChild('postazasiNev', 'Kovács Bt. postázási név');
        $vevo->addChild('postazasiIrsz', '2040');
        $vevo->addChild('postazasiTelepules', 'Budaörs');
        $vevo->addChild('postazasiCim', 'Szivárvány utca 8.');
        $vevo->addChild('telefonszam', 'Tel:+3630-555-55-55, Fax:+3623-555-555');
        $vevo->addChild('megjegyzes', 'A portáról felszólni a 214-es mellékre.');
        //fuvarlevel
        $fuvarlevel = $xml->addChild('fuvarlevel');
        $fuvarlevel->addChild('uticel');
        $fuvarlevel->addChild('futarSzolgalat');
        //tetelek
        $tetelek = $xml->addChild('tetelek');
        // itt ha több tétel van akkor azokon foreachelni kell majd és úgy berakni.
        $tetel = $tetelek->addChild('tetel');
        $tetel->addChild('megnevezes', 'Elado izé');
        $tetel->addChild('mennyiseg', '3.0');
        $tetel->addChild('mennyisegiEgyseg', 'db');
        $tetel->addChild('nettoEgysegar', '40000');
        $tetel->addChild('afakulcs', '27');
        $tetel->addChild('nettoErtek', '120000.0');
        $tetel->addChild('afaErtek', '32400.0');
        $tetel->addChild('bruttoErtek', '152400.0');
        $tetel->addChild('megjegyzes', 'lorem ipsum');

        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'xmlagentxmlfile');
        dd($responseXml);
    }

    public function reverseInvoice()
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlast'));
        //beallitasok
        $beallitasok = $xml->addChild('beallitasok');

        $beallitasok->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $beallitasok->addChild('jelszo', config('gbl.szamlazz_password'));
        $beallitasok->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));

        $beallitasok->addChild('eszamla', 'false');
        $beallitasok->addChild('szamlaLetoltes', 'true');
        $beallitasok->addChild('szamlaLetoltesPld', '1');
        //fejlec
        $fejlec = $xml->addChild('fejlec');
        $fejlec->addChild('szamlaszam', 'GBL-2020-5');
        $fejlec->addChild('keltDatum', '2020-01-21');
        $fejlec->addChild('teljesitesDatum', '2020-01-21');
        $fejlec->addChild('tipus', 'SS');
        //elado
        $elado = $xml->addChild('elado');
        $elado->addChild('emailReplyto', 'elado@example.com');
        $elado->addChild('emailTargy', 'Email tárgya');
        $elado->addChild('emailSzoveg', 'Lorem ipsum');
        //vevo
        $vevo = $xml->addChild('vevo');
        $vevo->addChild('email', 'buyer@example.com');


        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_st');
//        pdf mentés
//        $name = date('Y-m-d') . 'GBL-2020-5' . ' reverseInvoice.pdf';
//        Storage::disk('public')->put($name,$responseXml);
        dd($responseXml);
        die();

    }

    // TODO Élesben tesztelni kell a szamlazz.hu teszt fiókkal nem lehet.
    public function registerCreditEntries()
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlakifiz'));

        $beallitasok = $xml->addChild('beallitasok');

        $beallitasok->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $beallitasok->addChild('jelszo', config('gbl.szamlazz_password'));
        $beallitasok->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));

        $beallitasok->addChild('szamlaszam', 'GBL-2020-1');
        $beallitasok->addChild('additiv', 'false');
        //kifizetes
        $kifizetes = $xml->addChild('kifizetes');
        // itt maximon 5 jóváírás lehet akkor azokon foreachelni kell majd és úgy berakni.
        $kifizetes->addChild('datum', '2020-01-20');
        $kifizetes->addChild('jogcim', 'készpénz');
        $kifizetes->addChild('osszeg', '1000');

        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_kifiz');
        dd($responseXml);
    }

    public function queryInvoicePdf()
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlapdf'));

        $xml->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $xml->addChild('jelszo', config('gbl.szamlazz_password'));
        $xml->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));
//        $xml->addChild('szamlaszam', $request->szamlaszam);
//        $xml->addChild('valaszVerzio', $request->reaponseversion);

        $xml->addChild('szamlaszam', 'GBL-2020-1');
        $xml->addChild('valaszVerzio', '2');

        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_pdf');
        dd($responseXml);
    }

    public function queryIncoiceXml(Request $request)
    {
        $xml = new SimpleXMLElement($this->xmlSkeleton('xmlszamlaxml'));

        $xml->addChild('felhasznalo', config('gbl.szamlazz_user'));
        $xml->addChild('jelszo', config('gbl.szamlazz_password'));
        $xml->addChild('szamlaagentkulcs', config('gbl.szamlazz_agent_key'));
//        $xml->addChild('szamlaszam', $request->szamlaszam);
//        $xml->addChild('rendelesSzam');
//        $xml->addChild('pdf', $request->pdf);

        $xml->addChild('szamlaszam', 'GBL-2020-1');
        $xml->addChild('rendelesSzam');
        $xml->addChild('pdf', 'true');

        $xml = $xml->asXML();

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_xml');
        dd($responseXml);
    }

    public function deletingProFormaInvoice(Request $request)
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

        $responseXml = $this->sendXmlAsFileWithGuzzle($xml, 'szamla_agent_dijbekero_torlese');
        dd($responseXml);
    }

    /**
     * @param $xml
     * @param string $action
     * @param Client $client
     * @return string
     */
    private function sendXmlAsFileWithGuzzle($xml, $action, Client $client): string
    {
        $uri = 'https://www.szamlazz.hu/szamla/?action=' . $action;
        $guzzleRequest = new GuzzleRequest('POST', $uri, ['Content-Type' => 'application/xml'], $xml);
        try
        {
            $response = $client->send($guzzleRequest);
        }
        catch(BadResponseException $e)
        {
            echo 'Uh oh! ' . $e->getMessage();
            echo 'HTTP request URL: ' . $e->getRequest()->getUrl() . PHP_EOL;
            echo 'HTTP request: ' . $e->getRequest() . PHP_EOL;
            echo 'HTTP response status: ' . $e->getResponse()->getStatusCode() . PHP_EOL;
            echo 'HTTP response: ' . $e->getResponse() . PHP_EOL;
        }
        if(isset($response->getHeaders()['szlahu_error']))
        {
            $errorDate = $response->getHeader('Date')[0];
            $errorCode = $response->getHeader('szlahu_error_code')[0];
            $errorMessage = urldecode($response->getHeader('szlahu_error')[0]);

            $xml = simplexml_load_string($xml);
            Log::alert($errorDate . ' számlaszám: ' . $xml->fejlec->szamlaszam . PHP_EOL . ' hibakód: ' . $errorCode . ', ' . $errorMessage);

            return 'HIBA a mentési folyamat során';
        }
        return $response->getHeaders();
//        pdf mentés esetén
//        return $response->getBody()->getContents();
    }

    private function xmlSkeleton($xmlType)
    {
        // Generate invoice
        if($xmlType === 'xmlszamla')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamla 
                        xmlns="http://www.szamlazz.hu/xmlszamla"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamla  http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamla .xsd"> 
                </xmlszamla>';
        }
        // Reverse invoice
        elseif($xmlType === 'xmlszamlast')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlast 
                        xmlns="http://www.szamlazz.hu/xmlszamlast"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlast http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlast.xsd"> 
                </xmlszamlast>';
        }
        // Register credit entry
        elseif($xmlType === 'xmlszamlakifiz')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlakifiz 
                        xmlns="http://www.szamlazz.hu/xmlszamlakifiz"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlakifiz http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlakifiz.xsd"> 
                </xmlszamlakifiz>';
        }
        // Query invoice pdf
        elseif($xmlType === 'xmlszamlapdf')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlapdf 
                        xmlns="http://www.szamlazz.hu/xmlszamlapdf"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlapdf http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlapdf.xsd"> 
                </xmlszamlapdf>';
        }
        // Query invoice xml
        elseif($xmlType === 'xmlszamlaxml')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamlaxml 
                        xmlns="http://www.szamlazz.hu/xmlszamlaxml"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamlaxml http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamlaxml.xsd"> 
                </xmlszamlaxml>';
        }
        // Delete Pro Forma Invoices
        elseif($xmlType === 'xmlszamladbkdel')
        {
            return '<?xml version="1.0" encoding="UTF-8"?>
                <xmlszamladbkdel 
                        xmlns="http://www.szamlazz.hu/xmlszamladbkdel"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                        xsi:schemaLocation="http://www.szamlazz.hu/xmlszamladbkdel http://www.szamlazz.hu/docs/xsds/agentpdf/xmlszamladbkdel.xsd"> 
                </xmlszamladbkdel>';
        }
        else
        {
            return false;
        }
    }


}
