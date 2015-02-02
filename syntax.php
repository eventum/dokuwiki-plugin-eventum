<?php
/**
 * Eventum Plugin:  Evetnum SCM addons
 *
 * interpret eventum issue tags in DokuWiki
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

require_once 'XML/RPC.php';
require_once 'class.Eventum_RPC.php';

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_eventum extends DokuWiki_Syntax_Plugin {
    /*
     * keys that we use for caching, to avoid running out of memory when serializing
     */
    static $cache_keys = array(
        'iss_summary',
        'sta_title',
        'sta_is_closed',
    );

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 290;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[\[issue>.+?\]\]', $mode, 'plugin_eventum');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
		$raw = $match = substr($match, 8, -2);
        // extract title
        list($match, $title) = explode('|', $match, 2);
        // extract id
        list($id, $attrs) = explode('&', $match, 2);

        $data = array('raw' => $raw, 'id' => $id, 'attrs' => $attrs);

        // set title only when specified to be able to make difference between empty and unset
        if ($title !== null) {
            $data['title'] = trim($title);
        }

        return $data;
    }

    function cache($id, $data = null) {
        global $conf;
        $cachefile = $conf['cachedir'].'/'.$this->getPluginName().'.cache';

        // mode: get but no cachefile
        if ($data === null && !file_exists($cachefile)) {
            return null;
        }

        // read cache
        $cache = array();
        if (file_exists($cachefile)) {
            $cache = unserialize(file_get_contents($cachefile));
        }

        // expire as long as page edit time to make page edit smooth but still
        // have almost accurate data.
        $mtime = time() - $conf['locktime'];
        foreach ($cache as $i => $ent) {
            if ($ent['mtime'] < $mtime) {
                unset($cache[$i]);
            }
        }

        // mode get:
        if ($data === null) {
            return isset($cache[$id]) ? $cache[$id] : null;
        }

        // mode: set
        $cache[$id] = $data;
        file_put_contents($cachefile, serialize($cache));
        return true;
    }

    /*
     * combine unequal array to new one, taking $keys from first array and
     * values by same key from second array
     *
     * i'd used array_combine, but that does not support arrays with keys.
     */
    function filter_keys($keys, $data) {
        $res = array();
        foreach ($keys as $key) {
            $res[$key] = $data[$key];
        }
        return $res;
    }

    /**
     * Query data from Eventum server
     */
    function query($id) {
        global $conf;

        $cache = $this->cache($id);
        if ($cache !== null) {
            return $cache;
        }

        static $client = null;
        static $eventum_url, $rpc_url;

        if (!$client) {
            // setup RPC object
            $rpc_url = $this->getConf('url') . '/rpc/xmlrpc.php';
            $client = new Eventum_RPC($rpc_url);
            $client->setCredentials($this->getConf('username'), $this->getConf('password'));
            //$client->setDebug(1);

            // Issue link
            $eventum_url = $this->getConf('url') . '/view.php?id=';
        }
        $data['url'] = $eventum_url . $id;

        try {
            $data['details'] = self::filter_keys(self::$cache_keys, $client->getIssueDetails((int )$id));

        } catch (Eventum_RPC_Exception $e) {
            $data['error'] = $e->getMessage();

            if ($conf['allowdebug']) {
                $data['rpcurl'] = $rpc_url;
            }
        }

        $data['id'] = $id;
        $data['mtime'] = time();
        $this->cache($id, $data);

        return $data;
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;

        // fetch extra data from eventum
        $data += $this->query($data['id']);

        // link title
        $link = 'issue #'. $data['id'];

        if ($data['error']) {
            if ($format == 'xhtml') {
                $renderer->doc .= $link;
                $renderer->doc .= ': <i style="color:red">'.$data['error'].'</i>';
                if (isset($data['rpcurl'])) {
                    $renderer->doc .= " <tt>RPC URL: {$data['rpcurl']}</tt>";
                }
            } else {
                $renderer->cdata($data['error']);
            }
            return;
        }

        if (!isset($data['title'])) {
            $data['title'] = $data['details']['iss_summary'];
        }

        if ($format == 'xhtml' || $format == 'odt') {
            $html = '';
            $html .= $this->link($format, $data['url'], $link, $data['details']['iss_summary']);
            if ($data['title']) {
                $html .= ': '. hsc($data['title']);
            }

            if ($data['details']['sta_title']) {
                $html .= ' '. $this->emphasis($format, '('.$data['details']['sta_title'].')');
            }

            if ($data['details']['sta_is_closed']) {
                $html = $this->strike($format, $html);
            }

            $renderer->doc .= $this->html($format, $html);

        } elseif ($format == 'odt') {
            $renderer->externallink($data['url'], $link);
            $renderer->cdata(': '.$data['title']);
        }

        return true;
    }

    /** odt/html export helpers, partly ripped from odt plugin */
    function _xmlEntities($value) {
        return str_replace( array('&','"',"'",'<','>'), array('&#38;','&#34;','&#39;','&#60;','&#62;'), $value);
    }

    function strike($format, $text) {
        $doc = '';
        if ($format == 'xhtml') {
            $doc .= '<strike>';
            $doc .= $text;
            $doc .= '</strike>';
        } elseif ($format == 'odt') {
            $doc .= '<text:span text:style-name="del">';
            $doc .= $text;
            $doc .= '</text:span>';
        }
        return $doc;
    }

    function emphasis($format, $text) {
        if ($format == 'xhtml') {
            $doc .= '<i>';
            $doc .= $text;
            $doc .= '</i>';
        } elseif ($format == 'odt') {
            $doc .= '<text:span text:style-name="Emphasis">';
            $doc .= $text;
            $doc .= '</text:span>';
        }
        return $doc;
    }

    function html($format, $text) {
        $doc = '';
        if ($format == 'xhtml') {
            $doc .= $text;
        } elseif ($format == 'odt') {
            $doc .= '<text:span>';
            $doc .= $text;
            $doc .= '</text:span>';
        }
        return $doc;
    }

    function link($format, $url, $name, $title) {
        $doc = '';
        if ($format == 'xhtml') {
            $doc .= '<a class="iw_eventum" href="'.$url.'" target="_blank" title="'.$title.'">'.hsc($name).'</a>';

        } elseif ($format == 'odt') {
            $url = $this->_xmlEntities($url);
            $doc .= '<text:a xlink:type="simple" xlink:href="'.$url.'">';
            $doc .= $name; // we get the name already XML encoded
            $doc .= '</text:a>';
        }
        return $doc;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
