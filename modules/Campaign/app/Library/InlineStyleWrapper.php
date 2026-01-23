<?php

namespace Modules\Campaign\Library;

use Symfony\Component\CssSelector\Exception\ParseException;

/**
 * Parses a html file and applies all embedded and external stylesheets inline
 */
class InlineStyleWrapper
{
    /**
     * @var \DOMDocument the HTML as DOMDocument
     */
    private $_dom;

    /**
     * Prepare all the necessary objects
     *
     * @param  string  $html
     */
    public function __construct($html = '')
    {
        if ($html) {
            if ($html instanceof \DOMDocument) {
                $this->loadDomDocument(clone $html);
            } elseif (strlen($html) <= PHP_MAXPATHLEN && file_exists($html)) {
                $this->loadHTMLFile($html);
            } else {
                $this->loadHTML($html);
            }
        }
    }

    /**
     * Load HTML file
     *
     * @param  string  $filename
     */
    public function loadHTMLFile($filename)
    {
        $this->loadHTML(file_get_contents($filename));
    }

    /**
     * Load HTML string (UTF-8 encoding assumed)
     *
     * @param  string  $html
     */
    public function loadHTML($html)
    {
        $document = new \DOMDocument;
        $document->encoding = 'utf-8';
        $document->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NODEFDTD);
        $this->loadDomDocument($document);
    }

    /**
     * Load the HTML as a DOMDocument directly
     */
    public function loadDomDocument(\DOMDocument $domDocument)
    {
        $this->_dom = $domDocument;
        foreach ($this->_getNodesForCssSelector('[style]') as $node) {
            $node->setAttribute(
                'inlinestyle-original-style',
                $node->getAttribute('style')
            );
        }
    }

    /**
     * Applies one or more stylesheets to the current document
     *
     * @param  string  $stylesheet
     * @return InlineStyle self
     */
    public function applyStylesheet($stylesheet)
    {
        $stylesheet = (array) $stylesheet;
        foreach ($stylesheet as $ss) {
            $parsed = $this->parseStylesheet($ss);
            $parsed = $this->sortSelectorsOnSpecificity($parsed);
            foreach ($parsed as $arr) {
                [$selector, $style] = $arr;
                $this->applyRule($selector, $style);
            }
        }

        return $this;
    }

    /**
     * @param  string  $sel  Css Selector
     * @return array|\DOMNodeList|\DOMElement[]
     */
    private function _getNodesForCssSelector($sel)
    {
        try {
            if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
                $converter = new \Symfony\Component\CssSelector\CssSelectorConverter(true);
                $xpathQuery = $converter->toXPath($sel);
            } else {
                $xpathQuery = \Symfony\Component\CssSelector\CssSelector::toXPath($sel);
            }

            return $this->_getDomXpath()->query($xpathQuery);
        } catch (ParseException $e) {
            // ignore css rule parse exceptions
        }

        return [];
    }

    /**
     * Applies a style rule on the document
     *
     * @param  string  $selector
     * @param  string  $style
     * @return InlineStyle self
     */
    public function applyRule($selector, $style)
    {
        if ($selector) {
            $nodes = $this->_getNodesForCssSelector($selector);
            $style = $this->_styleToArray($style);

            foreach ($nodes as $node) {
                $current = $node->hasAttribute('style') ?
                    $this->_styleToArray($node->getAttribute('style')) :
                    [];

                $current = $this->_mergeStyles($current, $style);

                $node->setAttribute('style', $this->_arrayToStyle($current));
            }
        }

        return $this;
    }

    /**
     * Return the DOMDocument as html
     *
     * @return string the HTML
     */
    public function getHTML()
    {
        $clone = $this;
        foreach ($clone->_getNodesForCssSelector('[inlinestyle-original-style]') as $node) {
            $current = $node->hasAttribute('style') ?
                $this->_styleToArray($node->getAttribute('style')) :
                [];
            $original = $node->hasAttribute('inlinestyle-original-style') ?
                $this->_styleToArray($node->getAttribute('inlinestyle-original-style')) :
                [];

            $current = $clone->_mergeStyles($current, $original);

            $node->setAttribute('style', $this->_arrayToStyle($current));
            $node->removeAttribute('inlinestyle-original-style');
        }

        return StringHelper::saveHTMLWithUTF8AndDoctype($clone->_dom);
    }

    /**
     * Recursively extracts the stylesheet nodes from the DOMNode
     *
     * This cannot be done with XPath or a CSS selector because the order in
     * which the elements are found matters
     *
     * @param  \DOMNode  $node  leave empty to extract from the whole document
     * @param  string  $base  The base URI for relative stylesheets
     * @param  array  $devices  Considered devices
     * @param  bool  $remove  Should it remove the original stylesheets
     * @return array the extracted stylesheets
     */
    public function extractStylesheets($node = null, $base = '', $devices = ['all', 'screen', 'handheld'], $remove = true)
    {
        if ($node === null) {
            $node = $this->_dom;
        }

        $stylesheets = [];
        if ($node->hasChildNodes()) {
            $removeQueue = [];
            /** @var $child \DOMElement */
            foreach ($node->childNodes as $child) {
                $nodeName = strtolower($child->nodeName);
                if ($nodeName === 'style' && $this->isForAllowedMediaDevice($child->getAttribute('media'), $devices)) {
                    $stylesheets[] = $child->nodeValue;
                    $removeQueue[] = $child;
                } elseif ($nodeName === 'link' && strtolower($child->getAttribute('rel')) === 'stylesheet' && $this->isForAllowedMediaDevice($child->getAttribute('media'), $devices)) {
                    if ($child->hasAttribute('href')) {
                        $href = $child->getAttribute('href');

                        if ($base && strpos($href, '://') === false) {
                            $href = "{$base}/{$href}";
                        }

                        $ext = @url_get_contents_ssl_safe($href);

                        if ($ext) {
                            $removeQueue[] = $child;
                            $stylesheets[] = $ext;
                        }
                    }
                } else {
                    $stylesheets = array_merge(
                        $stylesheets,
                        $this->extractStylesheets($child, $base, $devices, $remove)
                    );
                }
            }

            if ($remove) {
                foreach ($removeQueue as $child) {
                    $child->parentNode->removeChild($child);
                }
            }
        }

        return $stylesheets;
    }

    /**
     * Extracts the stylesheet nodes nodes specified by the xpath
     *
     * @param  string  $xpathQuery  xpath query to the desired stylesheet
     * @return array the extracted stylesheets
     */
    public function extractStylesheetsWithXpath($xpathQuery)
    {
        $stylesheets = [];

        $nodes = $this->_getDomXpath()->query($xpathQuery);
        foreach ($nodes as $node) {
            $stylesheets[] = $node->nodeValue;
            $node->parentNode->removeChild($node);
        }

        return $stylesheets;
    }

    /**
     * Parses a stylesheet to selectors and properties
     *
     * @param  string  $stylesheet
     * @return array
     */
    public function parseStylesheet($stylesheet)
    {
        $parsed = [];
        $stylesheet = $this->_stripStylesheet($stylesheet);
        $stylesheet = trim(trim($stylesheet), '}');
        foreach (explode('}', $stylesheet) as $rule) {
            // Don't parse empty rules
            if (! trim($rule)) {
                continue;
            }
            [$selector, $style] = explode('{', $rule, 2);
            foreach (explode(',', $selector) as $sel) {
                $parsed[] = [trim($sel), trim(trim($style), ';')];
            }
        }

        return $parsed;
    }

    public function sortSelectorsOnSpecificity($parsed)
    {
        usort($parsed, [$this, 'sortOnSpecificity']);

        return $parsed;
    }

    private function sortOnSpecificity($a, $b)
    {
        $a = $this->getScoreForSelector($a[0]);
        $b = $this->getScoreForSelector($b[0]);

        foreach (range(0, 2) as $i) {
            if ($a[$i] !== $b[$i]) {
                return $a[$i] < $b[$i] ? -1 : 1;
            }
        }

        return -1;
    }

    public function getScoreForSelector($selector)
    {
        return [
            preg_match_all('/#\w/i', $selector, $result), // ID's
            preg_match_all('/\.\w/i', $selector, $result), // Classes
            preg_match_all('/^\w|\ \w|\(\w|\:[^not]/i', $selector, $result), // Tags
        ];
    }

    /**
     * Parses style properties to a array which can be merged by mergeStyles()
     *
     * @param  string  $style
     * @return array
     */
    private function _styleToArray($style)
    {
        $styles = [];
        $style = trim(trim($style), ';');
        if ($style) {
            foreach (explode(';', $style) as $props) {
                $props = trim(trim($props), ';');
                // Don't parse empty props
                if (! trim($props)) {
                    continue;
                }
                // Only match valid CSS rules
                if (preg_match('#^([-a-z0-9\*]+):(.*)$#i', $props, $matches) && isset($matches[0], $matches[1], $matches[2])) {
                    [, $prop, $val] = $matches;
                    $styles[$prop] = $val;
                }
            }
        }

        return $styles;
    }

    private function _arrayToStyle($array)
    {
        $st = [];
        foreach ($array as $prop => $val) {
            $st[] = "{$prop}:{$val}";
        }

        return \implode(';', $st);
    }

    /**
     * Merges two sets of style properties taking !important into account
     *
     * @return array
     */
    private function _mergeStyles(array $styleA, array $styleB)
    {
        foreach ($styleB as $prop => $val) {
            if (! isset($styleA[$prop])
                || substr(str_replace(' ', '', strtolower($styleA[$prop])), -10) !== '!important') {
                $styleA[$prop] = $val;
            }
        }

        return $styleA;
    }

    private function _stripStylesheet($s)
    {
        // strip comments
        $s = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $s);

        // strip keyframes rules
        $s = preg_replace('/@[-|keyframes].*?\{.*?\}[ \r\n]*\}/s', '', $s);

        return $s;
    }

    private function _getDomXpath()
    {
        return new \DOMXPath($this->_dom);
    }

    public function __clone()
    {
        $this->_dom = clone $this->_dom;
    }

    private function isForAllowedMediaDevice($mediaAttribute, array $devices)
    {
        $mediaAttribute = strtolower($mediaAttribute);
        $mediaDevices = explode(',', $mediaAttribute);

        foreach ($mediaDevices as $device) {
            $device = trim($device);
            if (! $device || in_array($device, $devices)) {
                return true;
            }
        }

        return false;
    }
}
