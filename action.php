<?php
/**
 * Eventum Plugin:  Evetnum SCM addons
 *
 * Adds Eventum button to edit toolbar
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@delfi.ee>  
 */
if(!defined('DOKU_INC')) die();  // no Dokuwiki, no go
 
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_eventum extends DokuWiki_Action_Plugin {
    /**
     * return some info
     */
    function getInfo(){
      return array(
        'author' => 'Elan Ruusamäe',
        'email'  => 'glen@delfi.ee',
        'date'   => '2008-03-03',
        'name'   => 'Eventum Plugin',
        'desc'   => 'Eventum addons plugin',
        'url'    => 'http://wiki.splitbrain.org/plugin:eventum',
      );
    }
    
    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     */
    function register(&$controller) {
      $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
    }

    /**
     * Inserts a toolbar button
     */
    function insert_button(& $event, $param) {
        global $lang;
        global $conf;

        include_once (dirname(__FILE__) . '/lang/en/lang.php');
        @include_once (dirname(__FILE__) . '/lang/' . $conf['lang'] . '/lang.php');
     
        $event->data[] = array (
            'type' => 'format',
            'title' => $lang['btn_issue'],
            'icon' => '../../images/interwiki/issue.gif',
            'open' => '[[issue>',
            'close' => ']]',
        );
    }
}
 
//Setup VIM: ex: et ts=4 enc=utf-8 :
