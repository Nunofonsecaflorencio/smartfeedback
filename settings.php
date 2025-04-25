<?php
/**
 * This file defines the admin settings for this plugin
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox('assignfeedback_smartfeedback/default',
                   new lang_string('default', 'assignfeedback_smartfeedback'),
                   new lang_string('default_help', 'assignfeedback_smartfeedback'), 1));
