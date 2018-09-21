<?php
namespace TeaminmediasPluswerk\KeSearch\Indexer\Filetypes;

interface FileIndexerInterface
{

    /**
     * get Content of file
     * @param string $file
     * @return string The extracted content of the file
     */
    public function getContent($file);
}
