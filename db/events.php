<?php
/**
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 $observers = [
    [
        'eventname' => '\\mod_assign\\event\\submission_created',
        'callback' => '\\assignfeedback_smartfeedback\\event\\submission_observer::submission_created',
    ],
    [
        'eventname' => '\\mod_assign\\event\\submission_updated',
        'callback' => '\\assignfeedback_smartfeedback\\event\\submission_observer::submission_updated',
    ],
];
