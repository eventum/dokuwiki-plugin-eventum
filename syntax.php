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

require_once 'class.Eventum_RPC.php';

/**
// auth info for xml rpc
$conf['plugin']['eventum']['url'] = 'http://example.org/eventum';
$conf['plugin']['eventum']['username'] = 'admin@example.org';
$conf['plugin']['eventum']['password'] = 'admin';
 */

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_eventum extends DokuWiki_Syntax_Plugin {

    /**
     * return some info
     */
    function getInfo() {
      return array(
        'author' => 'Elan Ruusamäe',
        'email'  => 'glen@delfi.ee',
        'date'   => '2008-09-10',
        'name'   => 'Eventum Plugin',
        'desc'   => 'Eventum addons plugin',
        'url'    => 'https://cvs.delfi.ee/dokuwiki/plugin/eventum/',
      );
    }

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
        return 300;
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

        $data = array('raw' => $raw, 'id' => $id, 'attrs' => $attrs, 'title' => hsc($title));
        return $data;
    }

    /**
     * Create output
     */
    function render($format, &$renderer, $data) {
        global $ID;
        if ($format != 'xhtml') {
            return false;
        }

        // link title
        $link = hsc('issue #'. $data['id']);
        $title = '';
        if (!empty($data['title'])) {
            $title = $data['title'];
        }

        // fetch extra data from eventum
        static $client = null;
        static $eventum_url;
        if (!$client) {
            global $conf;
            // get plugin config
            $c = $conf['plugin']['eventum'];
            $client = new Eventum_RPC();
            $client->setAuth($c['username'], $c['password']);
            $client->setURL($c['url']);

            // and link to eventum
            $eventum_url = $c['url'] . '/view.php?id=';
        }
        $url = $eventum_url . $data['id'];

        try {
            $details = $client->getIssueDetails((int )$data['id']);
        } catch (Eventum_RPC_Exception $e) {
            $renderer->doc .= $link;
            $renderer->doc .= ' <i style="color:red">'.$e->getMessage().'</i>';
            return;
        }

        if ($details['sta_is_closed']) {
            $renderer->doc .= '<strike>';
        }
        $renderer->doc .= '<a class="interwiki iw_issue" href="'.$url.'" target="_blank" title="'.$details['iss_summary'].'">'.$link.'</a>';
        if ($title) {
            $renderer->doc .= ': '.$title;
        } elseif ($details['iss_summary']) {
            $renderer->doc .= ': '.hsc($details['iss_summary']);
        }
        if ($details['sta_title']) {
            $renderer->doc .= ' <i>('.$details['sta_title'].')</i>';
        }
        if ($details['sta_is_closed']) {
            $renderer->doc .= '</strike>';
        }

        return true;
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
