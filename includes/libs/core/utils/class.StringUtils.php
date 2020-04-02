<?php
namespace core\utils
{
    use core\application\Dictionary;
    use core\application\Configuration;
    class StringUtils
    {
        static public function sanitizeHTML($pHtml)
        {
            $mustFind = array(
                '/valign="[a-z\s]+"\s*/i',
                '/align="[a-z\s]+"\s*/i',
                '/bgcolor="#[0-9a-f]{6}"/i',
                '/color="#[0-9a-f]{6}"/i',
                '/\s*style="[a-z0-9\:\;\-\s\%\.\'\,]+"\s*/i',
                '/face="[a-z0-9\:\;\-\+\s\,]+"/i',
                '/size="[a-z0-9\:\;\-\+\s]+"/i',
                '/cellpadding="[0-9]+"/i',
                '/cellspacing="[0-9]+"/i',
                '/border="[0-9]+"/i',
                '/<font\s*>/i',
                '/<\/font>/i'
            );
            return preg_replace($mustFind, "", $pHtml);
        }
        static public function dateToStr($pDate)
        {
            $str = strtotime($pDate);
            return date("d",$str)." ".Dictionary::term("global.months.".(date("n", $str)-1))." ".date("Y", $str);
        }
        static function convertDate($date, $format = "d/m/Y")
        {
            if (preg_match("/([0-9]{2})\/([0-9]{2})\/([0-9]{4})/", $date, $vals))
                $time = mktime(0, 0, 0, $vals[2], $vals[1], $vals[3]);
            else
                $time = strtotime($date);
            if (!$time || $time < 0) return '';
            return date($format, $time);
        }
        static function getIP()
        {
            $ip = ip2long($_SERVER['REMOTE_ADDR']);
            return sprintf("%u", $ip);
        }
        static function base64URLEncode($pStr)
        {
            return strtr(base64_encode($pStr), "+/=", "-_.");
        }
        static function base64URLDecode($pStr)
        {
            return base64_decode(strtr($pStr, "-_.", "+/="));
        }
        static public function timecmp($a, $b)
        {
            if ($a["timestamp"] < $b["timestamp"]) return 1;
            elseif ($a["timestamp"] == $b["timestamp"]) return 0;
            else return -1;
        }
        static public function substr($pStr, $pLength, $pEllipsis = true)
        {
            $str = html_entity_decode($pStr, ENT_QUOTES, Configuration::$site_encoding);
            $size = mb_strlen($str, Configuration::$site_encoding);
            if ($size <= $pLength)
                return $str;
            $space = mb_strpos($str, ' ', $pLength, Configuration::$site_encoding);
            if (!$space) $space = $size;
            $str = mb_substr($str, 0, $space, Configuration::$site_encoding);
            if ($pEllipsis && $size > strlen($str)) $str .= "...";
            return $str;
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
        static public function removeAccents($pStr, $pCharset='utf-8')
        {
            $pStr = htmlentities($pStr, ENT_NOQUOTES, $pCharset);
            $pStr = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $pStr);
            $pStr = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $pStr); // pour les ligatures e.g. '&oelig;'
            $pStr = preg_replace('#&[^;]+;#', '', $pStr); // supprime les autres caractères non traités
            return $pStr;
        }
        /**
         * Removes all non-letter characters (keep accented) from string, also spaces if specified
         * @param $pStr
         * @param bool|false $pRemoveSpaces
         * @return mixed
         */
        static public function removeNonLetters ($pStr, $pRemoveSpaces = false) {
            $exp = '/[^-\p{L}' . ($pRemoveSpaces === false ? ' ' : '') . ']/u';
            return preg_replace($exp, '', $pStr);
        }
        static public function extractUniqueXMLTagContent($pTagName, $pContent)
        {
            $startTag = "<".$pTagName;
            $endTag = "</".$pTagName.">";
            $startPos = strpos($pContent, $startTag);
            if($startPos === false)
                return false;
            $endPos = strpos($pContent, $endTag);
            if($endPos === false)
                return false;
            $content = substr($pContent, 0, $endPos);
            $content = substr($content, $startPos, strlen($content));
            $startTag = "/".$startTag."[^>]*>/";
            $content = preg_replace($startTag, "", $content);
            return $content;
        }
    }
}