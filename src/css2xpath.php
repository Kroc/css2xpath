<?php
/**
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 */

/**
 * should you modify this project for your own use (rather than say, contributing back to the original)
 * you MUST change this namespace to use your own name; likewise, check "composer.json"
 */
namespace kroc\css2xpath;

/**
 * @api
 * @param       string $query   the CSS query to translate into XPath
 * @return      string          an XPath query string
 */
function translateQuery ($query)
{
        //leading & trailing whitespace is stripped so as not to be confused for a CSS 'descendent combinator'
        $query = trim( $query );
        
        
        while (preg_match(
<<<'REGEX'
        /
                (?(DEFINE)(?'__IDENT'
                        [^\s!"#$%&'()*+,.\/:;<=>?@\[\]^`{|}~]+
                ))
                
                # so that we don`t confuse one part of the CSS with another,
                # we always start with the first character in the remaining query
                ^
                
                (?P<fragment>
                
                # 1.    COMMA:
                #       ----------------------------------------------------------------------------------------------------
                #       A comma separates multiple CSS queries. whitespace is ignored before &
                #       after to not mistake this for CSS descendents, i.e. "a b"
                
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
                        (?P<attrib>
                                (?P<attr>
                                        (?:
                                                (?P>namespace)?
                                                \|
                                        )?
                                        (?P>element)
                                )
                                
                                (?:
                                        \s*
                                        (?P<comparison> [~|^$*]? = )
                                        \s*
                                        (?:
                                                " [^"]+ "
                                        |       ' [^']+ '
                                        |       [^\]]+
                                        )
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
                                        ? '*'
                                        //any element of a specific namespace:
                                        : "${match['namespace']}:*"
                                ) : (
                                        ($match['namespace'] == '*')
                                        //XPath 1.0 does not support the notion of a namespace-wildcard,
                                        //i.e. `*:`, instead we look at element names without the namespace
                                        ? "*[local-name()='${match['element']}']"
                                        //in XPath, namespaces are separated by colon, not bar;
                                        //include the namespace in the XPath only if provided from the CSS
                                        : (!empty( $match['namespace'] ) ? $match['namespace'] . ':' : '')
                                          . $match['element']
                                );
                                break;

                        //multiple CSS queries are separated by commas
                        //..................................................................................................
                        case    !empty( $match['comma'] ):
                                //the XPath equivilent is the bar
                                $result .= ' | ';
                                break;
                        
                        //combinator between sequences, e.g. `a + b`, `a > b`, 'a ~ b' and also 'a b' (space)
                        //..................................................................................................
                        case    !empty( $match['combinator'] ):
                                switch (trim( $match['combinator'] )) {
                                        case    '+':
                                                $result .= '/following-sibling::*[1]/self::';
                                                break;

                                        case    '>':
                                                $result .= '/';
                                                break;

                                        case    '~':
                                                $result .= '/following-sibling::';
                                                break;
                                        
                                        //the space combinator
                                        default:
                                                //in XPath, the double-slash requests any depth between elements
                                                $result .= '//';
                                }
                                break;

                        //..................................................................................................
                        case    !empty( $match['hash'] ):
                                $result .= "[@id='${match['hash']}']";
                                break;

                        //..................................................................................................
                        case    !empty( $match['class'] ):
                                $result .= "[contains(concat(' ',normalize-space(@class),' '),' ${match['class']} ')]";
                                break;

                        //..................................................................................................
                        case    !empty( $match['attr'] ):
                                $result .= "[@${match['attr']}]";
                                break;

                        //..................................................................................................
                        case    !empty( $match['pseudo'] ):
                                switch ($match['pseudo']) {
                                        case    'empty':
                                                $result .= '[not(*) and not(normalize-space())]';
                                                break;
                                }
                                break;

                        //..................................................................................................
                        default:
                                die();
                }

                $offset += mb_strlen( $match[0] );
        };
        
        return  $result;
}

?>