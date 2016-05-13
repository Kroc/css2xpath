<?php
/**
 * This file creates a custom PHPUnit assertion that tests a CSS selector against some XML and checks the elements selected
 * were correct. That is, it translates a CSS selector into XPath, applies it to an XML document -- provided in serialized
 * form -- and compares the result with an expected serialized XML form.
 *
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 */

//library to de/serialize XML with a simple form
require_once 'domserialize.php';

//shorthand the namespace
use kroc\css2xpath as css2xpath;

/**
 * This is the assertion where the test is performed
 */
abstract class SerializedXMLAssert
        extends PHPUnit_Framework_Assert
{
        //this will be the CSS2XPath translator
        private static $Translator;
        
        //the test case will call this method to initialise the CSS2XPath translator
        public static function setUpBeforeClass ()
        {
                //create a css2xpath translator
                static::$Translator = new css2xpath\Translator(
                        css2xpath\XPATH_AXIS_DESCENDANT
                );
        }
        
        /**
         * @param       string  $css            CSS selector to convert to XML and test
         * @param       string  $test_sxml      A test XML document, in serialized form
         * @param       string  $expected_sxml  Serialized representation of the [expected] result of the XPath query
         */
        public static function assertCSS2XPathSerializedXMLEquals ($css, $test_sxml, $expected_sxml)
        {
                //convert the serialised XML into a proper XML document tree
                $document = DOMDocumentSerialize::deserialize( $test_sxml );
                
                //do the CSS to XPath translation
                $xpath = static::$Translator->translateQuery( $css );
                
                //apply the XPath; this will give us a list of nodes it selected
                $xquery = new DOMXPath( $document );
                $nodes = $xquery->query(
                        '//DOMDocumentSerialize' . $xpath
                ,       $document->documentElement
                );
                
                //re-serialize the XPath result
                $result_sxml = DOMDocumentSerialize::serializeDOMNodeList( $nodes );
                //clean-up
                unset( $nodes, $xquery, $document );
                
                self::assertEquals(
                        $expected_sxml
                ,       $result_sxml
                ,       "Failed to assert that the CSS to XPath translation produced the expected behaviour\n"
                        . "xpath : $xpath"
                );
        }
}

/**
 * We cannot simply inject our custom assertion into PHPUnit's regular `PHPUnit_Framework_TestCase` that you use to write
 * tests. Instead write your tests using this class and it'll include the new assertion.
 */
abstract class CSS2XPath_TestCase
        extends PHPUnit_Framework_TestCase
{
        /**
         * When the test case is created, set up our custom assertion
         */
        public static function setUpBeforeClass ()
        {
                SerializedXMLAssert::setUpBeforeClass();
        }
        
        /**
         * This is the custom assertion your test case sees
         *
         * @param       string  $css            CSS selector to convert to XML and test
         * @param       string  $test_sxml      A test XML document, in serialized form
         * @param       string  $expected_sxml  Serialized representation of the [expected] result of the XPath query
         */
        public function assertCSS2XPathSerializedXMLEquals ($css, $test_sxml, $expected_sxml)
        {
                //we pass this up as the `PHPUnit_Framework_Assert` class has the funcationality we need
                return SerializedXMLAssert::assertCSS2XPathSerializedXMLEquals( $css, $test_sxml, $expected_sxml );
        }
}

?>