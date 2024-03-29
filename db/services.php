<?php

/**
 * Portfolio builder services definition
 *
 * @package     mod_portfoliobuilder
 * @copyright   2023 World Bank Group <https://worldbank.org>
 * @author      Willian Mano <willianmanoaraujo@gmail.com>
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_portfoliobuilder_enrolledusers' => [
        'classname' => 'mod_portfoliobuilder\external\course',
        'classpath' => 'mod/portfoliobuilder/classes/external/course.php',
        'methodname' => 'enrolledusers',
        'description' => 'Get the list of enrolled users in a course',
        'type' => 'read',
        'ajax' => true
    ],
    'mod_portfoliobuilder_togglereaction' => [
        'classname' => 'mod_portfoliobuilder\external\reaction',
        'classpath' => 'mod/portfoliobuilder/classes/external/reaction.php',
        'methodname' => 'toggle',
        'description' => 'Toggle a user reaction',
        'type' => 'write',
        'ajax' => true
    ],
    'mod_portfoliobuilder_addcomment' => [
        'classname' => 'mod_portfoliobuilder\external\comment',
        'classpath' => 'mod/portfoliobuilder/classes/external/comment.php',
        'methodname' => 'add',
        'description' => 'Add a new comment',
        'type' => 'write',
        'ajax' => true
    ],
    'mod_portfoliobuilder_editcomment' => [
        'classname' => 'mod_portfoliobuilder\external\comment',
        'classpath' => 'mod/portfoliobuilder/classes/external/comment.php',
        'methodname' => 'edit',
        'description' => 'Edit a new comment',
        'type' => 'write',
        'ajax' => true
    ],
    'mod_portfoliobuilder_loadportfolios' => [
        'classname' => 'mod_portfoliobuilder\external\portfolio',
        'classpath' => 'mod/portfoliobuilder/classes/external/portfolio.php',
        'methodname' => 'load',
        'description' => 'Load users portfolios',
        'type' => 'read',
        'ajax' => true
    ],
    'mod_portfoliobuilder_gradeportfolio' => [
        'classname' => 'mod_portfoliobuilder\external\grade',
        'classpath' => 'mod/portfoliobuilder/classes/external/grade.php',
        'methodname' => 'grade',
        'description' => 'Grade a user portfolio',
        'type' => 'write',
        'ajax' => true
    ],
    'mod_portfoliobuilder_entrydelete' => [
        'classname' => 'mod_portfoliobuilder\external\entry',
        'classpath' => 'mod/portfoliobuilder/classes/external/entry.php',
        'methodname' => 'delete',
        'description' => 'Delete a portfolio entry',
        'type' => 'write',
        'ajax' => true
    ],
    'mod_portfoliobuilder_alchemy_comment_add' => [
        'classname' => 'mod_portfoliobuilder\external\alchemy',
        'classpath' => 'mod/portfoliobuilder/classes/external/alchemy.php',
        'methodname' => 'comment_add',
        'description' => 'Add a comment in a portfolio entry',
        'type' => 'write',
    ],
    'mod_portfoliobuilder_alchemy_comment_get' => [
        'classname' => 'mod_portfoliobuilder\external\alchemy',
        'classpath' => 'mod/portfoliobuilder/classes/external/alchemy.php',
        'methodname' => 'comment_get',
        'description' => 'Get all portfolio entries of a given date in y-m-d format',
        'type' => 'read',
    ],
];

$services = [
    'Alchemy IA portfolio integration' => [
        'functions' => [
            'mod_portfoliobuilder_alchemy_comment_add',
            'mod_portfoliobuilder_alchemy_comment_get'
        ],
        'restrictedusers' => 1,
        'enabled' => 1
    ]
];
