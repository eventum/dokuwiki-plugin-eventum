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
        'date'   => '2010-09-27',
        'name'   => 'Eventum Plugin',
        'desc'   => 'Eventum addons plugin',
        'url'    => 'https://cvs.delfi.ee/dokuwiki/plugin/eventum/',
      );
    }

    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     */
    function register(&$controller) {
      $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button');
    }

    /**
     * Inserts a toolbar button
     */
    function insert_button(&$event) {
        $event->data[] = array(
            'type' => 'format',
            'title' => $this->getLang('btn_issue'),
            'icon' => '../../images/interwiki/issue.gif',
            'open' => '[[issue>',
            'close' => ']]',
            'key'    => 'e',
        );
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
