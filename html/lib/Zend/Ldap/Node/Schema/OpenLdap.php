<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Ldap
 * @subpackage Schema
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: OpenLdap.php 23775 2011-03-01 17:25:24Z ralph $
 */

/**
 * @see Zend_Ldap_Node_Schema
 */
require_once 'Zend/Ldap/Node/Schema.php';
/**
 * @see Zend_Ldap_Node_Schema_AttributeType_OpenLdap
 */
require_once 'Zend/Ldap/Node/Schema/AttributeType/OpenLdap.php';
/**
 * @see Zend_Ldap_Node_Schema_ObjectClass_OpenLdap
 */
require_once 'Zend/Ldap/Node/Schema/ObjectClass/OpenLdap.php';

/**
 * Zend_Ldap_Node_Schema_OpenLdap provides a simple data-container for the Schema node of
 * an OpenLDAP server.
 *
 * @category   Zend
 * @package    Zend_Ldap
 * @subpackage Schema
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Ldap_Node_Schema_OpenLdap extends Zend_Ldap_Node_Schema
{
    /**
     * The attribute Types
     *
     * @var array
     */
    protected $_attributeTypes = null;
    /**
     * The object classes
     *
     * @var array
     */
    protected $_objectClasses = null;
    /**
     * The LDAP syntaxes
     *
     * @var array
     */
    protected $_ldapSyntaxes = null;
    /**
     * The matching rules
     *
     * @var array
     */
    protected $_matchingRules = null;
    /**
     * The matching rule use
     *
     * @var array
     */
    protected $_matchingRuleUse = null;

    /**
     * Parses the schema
     *
     * @param  Zend_Ldap_Dn $dn
     * @param  Zend_Ldap    $ldap
     * @return Zend_Ldap_Node_Schema Provides a fluid interface
     */
    protected function _parseSchema(Zend_Ldap_Dn $dn, Zend_Ldap $ldap)
    {
        parent::_parseSchema($dn, $ldap);
        $this->_loadAttributeTypes();
        $this->_loadLdapSyntaxes();
        $this->_loadMatchingRules();
        $this->_loadMatchingRuleUse();
        $this->_loadObjectClasses();
        return $this;
    }

    /**
     * Gets the attribute Types
     *
     * @return array
     */
    public function getAttributeTypes()
    {
        return $this->_attributeTypes;
    }

    /**
     * Gets the object classes
     *
     * @return array
     */
    public function getObjectClasses()
    {
        return $this->_objectClasses;
    }

    /**
     * Gets the LDAP syntaxes
     *
     * @return array
     */
    public function getLdapSyntaxes()
    {
        return $this->_ldapSyntaxes;
    }

    /**
     * Gets the matching rules
     *
     * @return array
     */
    public function getMatchingRules()
    {
        return $this->_matchingRules;
    }

    /**
     * Gets the matching rule use
     *
     * @return array
     */
    public function getMatchingRuleUse()
    {
        return $this->_matchingRuleUse;
    }

    /**
     * Loads the attribute Types
     *
     * @return void
     */
    protected function _loadAttributeTypes()
    {
        $this->_attributeTypes = array();
        foreach ($this->getAttribute('attributeTypes') as $value) {
            $val = $this->_parseAttributeType($value);
            $val = new Zend_Ldap_Node_Schema_AttributeType_OpenLdap($val);
            $this->_attributeTypes[$val->getName()] = $val;

        }
        foreach ($this->_attributeTypes as $val) {
            if (count($val->sup) > 0) {
                $this->_resolveInheritance($val, $this->_attributeTypes);
            }
            foreach ($val->aliases as $alias) {
                $this->_attributeTypes[$alias] = $val;
            }
        }
        ksort($this->_attributeTypes, SORT_STRING);
    }

    /**
     * Parses an attributeType value
     *
     * @param  string $value
     * @return array
     */
    protected function _parseAttributeType($value)
    {
        $attributeType = array(
            'oid'                  => null,
            'name'                 => null,
            'desc'                 => null,
            'obsolete'             => false,
            'sup'                  => null,
            'equality'             => null,
            'ordering'             => null,
            'substr'               => null,
            'syntax'               => null,
            'max-length'           => null,
            'single-value'         => false,
            'collective'           => false,
            'no-user-modification' => false,
            'usage'                => 'userApplications',
            '_string'              => $value,
            '_parents'             => array());

        $tokens = $this->_tokenizeString($value);
        $attributeType['oid'] = array_shift($tokens); // first token is the oid
        $this->_parseLdapSchemaSyntax($attributeType, $tokens);

        if (array_key_exists('syntax', $attributeType)) {
            // get max length from syntax
            if (preg_match('/^(.+){(\d+)}$/', $attributeType['syntax'], $matches)) {
                $attributeType['syntax'] = $matches[1];
                $attributeType['max-length'] = $matches[2];
            }
        }

        $this->_ensureNameAttribute($attributeType);

        return $attributeType;
    }

    /**
     * Loads the object classes
     *
     * @return void
     */
    protected function _loadObjectClasses()
    {
        $this->_objectClasses = array();
        foreach ($this->getAttribute('objectClasses') as $value) {
            $val = $this->_parseObjectClass($value);
            $val = new Zend_Ldap_Node_Schema_ObjectClass_OpenLdap($val);
            $this->_objectClasses[$val->getName()] = $val;
        }
        foreach ($this->_objectClasses as $val) {
            if (count($val->sup) > 0) {
                $this->_resolveInheritance($val, $this->_objectClasses);
            }
            foreach ($val->aliases as $alias) {
                $this->_objectClasses[$alias] = $val;
            }
        }
        ksort($this->_objectClasses, SORT_STRING);
    }

    /**
     * Parses an objectClasses value
     *
     * @param string $value
     * @return array
     */
    protected function _parseObjectClass($value)
    {