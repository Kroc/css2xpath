<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 *      NOTE:   the "phpunit.xml.dist" file in root defines the configuration of PHPUnit, including the autoloading
 *              of our project's PHP source files, which is why you don't see any `include` statements up here
 */ 

use kroc\css2xpath as css2xpath;

/**
 * @coversDefaultClass \kroc\css2xpath
 */
class css2xpathTest extends PHPUnit_Framework_TestCase
{       
        /**
         * @test
         * @covers translateQuery
         */
        function handlesWhitespaceCorrectly ()
        {       
                //leading & trailing whitespace must be stripped
                $this->assertEquals(
                 /* xpath = */  'a'
                ,/* css   = */  css2xpath\translateQuery( "\ta\r\n" )
                );
                
                //whitespace between elements constitutes a 'descendent combinator'
                $this->assertEquals(
                 /* xpath = */  'a' . css2xpath\XPATH_DESCENDANT . 'b'
                ,/* css   = */  css2xpath\translateQuery( "a\r\t\n b" )
                );
                
                //don't confuse whitespace with use of the comma query separator
                $this->assertEquals(
                 /* xpath = */  'a' . css2xpath\XPATH_OR . 'b'
                ,/* css   = */  css2xpath\translateQuery( "a   ,\r\t\n b" )
                );
        }
        
        function handlesNamespacesCorrectly ()
        {
                
        }
        
        function handlesUnicodeCorrectly ()
        {
                
        }
}

?>