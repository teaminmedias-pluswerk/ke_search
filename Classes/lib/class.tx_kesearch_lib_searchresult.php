<?php
/***************************************************************
 *  Copyright notice
 *  (c) 2010 Stefan Froemken
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Plugin 'Faceted search - searchbox and filters' for the 'ke_search' extension.
 * @author    Stefan Froemken
 * @package    TYPO3
 * @subpackage    tx_kesearch
 */
class tx_kesearch_lib_searchresult
{

    protected $conf = array();
    protected $row = array();

    /**
     * @var tx_kesearch_lib
     */
    protected $pObj;

    /**
     * @var tslib_cObj
     */
    protected $cObj;

    /**
     * @var tx_kesearch_lib_div
     */
    protected $div;


    /**
     * The constructor of this class
     * @param tx_kesearch_lib $pObj
     */
    public function __construct(tx_kesearch_lib $pObj)
    {
        // initializes this object
        $this->init($pObj);
    }


    /**
     * Initializes this object
     * @param tx_kesearch_lib $pObj
     * @return void
     */
    public function init(tx_kesearch_lib $pObj)
    {
        $this->pObj = $pObj;
        $this->cObj = $this->pObj->cObj;
        $this->conf = $this->pObj->conf;
    }


    /**
     * set row array with current result element
     * @param array $row
     * @return void
     */
    public function setRow(array $row)
    {
        $this->row = $row;
    }


    /**
     * get title for result row
     * @return string The linked result title
     */
    public function getTitle()
    {
        // configure the link
        $linkconf = $this->getResultLinkConfiguration();

        list($type) = explode(':', $this->row['type']);
        switch ($type) {
            case 'file':
                // if we use FAL, see if we have a title in the metadata
                if ($this->row['orig_uid'] && ($fileObject = tx_kesearch_helper::getFile($this->row['orig_uid']))) {
                    $metadata = $fileObject->_getMetaData();
                    $linktext = ($metadata['title'] ? $metadata['title'] : $this->row['title']);
                } else {
                    $linktext = $this->row['title'];
                }
                break;
            default:
                $linktext = $this->row['title'];
                break;
        }

        // clean title
        $linktext = strip_tags($linktext);
        $linktext = $this->pObj->div->removeXSS($linktext);

        // highlight hits in result title?
        if ($this->conf['highlightSword'] && count($this->pObj->swords)) {
            $linktext = $this->highlightArrayOfWordsInContent($this->pObj->swords, $linktext);
        }
        return $this->cObj->typoLink($linktext, $linkconf);
    }

    /**
     * get result url (not) linked
     * @return string The results URL
     */
    public function getResultUrl($linked = false)
    {
        $linkText = $this->cObj->typoLink_URL($this->getResultLinkConfiguration());
        $linkText = htmlspecialchars($linkText);
        if ($linked) {
            return $this->cObj->typoLink($linkText, $this->getResultLinkConfiguration());
        }
        return $linkText;
    }

    /**
     * get result link configuration
     * It can devide between the result types (file, page, content)
     *
     * @return array configuration for typolink
     */
    public function getResultLinkConfiguration()
    {
        return tx_kesearch_helper::getResultLinkConfiguration(
            $this->row,
            $this->conf['resultLinkTarget'],
            $this->conf['resultLinkTargetFiles']
        );
    }

    /**
     * get teaser for result list
     *
     * @return string The teaser
     */
    public function getTeaser()
    {
        $content = $this->getContentForTeaser();
        return $this->buildTeaserContent($content);
    }

    /**
     * get content for teaser
     * This can be the abstract or content col
     *
     * @return string The content
     */
    public function getContentForTeaser()
    {
        $content = $this->row['content'];
        if (!empty($this->row['abstract'])) {
            $content = nl2br($this->row['abstract']);
            if ($this->conf['previewMode'] == 'hit') {
                if (!$this->isArrayOfWordsInString($this->pObj->swords, $this->row['abstract'])) {
                    $content = $this->row['content'];
                }
            }
        }
        return $content;
    }

    /**
     * check if an array with words was found in given content
     * @param array $wordArray A single dimmed Array containing words
     * to search for. F.E. array('hello', 'georg', 'company')
     * @param string $content The string to search in
     * @param boolean $checkAll If this is checked, then all words have to be found in string.
     * If false: The method returns true directly, if one of the words was found
     * @return boolean Returns true if the word(s) are found
     */
    public function isArrayOfWordsInString(array $wordArray, $content, $checkAll = false)
    {
        $found = false;
        foreach ($wordArray as $word) {
            if (stripos($content, $word) === false) {
                $found = false;
                if ($checkAll === true) {
                    return false;
                }
            } else {
                $found = true;
                if ($checkAll === false) {
                    return true;
                }
            }
        }
        return $found;
    }

    /**
     * Find and highlight the searchwords
     *
     * @param array $wordArray
     * @param string $content
     * @return string The content with highlighted searchwords
     */
    public function highlightArrayOfWordsInContent($wordArray, $content)
    {
        if (is_array($wordArray) && count($wordArray)) {
            $highlightedWord = (!empty($this->conf['highlightedWord_stdWrap.'])) ?
                $this->cObj->stdWrap('\0', $this->conf['highlightedWord_stdWrap.']) :
                '<span class="hit">\0</span>';

            foreach ($wordArray as $word) {
                $word = str_replace('/', '\/', $word);
                $word = htmlspecialchars($word);
                $content = preg_replace('/(' . $word . ')/iu', $highlightedWord, $content);
            }
        }
        return $content;
    }

    /**
     * Build Teasercontent
     *
     * @param string $content The whole resultcontent
     * @return string The cutted recultcontent
     */
    public function buildTeaserContent($content)
    {
        if (is_array($this->pObj->swords) && count($this->pObj->swords)) {
            $amountOfSearchWords = count($this->pObj->swords);
            $content = strip_tags($content);
            // with each new searchword and all the croppings here the teaser for each word will become too small/short
            // I decided to add 20 additional letters for each searchword. It looks much better and is more readable
            $charsForEachSearchWord = ceil($this->conf['resultChars'] / $amountOfSearchWords) + 20;
            $charsBeforeAfterSearchWord = ceil($charsForEachSearchWord / 2);
            $aSearchWordWasFound = false;
            $isSearchWordAtTheBeginning = false;
            $teaserArray = [];
            foreach ($this->pObj->swords as $word) {
                // Always remove whitespace around searchword first
                $word = trim($word);

                // Check teaser text array first to avoid duplicate text parts
                if (count($teaserArray) > 0) {
                    foreach ($teaserArray as $teaserArrayItem) {
                        $searchWordPositionInTeaserArray = stripos($teaserArrayItem, $word);
                        if (false === $searchWordPositionInTeaserArray) {
                            continue;
                        } else {
                            // One finding in teaser text array is sufficient
                            $aSearchWordWasFound = true;
                            break;
                        }
                    }
                }

                // Only search for current search word in content if it wasn't found in teaser text array already
                if (false === $aSearchWordWasFound) {
                    $pos = stripos($content, $word);
                    if (false === $pos) {
                        continue;
                    }
                    $aSearchWordWasFound = true;

                    // if search word is the first word
                    if (0 === $pos) {
                        $isSearchWordAtTheBeginning = true;
                    }

                    // find search starting point
                    $startPos = $pos - $charsBeforeAfterSearchWord;
                    if ($startPos < 0) {
                        $startPos = 0;
                    }

                    // crop some words behind search word
                    $partWithSearchWord = substr($content, $startPos);
                    $temp = $this->cObj->crop($partWithSearchWord, $charsForEachSearchWord . '|...|1');

                    // crop some words before search word
                    // after last cropping our text is too short now. So we have to find a new cutting position
                    ($startPos > 10) ? $length = strlen($temp) - 10 : $length = strlen($temp);

                    // Store content part containing the search word in teaser text array
                    $teaserArray[] = $this->cObj->crop($temp, '-' . $length . '||1');
                }
            }

            // When the searchword was found in title but not in content the teaser is empty
            // in that case we have to get the first x letters without containing any searchword
            if ($aSearchWordWasFound === false) {
                $teaser = $this->cObj->crop($content, $this->conf['resultChars'] . '||1');
            } elseif ($isSearchWordAtTheBeginning === true) {
                $teaser = implode(' ', $teaserArray);
            } else {
                $teaser = '...' . implode(' ', $teaserArray);
            }

            // highlight hits?
            if ($this->conf['highlightSword']) {
                $teaser = $this->highlightArrayOfWordsInContent($this->pObj->swords, $teaser);
            }
            return $teaser;
        } else {
            return $this->cObj->crop($content, $this->conf['resultChars'] . '|...|1');
        }
    }
}
