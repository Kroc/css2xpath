<?php
/**
 * A simple CSS to XPath translator intended to handle 'unsafe' user input
 *
 * @copyright   Copyright 2016, Kroc Camen, all rights reserved
 * @author      Kroc Camen <kroc@camendesign.com>
 * @license     BSD-2-Clause
 *
 * @version     0.0.0
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
/* when using css2xpath, you will have to 'catch' errors that would otherwise halt your script.
 * If you're not familiar with Exceptions, the basic pattern looks like this:
 *
 *      //if an error occurs within the `try` block,
 *      //we will skip to the `catch` block to handle the error
 *      try {
 *              $xpath = kroc\css2xpath\translateQuery( 'a[href]' );
 *
 *      } catch (kroc\css2xpath\InvalidCSSException $e) {
 *              //...
 *
 *      } catch (kroc\css2xpath\UnimplementedCSSException $e) {
 *              //...
 *
 *      } catch (kroc\css2xpath\UntranslatableCSSException $e) {
 *              //...
 *
 *      } catch (\Exception $e) {
 *              //...
 *      }
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


/**
 * The brains of the operation. It will encapsulate the settings you choose for CSS to XPath translation.
 * You cannot change the settings after class instantiation because:
 *
 * 1.   This would invalidate the internal cache
 * 2.   You will shoot yourself in the foot if you re-use the object elsewhere
 *      without realising the settings are (possibly) different
 *
 * @api
 * @todo        feature : Handle default namespaces
 * @todo        feature : Provide a parameter to set the maximum CSS Selector Level supported / allowed
 */
class Translator
{
        /* An XPath query consists of one or more 'Steps', with each Step being made up of 3 Parts,
         * which *must* be, in order: 1. An 'Axis', 2. A 'Node Test' and 3. Zero or more 'Predicates' */
         
        /** @internal The XPath Union `|` acts as an "or" separator to combine more than one XPath query.
          * This is the equivalent of the CSS "," separator */
        const STEP_UNION        = 0;    //I.e. `|`
        /** @internal The Axis decides which set of Nodes to select --
          * - The current Node `self::`, abbreviated as `.`
          * - Children of the current Node, `child::` (though this is implied and can be omitted)
          * - Descendants (`descendant::`), but most typically `descendant-or-self::` -- abbreviated as `//`
          * - The Nodes before or after the current node, `following-sibling::` & `preceding-sibling::`
          * - Attributes of the current Node, `attribute::`, abbreviated `@` */
        const STEP_AXIS         = 1;
        /** @internal */
        const STEP_NODE         = 2;    //I.e. `*`, `ns:*`, `ns:e`, `e`
        /** @internal */
        const STEP_PREDICATE    = 3;    //I.e. `[...]`
        
        /* XPath string fragments:
           -------------------------------------------------------------------------------------------------------------- */
        /** XPath equivalent of CSS's 'child combinator': `>` */
        const XPATH_AXIS_CHILD          = '/child::';
        /** XPath equivalent of CSS's 'descendant combinator' */
        const XPATH_AXIS_DESCENDANT     = '/descendant::';
        /** XPaths will begin with this to ensure that they search the whole document */
        const XPATH_AXIS_DESCENDANTSELF = '/descendant-or-self::';
        /** XPath reference to the current context node */
        const XPATH_AXIS_SELF           = '/self::';
        /** XPath equivalent of CSS's 'general sibling combinator': `~` */
        const XPATH_AXIS_SIBLING        = '/following-sibling::';
        
        const XPATH_NODE_ANYNS          = "[local-name()='%s']";
        
        /** XPath equivalent of CSS's `::first-child` */
        const XPATH_FIRST               = '[1]';
        
        /** Just the universal selector "*" */
        const XPATH_NODE_ANY            = '*';
        /** Xpath template to select an element that has a particular attribute.
          * `sprintf` is used to populate the data, the first argument is the attribute name */
        const XPATH_ATTR                = '[@%s]';
        /** XPath template to select an element that has a particular attribute beginning with a particular string.
          * `sprintf` is used to populate the data; the first argument is the attribute name, the second is the string */
        const XPATH_ATTR_BEGIN          = "[starts-with( @%1s, '%2s' )]";
        /** XPath template to select an element that has a particular attribute containing a particular string.
          * `sprintf` is used to populate the data; the first argument is the attribute name, the second is the string */
        const XPATH_ATTR_MATCH          = "[contains( @%1s, '%2s' )]";
        /** XPath template to select a hyphen-separated list of values (`sprintf` is used to populate the name and value) */
        const XPATH_ATTR_DASH           = "[@%1s='%2s' or starts-with( @%1s, concat( '%2s', '-' ) )]";
        /** XPath template to select an element based on the attribute value ending with a particular string.
          * `sprintf` is used to populate the data: `%1s` is the attribute name and %2s is the string */
        const XPATH_ATTR_END            = "[substring( @%1s, string-length( @%1s ) - string-length( '%2s' ) - 1) = '%2s']";
        /** XPath template to select an element based on its attribute (`sprintf` is used to populate the name and value) */
        const XPATH_ATTR_EQUAL          = "[@%1s='%2s']";
        /** XPath template to select a space-separated list of values (`sprintf` is used to populate the name and value) */
        const XPATH_ATTR_SPACE          = "[contains( concat( ' ', normalize-space( @%1s ), ' ' ), ' %2s ' )]";
        /** XPath template to select a particular CSS class (`sprintf` is used to insert the class name) */
        const XPATH_CLASS               = "[contains( concat( ' ', normalize-space( @class ), ' '), ' %s ' )]";
        /** XPath equivalent of CSS's `:empty` psuedo-class */
        const XPATH_EMPTY               = '[not(*) and not( normalize-space() )]';
        /** XPath template to select a particular CSS ID (`sprintf` is used to insert the ID) */
        const XPATH_ID                  = "[@id='%s']";
        /** XPath's namespace separator */
        const XPATH_NAMESPACE           = ':';
        /** XPath equivalent to CSS's `::nth-child(even)` psuedo-element selector */
        const XPATH_NTH_EVEN            = '[position() mod 2=0 and position()>=0]';
        /** XPath equivalent to CSS's `::nth-child(odd)` psuedo-element selector */
        const XPATH_NTH_ODD             = '[(count( ./preceding-sibling::* ) + 1) mod 2 = 1]';
        /** XPath equivalent to the separation of queries by a comma in CSS */
        const XPATH_UNION               = ' | ';
        
        
        /** @internal If the CSS input is the same, the output XPath will be the same,
          * therefore we cache 'input => output' */
        private $cache = Array();
        
        /** @internal A lookup table to map CSS attribute selector comparisons to the equivalent XPath template */
        private static $attrs = Array(
                 '='    => self::XPATH_ATTR_EQUAL
        ,       '~='    => self::XPATH_ATTR_SPACE
        ,       '|='    => self::XPATH_ATTR_DASH
        ,       '^='    => self::XPATH_ATTR_BEGIN
        ,       '$='    => self::XPATH_ATTR_END
        ,       '*='    => self::XPATH_ATTR_MATCH
        );
        
        /** @internal The default Axis determines the initial search behaviour of an XPath query;
          * CSS includes the context node in its search -- i.e. in a document beginning with `<html>`,
          * the CSS selector `html` will select the root element, rather than searching only its children
          * -- this corresponds to XPath's `descendant-or-self` Axis */
        private $default_axis;
        
        /**
         * @param       string  $default_axis           Must be an `XPATH_AXIS_*` constant.
         *                                              This string will be prepended to every XPath output. In essence,
         *                                              this will control where the XPath begins searching and the default
         *                                              value of `XPATH_AXIS_DESCENDANTSELF` reflects the behaviour of CSS
         *
         * @throws      \InvalidArgumentException
         */
        public function __construct (
                $default_axis = self::XPATH_AXIS_DESCENDANTSELF
        ) {
                //verify that the `$default_axis` argument is one of the allowed constant values
                switch ($default_axis) {
                        case self::XPATH_AXIS_CHILD:
                        case self::XPATH_AXIS_DESCENDANT:
                        case self::XPATH_AXIS_DESCENDANTSELF:
                        case self::XPATH_AXIS_SELF:
                        case self::XPATH_AXIS_SIBLING:
                                //save this choice for use when translating CSS
                                $this->default_axis = $default_axis;
                                break;
                        default:
                                throw new \InvalidArgumentException(
                                        '`$default_axis` argument must be one of the `XPATH_AXIS_*` constants.'
                                );
                }
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
                //@todo: return what for an empty query?
                /* leading & trailing whitespace is stripped so as not to be
                 * confused for a CSS 'descendant combinator', i.e. 'a b' */
                if (empty( $query = trim( $query ))) return NULL;
                
                //return from cache if possible:
                if (in_array( $query, $this->cache )) return $this->cache[$query];
                
                //begin with the default Axis, then translate the CSS query into XPath Parts
                $results = Array_merge(
                        Array (Array ( self::STEP_AXIS, $this->default_axis ))
                ,       $this->translate( $query )
                );
                
                //convert our chain of XPath Parts into an XPath string
                return array_reduce( $results, function($xpath, $step){
                        static $last_step;
                        
                        $last_step = $step;
                        
                        /** @todo Fill in missing steps */
                        return $xpath .= $step[1];
                } );
        }
        
        /**
         * Split off the fragments of CSS from a query and convert them into XPath Parts
         *
         * @param       string  $css
         * @return      array
         *
         * @throws      InvalidCSSException
         */
        private function translate (
                $css
        ) {
                $return = Array();
                
                $offset = 0;
                while (preg_match( "/
                        (?(DEFINE)(?'__IDENT'
                                # TODO: rework this to not allow Unicode punctuation or whitespace etc.
                                [^\s!\"#$%&'()*+,.\/:;<=>?@\[\]^`{|}~]+
                        ))
                        
                        (?:
                        
                        # 1.    COMMA:
                        #       --------------------------------------------------------------------------------------------
                        #       A comma separates multiple CSS selectors. whitespace is ignored before &
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
                        #       An optional namespace identifier can prefix an element name.
                        #       For CSS, this can also be a universal namespace identifier '*'
                                
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
                        
                        |       \# (?P<id>      (?P>__IDENT) )
                        
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
                                        # is there a wrapping quote?
                                        ( [\"']? )
                                                # TODO: use of square brackets in the quote!
                                                (?P<value> [^\]]+ )
                                        
                                        # use the same quote to close
                                        # (relative back-reference)
                                        \g{-2}
                                )?
                                \s*
                                \]
                        
                        )
                        
                        # REGEX FLAGS:
                        # A - Anchor
                        #     (so that we don`t confuse one part of the CSS with another,
                        #      we always start with the first character in the remaining query)
                        # i - Ignore case
                        # s - Whitespace includes new-lines
                        #     (we allow this in case the CSS given to us is from an unknown external source)
                        # u - Handle Unicode correctly
                        # x - Allow this freeform regex style with whitespace and comments
                        #     (rather than this regex string having to be on a single line)
                        
                        /Aisux"
                      , substr( $css, $offset )
                      , $match
                )) {        
                        /** @todo nth-child(even) needs the previous Part to go at the end! */
                        $return = array_merge(
                                $return,
                                $this->translateFragment(
                                        $match['comma'], $match['combinator'], $match['namespace'], $match['element'],
                                        $match['id'], $match['class'], $match['pseudo'], $match['nthof'], $match['nth'],
                                        $match['a'], $match['n'], $match['not'], $match['lang'], $match['attr'],
                                        $match['comparator'], $match['value']
                                )
                        );
                        $offset += strlen( $match[0] );
                };
                
                /* when all parts of the CSS query are valid, all fragments will have been processed in the loop above
                 * and the `$offset` index will be at the end of the CSS query. If the regex does not match a fragment,
                 * (i.e. invalid CSS), then the offset index will not have progressed to the end of the string and we
                 * know that some invalid CSS was encountered */
                if ($offset < strlen( $css )) throw new InvalidCSSException(
                        'Invalid CSS fragment within CSS query.'
                );
                
                return $return;
        }
        
        /**
         * @internal
         *
         * NOTE: These parameters are not all required, as they match up to the regex used and certain combinations
         *       of parameters will produce different results
         *
         * @param       string  $comma          Set to "," to indicate a CSS selector separator
         * @param       string  $combinator     A CSS combinator (" ", "+", ">", "~")
         * @param       string  $namespace      Empty, "*" or a namespace name. implies `$element` present
         * @param       string  $id             A CSS ID (sans the "#")
         * @param       string  $class          A CSS class name (sans the ".")
         *
         * @todo        fix case sensitivity (e.g. `$pseudo == 'empty'`)
         */
        private function translateFragment (
                &$comma = '', &$combinator = '', &$namespace = '', &$element = '', &$id = '', &$class = '', &$pseudo = '',
                &$nthof = '', &$nth = '', &$a = '', &$n = '', &$not = '', &$lang = '', &$attr = '', &$comparator = '',
                &$value = ''
        ) {
                switch (TRUE) {
                        /* element (and namespace)
                         * .............................................................................................. */
                        /* I've seen it said more than once that CSS and XPath namespaces map 1:1, but when you look at
                         * the details, this simply isn't true. XPath 1.0 does not have a wildcard for namespaces like
                         * CSS does (i.e. `*|e`), and namespaces are inferred differently -- for example:
                         *
                         *      CSS:    e       - Element `e` from ANY namespace (including none)
                         *      XPath:  e       - Element `e` with NO namespace only
                         *
                         * The presence of a default namespace greatly alters the behaviour too
                         * (this is discussed in detail further down)
                         *
                         * Therefore, here is a table of comparisons between CSS & XPath queries
                         *
                         *      ANY namespace (including none)           e              *[local-name()="e"]
                         *      NO namespace only                       |e              *[name()="e"]
                         *
                         */
                         
                        /* the namespace can be either "*" (universal), specific (e.g. "xml"),
                         * or blank -- which would indicate either no namespace, or no CSS Type selector at all.
                         * we check for the universal namespace selector first as it implies a following element
                         * .............................................................................................. */
                        case    $namespace == '*':
                                //if the Type element is also universal (i.e. `*|*`) ...
                                if ($element == '*') {
                                        //Xpath has no universal namespace selector,
                                        //it's implied by the normal universal selector
                                        return Array (Array (self::STEP_NODE, self::XPATH_NODE_ANY));
                                } else {
                                        //push the XPath: `*[local-name()='e']`
                                        return Array (
                                                Array (self::STEP_NODE, self::XPATH_NODE_ANY)
                                        ,       Array (
                                                        self::STEP_PREDICATE,
                                                        //insert the element name
                                                        sprintf( self::XPATH_NODE_ANYNS, $element )
                                                )
                                        );
                                }
                                break;
                                
                        /* next up, Type selector of a specific namespace
                         * .............................................................................................. */
                        // NB: `!!` is a neat trick to check if a variable is not blank / 0 (including string: "0")
                        // NB: the `!!` would fail if `$namespace` were "0"; but this is precluded by the regex
                        case    !!$namespace:
                                //push the XPath: `ns:e` (or `ns:*`)
                                return Array (Array(
                                        self::STEP_NODE
                                        //we rely on the CSS and XPath universal selector being the same here
                                ,       $namespace . self::XPATH_NAMESPACE . $element
                                ));
                                break;
                                
                        /* element, with no namespace given
                           .............................................................................................. */
                        // NB: the `!!` would fail if `$element` were "0"; but this is precluded by the regex
                        case    !!$element:
                                //this is straight-forward...
                                return Array (
                                        Array (self::STEP_NODE, $element )
                                );
                                break;

                        /* multiple CSS queries are separated by commas
                         * .............................................................................................. */
                        case    !!$comma:
                                //the XPath equivalent is the bar
                                return Array (
                                        Array ( self::STEP_UNION, self::XPATH_UNION )
                                );
                                break;
                        
                        /* combinator between sequences, e.g. `a + b`, `a > b`, 'a ~ b' and also 'a b' (space)
                         * .............................................................................................. */
                        case    !!$combinator:
                                //match up the CSS combinator with the XPath equivalent
                                switch (trim( $combinator )) {
                                        //CSS adjacent sibling selector:
                                        case '+':
                                                //push the XPath: `/following-sibling::*[1]/self::`
                                                return Array (
                                                        Array ( self::STEP_AXIS, self::XPATH_AXIS_FOLLOWING )
                                                ,       Array ( self::STEP_NODE, self::XPATH_NODE_ANY )
                                                ,       Array ( self::STEP_PREDICATE, self::XPATH_FIRST )
                                                ,       Array ( self::STEP_AXIS, self::XPATH_AXIS_SELF )
                                                );
                                                break;
                                        //CSS child selector:
                                        case '>':
                                                return Array (
                                                        Array (self::STEP_AXIS, self::XPATH_AXIS_CHILD)
                                                );
                                                break;
                                        //CSS general sibling selector:
                                        case '~':
                                                return Array (
                                                        Array (self::STEP_AXIS, self::XPATH_AXIS_SIBLING)
                                                );
                                                break;
                                        default:
                                                //just whitespace? (or `>>`) use the XPath descendant combinator
                                                return Array (
                                                        Array (self::STEP_AXIS, self::XPATH_AXIS_DESCENDANT)
                                                );
                                }
                                break;
                        
                        /* ID selector
                         * .............................................................................................. */
                        // NB: the `!!` would fail if `$id` were "0"; but this is precluded by the regex
                        case    !!$id:
                                //add the XPath fragment to select an ID
                                return Array (Array (
                                        self::STEP_PREDICATE
                                        //apply the captured ID to the XPath template
                                ,       sprintf( self::XPATH_ID, $id )
                                ));
                                break;
                        
                        /* class selector
                         * .............................................................................................. */
                        // NB: the `!!` would fail if `$class` were "0"; but this is precluded by the regex
                        case    !!$class:
                                //add the XPath fragment to select an element based on CSS class
                                return Array (Array (
                                        self::STEP_PREDICATE
                                        //apply the captured class name to the XPath template
                                ,       sprintf( self::XPATH_CLASS, $class )
                                ));
                                break;
                                
                        /* attribute selector
                         * .............................................................................................. */
                        // NB: the `!!` would fail if `$attr` were "0"; but this is precluded by the regex
                        case    !!$attr:
                                //a CSS attribute selector can be just an atrtibute, or an attribute test
                                //(with a condition). if there's no condition, we can move ahead quickly
                                if (!$comparator) {
                                        //produce the simple XPath attribute test
                                        return Array( Array (
                                                self::STEP_PREDICATE
                                        ,       sprintf( self::XPATH_ATTR, $attr )
                                        ));
                                        break;
                                }
                                //get the relevant template and insert the attribute and comparison value:
                                return Array (Array (
                                        self::STEP_PREDICATE
                                ,       sprintf(
                                                self::$attrs[$comparator]
                                        ,       $attr
                                                //the comparison value provided by the user could be within single,
                                                //double, or no quotes, and could likewise contain single or double
                                                //quotes. the regex strips the outer quotes (if present), and we
                                                //convert any internal quotes into safe XML entities
                                        ,       htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' )
                                        )
                                ));
                                break;
                        
                        /* nth-child selector
                         * .............................................................................................. */
                        case    $nthof == 'nth-child':
                                
                                switch ($nth) {
                                        //`::nth-child(n)` simply selects all child elements anyway
                                        case    'n':
                                                //$results[] = Array ( $this::STEP_AXIS, namespace\XPATH_CHILD );
                                                return Array ();
                                                break;
                                        
                                        case    'odd':
                                                Return Array (
                                                        Array ( self::STEP_PREDICATE, self::XPATH_NTH_ODD )
                                                );
                                                break;
                                        
                                        case    'even':
                                                /** @todo the previous Part needs to be moved to the end! */
                                                return Array (
                                                        Array ( self::STEP_NODE, self::XPATH_NODE_ANY )
                                                ,       Array ( self::STEP_PREDICATE, self::XPATH_NTH_EVEN )
                                                ,       Array ( self::STEP_AXIS, self::XPATH_AXIS_SELF )
                                                );
                                                break;
                                        
                                        default:
                                                /** @todo Implement `an+b` nth-child **/
                                                throw new UnimplementedCSSException();
                                }
                                break;
                        
                        /*
                         * .............................................................................................. */
                        case    $nthof == 'nth-last-child':
                                /** @todo Implement `::nth-last-child` */
                                throw new UnimplementedCSSException();
                                break;
                        
                        /*
                         * .............................................................................................. */
                        case    $nthof == 'nth-of-type':
                                /** @todo Implement `::nth-of-type` */
                                throw new UnimplementedCSSException();
                                break;
                        
                        /*
                         * .............................................................................................. */
                        case    $nthof == 'nth-last-of-type':
                                /** @todo Implement `::nth-last-of-type` */
                                throw new UnimplementedCSSException();
                                break;
                                
                        /* first-child pseudo element
                         * .............................................................................................. */
                        case    $pseudo == 'first-child':
                                //add the XPath fragment for selecting the first node
                                return Array (
                                        Array ( self::STEP_PREDICATE, self::XPATH_FIRST )
                                );
                                break;
                                
                        /* empty pseudo element
                         * .............................................................................................. */
                        case    $pseudo == 'empty':
                                return Array (
                                        Array ( self::STEP_PREDICATE, self::XPATH_EMPTY )
                                );
                                break;
                                
                        //..................................................................................................
                        default:
                                throw new UnimplementedCSSException();
                }
                
                return $results;
        }
}

?>