<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 *      NOTE:   the "phpunit.xml.dist" file in root defines the configuration of PHPUnit, including the autoloading
 *              of our project's PHP source files, which is why you don't see any `include` statements up here
 */

/* because programming errors in our testing code would be a very bad thing, we will force PHP to emit all errors
   including simple warnings. since PHPUnit will catch PHP errors, a mistake in the test code will appear as if the
   particular test failed due to the test code linting error. This isn't ideal, but it's better than a typo in the
   test code causing a test to give wrong results */
error_reporting( E_ALL | E_STRICT );

require_once 'css2xpathPHPUnit.php';

/**
 * @coversDefaultClass \kroc\css2xpath
 */
class TranslateQueryTest
        extends CSS2XPath_TestCase //PHPUnit_Framework_TestCase
{
        /**
         * Rather than just compare CSS > XPath strings, which relies on us formulating the XPath equivalents manually
         * (prone to fault), we apply the XPath string we generate to a test document and check the nodes returned.
         * This way, we only need to confirm that our CSS tests match the expeted elements in the test document
         * (easily and visually done), with which the XPath can be automatically confirmed against
         */
        
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
                //'CSS Universal Type selector'
                $this->assertCSS2XPathSerializedXMLEquals(
                        '*'
                ,       '<e : 1st-level <f : 2nd-level <g : 3rd-level >>>'
                ,       '<e : 1st-level <f : 2nd-level <g : 3rd-level >>><f : 2nd-level <g : 3rd-level >><g : 3rd-level >'
                );
                
                //'CSS Type selector'
                $this->assertCSS2XPathSerializedXMLEquals(
                        'f'
                ,       '<e : 1st-level <f : 2nd-level <g : 3rd-level >><f : 2nd-level >>'
                ,       '<f : 2nd-level <g : 3rd-level >><f : 2nd-level >'
                );
/*                $this->assertTrue( $this->XMLTest(
                        '*|e'
                ,       '<e>e0</e><ns1:e xmlns:ns1="urn:ns1">e1</ns1:e><ns2:e xmlns:ns2="urn:ns2">e2</ns2:e>'
                ,       '<e>e0</e>, <ns1:e xmlns:ns1="urn:ns1">e1</ns1:e>, <ns2:e xmlns:ns2="urn:ns2">e2</ns2:e>'
                ),      'CSS Universal Namespace Type selector'
                );
                $this->assertTrue( $this->XMLTest(
                        '*|*'
                ,       '<e>e</e><ns1:f xmlns:ns1="urn:ns1">f</ns1:f><ns2:g xmlns:ns2="urn:ns2">g</ns2:g>'
                ,       '<e>e0</e>, <ns1:f xmlns:ns1="urn:ns1">f</ns1:f>, <ns2:g xmlns:ns2="urn:ns2">g</ns2:g>'
                ),      'CSS Universal Namespace & Type selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( '*|*' )
                ,  'CSS Universal Namespace & Type selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( '|*' )
                ,  'CSS Namespaceless Universal Type selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( '|e' )
                ,  'CSS Namespaceless Type selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a, b' )
                ,  'CSS Selector Groups'
                );
*/
                
                /* CSS Attribute Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
                
/*
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr]' )
                ,  'CSS Attribute selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[|attr]' )
                ,  'CSS Namespaceless Attribute selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[*|attr]' )
                ,  'CSS Universal Namespace Attribute selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[ns|attr]' )
                ,  'CSS Namespace Attribute selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr=value]' )
                ,  'CSS Attribute Value selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr~=value]' )
                ,  'CSS Attribute Whitespace Value selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr|=value]' )
                ,  'CSS Attribute Subcode Value selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr^=value]' )
                ,  'CSS Substring Matching Attribute "Begins With" selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr$=value]' )
                ,  'CSS Substring Matching Attribute "Ends With" selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e[attr*=value]' )
                ,  'CSS Substring Matching Attribute "Contains" selector'
                );
*/
                
                /* CSS Class & ID Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
/*
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e.class' )
                ,  'CSS Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e.class.more' )
                ,  'CSS multiple Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e#id' )
                ,  'CSS ID selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e#id.class' )
                ,  'CSS ID & Class selector'
                );
*/
                /* CSS Pseudo-Class Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
/*
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:link' )
                ,  'CSS Dynamic Pseudo-Class Link selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:visited' )
                ,  'CSS Dynamic Pseudo-Class Link Visited selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:hover' )
                ,  'CSS Dynamic Pseudo-Class User-Action Hover selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:active' )
                ,  'CSS Dynamic Pseudo-Class User-Action Active selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:focus' )
                ,  'CSS Dynamic Pseudo-Class User-Action Focus selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'a:hover' )
                ,  'CSS Target Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:lang(en)' )
                ,  'CSS Language Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'i:enabled' )
                ,  'CSS UI-Element State Enabled Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'i:disabled' )
                ,  'CSS UI-Element State Disabled Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'i:checked' )
                ,  'CSS UI-Element State Checked Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'i:indeterminate' )
                ,  'CSS UI-Element State Indeterminate Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:root' )
                ,  'CSS Root Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(1)' )
                ,  'CSS Nth-Child (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(odd)' )
                ,  'CSS Nth-Child (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(even)' )
                ,  'CSS Nth-Child (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(n)' )
                ,  'CSS Nth-Child ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(n+1)' )
                ,  'CSS Nth-Child ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n)' )
                ,  'CSS Nth-Child ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n+1)' )
                ,  'CSS Nth-Child ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(2n-1)' )
                ,  'CSS Nth-Child ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(-2n)' )
                ,  'CSS Nth-Child ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(-n)' )
                ,  'CSS Nth-Child ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-child(+2)' )
                ,  'CSS Nth-Child ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(1)' )
                ,  'CSS Nth-Last-Child (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(odd)' )
                ,  'CSS Nth-Last-Child (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(even)' )
                ,  'CSS Nth-Last-Child (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(n)' )
                ,  'CSS Nth-Last-Child ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(n+1)' )
                ,  'CSS Nth-Last-Child ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n)' )
                ,  'CSS Nth-Last-Child ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n+1)' )
                ,  'CSS Nth-Last-Child ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(2n-1)' )
                ,  'CSS Nth-Last-Child ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(-2n)' )
                ,  'CSS Nth-Last-Child ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(-n)' )
                ,  'CSS Nth-Last-Child ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-child(+2)' )
                ,  'CSS Nth-Last-Child ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(1)' )
                ,  'CSS Nth-Of-Type (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(odd)' )
                ,  'CSS Nth-Of-Type (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(even)' )
                ,  'CSS Nth-Of-Type (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(n)' )
                ,  'CSS Nth-Of-Type ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(n+1)' )
                ,  'CSS Nth-Of-Type ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n)' )
                ,  'CSS Nth-Of-Type ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n+1)' )
                ,  'CSS Nth-Of-Type ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(2n-1)' )
                ,  'CSS Nth-Of-Type ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(-2n)' )
                ,  'CSS Nth-Of-Type ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(-n)' )
                ,  'CSS Nth-Of-Type ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(+2)' )
                ,  'CSS Nth-Of-Type ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(1)' )
                ,  'CSS Nth-Last-Of-Type (first) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(odd)' )
                ,  'CSS Nth-Last-Of-Type (odd) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(even)' )
                ,  'CSS Nth-Last-Of-Type (even) Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(n)' )
                ,  'CSS Nth-Last-Of-Type ("n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(n+1)' )
                ,  'CSS Nth-Last-Of-Type ("n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n)' )
                ,  'CSS Nth-Last-Of-Type ("2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n+1)' )
                ,  'CSS Nth-Last-Of-Type ("2n+1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(2n-1)' )
                ,  'CSS Nth-Last-Of-Type ("2n-1") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(-2n)' )
                ,  'CSS Nth-Last-Of-Type ("-2n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-last-of-type(-n)' )
                ,  'CSS Nth-Last-Of-Type ("-n") Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:nth-of-type(+2)' )
                ,  'CSS Nth-Last-Of-Type ("+2") Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:first-child' )
                ,  'CSS First-Child Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:last-child' )
                ,  'CSS Last-Child Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:first-of-type' )
                ,  'CSS First-Of-Type Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:last-of-type' )
                ,  'CSS Last-Of-Type Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:only-child' )
                ,  'CSS Only Child Structural Pseudo-Class selector'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:only-of-type' )
                ,  'CSS Only-Of-Type Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e:empty' )
                ,  'CSS Empty Structural Pseudo-Class selector'
                );
                
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'x:not(y)' )
                ,  'CSS Negation Pseudo-Class selector'
                );
*/
                /* CSS Pseudo-Elements Selectors:
                 * ------------------------------------------------------------------------------------------------------ */
/*
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e::first-line' )
                ,  'CSS First-Line Pseudo Element'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e::first-letter' )
                ,  'CSS First-Letter Pseudo Element'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e::before' )
                ,  'CSS Before Pseudo Element'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e::after' )
                ,  'CSS After Pseudo Element'
                );
*/
                /* CSS Combinators:
                 * ------------------------------------------------------------------------------------------------------ */
/*
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e f' )
                ,  'CSS Descendant Combinator'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e > f' )
                ,  'CSS Child Combinator'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e + f' )
                ,  'CSS Adjacent Sibling Combinator'
                );
                $this->assertEquals(
                   '?'
                ,  static::$TranslatorDefault->translateQuery( 'e ~ f' )
                ,  'CSS General Sibling Combinator'
                );
*/
                $this->markTestIncomplete();
        }
        
        /**
         *
         * @covers translateQuery
         */
        public function handlesWhitespaceCorrectly ()
        {
/*
                //leading & trailing whitespace must be stripped
                $this->assertEquals(
                   css2xpath\XPATH_AXIS_DESCENDANTSELF . 'a'
                ,  static::$TranslatorDefault->translateQuery( "\ta\r\n" )
                );
                
                //whitespace between elements constitutes a 'descendant combinator'
                $this->assertEquals(
                   css2xpath\XPATH_AXIS_DESCENDANTSELF . 'a' . css2xpath\XPATH_AXIS_DESCENDANT . 'b'
                ,  static::$TranslatorDefault->translateQuery( "a\r\t\n b" )
                );
                
                //don't confuse whitespace with use of the comma query separator
                $this->assertEquals(
                   css2xpath\XPATH_AXIS_DESCENDANTSELF . 'a' . css2xpath\XPATH_UNION . 'b'
                ,  static::$TranslatorDefault->translateQuery( "a   ,\r\t\n b" )
                );
*/
        }
        
}

?>