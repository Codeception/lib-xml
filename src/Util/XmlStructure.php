<?php

declare(strict_types=1);

namespace Codeception\Util;

use Codeception\Exception\ElementNotFound;
use Codeception\Exception\MalformedLocatorException;
use Codeception\Util\Soap as SoapXmlUtil;
use DOMDocument;
use DOMNode;
use DOMXPath;
use Symfony\Component\CssSelector\CssSelectorConverter;

class XmlStructure
{
    protected DOMDocument $xml;

    /**
     * @param DOMNode|XmlBuilder|array<mixed>|string|null $xml
     */
    public function __construct(DOMNode|XmlBuilder|array|string|null $xml)
    {
        $this->xml = SoapXmlUtil::toXml($xml);
    }

    public function matchesXpath(string $xpath): bool
    {
        $domXpath = new DOMXPath($this->xml);
        $res = $domXpath->query($xpath);
        if ($res === false) {
            throw new MalformedLocatorException($xpath);
        }
        return $res->length > 0;
    }

    public function matchElement(string $cssOrXPath): ?DOMNode
    {
        $domXpath = new DOMXpath($this->xml);
        $selector = (new CssSelectorConverter())->toXPath($cssOrXPath);
        $els = $domXpath->query($selector);
        if ($els !== false && count($els) > 0) {
            return $els->item(0);
        }
        $els = $domXpath->query($cssOrXPath);
        if ($els !== false && count($els) > 0) {
            return $els->item(0);
        }
        throw new ElementNotFound($cssOrXPath);
    }

    /**
     * @param DOMNode|XmlBuilder|array<mixed>|string|null $xml
     */
    public function matchXmlStructure(DOMNode|XmlBuilder|array|string|null $xml): bool
    {
        $xml = SoapXmlUtil::toXml($xml);
        $root = $xml->firstChild;
        if ($root === null) {
            throw new \Exception('XML is empty');
        }
        $els = $this->xml->getElementsByTagName($root->nodeName);
        if (count($els) === 0) {
            throw new ElementNotFound($root->nodeName, 'Element');
        }

        foreach ($els as $node) {
            /**
             * @var DOMNode $node
             */
            if ($this->matchForNode($root, $node)) {
                return true;
            }
        }

        return false;
    }

    protected function matchForNode(DOMNode $schema, DOMNode $xml): bool
    {
        foreach ($schema->childNodes as $node1) {
            /**
             * @var DOMNode $node1
             */
            $matched = false;
            foreach ($xml->childNodes as $node2) {
                /**
                 * @var DOMNode $node2
                 */
                if ($node1->nodeName == $node2->nodeName) {
                    $matched = $this->matchForNode($node1, $node2);
                    if ($matched) {
                        break;
                    }
                }
            }
            if (!$matched) {
                return false;
            }
        }
        return true;
    }
}
