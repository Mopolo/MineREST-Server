<?php
/**
 *
 * @package MineREST
 * @copyright (c) 2013 MineREST
 * @author: Mopolo
 *
 */

namespace MineREST\http;

use MineREST\Kernel;
use MineREST\util\Config;

class Response
{
    const OK = 200;
    const NOT_FOUND = 404;
    const FORBIDDEN = 403;
    const ERROR = 500;

    private $data = array();
    private $html;

    /**
     * @param int $status HTTP status
     * @param null $data A php array that will be converted in json
     * @param bool $html  - If true, the response will be a string
     *                    - If false, the response will be in json
     */
    public function __construct($status = self::OK, $data = null, $html = false)
    {
        $this->data = array(
            'status' => $status
        );

        $this->html = $html;

        if ($status == self::ERROR || $status == self::NOT_FOUND) {
            if ($data == null) $data = 'An unknown error occured.';
            $this->data['error'] = $data;
        } elseif ($data != null) {
            $this->data['data'] = $data;
        }
    }

    public function send()
    {
        if ($this->data['status'] == self::FORBIDDEN) {
            header("HTTP/1.0 403 Forbidden");
            exit('Forbidden');
        }

        if (Kernel::env() == 'dev') {
            header('Access-Control-Allow-Origin: *');
        } else {
            header('Access-Control-Allow-Origin: ' . Config::get('security.domain'));
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        if ($this->html === false) {
            header('Content-type: application/json');
            $json = json_encode($this->data);
            if (Kernel::env() == 'dev') echo $this->json_format($json);
            else echo $json;
            return;
        }

        echo $this->data['data'];
    }

    private function json_format($json)
    {
        $tab = "  ";
        $new_json = "";
        $indent_level = 0;
        $in_string = false;

        $json_obj = json_decode($json);

        if ($json_obj === false) return false;

        $json = json_encode($json_obj);
        $len = strlen($json);

        for ($c = 0; $c < $len; $c++) {
            $char = $json[$c];
            switch ($char) {
                case '{':
                case '[':
                    if (!$in_string) {
                        $new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
                        $indent_level++;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case '}':
                case ']':
                    if (!$in_string) {
                        $indent_level--;
                        $new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ',':
                    if (!$in_string) {
                        $new_json .= ",\n" . str_repeat($tab, $indent_level);
                    } else {
                        $new_json .= $char;
                    }
                    break;
                case ':':
                    if (!$in_string) {
                        $new_json .= ": ";
                    } else {
                        $new_json .= $char;
                    }
                    break;
                default:
                    $new_json .= $char;
                    break;
            }
        }

        return $new_json;
    }
}
