<?php
/**
 * Strings for component 'assignfeedback_smartfeedback', language 'en'
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['default'] = 'Enabled by default';
$string['default_help'] = 'If set, this feedback method will be enabled by default for all new assignments.';
$string['enabled'] = 'Smart feedback';
$string['enabled_help'] = 'If enabled, this will provide AI-powered automatic feedback for student submissions.';
$string['pluginname'] = 'Smart Feedback';

// OpenAI settings
$string['openaiheading'] = 'OpenAI API Settings';
$string['openaiheading_help'] = 'Configure the OpenAI API integration for automated feedback generation.';
$string['apikey'] = 'OpenAI API Key';
$string['apikey_help'] = 'Your OpenAI API key for accessing the service. Keep this private and secure.';
$string['model'] = 'OpenAI Model';
$string['model_help'] = 'The OpenAI model to use for generating feedback (e.g., gpt-4-turbo, gpt-3.5-turbo).';
$string['maxtoken'] = 'Maximum Tokens';
$string['maxtoken_help'] = 'Maximum number of tokens that can be generated in a response.';


// Form elements for assignment setup
$string['feedbackinstructions'] = 'Feedback Instructions';
$string['feedbackinstructions_help'] = 'Instructions for the AI on how to evaluate and provide feedback for this specific assignment.';
$string['referencematerial'] = 'Reference Material';
$string['referencematerial_help'] = 'Source of truth or reference content that the AI should use when evaluating student submissions.';

// Process messages
$string['generatingfeedback'] = 'Generating AI feedback...';
$string['feedbackgenerated'] = 'AI feedback has been generated successfully.';
$string['feedbackgenerationfailed'] = 'Failed to generate AI feedback. Please check your settings or try again later.';
$string['noapikey'] = 'OpenAI API key is not configured. Please contact your administrator.';