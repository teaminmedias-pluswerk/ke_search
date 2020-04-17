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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TeaminmediasPluswerk\KeSearch\Lib\Db;
use TeaminmediasPluswerk\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Command for completely clearing the ke_search index
 */
class ClearIndexCommand extends Command
{
    /**
     * Configure command
     */
    protected function configure()
    {
        $this->setDescription('Truncates the ke_search index table')
            ->setHelp(
                'Completely truncates the ke_search index table. Use with care!'
            )
            ->setAliases([
                'kesearch:clearindex',
                'ke_search:cleanindex',
                'kesearch:cleanindex'
            ]);
    }

    /**
     * Removes the lock for the ke_search index process
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Clear ke_search index table');

        /** @var IndexerRunner $indexerRunner */
        $indexerRunner = GeneralUtility::makeInstance(IndexerRunner::class);
        $indexerRunner->logger->log('notice', 'Clear index table started by command.');

        // get number of records in index
        $queryBuilder = Db::getQueryBuilder('tx_kesearch_index');
        $countIndex = $queryBuilder
            ->count('*')
            ->from('tx_kesearch_index')
            ->execute()
            ->fetchColumn(0);

        if ($countIndex > 0) {
            // ask for confirmation before processing
            $question = 'WARNING!' . chr(10);
            $question .= ' This will clear the whole index. Do you want to proceed?';
            if ($io->confirm($question, true)) {
                try {
                    $io->text($countIndex . ' index records found');
                    $databaseConnection = Db::getDatabaseConnection('tx_kesearch_index');
                    $databaseConnection->truncate('tx_kesearch_index');
                    $io->success('ke_search index table was truncated');
                    $logMessage = 'Index table was cleared';
                    $logMessage .= ' (' . $countIndex . ' records deleted)';
                    $indexerRunner->logger->log('notice', $logMessage);
                } catch (\Exception $e) {
                    $io->error($e->getMessage());
                    $indexerRunner->logger->log('error', $e->getMessage());
                }
            }
        } else {
            $io->note('There are no entries in ke_search index table.');
        }

        return 0;

    }

}
