<?php
/** @var string $_EXTKEY */

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Fluid Styled Responsive Images',
    'description' => 'Enables creation of responsive images for fluid styled content elements.',
    'category' => 'fe',
    'version' => '9.0.0',
    'state' => 'alpha',
    'uploadfolder' => false,
    'clearcacheonload' => true,
    'author' => 'Alexander Schnitzler',
    'author_email' => 'git@alexanderschnitzler.de',
    'constraints' => [
        'depends' => [
            'php' => '7.0.0-7.1.99',
            'typo3' => '9.0.0-9.0.99',
            'fluid_styled_content' => '9.0.0-9.0.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
);
