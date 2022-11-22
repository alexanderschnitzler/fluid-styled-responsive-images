<?php
/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Fluid Styled Responsive Images',
    'description' => 'Enables creation of responsive images for fluid styled content elements.',
    'category' => 'fe',
    'version' => '11.5.1',
    'state' => 'stable',
    'uploadfolder' => false,
    'clearcacheonload' => true,
    'author' => 'Alexander Schnitzler',
    'author_email' => 'git@alexanderschnitzler.de',
    'constraints' => [
        'depends' => [
            'php' => '8.0.0-8.1.99',
            'typo3' => '11.5.1-11.5.99',
            'fluid_styled_content' => '11.5.1-11.5.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
