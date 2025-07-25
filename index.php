<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is a one-line short description of the file
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

$event = \mod_intebchat\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/intebchat/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_intebchat');
echo $OUTPUT->heading($modulenameplural);

if (!$intebchats = get_all_instances_in_course('intebchat', $course)) {
    notice(get_string('noopenaichats', 'mod_intebchat'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left');
}

foreach ($intebchats as $intebchat) {
    if (!$intebchat->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/intebchat/view.php', array('id' => $intebchat->coursemodule)),
            format_string($intebchat->name, true),
            array('class' => 'dimmed'));
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/intebchat/view.php', array('id' => $intebchat->coursemodule)),
            format_string($intebchat->name, true));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($intebchat->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();