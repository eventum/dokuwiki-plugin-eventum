<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Defect Tracking System                                     |
// +----------------------------------------------------------------------+
// | Copyright (c) 2008 Elan Ruusamäe                                     |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//
// @(#) $Id$

require_once 'XML/RPC.php';

class Eventum_RPC_Exception extends Exception {
};

class Eventum_RPC {
    private $user;
    private $password;
    private $url;

    public function setAuth($user, $password) {
        $this->user = $user;
        $this->password = $password;
    }

    public function setURL($url) {
        $this->url = $url;
    }

    private $client;
    private $debug = 0;
    private function getClient() {
        if (isset($this->client)) {
            return $this->client;
        }

        $data = parse_url($this->url);
        if (!isset($data['port'])) {
            $data['port'] = $data['scheme'] == 'https' ? 443 : 80;
        }
        $data['path'] .= '/rpc/xmlrpc.php';

        $this->client = new XML_RPC_Client($data['path'], $data['host'], $data['port']);
        $this->client->setDebug($this->debug);

        return $this->client;
    }

    public function setDebug($debug) {
        $this->debug = $debug;
    }

    public function __call($method, $args) {
        $params = array();
        $params[] = new XML_RPC_Value($this->user, 'string');
        $params[] = new XML_RPC_Value($this->password, 'string');
        foreach ($args as $arg) {
            $type = gettype($arg);
            if ($type == 'integer') {
                $type = 'int';
            }
            $params[] = new XML_RPC_Value($arg, $type);
        }
        $msg = new XML_RPC_Message($method, $params);

        $client = $this->getClient();
        $result = $client->send($msg);

        if ($result->faultCode()) {
            throw new Eventum_RPC_Exception($result->faultString());
        }

        $details = XML_RPC_decode($result->value());
        foreach ($details as $k => $v) {
            $details[$k] = base64_decode($v);
        }
        return $details;
    }
};
