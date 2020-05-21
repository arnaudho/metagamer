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
        $r = new Request($pUrl);
        $r->setMethod($pMethod);
        $r->setOption(CURLOPT_SSL_VERIFYPEER, false);
        try {
            $data = $r->execute();
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            trace_r($msg);
        }
        return $data;
    }
}