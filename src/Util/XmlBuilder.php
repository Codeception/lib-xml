<?php

declare(strict_types=1);

namespace Codeception\Util;

use DOMDocument;
use DOMElement;
use DOMNode;
use Exception;

/**
 * That's a pretty simple yet powerful class to build XML structures in jQuery-like style.
 * With no XML line actually written!
 * Uses DOM extension to manipulate XML data.
 *
 * ```php
 * <?php
 * $xml = new \Codeception\Util\XmlBuilder();
 * $xml->users
 *    ->user
 *        ->val(1)
 *        ->email
 *            ->val('davert@mail.ua')
 *            ->attr('valid','true')
 *            ->parent()
 *        ->cart
 *            ->attr('empty','false')
 *            ->items
 *                ->item
 *                    ->val('useful item');
 *                ->parents('user')
 *        ->active
 *            ->val(1);
 * echo $xml;
 * ```
 *
 * This will produce this XML
 *
 * ```xml
 * <?xml version="1.0"?>
 * <users>
 *    <user>
 *        1
 *        <email valid="true">davert@mail.ua</email>
 *        <cart empty="false">
 *            <items>
 *                <item>useful item</item>
 *            </items>
 *        </cart>
 *        <active>1</active>
 *    </user>
 * </users>
 * ```
 *
 * ### Usage
 *
 * Builder uses chained calls. So each call to builder returns a builder object.
 * Except for `getDom` and `__toString` methods.
 *
 *  * `$xml->node` - create new xml node and go inside of it.
 *  * `$xml->node->val('value')` - sets the inner value of node
 *  * `$xml->attr('name','value')` - set the attribute of node
 *  * `$xml->parent()` - go back to parent node.
 *  * `$xml->parents('user')` - go back through all parents to `user` node.
 *
 * Export:
 *
 *  * `$xml->getDom` - get a DOMDocument object
 *  * `$xml->__toString` - get a string representation of XML.
 *
 * [Source code](https://github.com/Codeception/lib-xml/blob/main/src/Util/XmlBuilder.php)
 */
class XmlBuilder
{
    protected DOMDocument $dom;

    protected DOMNode $currentNode;

    public function __construct()
    {
        $this->dom = new DOMDocument();
        $this->currentNode = $this->dom;
    }

    /**
     * Appends child node
     */
    public function __get(string $tag): XmlBuilder
    {
        $domElement = $this->dom->createElement($tag);
        $this->currentNode->appendChild($domElement);
        $this->currentNode = $domElement;
        return $this;
    }

    public function val(string $val): self
    {
        $this->currentNode->nodeValue = $val;
        return $this;
    }

    /**
     * Sets attribute for current node
     */
    public function attr(string $attr, string $val): self
    {
        if (!$this->currentNode instanceof DOMElement) {
            throw new Exception('Current node is not DOMElement');
        }
        $this->currentNode->setAttribute($attr, $val);
        return $this;
    }

    /**
     * Traverses to parent
     */
    public function parent(): self
    {
        if ($this->currentNode->parentNode === null) {
            throw new Exception('Element has no parent');
        }
        $this->currentNode = $this->currentNode->parentNode;
        return $this;
    }

    /**
     * Traverses to parent with $tagName
     *
     * @throws Exception
     */
    public function parents(string $tagName): self
    {
        $traverseNode = $this->currentNode;
        $elFound = false;
        while ($traverseNode->parentNode) {
            $traverseNode = $traverseNode->parentNode;
            if ($traverseNode instanceof DOMElement && $traverseNode->tagName === $tagName) {
                $this->currentNode = $traverseNode;
                $elFound = true;
                break;
            }
        }

        if (!$elFound) {
            throw new Exception("Parent {$tagName} not found in XML");
        }

        return $this;
    }

    public function __toString(): string
    {
        $string = $this->dom->saveXML();
        if ($string === false) {
            throw new Exception('Failed to convert DOM to string');
        }

        return $string;
    }

    public function getDom(): DOMDocument
    {
        return $this->dom;
    }
}
