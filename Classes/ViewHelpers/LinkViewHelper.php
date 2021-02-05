<?php
namespace TeaminmediasPluswerk\KeSearch\ViewHelpers;

use TeaminmediasPluswerk\KeSearch\Lib\SearchHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * ViewHelper to render links to search results including filters
 *
 */
class LinkViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'a';

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('page', 'int', 'Target page', false);
        $this->registerArgument('piVars', 'array', 'piVars', false);
        $this->registerArgument('resetFilters', 'array', 'Filters to reset', false);
        $this->registerArgument('content', 'string', 'content', false, '');
        $this->registerArgument('keepPiVars', 'boolean', 'keep piVars?', false, '');
        $this->registerArgument('uriOnly', 'bool', 'url only', false, false);
        $this->registerTagAttribute('section', 'string', 'Anchor for links', false);
    }

    /**
     * Render link to news item or internal/external pages
     *
     * @return string link
     */
    public function render(): string
    {
        $page = $this->arguments['page'] ?? $GLOBALS['TSFE']->id;
        $resetFilters = $this->arguments['resetFilters'] ?? [];
        $content = $this->arguments['content'] ?? '';
        $keepPiVars = !empty($this->arguments['keepPiVars']);
        $piVars = $this->arguments['piVars'] ?? [];
        $uriOnly = $this->arguments['uriOnly'] ?? false;

        if (!empty($piVars)) {
            $piVars = SearchHelper::explodePiVars($piVars);
        }

        if ($keepPiVars) {
            $piVars = array_merge(
                SearchHelper::explodePiVars(GeneralUtility::_GPmerged('tx_kesearch_pi1')),
                $piVars
            );
        }

        $linkedContent = $this->renderChildren();
        if (empty($content)) {
            $content = $linkedContent;
        }

        $url = SearchHelper::searchLink($page, $piVars, $resetFilters);

        if ($uriOnly) {
            return $url;
        }

        if ($url === '' || $linkedContent === $url) {
            return $linkedContent;
        }

        if ($this->hasArgument('section')) {
            $url .= '#' . $this->arguments['section'];
        }

        $this->tag->addAttribute('href', $url);

        if (empty($content)) {
            $content = $linkedContent;
        }
        $this->tag->setContent($content);

        return $this->tag->render();
   }
}