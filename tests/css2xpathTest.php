<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 *      NOTE:   the "phpunit.xml.dist" file in root defines the configuration of PHPUnit, including the autoloading
 *              of our project's PHP source files, which is why you don't see any `include` statements up here
 */

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
                ,/* css   = */  \kroc\css2xpath\translateQuery( "\ta\r\n" )
                );
                
                $this->assertEquals(
                 /* xpath = */  'a//b'
                ,/* css   = */  \kroc\css2xpath\translateQuery( 'a b' )
                );
        }
        
        function handlesNamespacesCorrectly ()
        {
                
        }
        
        function handlesUnicodeCorrectly ()
        {
                
        }
}