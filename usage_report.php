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
 * Usage report page showing token consumption by agent/user
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');
require_once($CFG->dirroot . '/mod/intebchat/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course module ID (optional)
$period = optional_param('period', 30, PARAM_INT); // Days to look back

// If ID is provided, we're looking at a specific instance
if ($id) {
    $cm = get_coursemodule_from_id('intebchat', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $intebchat = $DB->get_record('intebchat', array('id' => $cm->instance), '*', MUST_EXIST);
    
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    require_capability('mod/intebchat:viewreport', $context);
    
    $PAGE->set_url('/mod/intebchat/usage_report.php', array('id' => $id));
    $PAGE->set_title(format_string($intebchat->name) . ' - ' . get_string('tokens', 'mod_intebchat'));
    $PAGE->set_heading(format_string($course->fullname));
} else {
    // Site-wide report
    require_login();
    require_capability('mod/intebchat:viewallreports', context_system::instance());
    
    $PAGE->set_url('/mod/intebchat/usage_report.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('intebchat_logs', 'mod_intebchat') . ' - ' . get_string('tokens', 'mod_intebchat'));
    $PAGE->set_heading(get_string('intebchat_logs', 'mod_intebchat'));
}

echo $OUTPUT->header();

// Calculate time range
$endtime = time();
$starttime = strtotime("-$period days");

// Get usage data
$usage_by_agent = intebchat_get_usage_by_agent($starttime, $endtime, $id ? $intebchat->id : 0);
$usage_by_user = intebchat_get_usage_by_user($starttime, $endtime, $id ? $intebchat->id : 0);
$daily_usage = intebchat_get_daily_usage($period, $id ? $intebchat->id : 0);

// Summary statistics
$total_tokens = 0;
$total_requests = 0;
$total_cost = 0;

foreach ($usage_by_agent as $agent) {
    $total_tokens += $agent->total_tokens;
    $total_requests += $agent->request_count;
    
    // Calculate estimated cost
    $model = ($agent->agentid == 'default') ? 'gpt-4o-mini' : $agent->agentid;
    $total_cost += intebchat_calculate_estimated_cost(
        $agent->total_prompt_tokens,
        $agent->total_completion_tokens,
        $model
    );
}

// Display summary cards
?>
<div class="mod_intebchat usage-report-container">
    <h2><?php echo get_string('summary', 'mod_intebchat'); ?></h2>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title"><?php echo number_format($total_requests); ?></h3>
                    <p class="card-text">Total Requests</p>
                    <i class="fa fa-exchange-alt fa-3x text-muted"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title"><?php echo number_format($total_tokens); ?></h3>
                    <p class="card-text"><?php echo get_string('tokens', 'mod_intebchat'); ?></p>
                    <i class="fa fa-coins fa-3x text-muted"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title">$<?php echo number_format($total_cost, 2); ?></h3>
                    <p class="card-text">Estimated Cost</p>
                    <i class="fa fa-dollar-sign fa-3x text-muted"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="card-title"><?php echo count($usage_by_user); ?></h3>
                    <p class="card-text"><?php echo get_string('activeusers', 'mod_intebchat'); ?></p>
                    <i class="fa fa-users fa-3x text-muted"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Usage by Agent/Model -->
    <h3>Usage by Agent/Model</h3>
    <div class="table-responsive mb-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Agent/Model</th>
                    <th>Requests</th>
                    <th>Prompt Tokens</th>
                    <th>Completion Tokens</th>
                    <th>Total Tokens</th>
                    <th>Est. Cost</th>
                    <th>Last Used</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usage_by_agent as $agent): ?>
                <tr>
                    <td><code><?php echo s($agent->agentid); ?></code></td>
                    <td><?php echo number_format($agent->request_count); ?></td>
                    <td><?php echo number_format($agent->total_prompt_tokens); ?></td>
                    <td><?php echo number_format($agent->total_completion_tokens); ?></td>
                    <td><strong><?php echo number_format($agent->total_tokens); ?></strong></td>
                    <td>$<?php 
                        $cost = intebchat_calculate_estimated_cost(
                            $agent->total_prompt_tokens,
                            $agent->total_completion_tokens,
                            $agent->agentid
                        );
                        echo number_format($cost, 3);
                    ?></td>
                    <td><?php echo userdate($agent->last_use); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Users -->
    <h3><?php echo get_string('topusers', 'mod_intebchat'); ?></h3>
    <div class="table-responsive mb-4">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th><?php echo get_string('username', 'mod_intebchat'); ?></th>
                    <th>Requests</th>
                    <th>Total Tokens</th>
                    <th>Avg Tokens/Request</th>
                    <th>Last Activity</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $count = 0;
                foreach ($usage_by_user as $user): 
                    if ($count++ >= 20) break;
                ?>
                <tr>
                    <td>
                        <a href="<?php echo $CFG->wwwroot; ?>/user/profile.php?id=<?php echo $user->userid; ?>">
                            <i class="fa fa-user"></i> <?php echo fullname($user); ?>
                        </a>
                    </td>
                    <td><?php echo number_format($user->request_count); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <i class="fa fa-coins"></i> <?php echo number_format($user->total_tokens); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($user->total_tokens / $user->request_count, 1); ?></td>
                    <td><?php echo userdate($user->last_use); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Daily Usage Chart (simplified) -->
    <h3>Daily Token Usage (Last <?php echo $period; ?> days)</h3>
    <div class="card">
        <div class="card-body">
            <?php if (!empty($daily_usage)): ?>
            <div style="height: 300px; overflow-x: auto;">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Requests</th>
                            <th>Tokens</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_usage as $day): ?>
                        <tr>
                            <td><?php echo $day->usage_date; ?></td>
                            <td><?php echo $day->request_count; ?></td>
                            <td><?php echo number_format($day->daily_total_tokens); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No usage data available for this period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-4">
    <?php if ($id): ?>
        <a href="<?php echo new moodle_url('/mod/intebchat/report.php', array('id' => $id)); ?>" 
           class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Message Log
        </a>
    <?php else: ?>
        <a href="<?php echo new moodle_url('/mod/intebchat/adminreport.php'); ?>" 
           class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Admin Report
        </a>
    <?php endif; ?>
</div>

<?php
echo $OUTPUT->footer();