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

/** XPath equivalent to the CSS 'adjacent sibling combinator': `+` */
const XPATH_ADJACENT    = '/following-sibling::*[1]/self:';
/** Just the universal selector "*" */
const XPATH_ANY         = '*';
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
/** XPath equivalent of CSS's 'child combinator': `>` */
const XPATH_CHILD       = '/';
/** XPath template to select a particular CSS class (`sprintf` is used to insert the class name) */
const XPATH_CLASS       = "[contains( concat( ' ', normalize-space( @class ), ' '), ' %s ' )]";
/** XPath equivalent of CSS's 'descendant combinator' */
const XPATH_DESCENDANT  = '//';

const XPATH_ELEMENT     = "*[local-name()='%s']";
/** XPath equivalent of CSS's `:empty` psuedo-selector */
const XPATH_EMPTY       = '[not(*) and not( normalize-space() )]';
/** XPath template to select a particular CSS ID (`sprintf` is used to insert the ID) */
const XPATH_ID          = "[@id='%s']";
/** XPath's namespace separator */
const XPATH_NAMESPACE   = ':';
/** XPath equivalent to the separation of queries by a comma in CSS */
const XPATH_OR          = ' | ';
/** XPath equivalent of CSS's 'general sibling combinator': `~` */
const XPATH_SIBLING     = '/following-sibling::';

/**
 * Converts CSS queries into equivalent XPath (1.0)
 * 
 * @api
 * @param       string  $query          The CSS query to translate into XPath
 * @return      string                  An XPath query string
 *
 * @todo        feature : Handle default namespaces
 * @todo        feature : Provide a parameter to set the maximum CSS Selector Level supported / allowed
 */
function translateQuery ($query)
{
        //leading & trailing whitespace is stripped so as not to be confused for a CSS 'descendant combinator'
        $query = trim( $query );
        
        while (preg_match(
<<<'REGEX'
        /
                (?(DEFINE)(?'__IDENT'
                        # TODO: rework this to not allow Unicode punctuation or whitespace etc.
                        [^\s!"#$%&'()*+,.\/:;<=>?@\[\]^`{|}~]+
                ))
                
                # so that we don`t confuse one part of the CSS with another,
                # we always start with the first character in the remaining query
                ^
                
                (?P<fragment>
                
                # 1.    COMMA:
                #       ----------------------------------------------------------------------------------------------------
                #       A comma separates multiple CSS queries. whitespace is ignored before &
                #       after to not mistake this for CSS descendants, i.e. "a b"
                
                        \s*
                        (?P<comma> , )
                        \s*
                        
                # 2.    COMBINATOR:
                #       ----------------------------------------------------------------------------------------------------
                #       Combinators separate sequences within a selector, e.g. "a b + c > d ~ e".
                #       care must be taken to not confuse whitespace between "+", ">" or "~" and a blank space
                #       between separate selectors (e.g. "a b c")
                
                |       (?P<combinator>
                                \s* [+>~] \s*                           # either +, > or ~ with optional whitespace
                        |       \s+                                     # or, just whitespace between selectors
                        )
                        
                # 3.    ELEMENT:
                #       ----------------------------------------------------------------------------------------------------
                        
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
                #       ----------------------------------------------------------------------------------------------------
                
                |       \# (?P<hash>    (?P>__IDENT) )
                
                # 5.    CLASS:
                #       ----------------------------------------------------------------------------------------------------
                
                |       \. (?P<class>   (?P>__IDENT) )
                        
                # 6.    PSUEDO CLASS or ELEMENT:
                #       ----------------------------------------------------------------------------------------------------
                
                |       ::?
                        (?P<pseudo>
                                
                                (?:first|last|only)-(?:child|-of-type)
                                
                        |       (?P<nth>
                                        nth-(?:last-)?(?:child|of-type)
                                )
                                \(
                                        (?:
                                                even
                                        |       odd
                                        )
                                \)
                                
                        |       not
                                
                        |       lang
                                
                        |       (?:dis|e)nabled
                        |       checked
                        |       root
                        |       empty
                        |       first-line
                        |       first-letter
                        )
                        
                # 7.    ATTRIBUTE:
                #       ----------------------------------------------------------------------------------------------------
                
                |       \[
                        \s*
                        (?P<attribute>
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
                                        (?P<quote> ["']? )
                                                (?P<value> [^\]]+ )
                                        (?P=quote)
                                )?
                        )
                        \s*
                        \]
                
                )
        /isux
REGEX
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
                        //..................................................................................................
                        case    !empty( $match['element'] ):

                                $result .= ($match['element'] == '*')
                                ? (
                                        ($match['namespace'] == '*')
                                        //any element, any namespace
                                        ? XPATH_ANY
                                        //any element of a specific namespace:
                                        : $match['namespace'] . XPATH_NAMESPACE . XPATH_ANY
                                ) : (
                                        ($match['namespace'] == '*')
                                        //XPath 1.0 does not support the notion of a namespace-wildcard,
                                        //i.e. `*:`, instead we look at element names without the namespace
                                        ? sprintf ( XPATH_ELEMENT, $match['element'] )
                                        //in XPath, namespaces are separated by colon, not bar;
                                        //include the namespace in the XPath only if provided from the CSS
                                        : (!empty( $match['namespace'] ) ? $match['namespace'] . XPATH_NAMESPACE : '')
                                          . $match['element']
                                );
                                break;

                        //multiple CSS queries are separated by commas
                        //..................................................................................................
                        case    !empty( $match['comma'] ):
                                //the XPath equivalent is the bar
                                $result .= XPATH_OR;
                                break;
                        
                        //combinator between sequences, e.g. `a + b`, `a > b`, 'a ~ b' and also 'a b' (space)
                        //..................................................................................................
                        case    !empty( $match['combinator'] ):
                                switch (trim( $match['combinator'] )) {
                                        case '+': $result .= XPATH_ADJACENT;    break;
                                        case '>': $result .= XPATH_CHILD;       break;
                                        case '~': $result .= XPATH_SIBLING;     break;
                                        //just whitespace? use the descendant combinator
                                        default:  $result .= XPATH_DESCENDANT;
                                }
                                break;
                        
                        //ID selector
                        //..................................................................................................
                        case    !empty( $match['hash'] ):
                                $result .= sprintf( XPATH_ID, $match['hash'] );
                                break;
                        
                        ///class selector
                        //..................................................................................................
                        case    !empty( $match['class'] ):
                                $result .=  sprintf( XPATH_CLASS, $match['class'] );
                                break;
                                
                        //attribute selector
                        //..................................................................................................
                        case    !empty( $match['attribute'] ):
                                
                                if (empty( $match['comparator'] )) {
                                        $result .= sprintf( XPATH_ATTR, $match['attr'] );
                                        break;
                                }
                                
                                $match['value'] = htmlspecialchars( $match['value'], ENT_QUOTES, 'UTF-8' );
                                
                                switch ($match['comparator']) {
                                        case '=':
                                                $result .= sprintf( XPATH_ATTR_EQ,      $match['attr'], $match['value'] );
                                                break;
                                        case '~=':
                                                $result .= sprintf( XPATH_ATTR_SPACE,   $match['attr'], $match['value'] );
                                                break;
                                        case '|=':
                                                $result .= sprintf( XPATH_ATTR_DASH,    $match['attr'], $match['value'] );
                                                break;
                                        case '^=':
                                                $result .= sprintf( XPATH_ATTR_BEGIN,   $match['attr'], $match['value'] );
                                                break;
                                        case '$=':
                                                $result .= sprintf( XPATH_ATTR_END,     $match['attr'], $match['value']);
                                                break;
                                        case '*=':
                                                $result .= sprintf( XPATH_ATTR_MATCH,   $match['attr'], $match['value'] );
                                                break;
                                        default:
                                                die();
                                }
                                break;

                        //..................................................................................................
                        case    !empty( $match['pseudo'] ):
                                switch ($match['pseudo']) {
                                        case    'empty':
                                                $result .= XPATH_EMPTY;
                                                break;
                                }
                                break;

                        //..................................................................................................
                        default:
                                die();
                }

                $offset += mb_strlen( $match[0] );
        };
        
        if ($offset <= mb_strlen( $match[0] )) die ();
        
        return  $result;
}

?>