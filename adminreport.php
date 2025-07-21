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
 * Global log report table for administrators
 *
 * @package    mod_openai_chat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2024 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_openai_chat\adminreport;

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/adminlib.php');
global $DB;

require_login();
require_capability('mod/openai_chat:viewallreports', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$tsort = optional_param('tsort', '', PARAM_TEXT);

$pageurl = $CFG->wwwroot . "/mod/openai_chat/adminreport.php?" .
    "courseid=$courseid" .
    "&user=$user" .
    "&starttime=$starttime" .
    "&endtime=$endtime";
$starttime_ts = strtotime($starttime);
$endtime_ts = strtotime($endtime);

$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('openai_chat_logs', 'mod_openai_chat'));
$PAGE->set_heading(get_string('openai_chat_logs', 'mod_openai_chat'));

admin_externalpage_setup('mod_openai_chat_report');

$datetime = new DateTime();
$table = new \mod_openai_chat\adminreport(time());
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloading(
    $download, 
    get_string('downloadfilename', 'mod_openai_chat') 
        . '_admin_' 
        . $datetime->format(DateTime::ATOM)
);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('openai_chat_logs', 'mod_openai_chat') . ' - ' . get_string('administration'));
    
    // Get courses for filter.
    $courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
    $courses[0] = get_string('all');
    ksort($courses);
    
    echo $OUTPUT->render_from_template('mod_openai_chat/adminreport_page', [
        "courseid" => $courseid,
        "courses" => $courses,
        "user" => $user,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "link" => (new moodle_url("/mod/openai_chat/adminreport.php"))->out()
    ]);
}

$where = "1=1";
$params = [];

// Filter by course.
if ($courseid) {
    $where .= " AND oc.course = :courseid";
    $params['courseid'] = $courseid;
}

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
    "ocl.*, CONCAT(u.firstname, ' ', u.lastname) as user_name, c.fullname as course_name, oc.name as activity_name", 
    "{mod_openai_chat_log} ocl 
        JOIN {user} u ON u.id = ocl.userid
        JOIN {openai_chat} oc ON oc.id = ocl.instanceid
        JOIN {course} c ON c.id = oc.course",
    $where,
    $params
);
$table->define_baseurl($pageurl);
$table->out(50, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}