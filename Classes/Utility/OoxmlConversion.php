<?php
namespace TeaminmediasPluswerk\KeSearch\Utility;

/**
 * Class OoxmlConversion
 *
 * @package TeaminmediasPluswerk\KeSearch\Utility
 * @see https://stackoverflow.com/questions/19503653/how-to-extract-text-from-word-file-doc-docx-xlsx-pptx-php
 */
class OoxmlConversion
{
    /**
     * @var string
     */
    private $filename;

    /**
     * OoxmlConversion constructor
     *
     * @param string $filePath
     * @throws \Exception If given filePath is not existing
     */
    public function __construct($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File "' . $filePath . '" not found!');
        }
        $this->filename = $filePath;
    }

    /**
     * Read text contents from Word files (DOCX)
     *
     * @return string
     */
    private function readDocx()
    {
        $content = '';
        $zip = zip_open($this->filename);

        if (!$zip || is_numeric($zip)) {
            return false;
        }

        while ($zipEntry = zip_read($zip)) {
            if (zip_entry_open($zip, $zipEntry) === false) {
                continue;
            }
            if (zip_entry_name($zipEntry) !== 'word/document.xml') {
                continue;
            }
            $content .= zip_entry_read($zipEntry, zip_entry_filesize($zipEntry));
            zip_entry_close($zipEntry);
        }
        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', ' ', $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        return strip_tags($content);
    }

    /**
     * Read text contents from Excel file (XLSX)
     * @return string
     */
    private function readXlsx()
    {
        $zipHandle = new \ZipArchive();
        $outputText = '';
        if (true === $zipHandle->open($this->filename)) {
            if (($xmlIndex = $zipHandle->locateName('xl/sharedStrings.xml')) !== false) {
                $xmlData = $zipHandle->getFromIndex($xmlIndex);
                $domDocument = new \DOMDocument();
                $domDocument->loadXML($xmlData, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $outputText = strip_tags(str_replace('</t>', ' </t>', $domDocument->saveXML()));
            } else {
                $outputText .= '';
            }
            $zipHandle->close();
        } else {
            $outputText .= '';
        }
        return $outputText;
    }

    /**
     * Read text contents from Powerpoint file (PPTX)
     *
     * @return string
     */
    private function readPptx()
    {
        $zipHandle = new \ZipArchive();
        $outputText = '';
        if (true === $zipHandle->open($this->filename)) {
            $slideNumber = 1; //loop through slide files
            while (($xml_index = $zipHandle->locateName('ppt/slides/slide' . $slideNumber . '.xml')) !== false) {
                $xmlData = $zipHandle->getFromIndex($xml_index);
                $domDocument = new \DOMDocument();
                $domDocument->loadXML($xmlData, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                $outputText .= strip_tags(str_replace('</a:t>', ' </a:t>', $domDocument->saveXML()));
                $slideNumber++;
            }
            if ($slideNumber === 1) {
                $outputText .= ' ';
            }
            $zipHandle->close();
        } else {
            $outputText .= ' ';
        }
        return $outputText;
    }

    /**
     * Extract text from given OOXML file
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function convertToText()
    {
        $pathInfo = pathinfo($this->filename);
        switch (strtolower($pathInfo['extension'])) {
            case 'docx':
                return $this->readDocx();
            case 'xlsx':
                return $this->readXlsx();
            case 'pptx':
                return $this->readPptx();

            default:
                throw new \InvalidArgumentException('File extension "' . $pathInfo['extension'] . '" not supported!');
        }
    }

}
