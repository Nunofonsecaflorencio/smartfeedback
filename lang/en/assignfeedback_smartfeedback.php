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
$string['referencematerials'] = 'Reference Material';
$string['referencematerials_help'] = 'Source of truth or reference content that the AI should use when evaluating student submissions.';

// prompts
$string['assistantprompttemplate'] = 'You are an educational assistant using Vygotsky\'s theory of constructivism (dont mention this) to guide your feedback. '
    . '\nThe assignment is titled "{$a->assignmentname}" and it is described as: "{$a->assignmentdescription}".'
    . '\nUse the uploaded reference materials as your only source of truth. '
    . '\nDo not invent information or give direct solutions to the task. '
    . '\nInstead, offer guidance, scaffolding, and reflection to help the student construct their own understanding. '
    . '\nBase your support entirely on the materials provided and the context of the assignment.'
    . '\nThe teacher may have specific instructions for you: {$a->specificinstructions}';

$string['threadprompttemplate'] = 'Provide detailed and constructive feedback on the attached student submission. The feedback should:'
    . '- Focus on helping the student improve based on the assignment objectives. '
    . '- Be written in the same language used by the student in their submission.'
    . '- Use the provided reference materials to support your comments.'
    . '- Be formatted as HTML (paragraphs, bullet points, etc).'
    . '- Avoid giving away direct answers or solutions.'
    . '\n\nIn terms of response, follow this structure:'
    . '\n[Greeting and context]'
    . '\n[Areas for improvement and suggestions]'
    . '\n[Encouraging conclusion]'
    . '\nUse an encouraging and professional tone.';
