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
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \mod_intebchat\report;

require_once('../../config.php');
require_once($CFG->libdir.'/tablelib.php');
global $DB;

$id = required_param('id', PARAM_INT); // Course module ID.
$download = optional_param('download', '', PARAM_ALPHA);
$user = optional_param('user', '', PARAM_TEXT);
$starttime = optional_param('starttime', '', PARAM_TEXT);
$endtime = optional_param('endtime', '', PARAM_TEXT);
$tsort = optional_param('tsort', '', PARAM_TEXT);

// Get course module, course, and intebchat instance.
$cm = get_coursemodule_from_id('intebchat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$intebchat = $DB->get_record('intebchat', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/intebchat:viewreport', $context);

$pageurl = $CFG->wwwroot . "/mod/intebchat/report.php?id=$id" .
    "&user=$user" .
    "&starttime=$starttime" .
    "&endtime=$endtime";
$starttime_ts = strtotime($starttime);
$endtime_ts = strtotime($endtime);

$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('report');
$PAGE->set_title(format_string($intebchat->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('intebchat_logs', 'mod_intebchat'));

$datetime = new DateTime();
$table = new \mod_intebchat\report(time());
$table->show_download_buttons_at(array(TABLE_P_BOTTOM));
$table->is_downloading(
    $download, 
    get_string('downloadfilename', 'mod_intebchat') 
        . '_' 
        . $datetime->format(DateTime::ATOM)
);

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('intebchat_logs', 'mod_intebchat'));
    
    // Add token usage summary if enabled
    $config = get_config('mod_intebchat');
    if (!empty($config->enabletokenlimit)) {
        // Get statistics for this instance
        $stats = $table->get_user_stats($intebchat->id);
        
        echo '<div class="mod_intebchat token-stats-container mb-4">';
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h4 class="mb-0"><i class="fa fa-chart-bar"></i> ' . get_string('tokens', 'mod_intebchat') . ' - ' . get_string('summary') . '</h4>';
        echo '</div>';
        echo '<div class="card-body">';
        
        // Top users by token usage
        if (!empty($stats)) {
            echo '<h5>' . get_string('topusers', 'mod_intebchat') . '</h5>';
            echo '<div class="table-responsive">';
            echo '<table class="table table-sm">';
            echo '<thead><tr>';
            echo '<th>' . get_string('username', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('messagecount', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('tokens', 'mod_intebchat') . '</th>';
            echo '<th>' . get_string('lastactivity') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            $count = 0;
            foreach ($stats as $userstat) {
                if ($count >= 5) break;
                echo '<tr>';
                echo '<td><a class="user-link" href="/user/profile.php?id=' . $userstat->id . '">' . 
                     '<i class="fa fa-user"></i> ' . $userstat->firstname . ' ' . $userstat->lastname . '</a></td>';
                echo '<td>' . $userstat->message_count . '</td>';
                echo '<td><span class="badge badge-info"><i class="fa fa-coins"></i> ' . $userstat->total_tokens . '</span></td>';
                echo '<td class="text-muted">' . userdate($userstat->last_activity) . '</td>';
                echo '</tr>';
                $count++;
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    echo $OUTPUT->render_from_template('mod_intebchat/report_page', [
        "id" => $id,
        "user" => $user,
        "starttime" => $starttime,
        "endtime" => $endtime,
        "link" => (new moodle_url("/mod/intebchat/report.php"))->out()
    ]);
}

$where = "ocl.instanceid = :instanceid";
$params = ['instanceid' => $intebchat->id];

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
    "{mod_intebchat_log} ocl 
        JOIN {user} u ON u.id = ocl.userid",
    $where,
    $params
);
$table->define_baseurl($pageurl);
$table->out(10, true);

// Show total tokens if not downloading
if (!$table->is_downloading() && !empty($config->enabletokenlimit)) {
    $totaltokens = $table->get_total_tokens();
    if ($totaltokens > 0) {
        echo '<div class="alert alert-info mt-3">';
        echo '<i class="fa fa-info-circle"></i> ';
        echo get_string('totaltokensused', 'mod_intebchat', $totaltokens);
        echo '</div>';
    }
}

if (!$table->is_downloading()) {
    echo $OUTPUT->footer();
}