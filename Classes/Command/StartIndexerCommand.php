<?php

namespace TeaminmediasPluswerk\KeSearch\Command;

/***************************************************************
 *  Copyright notice
 *  (c) 2019 Andreas Kiefer <andreas.kiefer@pluswerk.ag>
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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for starting the index process of ke_search
 */
class StartIndexerCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setDescription('Starts the indexing process for ke_search')
            ->setHelp(
                'Will process all active indexer configuration records that are present in the database. '
                . 'There is no possibility to start indexing for a single indexer configuration')
            ->setAliases([
                'kesearch:index',
                'ke_search:indexing',
                'kesearch:indexing'
            ]);
    }

    /**
     * Runs the index process for ke_search
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Start ke_search indexer process');

        /** @var IndexerRunner $indexerRunner */
        $indexerRunner = GeneralUtility::makeInstance(IndexerRunner::class);
        $indexerRunner->logger->log('notice', 'Indexer process started by command.');
        $indexerResponse = $indexerRunner->startIndexing(
            true, [], 'CLI'
        );
        $indexerResponse = $indexerRunner->createPlaintextReport($indexerResponse);

        if (strstr($indexerResponse, 'You can\'t start the indexer twice')) {
            $io->warning(
                'Indexing lock is set. You can\'t start the indexer process twice.' . chr(10) . chr(10)
                . 'If lock was not reset because of indexer errors you can use ke_search:removelock command.'
            );
        } else {

            // set custom style
            $titleStyle = new OutputFormatterStyle('blue', 'black', array('bold'));
            $output->getFormatter()->setStyle('title', $titleStyle);
            $warning = false;

            foreach (explode(chr(10), $indexerResponse) as $line) {

                // skip empty lines
                if (empty(strip_tags($line))) {
                    continue;
                }

                // format lines and catch warnings
                if (strstr($line, 'There were errors')) {
                    $warning = strip_tags(str_replace('[DANGER] ', '', $line));
                } else if (strstr($line, 'Index contains')) {
                    $io->writeln('<info>' . strip_tags($line) . '</info>');
                } else if (strstr($line, '<p><b>')) {
                    $io->writeln(chr(10) . '<title>' . strip_tags($line) . '</>');
                } else {
                    $io->writeln(strip_tags($line));
                }
            }

            if ($warning !== false) {
                $io->warning($warning);
            }

            $io->success('Indexer process completed.');
        }

        return 0;
    }
}
