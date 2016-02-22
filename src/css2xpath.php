<?php
/**
 * A simple CSS to XPath translator intended to handle 'unsafe' user input
 *
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 * 
 * @version     0.1.0
 *              ^ ^ ^-- fixes
 *              | '---- features
 *              '------ api
 *
 * @see         https://www.w3.org/TR/css3-selectors/
 *              The CSS Selectors Level 3 standard
 * @see         https://drafts.csswg.org/selectors-4/
 *              The current draft of CSS Selectors Level 4
 * @see         https://www.w3.org/TR/xpath/
 *              The XPath 1.0 standard
 */


/**
 * should you modify this project for your own use (rather than say, contributing back to the original)
 * you MUST change this namespace to use your own name; likewise, check "composer.json"
 */
namespace kroc\css2xpath;


/* Exceptions:
   ---------------------------------------------------------------------------------------------------------------------- */
/**
 * Exception thrown when a query contains CSS we don't recognise
 */
class InvalidCSSException extends \Exception {}
/**
 * Exception thrown when a (valid) CSS fragment is encountered for which this project does not translate;
 * Future versions of this project may implement such translations
 */
class UnimplementedCSSException extends \Exception {}
/**
 * Exception thrown when CSS is encountered for which there is no XPath equivalent possible,
 * e.g. `:hover`
 */
class UntranslatableCSSException extends \Exception {}


/* XPath string fragments:
   ---------------------------------------------------------------------------------------------------------------------- */
// we use these to provide consistent syntax form between the translator and our unit tests

/** Just the universal selector "*" */
const XPATH_NODE_ANY    = '*';
/** Xpath template to select an element that has a particular attribute.
  * `sprintf` is used to populate the data, the first argument is the attribute name */
const XPATH_ATTR        = '[@%s]';
/** XPath template to select an element that has a particular attribute beginning with a particular string.
  * `sprintf` is used to populate the data; the first argument is the attribute name, the second argument is the string */
const XPATH_ATTR_BEGIN  = "[starts-with( @%1s, '%2s' )]";
/** XPath template to select an element that has a particular attribute containing a particular string.
  * `sprintf` is used to populate the data the first argument is the attribute name, the second argument is the string */ 
const XPATH_ATTR_MATCH  = "[contains( @%1s, '%2s' )]";
/** XPath template to select a hyphen-separated list of values (`sprintf` is used to populate the name and value) */
const XPATH_ATTR_DASH   = "[@%1s='%2s' or starts-with( @%1s, concat( '%2s', '-' ) )]";
/** XPath template to select an element based on the attribute value ending with a particular string.
  * `sprintf` is used to populate the data: `%1s` is the attribute name and %2s is the string */
const XPATH_ATTR_END    = "[substring( @%1s, string-length( @%1s ) - string-length( '%2s' ) - 1) = '%2s']";
/** XPath template to select an element based on its attribute (`sprintf` is used to populate the name and value) */
const XPATH_ATTR_EQUAL  = "[@%1s='%2s']";
/** XPath template to select a space-separated list of values (`sprintf` is used to populate the name and value) */
const XPATH_ATTR_SPACE  = "[contains( concat( ' ', normalize-space( @%1s ), ' ' ), ' %2s ' )]";
/** XPath template to select a particular CSS class (`sprintf` is used to insert the class name) */
const XPATH_CLASS       = "[contains( concat( ' ', normalize-space( @class ), ' '), ' %s ' )]";

const XPATH_ELEMENT     = "/child::*[local-name()='%s']";
/** XPath equivalent of CSS's `:empty` psuedo-class */
const XPATH_EMPTY       = '[not(*) and not( normalize-space() )]';
/** XPath template to select a particular CSS ID (`sprintf` is used to insert the ID) */
const XPATH_ID          = "[@id='%s']";
/** XPath's namespace separator */
const XPATH_NAMESPACE   = ':';
/** XPath equivalent to CSS's `::nth-child(even)` psuedo-element selector */
const XPATH_NTH_EVEN    = '[position() mod 2=0 and position()>=0]';
/** XPath equivalent to CSS's `::nth-child(odd)` psuedo-element selector */
const XPATH_NTH_ODD     = '[(count( ./preceding-sibling::* ) + 1) mod 2 = 1]';
/** XPath equivalent to the separation of queries by a comma in CSS */

/** XPath equivalent of CSS's 'child combinator': `>` */
const XPATH_AXIS_CHILD          = '/child::';
/** XPath equivalent of CSS's 'descendant combinator' */
const XPATH_AXIS_DESCENDANT     = '/descendant-or-self::';

const XPATH_AXIS_SELF           = '/self::';
/** XPath equivalent of CSS's 'general sibling combinator': `~` */
const XPATH_AXIS_SIBLING        = '/following-sibling::';

const XPATH_UNION        = ' | ';


/**
 * The brains of the operation. It will encapsulate the settings you choose for CSS to XPath translation.
 *
 * @api
 * @todo        feature : Handle default namespaces
 * @todo        feature : Provide a parameter to set the maximum CSS Selector Level supported / allowed
 */
class Translator
{        
        /* In order for an XPath query to be valid, each 'Location Path' must contain, in order:
           1. An 'Axis', 2. A 'Node Test' and 3. Zero or more 'Predicates'.
           
           The Axis decides which set of Nodes to select --
           - The current Node `self::`, abbreviated as `.`
           - Children of the current Node, `child::` (though this is implied and can be omitted)
           - Descendants (`descendant::`), but most typically `descendant-or-self::` -- abbreviated as `//`
           - The Nodes before or after the current node, `following-sibling::` & `preceding-sibling::`
           - Attributes of the current Node, `attribute::`, abbreviated `@`
        */
        /** @internal */
        const STEP_UNION        = 0;    //I.e. `|`
        /** @internal */
        const STEP_AXIS         = 1;    //I.e. `/descendant-or-self::` or `/following-sibling::`
        /** @internal */
        const STEP_NODE         = 2;    //I.e. `ns:e`, `*`, `ns:*`, `e`
        /** @internal */
        const STEP_PREDICATE    = 3;    //I.e. `[...]`
        
        
        /** @internal If the CSS input is the same, the output XPath will be the same,
          * therefore we cache 'input => output' */
        private $cache = Array();
        
        /** @internal A lookup table to map CSS attribute selector comparisons to the equivalent XPath template.
          * If this were PHP 7 we could make this a constant array */
        private $attrs = Array(
                '='     =>      namespace\XPATH_ATTR_EQUAL
        ,       '~='    =>      namespace\XPATH_ATTR_SPACE
        ,       '|='    =>      namespace\XPATH_ATTR_DASH
        ,       '^='    =>      namespace\XPATH_ATTR_BEGIN
        ,       '$='    =>      namespace\XPATH_ATTR_END
        ,       '*='    =>      namespace\XPATH_ATTR_MATCH
        );
        
        public function __construct ()
        {
                 
        }
        
        /**
         * Converts CSS queries into equivalent XPath (1.0)
         * 
         * @api
         * @param       string  $query          The CSS query to translate into XPath
         * @return      string                  An XPath query string
         *
         * @todo        fix     : Attribute selectors cannot be used without an element in XPath, e.g. `//[@href]`,
         *                        we'll need to insert `*` if no element has yet been specified
         * @todo        feature : Non-standard selectors, e.g. "<" parent, "-" previous sibling, "!=" inequal comparator
         */
        public function translateQuery ($query)
        {
                //return from cache if possible:
                if (in_array( $query, $this->cache)) return $this->cache[$query];
                
                $results[] = Array ( $this::STEP_AXIS, namespace\XPATH_AXIS_DESCENDANT );
                
                //leading & trailing whitespace is stripped so as not to be confused for a CSS 'descendant combinator'
                $query = trim( $query );
                
                while (preg_match( "/
                        (?(DEFINE)(?'__IDENT'
                                # TODO: rework this to not allow Unicode punctuation or whitespace etc.
                                [^\s!\"#$%&'()*+,.\/:;<=>?@\[\]^`{|}~]+
                        ))
                        
                        # so that we don`t confuse one part of the CSS with another,
                        # we always start with the first character in the remaining query
                        ^
                        
                        (?:
                        
                        # 1.    COMMA:
                        #       --------------------------------------------------------------------------------------------
                        #       A comma separates multiple CSS queries. whitespace is ignored before &
                        #       after to not mistake this for CSS descendants, i.e. `a b`
                        
                                \s*
                                (?P<comma> , )
                                \s*
                                
                        # 2.    COMBINATOR:
                        #       --------------------------------------------------------------------------------------------
                        #       Combinators separate sequences within a selector, e.g. `a b + c > d ~ e`.
                        #       care must be taken to not confuse whitespace between `+`, `>` or `~` and a blank space
                        #       between separate selectors (e.g. `a b c`)
                        
                        |       (?P<combinator>
                                        \s* [+>~] \s*                           # either +, > or ~ with optional whitespace
                                |       \s* >> \s*                              # CSS4 descendant combinator syntax
                                |       \s+                                     # CSS1 descendant combinator; whitespace
                                )
                                
                        # 3.    ELEMENT:
                        #       --------------------------------------------------------------------------------------------
                        #       An optional namespace identifier can prefix an element name. For CSS, this can also be
                        #       a universal namespace identifier '*'
                                
                        |       (?:
                                        (?P<namespace>
                                                (?P>__IDENT)                    # a specific namespace identitifer,
                                        |       \*                              # or universal namespace identifier
                                        )?
                                        \|                                      # the namespace bar
                                )?
                                (?P<element>
                                        (?P>__IDENT)
                                |       \*
                                )
                                
                        # 4.    ID:
                        #       --------------------------------------------------------------------------------------------
                        
                        |       \# (?P<hash>    (?P>__IDENT) )
                        
                        # 5.    CLASS:
                        #       --------------------------------------------------------------------------------------------
                        
                        |       \. (?P<class>   (?P>__IDENT) )
                                
                        # 6.    PSUEDO CLASS or ELEMENT:
                        #       --------------------------------------------------------------------------------------------
                        #       For pragmatic reasons, either single or double colon are allowed for psuedo classes and
                        #       elements; the user input might have it wrong and there`s no point failing for such a minor
                        #       transgression
                        
                        |       ::?
                                (?P<pseudo>
                                        
                                #       FIRST, LAST & ONLY:
                                #       ~~~~~~~~~~~~~~~~~~~
                                #       ::first-child   ::last-child    ::only-child
                                #       ::first-of-type ::last-of-type  ::only-of-type
                                        
                                        (?:first|last|only)-(?:child|-of-type)
                                        
                                #       N'TH ELEMENT:
                                #       ~~~~~~~~~~~~~
                                
                                |       (?P<nthof>
                                                nth-(?:last-)?(?:child|of-type)
                                        )
                                        \(
                                                \s*
                                                (?P<nth>
                                                #       A meaningless expression that just selects all elements;
                                                #       for the XPath we can omit the psuedo-element expression entirely
                                                        n
                                                
                                                #       Selects even indexed elements, equivalent to '2n'
                                                |       even
                                                
                                                #       Selects odd indexed elements, equivalent to '2n+1'
                                                |       odd
                                                
                                                #       The formula 'an+b'
                                                |       (?|
                                                                (?P<a> [-+]? \d*)?
                                                                \s*
                                                                (?P<n> n )
                                                                \s*
                                                                (?P<b> [-+]? \d+)?
                                                        
                                                        #       Selects exaclty the element index given in <a>, no 'n' 
                                                        
                                                        |       (?P<a> [-+]? \d+)
                                                        )
                                                )
                                                \s*
                                        \)
                                        
                                #       NOT PSUEDO CLASS:
                                #       ~~~~~~~~~~~~~~~~~
                                #       It's important to note that CSS is too complicated to validate entirely the
                                #       `not` psuedo class parameter here. We capture it roughly, and then feed it
                                #       back into this function again to process it in detail
                                
                                |       not
                                        \(
                                                (?P<not>
                                                        # Pair brackets & quotation marks so that we get the correct
                                                        # closing bracket for the current `not` psuedo class
                                                        (?:
                                                                \" [^\"]* \"
                                                        |        ' [^']*   '
                                                        |       \( [^\(]* \)
                                                        |       [^\"'\(]+
                                                        )*
                                                )
                                        \)
                                        
                                #       LANGUAGE:
                                #       ~~~~~~~~~
                                        
                                |       lang
                                        \(
                                                (?<lang>
                                                        [a-z0-9]+ (?:-(?P>lang))
                                                )
                                        \)
                                        
                                |       (?:dis|e)nabled
                                |       checked
                                |       indeterminate
                                |       root
                                |       empty
                                
                                #       removed from CSS3
                                
                                |       contains
                                |       selection
                                
                                #       intangible
                                
                                |       link
                                |       visited
                                |       hover
                                |       active
                                |       focus
                                |       target
                                
                                |       first-line
                                |       first-letter
                                
                                |       before
                                |       after
                                
                                )
                                
                        # 7.    ATTRIBUTE:
                        #       --------------------------------------------------------------------------------------------
                        
                        |       \[
                                \s*
                                (?P<attr>
                                        (?:
                                                (?P>namespace)?
                                                \|
                                        )?
                                        (?P>element)
                                )
                                (?:
                                        \s*
                                        (?P<comparator> [~|^$*]? = )
                                        \s*
                                        (?P<quote> [\"']? )
                                                (?P<value> [^\]]+ )
                                        (?P=quote)
                                )?
                                \s*
                                \]
                        
                        )
                        
                        # REGEX FLAGS:
                        # i - Ignore case
                        # s - Whitespace includes new-lines
                        #     (we allow this in case the CSS given to us is from an unknoen external source)
                        # u - Handle Unicode correctly
                        # x - Allow this freeform regex style with whitespace and comments
                        #     (rather than this regex string having to be on a single line)
                        
                        /isux"
                      , mb_substr( $query, $offset )
                      , $match
                )) {
                        switch (true) {
                                /*                                      CSS             XPATH
                                        any element, no namespace       *
                                        any element, any namespace      *|*               *
                                        any namespace                   *|e               *[local-name()='e']
                                        element not in a namespace        e               e
                                                                         |e               e
                                        must be in a namespace          x|e             x:e
                                        namespace, any element          x|*             x:*

                                */
                                //element (and namespace)
                                //..........................................................................................
                                case    !empty( $match['element'] ):
                                        
                                        $results[] = Array (
                                                $this::STEP_NODE
                                        ,       ($match['element'] == '*')
                                                ? (
                                                        ($match['namespace'] == '*')
                                                        //any element, any namespace
                                                        ? namespace\XPATH_NODE_ANY
                                                        //any element of a specific namespace:
                                                        : (
                                                                !empty( $match['namespace'] )
                                                                ? $match['namespace'] . namespace\XPATH_NAMESPACE : ''
                                                        ) . namespace\XPATH_NODE_ANY
                                                ) : (
                                                        ($match['namespace'] == '*')
                                                        //XPath 1.0 does not support the notion of a namespace-wildcard,
                                                        //i.e. `*:`, instead we look at element names ignoring namespace
                                                        ? sprintf ( namespace\XPATH_ELEMENT, $match['element'] )
                                                        //in XPath, namespaces are separated by colon, not bar;
                                                        //include the namespace in the XPath only if provided from the CSS
                                                        : (
                                                                !empty( $match['namespace'] )
                                                                ? $match['namespace'] . namespace\XPATH_NAMESPACE : ''
                                                        ) . $match['element']
                                                )
                                        );
                                        break;

                                //multiple CSS queries are separated by commas
                                //..........................................................................................
                                case    !empty( $match['comma'] ):
                                        //the XPath equivalent is the bar
                                        $results[] = Array ( $this::STEP_UNION, namespace\XPATH_UNION );
                                        break;
                                
                                //combinator between sequences, e.g. `a + b`, `a > b`, 'a ~ b' and also 'a b' (space)
                                //..........................................................................................
                                case    !empty( $match['combinator'] ):
                                        //match up the CSS combinator with the XPath equivalent
                                        switch (trim( $match['combinator'] )) {
                                                case '+':
                                                        array_push( $results
                                                        //`/following-sibling::*[1]/self::`
                                                        ,       Array ( $this::STEP_AXIS, namespace\XPATH_AXIS_FOLLOWING )
                                                        ,       Array ( $this::STEP_NODE, namespace\XPATH_NODE_ANY )
                                                        ,       Array ( $this::STEP_PREDICATE, '[1]' )
                                                        ,       Array ( $this::STEP_AXIS, namespace\XPATH_AXIS_SELF )
                                                        );
                                                        break;
                                                case '>':
                                                        $results[] = Array (
                                                                $this::STEP_AXIS
                                                        ,       namespace\XPATH_AXIS_CHILD
                                                        );
                                                        break;
                                                case '~':
                                                        $results[] = Array (
                                                                $this::STEP_AXIS
                                                        ,       namespace\XPATH_SIBLING
                                                        );
                                                        break;
                                                default:
                                                        //just whitespace? (or `>>`) use the XPath descendant combinator
                                                        $results[] = Array (
                                                                $this::STEP_AXIS
                                                        ,       namespace\XPATH_AXIS_DESCENDANT
                                                        );
                                        }
                                        break;
                                
                                //ID selector
                                //..........................................................................................
                                case    !empty( $match['hash'] ):
                                        //apply the captured ID to the XPath template
                                        $results[] = Array (
                                                $this::STEP_PREDICATE
                                        ,       sprintf( namespace\XPATH_ID, $match['hash'] )
                                        );
                                        break;
                                
                                ///class selector
                                //..........................................................................................
                                case    !empty( $match['class'] ):
                                        //apply the captured class name to the XPath template
                                        $results[] = Array (
                                                $this::STEP_PREDICATE
                                        ,       sprintf( namespace\XPATH_CLASS, $match['class'] )
                                        );
                                        break;
                                        
                                //attribute selector
                                //..........................................................................................
                                case    !empty( $match['attr'] ):
                                        //a CSS attribute selector can be just an atrtibute, or an attribute test
                                        //(with a condition). if there's no condition, we can move ahead quickly
                                        if (empty( $match['comparator'] )) {
                                                //produce the simple XPath attribute test
                                                $results[] = Array (
                                                        $this::STEP_PREDICATE
                                                ,       sprintf( namespace\XPATH_ATTR, $match['attr'] )
                                                );
                                                break;
                                        }
                                        //get the relevant template and insert the attribute and comparison value:
                                        $results[] = Array (
                                                $this::STEP_PREDICATE
                                        ,       sprintf(
                                                        $this->attrs[$match['comparator']]
                                                ,       $match['attr']
                                                        //the comparison value provided by the user could be within single,
                                                        //double, or no quotes, and could likewise contain single or double
                                                        //quotes. the regex strips the outer quotes (if present), and we
                                                        //convert any internal quotes into safe XML entities
                                                ,       htmlspecialchars( $match['value'], ENT_QUOTES, 'UTF-8' )
                                                )
                                        );
                                        break;
                                
                                //..........................................................................................
                                case    $match['nthof'] == 'nth-child':
                                        
                                        switch ($match['nth']) {
                                                //`::nth-child(n)` simply selects all child elements anyway
                                                case    'n':
                                                        $results[] = Array ( $this::STEP_AXIS, namespace\XPATH_CHILD );
                                                        break;
                                                
                                                case    'odd':
                                                        $results[] = Array ( $this::STEP_PREDICATE, namespace\XPATH_NTH_ODD );
                                                        break;
                                                
                                                case    'even':
                                                        $temp = array_pop( $results );
                                                        array_push ( $results
                                                        ,       Array ( $this::STEP_NODE, namespace\XPATH_NODE_ANY )
                                                        ,       Array ( $this::STEP_PREDICATE, namespace\XPATH_NTH_EVEN )
                                                        ,       Array ( $this::STEP_AXIS, namespace\XPATH_AXIS_SELF )
                                                        ,       $temp
                                                        );
                                                        break;
                                                
                                                default:
                                                        /** @todo Implement `an+b` nth-child **/
                                                        throw new UnimplementedCSSException();
                                        }
                                        break;
                                
                                //..........................................................................................
                                case    $match['nthof'] == 'nth-last-child':
                                        /** @todo Implement `::nth-last-child` */
                                        throw new UnimplementedCSSException();
                                        break;
                                
                                //..........................................................................................
                                case    $match['nthof'] == 'nth-of-type':
                                        /** @todo Implement `::nth-of-type` */
                                        throw new UnimplementedCSSException();
                                        break;
                                
                                //..........................................................................................
                                case    $match['nthof'] == 'nth-last-of-type':
                                        /** @todo Implement `::nth-last-of-type` */
                                        throw new UnimplementedCSSException();
                                        break;
                                
                                //psuedo class / element
                                //..........................................................................................
                                case    !empty( $match['pseudo'] ):
                                        switch ($match['pseudo']) {
                                                case    'empty':
                                                        $results[] = Array ( $this::STEP_PREDICATE, namespace\XPATH_EMPTY );
                                                        break;
                                                
                                                default:
                                                        throw new UnimplementedCSSException();
                                        }
                                        break;

                                //..........................................................................................
                                default:
                                        throw new UnimplementedCSSException();
                        }
                        $offset += mb_strlen( $match[0] );
                };
                
                //when all parts of the CSS query are valid, all fragments will have been processed in the loop above
                //and the `$offset` index will be at the end of the CSS query. If the regex does not match a fragment,
                //(i.e. invalid CSS), then the offset index will not have progressed to the end of the string and we
                //know that some invalid CSS was encountered
                if ($offset <= mb_strlen( $match[0] )) {
                        throw new InvalidCSSException( 'Invalid CSS fragment within CSS query.' );
                }
                
                foreach ($results as $xpath) $result .= $xpath[1];
                return  $result;
        }
}

?>