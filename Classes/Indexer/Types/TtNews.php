<?php

namespace TeaminmediasPluswerk\KeSearch\Indexer\Types;

use TeaminmediasPluswerk\KeSearch\Domain\Repository\PageRepository;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerBase;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/***************************************************************
 *  Copyright notice
 *  (c) 2021 Christian Bülter
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

class TtNews extends IndexerBase
{

    /** @var FileRepository $fileRepository  */
    protected $fileRepository = NULL;

    /** @var int $fileCounter */
    protected $fileCounter = 0;

    /**
     * Initializes indexer for tt_news
     *
     * @param IndexerRunner $pObj
     */
    public function __construct($pObj)
    {
        parent::__construct($pObj);
        $this->pObj = $pObj;
        $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);
    }

    /**
     * Index tt_news records
     *
     * @return string content which will be displayed in backend
     */
    public function startIndexing()
    {
        $content = '';
        $table = 'tt_news';

        // get the pages from where to index the news
        $indexPids = $this->getPidList(
            $this->indexerConfig['startingpoints_recursive'],
            $this->indexerConfig['sysfolder'],
            $table
        );

        // add the tags of each page to the global page array
        if ($this->indexerConfig['index_use_page_tags']) {
            $this->pageRecords = $this->getPageRecords($indexPids);
            $this->addTagsToRecords($indexPids);
        }

        // get all the news entries to index, don't index hidden or
        // deleted news, BUT  get the news with frontend user group
        // access restrictions or time (start / stop) restrictions.
        // Copy those restrictions to the index.
        $queryBuilder = Db::getQueryBuilder($table);
        $res = $queryBuilder
            ->select('*')
            ->from($table)
            ->where($queryBuilder->expr()->in('pid', implode(',', $indexPids)))
            ->execute();

        $indexedNewsCounter = 0;
        $resCount = $res->rowCount();

        if ($resCount) {
            while (($newsRecord = $res->fetch())) {

                $this->pObj->logger->debug('Indexing tt_news record "' . $newsRecord['title'] .'"', [
                    'uid' => $newsRecord['uid'],
                    'pid' => $newsRecord['pid']
                ]);

                // get category data for this news record (list of
                // assigned categories and single view from category, if it exists)
                $categoryData = $this->getCategoryData($newsRecord);

                // compile the information which should go into the index:
                // title, teaser, bodytext
                $type = 'tt_news';
                $title = strip_tags($newsRecord['title']);
                $abstract = strip_tags($newsRecord['short']);
                $content = strip_tags($newsRecord['bodytext']);

                // add additional fields to the content:
                // alternative_title, author, author_email, keywords
                if (isset($newsRecord['author'])) {
                    $content .= "\n" . strip_tags($newsRecord['author']);
                }
                if (isset($newsRecord['author_email'])) {
                    $content .= "\n" . strip_tags($newsRecord['author_email']);
                }
                if (!empty($newsRecord['keywords'])) {
                    $content .= "\n" . $newsRecord['keywords'];
                }

                // get related files if fileext is configured
                if (!empty($this->indexerConfig['fileext'])) {
                    $filesToIndex = $this->getFilesToIndex(
                        'tt_news',
                        'news_files',
                        $newsRecord['uid'],
                        $newsRecord['sys_language_uid']
                    );
                    if (!empty($filesToIndex)) {
                        if ($this->indexerConfig['index_news_files_mode'] === 1) {
                            // add file content to news index record
                            $content .= $this->getContentFromFiles($filesToIndex);
                        } else {
                            // index file as separate index record
                            $this->indexFilesAsSeparateResults($filesToIndex, $newsRecord);
                        }
                    }
                }

                // create content
                $fullContent = '';
                if (isset($abstract)) {
                    $fullContent .= $abstract . "\n";
                }
                $fullContent .= $content;

                // make it possible to modify the indexerConfig via hook
                $indexerConfig = $this->indexerConfig;

                // overwrite the targetpid if there is a category assigned
                // which has its own single view page
                if ($categoryData['single_pid']) {
                    $indexerConfig['targetpid'] = $categoryData['single_pid'];
                }

                // create params for news single view, example:
                // /tt-news-detail?tx_ttnews[tt_news]=1
                $paramsSingleView['tx_ttnews']['tt_news'] =
                    $newsRecord['l18n_parent'] ? $newsRecord['l18n_parent'] : $newsRecord['uid'];
                $params = '&' . http_build_query($paramsSingleView, null, '&');
                $params = rawurldecode($params);

                // add tags from pages
                if ($indexerConfig['index_use_page_tags']) {
                    $tags = $this->pageRecords[intval($newsRecord['pid'])]['tags'];
                } else {
                    $tags = '';
                }

                // add keywords from ext:tt_news as tags
                if (!empty($newsRecord['keywords'])) {
                    $keywordsList = GeneralUtility::trimExplode(',', $newsRecord['keywords']);
                    foreach ($keywordsList as $keyword) {
                        SearchHelper::makeTags($tags, array($keyword));
                    }
                }

                // add categories from from ext:news as tags
                SearchHelper::makeTags($tags, $categoryData['title_list']);

                // set additional fields
                $additionalFields = array();
                $additionalFields['orig_uid'] = $newsRecord['uid'];
                $additionalFields['orig_pid'] = $newsRecord['pid'];
                $additionalFields['sortdate'] = $newsRecord['crdate'];
                if (isset($newsRecord['datetime']) && $newsRecord['datetime'] > 0) {
                    $additionalFields['sortdate'] = $newsRecord['datetime'];
                }

                // hook for custom modifications of the indexed data, e.g. the tags
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtTtNewsIndexEntry'])) {
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['modifyExtTtNewsIndexEntry'] as
                             $_classRef) {
                        $_procObj = GeneralUtility::makeInstance($_classRef);
                        $_procObj->modifyExtNewsIndexEntry(
                            $title,
                            $abstract,
                            $fullContent,
                            $params,
                            $tags,
                            $newsRecord,
                            $additionalFields,
                            $indexerConfig,
                            $categoryData,
                            $this
                        );
                    }
                }

                // store this record to the index
                $this->pObj->storeInIndex(
                    $indexerConfig['storagepid'],    // storage PID
                    $title,                          // page title
                    $type,                           // content type
                    $indexerConfig['targetpid'],     // target PID: where is the single view?
                    $fullContent,                    // indexed content, includes the title (linebreak after title)
                    $tags,                           // tags
                    $params,                         // typolink params for singleview
                    $abstract,                       // abstract
                    $newsRecord['sys_language_uid'], // language uid
                    $newsRecord['starttime'],        // starttime
                    $newsRecord['endtime'],          // endtime
                    $newsRecord['fe_group'],         // fe_group
                    false,                           // debug only?
                    $additionalFields                // additional fields added by hooks
                );
                $indexedNewsCounter++;
            }

            $logMessage = 'Indexer "' . $this->indexerConfig['title'] . '" finished'
                . ' ('.$indexedNewsCounter.' records processed)';
            $this->pObj->logger->info($logMessage);
        }
        return $indexedNewsCounter . ' tt_news records and ' . $this->fileCounter . ' related files have been indexed.';
    }

    /**
     * checks if there is a news category assigned to the $newsRecord which has
     * its own single view page and if yes, returns the uid of the page
     * in $catagoryData['single_pid'].
     * It also compiles a list of all assigned categories and returns
     * it as an array in $categoryData['uid_list']. The titles of the
     * categories are returned in $categoryData['title_list'] (array)
     *
     * @param array $newsRecord
     * @return array
     */
    private function getCategoryData($newsRecord)
    {
        /** @var PageRepository $pageRepository */
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $categoryData = array(
            'single_pid' => 0,
            'uid_list' => array(),
            'title_list' => array()
        );

        $queryBuilder = Db::getQueryBuilder('tt_news_cat');

        $where = [];
        $where[] = $queryBuilder->expr()->eq(
            'tt_news.uid',
            $queryBuilder->quoteIdentifier('tt_news_cat_mm.uid_local')
        );
        $where[] = $queryBuilder->expr()->eq(
            'tt_news_cat.uid',
            $queryBuilder->quoteIdentifier('tt_news_cat_mm.uid_foreign')
        );
        $where[] = $queryBuilder->expr()->eq(
            'tt_news.uid',
            $queryBuilder->createNamedParameter($newsRecord['uid'], \PDO::PARAM_INT)
        );

        $catRes = $queryBuilder
            ->select(
                'tt_news_cat.uid',
                'tt_news_cat.single_pid',
                'tt_news_cat.title'
            )
            ->from('tt_news_cat')
            ->from('tt_news_cat_mm')
            ->from('tt_news')
            ->orderBy('tt_news_cat_mm.sorting')
            ->where(...$where)
            ->execute();

        while (($newsCat = $catRes->fetch())) {
            $categoryData['uid_list'][] = $newsCat['uid'];
            $categoryData['title_list'][] = $newsCat['title'];
            // check if this category has a single_pid and if this page really is reachable (not deleted, hidden or time restricted)
            if ($newsCat['single_pid'] && !$categoryData['single_pid'] && $pageRepository->findOneByUid($newsCat['single_pid'])) {
                $categoryData['single_pid'] = $newsCat['single_pid'];
            }
        }

        return $categoryData;
    }
}
