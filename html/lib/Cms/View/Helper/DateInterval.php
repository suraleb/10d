<?php
/**
 * kkCms: Content Management System
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are PROHIBITED without prior written permission from
 * the author. If you received this code occasionally and without
 * intent to use it, please report this incident to the author.
 *
 * @category Data
 * @package  Library
 * @author   Kanstantsin A Kamkou (2ka.by)
 * @license  http://creativecommons.org/licenses/by-nc-nd/3.0/legalcode
 * @version  $Id$
 */

/**
 * Show interval between now and the given date
 *
 * @package View
 * @subpackage Helper
 */
class Cms_View_Helper_DateInterval
{
    /**
     * Allows to use readable format of date
     * @var bool
     */
    static protected $_humanize;

    /**
     * Is time signed
     * @var string
     */
    static protected $_signed = false;

    /**
     * Show interval between now and the given date in the past(!)
     *
     * @param Cms_Date Date/time
     * @param bool $humanize (Default: false)
     * @param string $defaultFormat (Default: null)
     * @return string
     */
    public function dateInterval(Cms_Date $time, $humanize = false, $defaultFormat = null)
    {
        // current time and day compare
        $now = Cms_Date::now();
        $isCurrentDay = (string)$time->getDay() == (string)$now->getDay();

        // days compare to get correct sign
        self::$_signed = $now->isEarlier($time);
        $diff = (self::$_signed) ? $time->sub($now) : $now->sub($time);

        // time difference
        $hoursDifference = abs($diff->getTimestamp() / (60 * 60));

        // humanize set
        self::$_humanize = $humanize;
        if ($humanize && $hoursDifference > 48) {
            return $time->toString($defaultFormat);
        }

        // gets format according time diff
        switch (true) {
            // more than 24months days - we show years
            case $hoursDifference > 24 * 30 * 24:
                $years = $hoursDifference / (24 * 30 * 12);
                return self::_plural(self::_mod($years), 'year');

            // more than 60 days - we show months
            case $hoursDifference > 24 * 60:
                $months = $hoursDifference / (24 * 30);
                return self::_plural(self::_mod($months), 'month');

            // more than 14days - we show weeks
            case $hoursDifference > 24 * 14:
                $weeks = $hoursDifference / (24 * 7);
                return self::_plural(self::_mod($weeks), 'week');

            // more than 2 days we show days
            case $hoursDifference > 48:
                $hours = round(fmod($hoursDifference, 24));
                $hours = $hours ? ('&nbsp;' . self::_plural($hours, 'hour')) : false;
                $days = round($hoursDifference / 24);
                return self::_plural($days, 'day') . $hours;

            // more than 1 day, only for humanize
            case $hoursDifference > 23:
                if ($humanize) {
                    return self::_humanize($time->get('HH:mm'), 'BEFORETODAY');
                }

            // more than 5 hours - we show hours
            case $hoursDifference > 5:
                $hours = round($hoursDifference);
                if ($humanize) {
                    return self::_humanize($time->get('HH:mm'), $isCurrentDay ? 'TODAY' : 'BEFORETODAY');
                }
                return self::_humanize(self::_plural($hours, 'hour'), 'TODAY');

            // more than 1 hour - we show hour+min
            case $hoursDifference >= 1:
                $hrs = floor($hoursDifference);
                if ($humanize) {
                    if ($isCurrentDay) {
                        return self::_humanize(self::_plural($hrs, 'hour'), 'MINUTES');
                    } else {
                        return self::_humanize($time->get('HH:mm'), 'BEFORETODAY');
                    }
                }
                $minutes = round(fmod($hoursDifference, 1) * 60);
                $minutes = $minutes ? ('&nbsp;' . self::_plural($minutes, 'minute')) : null;
                return self::_humanize(self::_plural($hrs, 'hour') . $minutes, 'MINUTES');

            // otherwise just minutes
            default:
                $dif = $hoursDifference * 60;
                $minutes = round($dif);
                if (!$minutes) {
                    $sec = substr(sprintf('%01.2f', $dif), 2);
                    return self::_humanize(self::_plural($sec, 'second'), 'MINUTES');
                }
                return self::_humanize(self::_plural($minutes, 'minute'), 'MINUTES');
        }
    }

    /**
     * To convert "A/B" into "C + 1/2 or 1/4 or 3/4"
     *
     * @param float Value, with float point, e.g. 13.79
     * @return string String, e.g. "13 3/4"
     */
    protected static function _mod($a)
    {
        $str = floor($a);
        $mod = $a - $str;
        switch (true) {
            case ($mod > 0.75):
                $str .= '&frac34;';
                break;
            case ($mod > 0.5):
                $str .= '&frac12;';
                break;
            case ($mod > 0.25):
                $str .= '&frac14;';
                break;
        }
        return $str;
    }

    /**
     * Converts string into human readable string
     *
     * @param string $string
     * @param string $maskPart
     * @return string
     */
    protected static function _humanize($string, $maskPart)
    {
        if (!self::$_humanize) {
            return $string;
        }

        $data = array(
            Cms_Translate::getInstance()->_("TXT_CORE_TIME_MASK_{$maskPart}"),
        );

        if ($maskPart == 'BEFORETODAY') {
            $locale = Cms_Locale::getTranslationList('Relative');
            if (self::$_signed) {
                $data[] = $locale[1];
                $string = substr($string, 1);
            } else {
                $data[] = $locale[-1];
            }
        }

        $data[] = $string;

        return Cms_Functions::strtolower(call_user_func_array('sprintf', $data));
    }

    /**
     * Gets plural form of the string
     *
     * @param string $val
     * @param string $type
     * @return string
     */
    protected static function _plural($val, $type)
    {
        $locale = Cms_Locale::getTranslationList('Unit');
        if (!isset($locale[$type]['many'])) {
            $locale[$type]['many'] = $locale[$type]['other'];
        }

        return Cms_Functions::plural_form(
            $val, str_replace(
                '{0}', self::$_signed ? "-{$val}" : $val,
                "{$locale[$type]['many']},{$locale[$type]['one']},{$locale[$type]['other']}"
            )
        );
    }
}
