<?php
/**
 * Eventum Plugin:  Evetnum SCM addons
 *
 * Adds Eventum button to edit toolbar
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Elan Ruusamäe <glen@delfi.ee>
 */

if (!defined('DOKU_INC')) {
    die;
}

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class action_plugin_eventum extends DokuWiki_Action_Plugin
{
    /**
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     */
    public function register(Doku_Event_Handler $controller)
    {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insertToolbarButton');
        $controller->register_hook('IO_WIKIPAGE_WRITE', 'AFTER', $this, 'addRemoteIssueLinks');
    }

    /**
     * Inserts a toolbar button
     */
    public function insertToolbarButton(Doku_Event $event)
    {
        $event->data[] = array(
            'type' => 'format',
            'title' => $this->getLang('btn_issue'),
            'icon' => '../../plugins/eventum/images/eventum.gif',
            'open' => '[[issue>',
            'close' => ']]',
            'key' => 'e',
        );
    }

    private $alreadyTriggered;

    /**
     * Add remote issue links
     *
     * @param Doku_Event $event
     * @param mixed $param
     */
    public function addRemoteIssueLinks(Doku_Event $event, $param)
    {
        if ($this->alreadyTriggered) {
            return;
        }

        global $ID, $INFO, $conf;

        // Look for issue keys
        if (preg_match_all('/[A-Z]+?-[0-9]+/', $event->data[0][1], $keys)) {
            // Keys found, prepare data for the remote issue link
            $keys = array_unique($keys[0]);
            $url = wl($ID, '', true);
            // MD5 hash is used because the global id max length is 255 characters. An effective page URL might be longer.
            $globalId = md5($url);
            $applicationName = $conf['title'];
            $applicationType = 'org.dokuwiki';
            $title = $applicationName . ' - ' . (empty($INFO['meta']['title']) ? $event->data[2] : $INFO['meta']['title']);
            $relationship = $this->getConf('url_relationship');
            $favicon = tpl_getMediaFile(array(':wiki:favicon.ico', ':favicon.ico', 'images/favicon.ico'), true);

            foreach ($keys as $key) {
                $data = array(
                    'globalId' => $globalId,
                    'application' => array(
                        'type' => $applicationType,
                        'name' => $applicationName,
                    ),
                    'relationship' => $relationship,
                    'object' => array(
                        'url' => $url,
                        'title' => $title,
                        'icon' => array(
                            'url16x16' => $favicon,
                        ),
                    ),
                );

                $this->executeRequest("issue/{$key}/remotelink", 'POST', $data);
            }

            $this->alreadyTriggered = true;
        }
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
