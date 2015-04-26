<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

class Cms_View_Helper_TimeStamp extends Cms_View_Helper
{
    /**
    * Converts timestamp to date. Uses Cms_Date
    *
    * @see http://framework.zend.com/manual/en/zend.date.constants.html
    * @see Cms_Date
    * @param int $timestamp
    * @param string $type
    * @param string $mask
    */
    public function timeStamp($timestamp, $format = Cms_Date::DATE_SHORT, $humanizeSkip = false)
    {
        $date = new Cms_Date($timestamp, Cms_Date::TIMESTAMP);
        if (!$humanizeSkip && Cms_Config::getInstance()->cms->system->time_humanize) {
            return $this->getView()->dateInterval($date, true, $format);
        }
        return $date->toString($format);
    }
}
