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
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_intebchat\adminreport;

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/adminlib.php');
global $DB;

require_login();
require_capability('mod/intebchat:viewallreports', context_system::instance());

$courseid = optional_param('courseid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$tsort = optional_param('tsort', '', PARAM_TEXT);

$pageurl = $CFG->wwwroot . "/mod/intebchat/adminreport.php?" .
    "courseid=$courseid" .
    "&user=$user" .
    "&starttime=$starttime" .
    "&endtime=$endtime";
$starttime_ts = strtotime($starttime);
$endtime_ts = strtotime($endtime);

$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('intebchat_logs', 'mod_intebchat'));
$PAGE->set_heading(get_string('intebchat_logs', 'mod_intebchat'));

admin_externalpage_setup('mod_intebchat_report');

$datetime = new DateTime();
$table = new \mod_intebchat\adminreport(time());
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloading(
    $download, 
    get_string('downloadfilename', 'mod_intebchat') 
        . '_admin_' 
        . $datetime->format(DateTime::ATOM)
);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('intebchat_logs', 'mod_intebchat') . ' - ' . get_string('administration'));
    
    // Add global statistics if token limit is enabled
    $config = get_config('mod_intebchat');
    if (!empty($config->enabletokenlimit)) {
        $stats = $table->get_global_stats();
        
        echo '<div class="mod_intebchat global-stats-container mb-4">';
        
        // Summary cards
        echo '<div class="row">';
        
        // Total messages card
        echo '<div class="col-md-3">';
        echo '<div class="card text-center">';
        echo '<div class="card-body">';
        echo '<h2 class="card-title">' . number_format($stats->total_messages) . '</h2>';
        echo '<p class="card-text">' . get_string('messages', 'mod_intebchat') . '</p>';
        echo '<i class="fa fa-comments fa-3x text-muted"></i>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Total tokens card
        echo '<div class="col-md-3">';
        echo '<div class="card text-center">';
        echo '<div class="card-body">';
        echo '<h2 class="card-title">' . number_format($stats->total_tokens) . '</h2>';
        echo '<p class="card-text">' . get_string('tokens', 'mod_intebchat') . '</p>';
        echo '<i class="fa fa-coins fa-3x text-muted"></i>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Active users card
        echo '<div class="col-md-3">';
        echo '<div class="card text-center">';
        echo '<div class="card-body">';
        echo '<h2 class="card-title">' . number_format($stats->active_users) . '</h2>';
        echo '<p class="card-text">' . get_string('activeusers', 'mod_intebchat') . '</p>';
        echo '<i class="fa fa-users fa-3x text-muted"></i>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Average tokens per message
        $avg_tokens = $stats->total_messages > 0 ? round($stats->total_tokens / $stats->total_messages, 1) : 0;
        echo '<div class="col-md-3">';
        echo '<div class="card text-center">';
        echo '<div class="card-body">';
        echo '<h2 class="card-title">' . $avg_tokens . '</h2>';
        echo '<p class="card-text">' . get_string('avgtoken', 'mod_intebchat') . '</p>';
        echo '<i class="fa fa-chart-line fa-3x text-muted"></i>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        
        // Top users and courses
        echo '<div class="row mt-4">';
        
        // Top users
        if (!empty($stats->top_users)) {
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header">';
            echo '<h5 class="mb-0"><i class="fa fa-trophy"></i> ' . get_string('topusers', 'mod_intebchat') . '</h5>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<table class="table table-sm">';
            echo '<thead><tr>';
            echo '<th>' . get_string('username', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('messages', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('tokens', 'mod_intebchat') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($stats->top_users as $topuser) {
                echo '<tr>';
                echo '<td><a class="user-link" href="/user/profile.php?id=' . $topuser->id . '">' . 
                     '<i class="fa fa-user"></i> ' . $topuser->firstname . ' ' . $topuser->lastname . '</a></td>';
                echo '<td>' . $topuser->message_count . '</td>';
                echo '<td><span class="badge badge-info"><i class="fa fa-coins"></i> ' . $topuser->total_tokens . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        // Top courses
        if (!empty($stats->top_courses)) {
            echo '<div class="col-md-6">';
            echo '<div class="card">';
            echo '<div class="card-header">';
            echo '<h5 class="mb-0"><i class="fa fa-graduation-cap"></i> ' . get_string('topcourses', 'mod_intebchat') . '</h5>';
            echo '</div>';
            echo '<div class="card-body">';
            echo '<table class="table table-sm">';
            echo '<thead><tr>';
            echo '<th>' . get_string('course') . '</th>';
            echo '<th>' . get_string('messages', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('tokens', 'mod_intebchat') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($stats->top_courses as $topcourse) {
                echo '<tr>';
                echo '<td><a class="course-link" href="/course/view.php?id=' . $topcourse->id . '">' . 
                     '<i class="fa fa-book"></i> ' . $topcourse->fullname . '</a></td>';
                echo '<td>' . $topcourse->message_count . '</td>';
                echo '<td><span class="badge badge-success"><i class="fa fa-coins"></i> ' . $topcourse->total_tokens . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // Get courses for filter.
    $courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
    $courses[0] = get_string('all');
    ksort($courses);
    
    echo $OUTPUT->render_from_template('mod_intebchat/adminreport_page', [
        "courseid" => $courseid,
        "courses" => $courses,
        "user" => $user,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "link" => (new moodle_url("/mod/intebchat/adminreport.php"))->out()
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
    "{mod_intebchat_log} ocl 
        JOIN {user} u ON u.id = ocl.userid
        JOIN {intebchat} oc ON oc.id = ocl.instanceid
        JOIN {course} c ON c.id = oc.course",
    $where,
    $params
);
$table->define_baseurl($pageurl);
$table->out(50, true);

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}