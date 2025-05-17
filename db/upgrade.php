<?php

/**
 * Upgrade code for install
 *
 * @package   assignfeedback_smartfeedback
 * @copyright 2025, Nuno Fonseca <nunofonsecaflorencio@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_assignfeedback_smartfeedback_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025041401) {
        $table = new xmldb_table('assignfeedback_smartfeedback_conf');
        // $field = new xmldb_field('referencematerial');

        // if ($dbman->field_exists($table, $field)) {
        //     $dbman->rename_field($table, $field, 'reference_files_vs_id');
        // }

        if ($dbman->table_exists($table)) {
            $dbman->rename_table($table, "assignfeedback_smartfeedback_configs");
        }

        // upgrade_mod_savepoint(true, 2025041401, 'assignfeedback_smartfeedback');
    }

    return true;
}
