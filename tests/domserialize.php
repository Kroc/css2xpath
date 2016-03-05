<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 * In order to make the validating of XPath query results much easier to manage,
 * we serialize the nodes into a simple format we can compare against.
 *
 * A tag begins and ends with curly braces,
 * with the tag name (and namespace if present) within:
 *
 *      {e}, {ns|e}     - equivalent to `<e />` & `<ns:e />`
 *
 * Attributes are space-separated and prefixed with an "@" sigil:
 *
 *      {link @rel stylesheet @href http://camendesign.com }
 *
 * An element (a tag pair) that consists of an opening tag, some inner content (which may contain other tags & elements)
 * and a closing tag, is represented by a colon where the inner content begins, and a semi-colon and curly brace where
 * the element closes:
 * 
 *      {a @href http://camendesign.com : Click Here ;}         =       <a href="http://camendesign.com">Click Here</a>
 *      {b : bold, {i : italic, {u : underline ;} ;} ;}         =       <b>bold, <i>italic, <u>underline</u></i></b>
 *
 */

class DOMDocumentSerialize extends DOMDocument
{
        public function __construct ($version = '1.0', $encoding = 'UTF-8')
        {
                //construct the DOMDocument according to normal behaviour
                parent::__construct( $version, $encoding );
                //now register our class extensions for the various node types
                //(it's theoretically possible for this to fail, so we check for that)
                if (
                        ($this->registerNodeClass( 'DOMElement', 'DOMElementSerialize' ) === false)
                     || ($this->registerNodeClass( 'DOMAttr', 'DOMAttrSerialize' ) === false)
                     || ($this->registerNodeClass( 'DOMText', 'DOMTextSerialize' ) === false)
                
                //if that failed, there's really nothing we can do
                //-- trigger a fatal error and return false
                ) return !trigger_error (
                        'DOMDocumentSerialize could not register its Node classes.'
                ,       E_USER_ERROR
                );
        }
}
 
class DOMElementSerialize extends DOMElement
{
        public function nodeSerialize ()
        {
                $serialized = '{' . $this->nodeName;
                
                //check for attributes...
                if ($this->hasAttributes()) foreach ($this->attributes as $attr) $serialized .= $attr->nodeSerialize ();
                
                if ($this->hasChildNodes()) {
                        
                        $serialized .= ' :';
                        
                        foreach ($this->childNodes as $child) if (!empty(
                                $text = $child->nodeSerialize ()
                        )) $serialized .= " $text";
                        
                        $serialized .= ' ;';
                }
                
                return $serialized . '}';
        }
}

class DOMAttrSerialize extends DOMAttr
{
        public function nodeSerialize ()
        {
                $serialized = ' @' . $this->nodeName;
                
                if (!empty(
                        $text = $this->textContent
                )) $serialized .= " $text";
                
                return $serialized;
        }
}

class DOMTextSerialize extends DOMText
{
        public function nodeSerialize ()
        {
                return trim( $this->textContent );
        }
}

?>