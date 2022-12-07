<?php

/**
 * Portfolio builder services definition
 *
 * @package     mod_portfoliobuilder
 * @copyright   2022 Willian Mano {@link https://conecti.me}
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_portfoliobuilder_createentry' => [
        'classname' => 'mod_portfoliobuilder\external\entry',
        'classpath' => 'mod/ortfoliobuilder/classes/external/entry.php',
        'methodname' => 'create',
        'description' => 'Creates a new skill',
        'type' => 'write',
        'ajax' => true
    ],
];
