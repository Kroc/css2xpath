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
 * The extended `DOMDocument` (XML document class) with our added [de]serialization capabilities.
 * It is a PHP requirement that nodes being attached to a document must come from the same document
 * (i.e. the nodes must be created using the original document, and then attached);
 * this is why a subclass of DOMDocument is provided to work through.
 */
class DOMDocumentSerialize
        extends DOMDocument
{
        //to do XPath queries, we'll need a DOMXPath object
        //which has to be bound to the particular Document instance
        /** @var DOMXpath */
        public $xquery;
        
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
                     || ($this->registerNodeClass( 'DOMAttr',    'DOMAttrSerialize'    ) === false)
                     || ($this->registerNodeClass( 'DOMText',    'DOMTextSerialize'    ) === false)
                
                //if that failed, there's really nothing we can do
                //-- trigger a fatal error and return false
                ) return !trigger_error (
                        'DOMDocumentSerialize could not register its necessary Node classes.'
                ,       E_USER_ERROR
                );
                
                //create the DOMXPath object to be able to do XPath queries
                $this->xquery = new DOMXpath( $this );
        }
        
        /**
         * Creates a new document by deserializing a string into XML
         *
         * This is a static method so that you won't need to instantiate a class beforehand;
         * For example:
         *
         *      $document = DOMDocumentSerialize::deserialize( '<a @href # : Click Here >' );
         *
         * @param       string  $serialized_text        A string containing previously serialized XML
         * @param       string  $root_node              A DOMDocument may have only one root element, but the serialized
         *                                              XML form allows multiple elements side-by-side. In the instance of
         *                                              deserializing into a new document, a custom root node *must* be
         *                                              created. You can provide the name of this root element, otherwise
         *                                              it will be `<DOMDocumentSerialize />`
         *
         * @return      DOMDocumentSerialize            An instance of this class containing an XML document tree
         * @throws      \InvalidArgumentException
         */
        public static function deserialize ($serialized_text, $root_node = 'DOMDocumentSerialize')
        {
                //basic validation. an empty `$serialized_text` is valid,
                # and an empty (though with root node) document will be returned
                if (!is_string( $serialized_text )) throw new \InvalidArgumentException (
                        '`$serialized_text` parameter must be a string'
                );
                if (!is_string( $root_node ) || trim( $root_node ) === '') throw new \InvalidArgumentException (
                        '`$root_node` parameter must be a non-empty string'
                );
                
                //create a new, empty document to begin with
                $document = new DOMDocumentSerialize();
                
                //XML documents *must* have only one root element, i.e. `<a>1</a><b>2</b>` would be invalid.
                //we provide our own so that [de]serialized strings do not have to worry about this
                $context_node = $document->appendChild(
                        $document->createElement( trim( $root_node ))
                );
                
                //any empty string *is* valid input; you might be feeding in strings from external content that you don't
                //control. The result will be an effectively empty document (bar the root element)
                if (empty( $serialized_text = trim( $serialized_text ))) return $document;
                
                //begin our recursive descent into the source string
                $context_node->deserialize( $serialized_text );
                
                return $document;
        }
        
        /**
         * @return      string
         */
        public function serialize ()
        {
                return ($this->documentElement->hasChildNodes)
                        ? self::serializeDOMNodeList( $this->documentElement->childNodes )
                        : ''
                ;
        }
        
        /**
         * @return      string
         */
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
        /**
         * Convert the current DOMElement and all its children into a serialized XML string
         *
         * @return      string
         */
        public function nodeSerialize ()
        {
                //open the element with the element name
                /** @todo Element namespace */
                $nodeSerialize = '<' . $this->nodeName;
                
                /* check for namespace definitions on the element...
                 * ------------------------------------------------------------------------------------------------------ */
                if (
                        /* there isn't a DOM API for reading the namespaces defined on an element,
                         * so we have to query the element with XPath. we exclude XML's default namespace 'xml' */
                        /** @todo How does a different default namespace affect this? */
                        ($namespaces = $this->ownerDocument->xquery->query( 'namespace::*[local-name() != "xml"]', $this ))
                     && ($namespaces->length > 0)
                     
                ) foreach ($namespaces as $namespace) {
                        //namespaces are serialized as "!ns uri"
                        $nodeSerialize .= ' !' . $namespace->localName . ' ' . $namespace->nodeValue;
                }
                
                /* check for attributes...
                 * ------------------------------------------------------------------------------------------------------ */
                /* when an attribute has no value, the closing angle-bracket of the element does not require a space
                 * beforehand -- e.g. `<input @disabled>`. when an attribute has a value, there has to be a space between
                 * it and the closing angle-bracket e.g. `<a @href # >` */
                if ($this->hasAttributes()) for (
                        /* this requirement means we need to be aware which is the last attribute,
                         * so we loop using an old-style counter */
                        $i = 0; $i < $this->attributes->length; $i++
                ) {
                        //serializing the attribute node doesn't tell us if it had a value or not...
                        $nodeSerialize .= ' ' . $this->attributes->item( $i )->nodeSerialize ();
                        /* here we check for the last attribute and if it has a value and add a final space,
                         * this isn't necessary if the element has content as that will add a space too */
                        if (    $i == $this->attributes->length - 1
                             && !$this->hasChildNodes()
                             && !empty( $this->attributes->item( $i )->value )
                        ) $nodeSerialize .= ' ';
                }
                
                /* element content...
                 * ----------------------------------------------------------------------------------------------------- */
                if ($this->hasChildNodes()) {
                        
                        $nodeSerialize .= ' : ';
                        
                        foreach ($this->childNodes as $child) if (!empty(
                                trim( $text = $child->nodeSerialize() )
                        )) $nodeSerialize .= "$text";
                }
                
                //close the element
                return $nodeSerialize . '>';
        }
        
        /**
         * @param       string  $serialized_xml
         */
        public function deserialize ($serialized_xml)
        {
                $offset = 0;
                
        getFragment:
                /**
                 * For deserialization, this regex looks for an element as a whole. Not everything can be captured and
                 * separated due to the recursive nature of elements, so this regex aims to capture the outer-most
                 * element and the PHP code will re-run the regex on the inner elements, and so forth
                 */
                if (preg_match( "/
                        (?(DEFINE)(?'__IDENT'
                                [a-z][a-z0-9]*
                        ))
                        
                        #       capture a text node:
                        (?:
                                (?P<text>
                                        [^<>]+
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
                                        
                                        # attributes & namespace definitions (optional)
                                        (?P<attrs>
                                                # the forward-only group here `(?>` reduces the amount of back-tracking;
                                                # if there's multiple attributes we can only capture them as a whole,
                                                # so we're only concerned with moving over the whole group quickly
                                                (?>     \s+
                                                        [@!]                            # attribute or namespace sigil
                                                        (?: (?P>__IDENT) : )?           # attribute namespace
                                                        (?P>__IDENT)                    # attribute name
                                                        (?:                             # optional attribute value
                                                                \s+                     # whitespace before value
                                                                (?:
                                                                #       single-quoted value
                                                                        ' [^']+ '
                                                                #       double-quoted value
                                                                |       \" [^\"]+ \"
                                                                #       unquoted attribute value
                                                                |       [^\s<>]+
                                                                )
                                                        )?
                                                )+
                                        )?
                                        
                                        # optimisation hint: two variable-sized optional groups, one after the other, will
                                        # create a *lot* of back-tracking, slowing down the regex. if we bring the ending
                                        # forward, as an option, we can get the regex to finish earlier and quicker
                                        (?:
                                        #       if there's no element content, then we can match the end here
                                                \s*>
                                        
                                        |       # element content begins with a colon
                                                \s+ : \s+
                                                
                                                # content is anything other than `>`
                                                (?P<content>
                                                        (?>
                                                        #       allow any other non-closing-element character
                                                                [^<>]
                                                        #       handle nested elements
                                                        |       (?P>element)
                                                        )+
                                                )
                                                >
                                        )
                                )
                        )
                        /Aisux"
                ,       $serialized_xml, $match, 0, $offset
                )) {
                        if (!empty( $match['text'] )) {
                                $this->appendChild(
                                        $this->ownerDocument->createTextNode( trim( $match['text'] ))
                                );
                        } else {
                                //create the new element
                                //@todo Handle element namespace (default?)
                                $new_element = $this->ownerDocument->createElement( $match['tag'] );
                                
                                //create the attributes / namespaces
                                if (!empty( $match['attrs'] ))
                                        $new_element->deserializeAttrs( $match['attrs'] )
                                ;
                                
                                //if the element has some inner content, process this recursively
                                if (!empty( $match['content'] ))
                                        $new_element->deserialize ( $match['content'] )
                                ;
                                
                                //attach the element to its parent
                                $this->appendChild( $new_element );
                        }
                        
                        /* continue processing the serialized text:
                         * note that `preg_match`s offset is in bytes, not Unicode characters,
                         * so we don't use `mb_strlen` here */
                        $offset += strlen( $match[0] );
                } else {
                        throw new \InvalidArgumentException(
                                "The string passed does not conform to DOMSerialize's serialized XML form.\n"
                                . "--> $serialized_xml"
                        );
                }
                
                if ($offset < strlen( $serialized_xml )) goto getFragment;
        }
        
        /**
         * Take the attributes and namespaces portion of a serialized-xml string and create the relevent DOMNodes
         *
         * @param       string  $serialized_attrs
         * @throws      \InvalidArgumentException
         */
        private function deserializeAttrs ($serialized_attrs)
        {
                $offset = 0;
                
        getFragment:
                if (preg_match( "/
                        \s*
                        (?:
                        #       A regular attribute name begins with '@'
                                @ (?: (?P<attr_ns> [a-z][a-z0-9]*) : )? (?P<attr_name> [a-z][a-z0-9]* )
                                
                        #       A namespace declaration begins with '!'
                        |       ! (?P<namespace> [a-z][a-z0-9]* )
                        )
                        (?:
                                \s+
                                # This is a rarely used and little known regex feature: branch reset groups!
                                (?|
                                #       single-quoted value:
                                         ' (?P<value> [^']+  )  '
                                #       double-quoted value:
                                |       \" (?P<value> [^\"]+ ) \"
                                #       unquoted value:
                                |          (?P<value> [^\s<>]+ )
                                )
                                \s*
                                $
                        )?
                        /Aisux"
                ,       $serialized_attrs, $match, 0, $offset
                )) {
                        $attr = $this->ownerDocument->createAttribute( $match['attr_name'] );
                        
                        print_r( $match );
                        
                        if (!empty( $match['value'] )) $attr->value = $match['value'];
                        
                        $this->appendChild( $attr );
                        
                        /* continue processing the serialized text:
                         * note that `preg_match`s offset is in bytes, not Unicode characters,
                         * so we don't use `mb_strlen` here */
                        $offset += strlen( $match[0] );
                        
                } else {
                        throw new \InvalidArgumentException(
                                "The string passed does not conform to DOMSerialize's serialized XML form.\n"
                                . "--> $serialized_attrs"
                        );
                };
                
                if ($offset < strlen( $serialized_attrs )) goto getFragment;
        }
}

class DOMAttrSerialize
        extends DOMAttr
{
        /**
         * @return      string
         */
        public function nodeSerialize ()
        {
                $nodeSerialize = '@' . $this->nodeName;
                
                if (!empty( $text = $this->textContent )) {
                        $text = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
                        /** @todo non-space whitespace?? */
                        $nodeSerialize .= (strpos( $text, ' ' ) !== FALSE) ? " '$text'" : " $text";
                }
                
                return $nodeSerialize;
        }
}

class DOMTextSerialize
        extends DOMText
{
        /**
         * @return      string
         */
        public function nodeSerialize ()
        {
                return trim( htmlspecialchars( $this->textContent, ENT_QUOTES, 'UTF-8' )) . ' ';
        }
}

?>