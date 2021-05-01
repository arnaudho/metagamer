<?php

namespace core\application {

    use core\data\SimpleCSV;
    use core\data\SimpleJSON;
    use core\data\SimpleXML;
    use lib\core\tools\Http;

    class RestController extends DefaultController
    {
        const FORMAT_JSON = "json";
        const FORMAT_XML = "xml";
        const FORMAT_CSV = "csv";
        const FORMAT_TXT = "txt";
        const FORMAT_HTML = "html";


        private $headers = array();

        protected $DEBUG_MODE = false;
        protected $version;
        protected $entity;
        protected $controller;
        protected $action;
        protected $requestMethod;
        protected $requestParameters;
        protected $responseCode;
        protected $format;
        protected $content;
        protected $contentTypeOverridden = false;
        protected $acceptedContentTypes = array(
            "application/json" => "json",
            "text/json" => "json",
            "application/xml" => "xml",
            "text/xml" => "xml",
            "application/csv" => "csv",
            "text/csv" => "csv",
            "text/plain" => "txt"
        );
        protected $acceptedFormats = array(
            "json",
            "xml",
            "csv",
            "txt"
        );

        function __construct()
        {
            $this->headers = getallheaders();
            $this->format = self::FORMAT_JSON;

            // treat input as JSON
            if (isset($this->headers['Content-Type']) && $this->headers['Content-Type'] == "application/json") {
                $tmp = null;
                try {
                    // because POST is empty due to Content-Type
                    if (in_array($this->requestMethod, array(Http::POST, Http::PUT, Http::PATCH, Http::DELETE))) {
                        // retrieve request body content
                        $request_body = file_get_contents("php://input");

                        // check request body
                        if (!empty($request_body)) {
                            // try to parse it into POST
                            parse_str($request_body, $_POST);

                            // retrieve params according to method : data=JSON or JSON directly
                            $params = isset($_POST['data']) && !empty($_POST['data']) ? $_POST['data'] : $request_body;

                            $tmp = SimpleJSON::decode($params);
                        }
                    }
                } catch(\Exception $e) {
                    $this->throwError(
                        422,
                        sprintf(Dictionary::term("global.expressions.validation.XMalformatted"), Dictionary::term("global.expression.generic.json_input")),
                        $e->getMessage(),
                        ucfirst(Dictionary::term("global.expression.generic.json_format")),
                        "ERR_JSON_FORMAT"
                    );
                }

                if (is_array($tmp) && !empty($tmp)) {
                    $_POST = $tmp;
                }
            }

            // get parameters from request
            if($this->requestMethod == Http::GET) {
                $this->requestParameters = $_GET;
            } else {
                $this->requestParameters = array_merge($_POST, $_GET);
            }
        }


        /**
         * Méthode public de rendu de la page en cours
         * @param $smarty
         * @param bool $pDisplay
         * @param bool $pCompression
         * @return string
         */
        public function render($smarty = null, $pDisplay = true, $pCompression = true) {
            // check response content type
            if (strpos($this->format, "image/") === false && $this->format != self::FORMAT_HTML && !$this->isValidFormat($this->format)) {
                // throw error of unsupported content-type
                $this->setResponseCode(406);
                $this->content = sprintf(Dictionary::term("global.expressions.validation.notValidX"), Dictionary::term("global.words.content_type"));
            } else {
                // handle no content case
                if (empty($this->content) && $this->responseCode == 200 && !$this->DEBUG_MODE) {
                    $this->setResponseCode(204);
                    $this->content = "";
                }
            }

            // build response according to content type
            switch($this->format) {
                case 'json':
                    if (is_array($this->content)) {
                        $this->content = SimpleJSON::encode($this->content, JSON_UNESCAPED_UNICODE | JSON_OBJECT_AS_ARRAY | JSON_NUMERIC_CHECK);
                    }
                    break;
                case 'xml':
                    if (is_array($this->content)) {
                        $this->content = SimpleXML::encode($this->content);
                    }
                    break;
                case 'csv':
                    if (is_array($this->content)) {
                        $this->content = SimpleCSV::encode($this->content, false);
                    }
                    break;
                case 'html':
                case 'txt':
                    break;
            }

            // set response headers
            $this->setResponseHeaders();


            // send response
            Core::performResponse($this->content, $this->format);
        }

        private function setResponseHeaders() {
            // expose header when content type is overridden
            if ($this->contentTypeOverridden) {
                Header::set("Content-Type-Overridden:".($this->contentTypeOverridden ? 'true' : 'false'));
            }

            // set content-type according to format
            Header::contentType($this->getContentTypeFromFormat($this->format));

            // set response code if set
            if(is_int($this->responseCode)) {
                $message = "";
                // define Http constant to target
                $const_name = 'CODE_' . $this->responseCode;
                // check if it exist on Http
                if (defined('lib\core\tools\Http::' . $const_name)) {
                    $message = constant('lib\core\tools\Http::' . $const_name);
                }
                // if we got a msg from constant, set headers
                if (!empty($message)) {
                    Header::http(Http::V_1_1 . " " . $message);
                    Header::status($message);
                }
            }
        }

        /**
         *
         * @param $pFormat
         * @return bool
         */
        private function isValidFormat($pFormat) {
            if (empty($pFormat)) {
                return false;
            }

            return in_array($pFormat, $this->acceptedFormats);
        }

        private function getContentTypeFromFormat($pFormat) {
            $content_type = null;

            foreach ($this->acceptedContentTypes as $ct => $format) {
                if ($format == $pFormat) {
                    $content_type = $ct;
                    break;
                }
            }

            return $content_type;
        }

        /**
         * @param $pStatus
         * @param $pMessage
         * @param string $pDevMessage
         * @param string $pProperty
         * @param string $pErrorCode
         */
        protected function throwError($pStatus, $pMessage, $pDevMessage = "", $pProperty = "", $pErrorCode = "") {
            $this->setResponseCode($pStatus);
            $this->content = array(
                "status" => $pStatus,
                "property" => $pProperty,
                "message" => $pMessage,
                "devMessage" => $pDevMessage,
                "errorCode" => $pErrorCode
            );

            $this->render();
        }
        protected function setResponseCode($pCode) {
            if (is_int($pCode)) {
                $this->responseCode = $pCode;
                return true;
            }

            return false;
        }

    }
}