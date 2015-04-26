<?php
/**
 * @author Kanstantsin A Kamkou
 * @link kkamkou at gmail dot com
 * @version $Id$
 */

class Cms_View_Helper_HeadScript extends Zend_View_Helper_HeadScript
{
    /**
     * Retrieve string representation
     *
     * @param  string|int $indent
     * @return string
     */
    public function toString($indent = null)
    {
        if (!CMS_DEBUG && !Cms_Config::getInstance()->cms->cache->disabled) {
            foreach ($this->getContainer()->getValue() as $obj) {
                $out = Cms_Minify_Uglify::minify($obj->source);
                if ($out === false) {
                    $out = Cms_Minify_Js::minify($obj->source);
                }
                $obj->source = $out;
            }
        }
        return parent::toString($indent);
    }
}
