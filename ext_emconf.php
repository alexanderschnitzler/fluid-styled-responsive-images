<?php
/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Fluid Styled Responsive Images',
    'description' => 'Enables creation of responsive images for fluid styled content elements.',
    'category' => 'fe',
    'version' => '1.1.0',
    'state' => 'beta',
    'uploadfolder' => false,
    'clearcacheonload' => true,
    'author' => 'Alexander Schnitzler',
    'author_email' => 'git@alexanderschnitzler.de',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.99.99',
            'typo3' => '7.6.0-7.99.99',
            'fluid_styled_content' => '7.6.0',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
);
