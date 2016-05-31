<?php

/**
 * Value object for index entries.
 *
 * @package    TYPO3
 * @subpackage tx_kesearch
 * @author Wolfram Eberius <edrush@posteo.de>
 */
class tx_kesearch_lib_vo_index_entry
{
    protected $title;
    protected $abstract;
    protected $content;
    protected $tags;
    protected $params;
    protected $languageUid;
    protected $additionalFields;

    //todo: make this configurable
    const CONTENT_SEPERATOR = ", \n";

    public function addContent($content, $title = null)
    {
        if (is_array($content)) {
            $content = implode(', ', $content);
        } else {
            $content = trim(strip_tags($content));
        }

        if (!empty($content)) {
            if (!empty($this->content)) {
                $this->content .= self::CONTENT_SEPERATOR;
            }
            if (!is_null($title)) {
                $content = $title.': '.$content;
            }
            $this->content .= $content;
        }
    }

    public function setTagsFromArray(array $tagsArray)
    {
        if (!empty($tagsArray)) {
            $this->tags = '#'.implode('#, #', $tagsArray).'#';
        }
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $title = trim(strip_tags($title));
        $this->title = $title;
    }

    public function getAbstract()
    {
        return $this->abstract;
    }

    public function setAbstract($abstract)
    {
        $abstract = trim(strip_tags($abstract));
        $this->abstract = $abstract;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    public function getLanguageUid()
    {
        return $this->languageUid;
    }

    public function setLanguageUid($languageUid)
    {
        $this->languageUid = $languageUid;
    }

    public function getAdditionalFields()
    {
        return $this->additionalFields;
    }

    public function setAdditionalFields($additionalFields)
    {
        $this->additionalFields = $additionalFields;
    }
}
