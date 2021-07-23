<?php
namespace app\main\src;

use core\application\DefaultController;
use core\tools\Request;

class BotController extends DefaultController
{
    const CODE_OK = 0;

    /**
     * @var string
     */
    protected $dbHandler = "default";

    /**
     * @var string
     */
    protected $name;

    public function __construct($pName)
    {
        $this->name = $pName;
        trace_r("Hello, my name is " . $this->name);
    }

    public function run ($pParameters = array()) {
        trace_r($pParameters);
        trace_r("Now running bot " . $this->name . "...");
    }

    public function callUrl ($pUrl, $pMethod = "GET") {
        $data = "";
        $start = microtime(true);
        $r = new Request($pUrl);
//        $r->setOption(CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        $r->setMethod($pMethod);
        $r->setOption(CURLOPT_SSL_VERIFYPEER, false);
        try {
            $data = $r->execute();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            trace_r($msg);
        }
        $end = microtime(true);
        $time = round($end - $start, 3);
        trace("REST Request <b>[" . $r->getResponseHTTPCode() . "]</b> (" . date("H:i:s", $start)." - ".self::convertToOctets(mb_strlen($data, "UTF-8"))." - ".$time."s) : <a href='".$pUrl."' target='_blank'>".$pUrl.'</a>');
        return $data;
    }

    static public function convertToOctets($pValue, $pPrecision = 2)
    {
        $units = array("octet", "ko", "Mo", "Go");
        $i = 0;
        while($pValue >= 1024 && $units[$i++])
        {
            $pValue /= 1024;
        }
        return round($pValue, $pPrecision)." ".$units[$i];
    }
}