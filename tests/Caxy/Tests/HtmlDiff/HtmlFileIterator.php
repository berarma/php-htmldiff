<?php

namespace Caxy\Tests\HtmlDiff;

use DOMDocument;
use Exception;
use Iterator;

class HtmlFileIterator implements Iterator
{
    protected $files       = [];
    protected $key         = 0;
    protected $loadedDiffs = [];

    public function __construct($directory)
    {
        $this->files = glob($directory.DIRECTORY_SEPARATOR."*.html");
    }

    /**
     * {@inheritDoc}
     */
    public function current() : array
    {
        return $this->loadHtmlFile($this->key);
    }

    /**
     * {@inheritDoc}
     */
    public function next() : void
    {
        $this->key++;
    }

    /**
     * {@inheritDoc}
     */
    public function key() : string
    {
        return basename($this->files[$this->key]);
    }

    /**
     * {@inheritDoc}
     */
    public function valid() : bool
    {
        return isset($this->files[$this->key]);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind() : void
    {
        $this->key = 0;
    }

    /**
     * @return array<string, string>
     */
    protected function loadHtmlFile($key) : array
    {
        $filename = $this->files[$key];

        if (!isset($this->loadedDiffs[$filename])) {

            $html = file_get_contents($filename);

            $this->loadedDiffs[$filename] = [
                $this->parseTagContent('oldText', $html),
                $this->parseTagContent('newText', $html),
                $this->parseTagContent('expected', $html),
                $this->configXmlToArray($this->parseTagContent('options', $html)),
            ];

        }

        return $this->loadedDiffs[$filename];
    }

    /**
     * @return array<string, int|bool|string>
     */
    protected function configXmlToArray(string $optionsXml) : array
    {
        $config = [];

        $xml = sprintf('<root>%s</root>', $optionsXml);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        foreach ($dom->getElementsByTagName('option') as $option) {
            $type = $option->getAttribute('type');

            switch ($type) {
                case 'boolean':
                    $config[$option->getAttribute('name')] = ($option->getAttribute('value') === 'true');

                    break;
                case 'integer':
                    $config[$option->getAttribute('name')] = (int) $option->getAttribute('value');

                    break;
                default:
                    $config[$option->getAttribute('name')] = (string) $option->getAttribute('value');

                    break;
            }
        }

        return $config;
    }

    protected function parseTagContent(string $tagName, string $content) : string
    {
        $matches = [];

        if (preg_match(sprintf('/<%s\s*[^>]*>(.*)<\/%s\s*>/is', $tagName, $tagName), $content, $matches)) {
            return $matches[1];
        }

        throw new Exception('Fixture file should have an ' . $tagName . ' tag');
    }
}
