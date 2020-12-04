<?php
declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Christian Buelter
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

namespace TeaminmediasPluswerk\KeSearch\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;

/**
 * Mapper for having a static list of mapping them to value properties.
 *
 * routeEnhancers:
 *   MyBlogExample:
 *     type: Extbase
 *     extension: BlogExample
 *     plugin: Pi1
 *     routes:
 *       - { routePath: '/archive/{year}', _controller: 'Blog::archive' }
 *     defaultController: 'Blog::list'
 *     aspects:
 *       year:
 *         type: StaticValueMapper
 *         map:
 *           2k17: '2017'
 *           2k18: '2018'
 *           next: '2019'
 *         # (optional)
 *         localeMap:
 *           - locale: 'en_US.*|en_GB.*'
 *             map:
 *               twenty-seventeen: '2017'
 *               twenty-eighteen: '2018'
 *               next: '2019'
 *           - locale: 'fr_FR'
 *             map:
 *               vingt-dix-sept: '2017'
 *               vingt-dix-huit: '2018'
 *               prochain: '2019'
 */
class UrlEncodeMapper implements StaticMappableAspectInterface
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function generate(string $value): ?string
    {
        return urlencode($value);
    }

    public function resolve(string $value): ?string
    {
        return urldecode($value);
    }
}
