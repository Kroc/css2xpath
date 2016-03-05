<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 *      NOTE:   the "phpunit.xml.dist" file in root defines the configuration of PHPUnit, including the autoloading
 *              of our project's PHP source files, which is why you don't see any `include` statements up here
 */
error_reporting( -1 );

require_once 'domserialize.php';

use kroc\css2xpath as _;

/**
 * @coversDefaultClass \kroc\css2xpath
 */
class TranslateQueryTest extends PHPUnit_Framework_TestCase
{       
        private static $TranslatorDefault;
        private static $TestDocument;
        private static $TestDOMXPath;
        
        public static function setUpBeforeClass()
        {
                //create a css2xpath translator with default options
                self::$TranslatorDefault = new _\Translator();
                
                self::$TestDocument = new DOMDocumentSerialize;
                
                //load the XML test document we'll use for confirming that our XPath works the same as the CSS
                self::$TestDocument->load( __DIR__ . DIRECTORY_SEPARATOR . 'test.xml');
                
                self::$TestDOMXPath = new DOMXPath( self::$TestDocument );
        }
        
        /**
         * Rather than just compare CSS > XPath strings, which relies on us formulating the XPath equivalents manually
         * (prone to fault), we apply the XPath string we generate to a test document and check the nodes returned.
         * This way, we only need to confirm that our CSS tests match the expeted elements in the test document
         * (easily and visually done), with which the XPath can be automatically confirmed against
         * 
         * @covers translateQuery
         */
        private function DOMTest ($css, $expected_dom_text)
        {
                fwrite( STDOUT, " css   : $css\n" );
                
                //do the CSS to XPath translation
                $xpath = static::$TranslatorDefault->translateQuery( $css );
                
                fwrite( STDOUT, " xpath : $xpath\n" );
                
                //run the XPath on the document, we should get some nodes back
                $nodes = static::$TestDOMXPath->query ( $xpath );
                //if the result is false, no nodes were found and the test has failed
                if ($nodes === false) return false;
                
                $text = Array();
                foreach ($nodes as $node) array_push ( $text, $node->nodeSerialize() );
                $text = implode( ', ', $text );
                
                fwrite( STDOUT, " text  : $text\n" );
                
                return true;
        }
        
        /**
         * @test
         * @covers translateQuery
         *
         * Walk through the basics of each type of CSS selector.
         * This is intended more as a feature checklist to ensure I haven't forgotten a CSS selector;
         * the other test routines will try and 'break' the parser
         */
        public function conformsToExpectedBehvaiour ()
        {
                /* CSS Type Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                $this->assertTrue(
                        $this->DOMTest( 'test#UniversalType *', '' )
                ,       'CSS Universal Type selector'
                );
                
                return;
                
                $this->assertEquals(
                 /* xpath = */  _\XPATH_AXIS_DESCENDANTSELF . _\XPATH_NODE_ANY
                ,/* css   = */  static::$TranslatorDefault->translateQuery( '*' )
                ,/* error = */  'CSS Universal Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  _\XPATH_AXIS_DESCENDANTSELF . 'e'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e' )
                ,/* error = */  'CSS Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( '*|e' )
                ,/* error = */  'CSS Universal Namespace Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( '*|*' )
                ,/* error = */  'CSS Universal Namespace & Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( '|*' )
                ,/* error = */  'CSS Namespaceless Universal Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( '|e' )
                ,/* error = */  'CSS Namespaceless Type selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a, b' )
                ,/* error = */  'CSS Selector Groups'
                );
                
                /* CSS Attribute Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr]' )
                ,/* error = */  'CSS Attribute selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[|attr]' )
                ,/* error = */  'CSS Namespaceless Attribute selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[*|attr]' )
                ,/* error = */  'CSS Universal Namespace Attribute selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[ns|attr]' )
                ,/* error = */  'CSS Namespace Attribute selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr=value]' )
                ,/* error = */  'CSS Attribute Value selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr~=value]' )
                ,/* error = */  'CSS Attribute Whitespace Value selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr|=value]' )
                ,/* error = */  'CSS Attribute Subcode Value selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr^=value]' )
                ,/* error = */  'CSS Substring Matching Attribute "Begins With" selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr$=value]' )
                ,/* error = */  'CSS Substring Matching Attribute "Ends With" selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e[attr*=value]' )
                ,/* error = */  'CSS Substring Matching Attribute "Contains" selector'
                );
                
                /* CSS Class & ID Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e.class' )
                ,/* error = */  'CSS Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e.class.more' )
                ,/* error = */  'CSS multiple Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e#id' )
                ,/* error = */  'CSS ID selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e#id.class' )
                ,/* error = */  'CSS ID & Class selector'
                );
                
                /* CSS Pseudo-Class Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:link' )
                ,/* error = */  'CSS Dynamic Pseudo-Class Link selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:visited' )
                ,/* error = */  'CSS Dynamic Pseudo-Class Link Visited selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:hover' )
                ,/* error = */  'CSS Dynamic Pseudo-Class User-Action Hover selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:active' )
                ,/* error = */  'CSS Dynamic Pseudo-Class User-Action Active selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:focus' )
                ,/* error = */  'CSS Dynamic Pseudo-Class User-Action Focus selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'a:hover' )
                ,/* error = */  'CSS Target Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:lang(en)' )
                ,/* error = */  'CSS Language Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'i:enabled' )
                ,/* error = */  'CSS UI-Element State Enabled Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'i:disabled' )
                ,/* error = */  'CSS UI-Element State Disabled Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'i:checked' )
                ,/* error = */  'CSS UI-Element State Checked Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'i:indeterminate' )
                ,/* error = */  'CSS UI-Element State Indeterminate Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:root' )
                ,/* error = */  'CSS Root Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(1)' )
                ,/* error = */  'CSS Nth-Child (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(odd)' )
                ,/* error = */  'CSS Nth-Child (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(even)' )
                ,/* error = */  'CSS Nth-Child (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(n)' )
                ,/* error = */  'CSS Nth-Child ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(n+1)' )
                ,/* error = */  'CSS Nth-Child ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n)' )
                ,/* error = */  'CSS Nth-Child ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n+1)' )
                ,/* error = */  'CSS Nth-Child ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n-1)' )
                ,/* error = */  'CSS Nth-Child ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(-2n)' )
                ,/* error = */  'CSS Nth-Child ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(-n)' )
                ,/* error = */  'CSS Nth-Child ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-child(+2)' )
                ,/* error = */  'CSS Nth-Child ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(1)' )
                ,/* error = */  'CSS Nth-Last-Child (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(odd)' )
                ,/* error = */  'CSS Nth-Last-Child (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(even)' )
                ,/* error = */  'CSS Nth-Last-Child (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(n)' )
                ,/* error = */  'CSS Nth-Last-Child ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(n+1)' )
                ,/* error = */  'CSS Nth-Last-Child ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n)' )
                ,/* error = */  'CSS Nth-Last-Child ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n+1)' )
                ,/* error = */  'CSS Nth-Last-Child ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n-1)' )
                ,/* error = */  'CSS Nth-Last-Child ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(-2n)' )
                ,/* error = */  'CSS Nth-Last-Child ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(-n)' )
                ,/* error = */  'CSS Nth-Last-Child ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(+2)' )
                ,/* error = */  'CSS Nth-Last-Child ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(1)' )
                ,/* error = */  'CSS Nth-Of-Type (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(odd)' )
                ,/* error = */  'CSS Nth-Of-Type (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(even)' )
                ,/* error = */  'CSS Nth-Of-Type (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(n)' )
                ,/* error = */  'CSS Nth-Of-Type ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(n+1)' )
                ,/* error = */  'CSS Nth-Of-Type ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n)' )
                ,/* error = */  'CSS Nth-Of-Type ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n+1)' )
                ,/* error = */  'CSS Nth-Of-Type ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n-1)' )
                ,/* error = */  'CSS Nth-Of-Type ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(-2n)' )
                ,/* error = */  'CSS Nth-Of-Type ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(-n)' )
                ,/* error = */  'CSS Nth-Of-Type ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(+2)' )
                ,/* error = */  'CSS Nth-Of-Type ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(1)' )
                ,/* error = */  'CSS Nth-Last-Of-Type (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(odd)' )
                ,/* error = */  'CSS Nth-Last-Of-Type (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(even)' )
                ,/* error = */  'CSS Nth-Last-Of-Type (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(n)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(n+1)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n+1)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n-1)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(-2n)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(-n)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(+2)' )
                ,/* error = */  'CSS Nth-Last-Of-Type ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:first-child' )
                ,/* error = */  'CSS First-Child Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:last-child' )
                ,/* error = */  'CSS Last-Child Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:first-of-type' )
                ,/* error = */  'CSS First-Of-Type Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:last-of-type' )
                ,/* error = */  'CSS Last-Of-Type Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:only-child' )
                ,/* error = */  'CSS Only Child Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:only-of-type' )
                ,/* error = */  'CSS Only-Of-Type Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e:empty' )
                ,/* error = */  'CSS Empty Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'x:not(y)' )
                ,/* error = */  'CSS Negation Pseudo-Class selector'
                );
                
                /* CSS Pseudo-Elements Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e::first-line' )
                ,/* error = */  'CSS First-Line Pseudo Element'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e::first-letter' )
                ,/* error = */  'CSS First-Letter Pseudo Element'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e::before' )
                ,/* error = */  'CSS Before Pseudo Element'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e::after' )
                ,/* error = */  'CSS After Pseudo Element'
                );
                
                /* CSS Combinators:
                 * ------------------------------------------------------------------------------------------------------ */
                
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e f' )
                ,/* error = */  'CSS Descendant Combinator'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e > f' )
                ,/* error = */  'CSS Child Combinator'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e + f' )
                ,/* error = */  'CSS Adjacent Sibling Combinator'
                );
                $this->assertEquals(
                 /* xpath = */  '?'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( 'e ~ f' )
                ,/* error = */  'CSS General Sibling Combinator'
                );
                
                $this->markTestIncomplete();
        }
        
        /**
         * @test
         * @covers translateQuery
         */
        public function handlesWhitespaceCorrectly ()
        {       
                //leading & trailing whitespace must be stripped
                $this->assertEquals(
                 /* xpath = */  _\XPATH_AXIS_DESCENDANTSELF . 'a'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( "\ta\r\n" )
                );
                
                //whitespace between elements constitutes a 'descendant combinator'
                $this->assertEquals(
                 /* xpath = */  _\XPATH_AXIS_DESCENDANTSELF . 'a' . _\XPATH_AXIS_DESCENDANT . 'b'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( "a\r\t\n b" )
                );
                
                //don't confuse whitespace with use of the comma query separator
                $this->assertEquals(
                 /* xpath = */  _\XPATH_AXIS_DESCENDANTSELF . 'a' . _\XPATH_UNION . 'b'
                ,/* css   = */  static::$TranslatorDefault->translateQuery( "a   ,\r\t\n b" )
                );
        }
        
}

?>