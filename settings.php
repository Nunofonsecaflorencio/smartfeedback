<?php

/**
 * This file defines the admin settings for this plugin
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$settings->add(new admin_setting_configcheckbox(
    'assignfeedback_smartfeedback/default',
    new lang_string('default', 'assignfeedback_smartfeedback'),
    new lang_string('default_help', 'assignfeedback_smartfeedback'),
    1
));


// OpenAI API settings
$settings->add(new admin_setting_heading(
    'assignfeedback_smartfeedback/openaiheading',
    new lang_string('openaiheading', 'assignfeedback_smartfeedback'),
    new lang_string('openaiheading_help', 'assignfeedback_smartfeedback')
));

$settings->add(new admin_setting_configtext(
    'assignfeedback_smartfeedback/apikey',
    new lang_string('apikey', 'assignfeedback_smartfeedback'),
    new lang_string('apikey_help', 'assignfeedback_smartfeedback'),
    '',
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'assignfeedback_smartfeedback/model',
    new lang_string('model', 'assignfeedback_smartfeedback'),
    new lang_string('model_help', 'assignfeedback_smartfeedback'),
    'gpt-4-turbo',
    PARAM_TEXT
));

$settings->add(new admin_setting_configtext(
    'assignfeedback_smartfeedback/maxtoken',
    new lang_string('maxtoken', 'assignfeedback_smartfeedback'),
    new lang_string('maxtoken_help', 'assignfeedback_smartfeedback'),
    '1024',
    PARAM_INT
));
