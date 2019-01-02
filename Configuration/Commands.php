<?php

return [
    'ke_search:indexing' => [
        'class' => \TeaminmediasPluswerk\KeSearch\Command\StartIndexerCommand::class,
        'schedulable' => false,
    ],
    'ke_search:clearindex' => [
        'class' => \TeaminmediasPluswerk\KeSearch\Command\ClearIndexCommand::class,
        'schedulable' => true,
    ],
    'ke_search:removelock' => [
        'class' => \TeaminmediasPluswerk\KeSearch\Command\RemoveLockCommand::class,
        'schedulable' => true,
    ],
];
