<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 *      NOTE:   the "phpunit.xml.dist" file in root defines the configuration of PHPUnit, including the autoloading
 *              of our project's PHP source files, which is why you don't see any `include` statements up here
 */ 

use kroc\css2xpath as _;

/**
 * @coversDefaultClass \kroc\css2xpath
 */
class TranslateQueryTest extends PHPUnit_Framework_TestCase
{       
        protected $TranslatorDefault;
        protected $TestDocument;
        
        protected function setUp()
        {
                $this->TranslatorDefault = new _\Translator();
        }
        
        protected function tearDown()
        {
                unset( $this->TranslatorDefault );
        }
        
        /**
         * @test
         * @covers translateQuery
         */
        public function handlesWhitespaceCorrectly ()
        {       
                //leading & trailing whitespace must be stripped
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . 'a'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( "\ta\r\n" )
                );
                
                //whitespace between elements constitutes a 'descendant combinator'
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . 'a' . _\XPATH_DESCENDANT . 'b'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( "a\r\t\n b" )
                );
                
                //don't confuse whitespace with use of the comma query separator
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . 'a' . _\XPATH_OR . 'b'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( "a   ,\r\t\n b" )
                );
        }
        
        /**
         * @test
         * @covers translateQuery
         */
        public function handlesAttributeSelectorsCorrectly ()
        {       
                //straight-forward expected behaviour test
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . 'a[@href]'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( 'a[href]' )
                );
                
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . '[@href][@style]'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( '[href][style]' )
                );
                
        }
        
        /**
         * @test
         * @covers translateQuery
         */
        public function handlesPseduoElementsCorrectly ()
        {
                $this->assertEquals(
                 /* xpath = */  _\XPATH_DESCENDANT . 'a[@href]'
                ,/* css   = */  $this->TranslatorDefault->translateQuery( '*::nth-child(odd)' )
                );
                
        }
}

?>