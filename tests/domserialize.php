<?php
/**
 * Serialize & deserialize XML into a simplified, consistent single string format.
 * I'm not publishing this as its own project as it's too incomplete and unsafe to use anywhere other than for unit testing
 *
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 * DOMSerialize was created to help with unit-testing of XPath queries wherein one would load a short piece of XML, apply
 * an XPath query and compare with another short piece of XML. There are a small number of issues with XML that make this
 * prone to error (particularly useful to avoid when writing tests). XML is dense and difficult to read when written
 * without whitespace, and more difficult to compare directly when using whitespace due to the extra text nodes.
 * Some kind of normalisation would be required.
 *
 * Why not JSON? There's no standard schema for JSON to represent XML and this causes unecessary bloat in dealing with the
 * lack of tag names, attributes &c. leading to deep nesting of JSON objects / arrays.
 *
 * An element begins and ends with angled brackets,
 * with the tag name (and namespace if present) within:
 *
 *      <e><ns:e>     - equivalent to `<e /><ns:e />`
 *
 * Attributes are space-separated and prefixed with an "@" sigil:
 *
 *      <link @rel stylesheet @href http://camendesign.com >
 *
 * An element (a tag pair) that consists of an opening tag, some inner content (which may contain other tags & elements)
 * and a closing tag, is represented by a colon where the inner content begins, and and automatically ends when the
 * element does:
 *
 *      <a @href http://camendesign.com : Click Here >          =       <a href="http://camendesign.com">Click Here</a>
 *      <b : bold, <i : italic, <u : underline >>>              =       <b>bold, <i>italic, <u>underline</u></i></b>
 *
 */

/**
 * The extended `DOMDocument` (XML document class) with our added [de]serialization capabilities
 */
class DOMDocumentSerialize
        extends DOMDocument
{
        /**
         * Creates a new document by deserializing a string into XML
         *
         * This is a static method so that you won't need to instantiate a class beforehand;
         * For example:
         *
         *      $document = DOMDocumentSerialize::deserialize( '<a @href # : Click Here >' );
         *
         * @param       string  $serialized_text        A string containing previously serialized XML
         * @return      DOMDocumentSerialize            An instance of this class containing an XML document tree
         */
        public static function deserialize ($serialized_text)
        {
                //create a new, empty document to begin with
                $DOMDocument = new DOMDocumentSerialize();
                
                //XML documents *must* have only one root element, i.e. `<a>1</a><b>2</b>` would be invalid.
                //we provide our own so that [de]serialized strings do not have to worry about this
                $context_node = $DOMDocument->appendChild(
                        //creates `<DOMDocumentSerialize></DOMDocumentSerialize>`
                        $DOMDocument->createElement( 'DOMDocumentSerialize' )
                );
                
                //any empty string *is* valid input; you might be feeding in strings from external content that you don't
                //control. The result will be an effectively empty document (bar the `<DOMDocumentSerialize>` root element)
                if (empty( $serialized_text = trim( $serialized_text ))) return $DOMDocument;
                
                //begin our recursive descent into the source string
                self::deserializeText( $DOMDocument->documentElement, $serialized_text );
                
                return $DOMDocument;
        }
        
        /**
         * @param       DOMDocumentNode $context_node
         * @param       string          $serialized_text
         * @throws      InvalidArgumentException
         */
        private static function deserializeText ($context_node, $serialized_text)
        {
                $offset = 0;
                /**
                 * For deserialization, this regex looks for an element as a whole. Not everything can be captured and
                 * separated due to the recursive nature of elements, so this regex aims to capture the outer-most element
                 * and the PHP code will re-run the regex on the inner elements, and so forth
                 */
                while (preg_match("/
                        (?(DEFINE)(?'__IDENT'
                                [a-z][a-z0-9]*
                        ))
                        
                        #       capture a text node:
                        (?:
                                (?P<text>
                                        [^<]+
                                )
                                
                        #       or capture an element:
                        |       (?P<element>
                                        <
                                        
                                        # tag name (with optional namespace)
                                        (?:
                                                (?P<namespace> (?P>__IDENT) )
                                                :
                                        )?
                                        (?P<tag>
                                                (?P>__IDENT)
                                        )
                                        
                                        # attributes & namespace defintions (optional)
                                        (?P<attrs>(?:
                                                \s+
                                                [@!] (?:(?P>__IDENT):)? (?P>__IDENT)
                                                (?:
                                                        .(?!= @[a-z] | ;> | :\s )
                                                )*
                                        )+)?
                                        
                                        # element content
                                        (?:
                                                # element content begins with a colon
                                                \s+ : \s+
                                                
                                                # content is anything other than `;>`
                                                (?P<content>
                                                        (?:
                                                        #       handle nested elements
                                                                (?P>element)
                                                        #       allow any other non-closing-element character
                                                        |       [^>]
                                                        )+
                                                )
                                        )?
                                        
                                        >
                                )
                        )
                        /Aisux"
                ,       $serialized_text, $matches, 0, $offset
                )) {
                        //did we match a text node, or an element?
                        if (!empty( $matches['text'] )) {
                                //append the text node
                                $context_node->appendChild(
                                        $context_node->ownerDocument->createTextNode( trim( $matches['text'] ))
                                );
                                
                        } else {
                                //create the new element
                                //@todo Handle element namespace (default?)
                                $new_element = $context_node->ownerDocument->createElement( $matches['tag'] );
                                
                                //@todo extract the attributes / namespaces
                                
                                //if the element has some inner content, process this recursively
                                if (!!$matches['content']) {
                                        //(recurse this function without having to use its name directly)
                                        self::{__FUNCTION__}( $new_element, $matches['content'] );
                                }
                                
                                //attach the element to its parent
                                $context_node->appendChild( $new_element );
                        }
                        
                        //continue processing the serialized text:
                        //note that `preg_match`es offset is in bytes, not Unicode characters,
                        //so we don't use `mb_strlen` here
                        $offset += strlen( $matches[0] );
                }
                
                if ($offset < mb_strlen( $serialized_text )) throw new InvalidArgumentException(
                        "The string passed does not conform to DOMSerialize's serialized XML form."
                );
                
        }
        
        /**
         * @param       string  $version        XML prolog version, best left as default
         * @param       string  $encoding       XML prolog character encoding, defaulting to UTF-8
         */
        public function __construct ($version = '1.0', $encoding = 'UTF-8')
        {
                //construct the DOMDocument according to normal behaviour
                parent::__construct( $version, $encoding );
                
                //now register our class extensions for the various node types
                //(it's theoretically possible for this to fail, so we check for that)
                if (
                        ($this->registerNodeClass( 'DOMElement', 'DOMElementSerialize' ) === false)
                     || ($this->registerNodeClass( 'DOMAttr',    'DOMAttrSerialize' ) === false)
                     || ($this->registerNodeClass( 'DOMText',    'DOMTextSerialize' ) === false)
                
                //if that failed, there's really nothing we can do
                //-- trigger a fatal error and return false
                ) return !trigger_error (
                        'DOMDocumentSerialize could not register its necessary Node classes.'
                ,       E_USER_ERROR
                );
        }
        
        public function serialize ()
        {
                $serialized_text = '';
                if ($this->documentElement->hasChildNodes)
                        $serialized_text = self::serializeDOMNodeList( $this->documentElement->childNodes )
                ;
                return $serialized_text;
        }
        
        public static function serializeDOMNodeList ($DOMNodeList)
        {
                $serialized_text = '';
                //@todo this doesn't check for `DOMElementSerialize`
                foreach ($DOMNodeList as $node)
                        $serialized_text .= $node->nodeSerialize()
                ;
                return $serialized_text;
        }
}
 
class DOMElementSerialize
        extends DOMElement
{
        public function nodeSerialize ()
        {
                $nodeSerialize = '<' . $this->nodeName;
                
                $xquery = new DOMXPath( $this->ownerDocument );
                
                //check for namespace definitions...
                if (
                        ($namespaces = $xquery->query( 'namespace::*[local-name() != "xml"]', $this ))
                     && ($namespaces->length > 0)
                     
                ) foreach ($namespaces as $namespace) {
                        $nodeSerialize .= ' !' . $namespace->localName . ' ' . $namespace->nodeValue;
                }
                
                unset( $xquery );
                
                //check for attributes...
                if ($this->hasAttributes()) foreach ($this->attributes as $attr) $nodeSerialize .= $attr->nodeSerialize ();
                
                if ($this->hasChildNodes()) {
                        
                        $nodeSerialize .= ' : ';
                        
                        foreach ($this->childNodes as $child) if (!empty(
                                trim( $text = $child->nodeSerialize() )
                        )) $nodeSerialize .= "$text";
                }
                
                return $nodeSerialize . '>';
        }
}

class DOMAttrSerialize
        extends DOMAttr
{
        public function nodeSerialize ()
        {
                $nodeSerialize = ' @' . $this->nodeName;
                
                if (!empty( $text = $this->textContent ))
                        $nodeSerialize .= htmlspecialchars( " $text", ENT_QUOTES, 'UTF-8' )
                ;
                
                return $nodeSerialize;
        }
}

class DOMTextSerialize
        extends DOMText
{
        public function nodeSerialize ()
        {
                return trim( htmlspecialchars( $this->textContent, ENT_QUOTES, 'UTF-8' )) . ' ';
        }
}

?>