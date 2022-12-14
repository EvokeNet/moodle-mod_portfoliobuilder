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
    'mod_portfoliobuilder_enrolledusers' => [
        'classname' => 'mod_portfoliobuilder\external\course',
        'classpath' => 'mod/portfoliobuilder/classes/external/course.php',
        'methodname' => 'enrolledusers',
        'description' => 'Get the list of enrolled users in a course',
        'type' => 'read',
        'ajax' => true
    ],
    'mod_portfoliobuilder_createentry' => [
        'classname' => 'mod_portfoliobuilder\external\entry',
        'classpath' => 'mod/portfoliobuilder/classes/external/entry.php',
        'methodname' => 'create',
        'description' => 'Creates a new skill',
        'type' => 'write',
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
];
