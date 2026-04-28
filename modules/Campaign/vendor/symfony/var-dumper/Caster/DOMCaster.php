<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Caster;

use Dom\Attr;
use Dom\CharacterData;
use Dom\DocumentType;
use Dom\Element;
use Dom\Entity;
use Dom\Exception;
use Dom\HTMLDocument;
use Dom\Implementation;
use Dom\Node;
use Dom\Notation;
use Dom\ProcessingInstruction;
use Dom\Text;
use Dom\XMLDocument;
use Dom\XPath;
use Symfony\Component\VarDumper\Cloner\Stub;

/**
 * Casts DOM related classes to array representation.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 *
 * @final
 *
 * @internal
 */
class DOMCaster
{
    private const ERROR_CODES = [
        0 => 'DOM_PHP_ERR',
        \DOM_INDEX_SIZE_ERR => 'DOM_INDEX_SIZE_ERR',
        \DOMSTRING_SIZE_ERR => 'DOMSTRING_SIZE_ERR',
        \DOM_HIERARCHY_REQUEST_ERR => 'DOM_HIERARCHY_REQUEST_ERR',
        \DOM_WRONG_DOCUMENT_ERR => 'DOM_WRONG_DOCUMENT_ERR',
        \DOM_INVALID_CHARACTER_ERR => 'DOM_INVALID_CHARACTER_ERR',
        \DOM_NO_DATA_ALLOWED_ERR => 'DOM_NO_DATA_ALLOWED_ERR',
        \DOM_NO_MODIFICATION_ALLOWED_ERR => 'DOM_NO_MODIFICATION_ALLOWED_ERR',
        \DOM_NOT_FOUND_ERR => 'DOM_NOT_FOUND_ERR',
        \DOM_NOT_SUPPORTED_ERR => 'DOM_NOT_SUPPORTED_ERR',
        \DOM_INUSE_ATTRIBUTE_ERR => 'DOM_INUSE_ATTRIBUTE_ERR',
        \DOM_INVALID_STATE_ERR => 'DOM_INVALID_STATE_ERR',
        \DOM_SYNTAX_ERR => 'DOM_SYNTAX_ERR',
        \DOM_INVALID_MODIFICATION_ERR => 'DOM_INVALID_MODIFICATION_ERR',
        \DOM_NAMESPACE_ERR => 'DOM_NAMESPACE_ERR',
        \DOM_INVALID_ACCESS_ERR => 'DOM_INVALID_ACCESS_ERR',
        \DOM_VALIDATION_ERR => 'DOM_VALIDATION_ERR',
    ];

    private const NODE_TYPES = [
        \XML_ELEMENT_NODE => 'XML_ELEMENT_NODE',
        \XML_ATTRIBUTE_NODE => 'XML_ATTRIBUTE_NODE',
        \XML_TEXT_NODE => 'XML_TEXT_NODE',
        \XML_CDATA_SECTION_NODE => 'XML_CDATA_SECTION_NODE',
        \XML_ENTITY_REF_NODE => 'XML_ENTITY_REF_NODE',
        \XML_ENTITY_NODE => 'XML_ENTITY_NODE',
        \XML_PI_NODE => 'XML_PI_NODE',
        \XML_COMMENT_NODE => 'XML_COMMENT_NODE',
        \XML_DOCUMENT_NODE => 'XML_DOCUMENT_NODE',
        \XML_DOCUMENT_TYPE_NODE => 'XML_DOCUMENT_TYPE_NODE',
        \XML_DOCUMENT_FRAG_NODE => 'XML_DOCUMENT_FRAG_NODE',
        \XML_NOTATION_NODE => 'XML_NOTATION_NODE',
        \XML_HTML_DOCUMENT_NODE => 'XML_HTML_DOCUMENT_NODE',
        \XML_DTD_NODE => 'XML_DTD_NODE',
        \XML_ELEMENT_DECL_NODE => 'XML_ELEMENT_DECL_NODE',
        \XML_ATTRIBUTE_DECL_NODE => 'XML_ATTRIBUTE_DECL_NODE',
        \XML_ENTITY_DECL_NODE => 'XML_ENTITY_DECL_NODE',
        \XML_NAMESPACE_DECL_NODE => 'XML_NAMESPACE_DECL_NODE',
    ];

    public static function castException(\DOMException|Exception $e, array $a, Stub $stub, bool $isNested): array
    {
        $k = Caster::PREFIX_PROTECTED.'code';
        if (isset($a[$k], self::ERROR_CODES[$a[$k]])) {
            $a[$k] = new ConstStub(self::ERROR_CODES[$a[$k]], $a[$k]);
        }

        return $a;
    }

    public static function castLength($dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castImplementation(\DOMImplementation|Implementation $dom, array $a, Stub $stub, bool $isNested): array
    {
        $a += [
            Caster::PREFIX_VIRTUAL.'Core' => '1.0',
            Caster::PREFIX_VIRTUAL.'XML' => '2.0',
        ];

        return $a;
    }

    public static function castNode(\DOMNode|Node $dom, array $a, Stub $stub, bool $isNested): array
    {
        return self::castDom($dom, $a, $stub, $isNested);
    }

    public static function castNameSpaceNode(\DOMNameSpaceNode $dom, array $a, Stub $stub, bool $isNested): array
    {
        return self::castDom($dom, $a, $stub, $isNested);
    }

    public static function castDocument(\DOMDocument $dom, array $a, Stub $stub, bool $isNested, int $filter = 0): array
    {
        if (! ($filter & Caster::EXCLUDE_VERBOSE)) {
            $formatOutput = $dom->formatOutput;
            $dom->formatOutput = true;
            $a += [Caster::PREFIX_VIRTUAL.'xml' => $dom->saveXML()];
            $dom->formatOutput = $formatOutput;
        }

        return $a;
    }

    public static function castXMLDocument(XMLDocument $dom, array $a, Stub $stub, bool $isNested, int $filter = 0): array
    {
        if (! ($filter & Caster::EXCLUDE_VERBOSE)) {
            $formatOutput = $dom->formatOutput;
            $dom->formatOutput = true;
            $a += [Caster::PREFIX_VIRTUAL.'xml' => $dom->saveXML()];
            $dom->formatOutput = $formatOutput;
        }

        return $a;
    }

    public static function castHTMLDocument(HTMLDocument $dom, array $a, Stub $stub, bool $isNested, int $filter = 0): array
    {
        if (! ($filter & Caster::EXCLUDE_VERBOSE)) {
            $a += [Caster::PREFIX_VIRTUAL.'html' => $dom->saveHTML()];
        }

        return $a;
    }

    public static function castCharacterData(\DOMCharacterData|CharacterData $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castAttr(\DOMAttr|Attr $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castElement(\DOMElement|Element $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castText(\DOMText|Text $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castDocumentType(\DOMDocumentType|DocumentType $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castNotation(\DOMNotation|Notation $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castEntity(\DOMEntity|Entity $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castProcessingInstruction(\DOMProcessingInstruction|ProcessingInstruction $dom, array $a, Stub $stub, bool $isNested): array
    {
        return $a;
    }

    public static function castXPath(\DOMXPath|XPath $dom, array $a, Stub $stub, bool $isNested): array
    {
        return self::castDom($dom, $a, $stub, $isNested);
    }

    public static function castDom($dom, array $a, Stub $stub, bool $isNested, int $filter = 0): array
    {
        foreach ($a as $k => $v) {
            if ($k === 'encoding' && $dom instanceof \DOMEntity
                || \in_array($k, ['actualEncoding', 'config', 'standalone', 'version'], true)
            ) {
                continue; // deprecated properties
            }

            $v = $dom->$k;

            $a[$k] = match (true) {
                $v instanceof \DOMNode || $v instanceof Node => new CutStub($v),
                $k === 'nodeType' => new ConstStub(self::NODE_TYPES[$v], $v),
                $k === 'baseURI' && $v,
                $k === 'documentURI' && $v => new LinkStub($v),
                default => $v,
            };
        }

        if ($dom instanceof \IteratorAggregate) {
            foreach ($dom as $k => $v) {
                $a[Caster::PREFIX_VIRTUAL.$k] = $v;
            }
        }

        return $a;
    }
}
