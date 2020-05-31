<?php
/**
 * Eventum Plugin:  Evetnum SCM addons
 *
 * Adds Eventum button to edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@pld-linux.org>
 */

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_eventum extends DokuWiki_Action_Plugin {
    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     */
    function register(Doku_Event_Handler $controller) {
      $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button');
    }

    /**
     * Inserts a toolbar button
     */
    function insert_button(&$event) {
        $event->data[] = array(
            'type' => 'format',
            'title' => $this->getLang('btn_issue'),
            'icon' => '../../plugins/eventum/images/eventum.gif',
            'open' => '[[issue>',
            'close' => ']]',
            'key'    => 'e',
        );
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
