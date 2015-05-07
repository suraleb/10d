<?php
/**
 * K System Engine
 *
 * @category Kkcms
 * @package  Cms_Text_Parser
 * @author   Kanstantsin A Kamkou <kkamkou@gmail.com>
 * @license  http://creativecommons.org/licenses/by-sa/3.0/ Creative Commons
 * @version  $Id$
 * @link     kkamkou@gmail.com
 */

/**
 * @see cPoll
 */
require_once CMS_ROOT . 'actions/cPoll.php';

/**
 * Parser for a query
 * Example: {poll id:1 tpl:"tpl.tpl" tpl-voted:"vtpl.tpl"}
 */
class Cms_Text_Parser_Poll extends Cms_Text_Parser_Abstract
{
    protected static $_fields = array(
        'id'        => array('~^\d+$~', true),
        'tpl'       => array('~^[\w-]+$~', false),
        'tpl-voted' => array('~^[\w-]+$~', false)
    );

    public static function convert($query)
    {
        // collect and validation of args
        $args = self::_parseQuery($query, self::$_fields);
        if (!$args) {
            return self::_badFormat();
        }

        $tpl = Cms_Template::getInstance();

        // poll find
        $poll = new cPoll(false);
        if (!($tpl->poll = $poll->fetchPoll($args['id']))) {
            return self::_badFormat();
        }

        // active check
        if (!$tpl->poll['active']) {
            return null;
        }

        // templates
        $tplDefault = isset($args['tpl']) ? $args['tpl'] : 'poll-default';
        $tplVoted = isset($args['tpl-voted']) ? $args['tpl-voted'] : 'poll-voted';

        return $tpl->render(
            'parser/' . ($tpl->poll['_voted'] ? $tplVoted : $tplDefault) . '.phtml'
        );
    }
}
