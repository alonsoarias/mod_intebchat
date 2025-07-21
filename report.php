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
 * Log report table for individual activity
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2024 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_openai_chat\report;

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course module ID.
$download = optional_param('download', '', PARAM_ALPHA);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$tsort = optional_param('tsort', '', PARAM_TEXT);

// Get course module, course, and openai_chat instance.
$cm = get_coursemodule_from_id('openai_chat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$openai_chat = $DB->get_record('openai_chat', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/openai_chat:viewreport', $context);

$pageurl = $CFG->wwwroot . "/mod/openai_chat/report.php?id=$id" .
    "&user=$user" .
    "&starttime=$starttime" .
    "&endtime=$endtime";
$starttime_ts = strtotime($starttime);
$endtime_ts = strtotime($endtime);

$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title(format_string($openai_chat->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('openai_chat_logs', 'mod_openai_chat'));

$datetime = new DateTime();
$table = new \mod_openai_chat\report(time());
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloading(
    $download, 
    get_string('downloadfilename', 'mod_openai_chat') 
        . '_' 
        . $datetime->format(DateTime::ATOM)
);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('openai_chat_logs', 'mod_openai_chat'));
    echo $OUTPUT->render_from_template('mod_openai_chat/report_page', [
        "id" => $id,
        "user" => $user,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "link" => (new moodle_url("/mod/openai_chat/report.php"))->out()
    ]);
}

$where = "ocl.instanceid = :instanceid";
$params = ['instanceid' => $openai_chat->id];

// Filter by user, starttime, endtime.
if ($user) {
    $where .= " AND CONCAT(u.firstname, ' ', u.lastname) like :user";
    $params['user'] = '%'.$user.'%';
}
if ($starttime_ts) {
    $where .= " AND ocl.timecreated > :starttime";
    $params['starttime'] = $starttime_ts;
}
if ($endtime_ts) {
    $where .= " AND ocl.timecreated < :endtime";
    $params['endtime'] = $endtime_ts;
}

if (!$tsort) {
    $where .= " ORDER BY ocl.timecreated DESC";
}

$table->set_sql(
    "ocl.*, CONCAT(u.firstname, ' ', u.lastname) as user_name", 
    "{mod_openai_chat_log} ocl 
        JOIN {user} u ON u.id = ocl.userid",
    $where,
    $params
);
$table->define_baseurl($pageurl);
$table->out(10, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}