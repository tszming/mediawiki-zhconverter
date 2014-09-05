<?php

# MediaWiki-ZhConverter
# Copyright (C) 2008 tszming (tszming@gmail.com)
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
# http://www.gnu.org/copyleft/gpl.html

/**
 * MediaWiki-ZhConverter
 *
 * A very simple helper class for calling MediaWiki's internal converter to do the
 * conversion for us. This helper work with both PHP4/PHP5 version of MediaWiki
 * and should work under both Windows and Linux.
 *
 * Please note that this simple library is not optimized for speed, but designed
 * for the ease of maintenance, I didn't modify a single line of codes in the original
 * MediaWiki, you have been warned for this.
 *
 * Some information about Chinese conversion you might be interested:
 *
 * For the Chinese conversion table used in MediaWiki, you can refer to:
 * 		MEDIAWIKI_PATH/includes/ZhConversion.php
 *
 * It should be automatically build by a script:
 * 		MEDIAWIKI_PATH/includes/zhtable/Makefile
 *
 * Latest Chinese mappings can be found online, depending on your needs, you might
 * want to merge these mappings back to the ZhConversion.php:
 *
 *		1. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-hans
 *		2. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-hant
 *		3. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-cn
 *		4. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-sg
 *		5. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-tw
 *		6. http://zh.wikipedia.org/w/index.php?title=MediaWiki:Conversiontable/zh-hk
 *
 * @author tszming (tszming@gmail.com)
 * @version 1.0.1
 *
 */

if (!defined("MEDIAWIKI_PATH")) {
    echo "Constant '<b>MEDIAWIKI_PATH</b>' must be defined!";
    die();
}

if (!file_exists(MEDIAWIKI_PATH)) {
    echo "MediaWiki not found on : " . MEDIAWIKI_PATH;
    die();
}

/* Add to the include path */
set_include_path(get_include_path() . PATH_SEPARATOR . MEDIAWIKI_PATH);
$IP = MEDIAWIKI_PATH;

/**
 * Needed some dummy classes/functions/variables in order to cheat the MediaWiki.
 **/

define('MEDIAWIKI', true);

class MagicWord {

    function &get( $id ) {

        static $instance;
        if (!isset($instance)) {
            $instance = new MagicWord();
        }
        return $instance;
    }

    function matchAndRemove() {    return false;    }

    function matchStart() {		return false;}
}

function wfProfileIn() {    return false;    }

function wfProfileOut() {    return false; }

class WebRequest {

    function getText() {	return false;	}
}

class FakeMemCachedClient {

    function add ($key, $val, $exp = 0) { return true; }
    function decr ($key, $amt=1) { return null; }
    function delete ($key, $time = 0) { return false; }
    function disconnect_all () { }
    function enable_compress ($enable) { }
    function forget_dead_hosts () { }
    function get ($key) { return null; }
    function get_multi ($keys) { return array_pad(array(), count($keys), null); }
    function incr ($key, $amt=1) { return null; }
    function replace ($key, $value, $exp=0) { return false; }
    function run_command ($sock, $cmd) { return null; }
    function set ($key, $value, $exp=0){ return true; }
    function set_compress_threshold ($thresh){ }
    function set_debug ($dbg) { }
    function set_servers ($list) { }
}

$wgRequest;    $wgMemc;

class MediaWikiZhConverter {

    var $_language;
    var $_converter;

    /**
     * Singleton to make sure only ONE object is initialized at anytime.
     */
    static function &getConverter() {
        static $instance;

        if (! isset($instance) ) {

            /* Initialize some global variables needed */
            global $wgRequest, $wgMemc;
            global $wgLocalisationCacheConf, $wgDisabledVariants, $wgExtraLanguageNames;
            global $wgLangConvMemc, $wgMessageCacheType, $wgObjectCaches;
            $wgRequest = new WebRequest();
            $wgMemc = new FakeMemCachedClient;

            $wgLocalisationCacheConf['class'] = 'FakeMemCachedClient';
            $wgLocalisationCacheConf['storeClass'] = 'LCStore_Null';
            $wgDisabledVariants = array();
            $wgExtraLanguageNames = array();
            $wgLangConvMemc = new FakeMemCachedClient;
            $wgObjectCaches = array(
                'FAKE' => array( 'class' => 'FakeMemCachedClient' ),
            );
            $wgMessageCacheType = 'FAKE';

            require_once "includes/GlobalFunctions.php";
            require_once "includes/AutoLoader.php";

            /* Switch for PHP4 and PHP5 version of MediaWiki */
            if (file_exists( MEDIAWIKI_PATH . "languages/LanguageZh.php")) {
                require_once "languages/LanguageZh.php";
            } else {
                require_once "languages/classes/LanguageZh.php";
                require_once "includes/utils/StringUtils.php";
            }

            $instance = new MediaWikiZhConverter();
            $instance->_language = new LanguageZh();
            $instance->_converter = $instance->_language->mConverter;
        }

        return $instance;
    }

    /**
     * Convert in action.
     *
     * @param string $str		text to be converted
     * @param string $variant	target language code, e.g. zh, zh-cn, zh-tw, zh-sg & zh-hk
     *
     * @return string the converted text
     */
    static function convert($str, $variant) {

        $converter =& MediaWikiZhConverter::getConverter();

        return $converter->_converter->translate( $str, $variant );
    }

    /**
     * Get all variants available.
     *
     * @return string All available variants, e.g. zh, zh-cn, zh-tw, zh-sg & zh-hk
     */
    function getVariants() {

   	    $converter =& MediaWikiZhConverter::getConverter();

        return $converter->_converter->getVariants();
    }
}

?>
