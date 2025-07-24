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
 * Test script to diagnose token tracking issues
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');

// This script should only be run from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line\n");
}

echo "=== INTEB Chat Token Tracking Diagnostic ===\n\n";

// Test 1: Check if tables exist
echo "1. Checking database tables...\n";
$tables = ['intebchat', 'mod_intebchat_log', 'mod_intebchat_token_usage', 'mod_intebchat_usage'];
foreach ($tables as $table) {
    if ($DB->get_manager()->table_exists($table)) {
        echo "   ✓ Table '{$table}' exists\n";
    } else {
        echo "   ✗ Table '{$table}' does NOT exist\n";
    }
}
echo "\n";

// Test 2: Check token fields in log table
echo "2. Checking token fields in mod_intebchat_log...\n";
$columns = $DB->get_columns('mod_intebchat_log');
$token_fields = ['prompttokens', 'completiontokens', 'totaltokens'];
foreach ($token_fields as $field) {
    if (isset($columns[$field])) {
        echo "   ✓ Field '{$field}' exists\n";
    } else {
        echo "   ✗ Field '{$field}' does NOT exist\n";
    }
}
echo "\n";

// Test 3: Test token normalization function
echo "3. Testing token normalization function...\n";
$test_cases = [
    // Old format
    ['prompt_tokens' => 10, 'completion_tokens' => 20, 'total_tokens' => 30],
    // New format
    ['input_tokens' => 15, 'output_tokens' => 25, 'total_tokens' => 40],
    // Mixed (shouldn't happen but testing robustness)
    ['prompt_tokens' => 5, 'output_tokens' => 10],
    // Empty
    [],
    // Null
    null
];

foreach ($test_cases as $i => $case) {
    echo "   Test case " . ($i + 1) . ": ";
    $result = intebchat_normalize_usage($case);
    if ($result) {
        echo "prompt={$result['prompt']}, completion={$result['completion']}, total={$result['total']}\n";
    } else {
        echo "null (no tokens)\n";
    }
}
echo "\n";

// Test 4: Check recent log entries
echo "4. Checking recent log entries for token data...\n";
$recent_logs = $DB->get_records_sql(
    "SELECT id, userid, totaltokens, prompttokens, completiontokens, timecreated 
     FROM {mod_intebchat_log} 
     ORDER BY timecreated DESC 
     LIMIT 10"
);

if (empty($recent_logs)) {
    echo "   No log entries found\n";
} else {
    $with_tokens = 0;
    $without_tokens = 0;
    
    foreach ($recent_logs as $log) {
        if ($log->totaltokens > 0) {
            $with_tokens++;
        } else {
            $without_tokens++;
        }
    }
    
    echo "   Found " . count($recent_logs) . " recent log entries:\n";
    echo "   - With token data: $with_tokens\n";
    echo "   - Without token data: $without_tokens\n";
    
    if ($with_tokens > 0) {
        echo "   Sample entry with tokens:\n";
        foreach ($recent_logs as $log) {
            if ($log->totaltokens > 0) {
                echo "     ID: {$log->id}, User: {$log->userid}, ";
                echo "Tokens: {$log->totaltokens} (prompt: {$log->prompttokens}, completion: {$log->completiontokens}), ";
                echo "Time: " . date('Y-m-d H:i:s', $log->timecreated) . "\n";
                break;
            }
        }
    }
}
echo "\n";

// Test 5: Check token usage tracking
echo "5. Checking token usage tracking...\n";
$token_usage = $DB->get_records_sql(
    "SELECT userid, tokensused, periodstart, periodtype 
     FROM {mod_intebchat_token_usage} 
     ORDER BY periodstart DESC 
     LIMIT 5"
);

if (empty($token_usage)) {
    echo "   No token usage records found\n";
} else {
    echo "   Found " . count($token_usage) . " token usage records\n";
    foreach ($token_usage as $usage) {
        echo "   - User: {$usage->userid}, Tokens: {$usage->tokensused}, ";
        echo "Period: {$usage->periodtype}, Start: " . date('Y-m-d', $usage->periodstart) . "\n";
    }
}
echo "\n";

// Test 6: Check detailed usage tracking (if table exists)
if ($DB->get_manager()->table_exists('mod_intebchat_usage')) {
    echo "6. Checking detailed usage tracking...\n";
    $detailed_usage = $DB->get_records_sql(
        "SELECT agentid, COUNT(*) as count, SUM(totaltokens) as total_tokens 
         FROM {mod_intebchat_usage} 
         GROUP BY agentid 
         ORDER BY total_tokens DESC 
         LIMIT 5"
    );
    
    if (empty($detailed_usage)) {
        echo "   No detailed usage records found\n";
    } else {
        echo "   Token usage by agent/model:\n";
        foreach ($detailed_usage as $usage) {
            echo "   - {$usage->agentid}: {$usage->count} requests, {$usage->total_tokens} tokens\n";
        }
    }
} else {
    echo "6. Detailed usage table not found (run upgrade script)\n";
}
echo "\n";

// Test 7: API configuration check
echo "7. Checking API configuration...\n";
$config = get_config('mod_intebchat');
if (empty($config->apikey)) {
    echo "   ✗ No API key configured\n";
} else {
    echo "   ✓ API key configured (length: " . strlen($config->apikey) . ")\n";
}
echo "   API type: " . ($config->type ?: 'not set') . "\n";
echo "   Token limit enabled: " . ($config->enabletokenlimit ? 'Yes' : 'No') . "\n";
if ($config->enabletokenlimit) {
    echo "   Max tokens per user: " . ($config->maxtokensperuser ?: 'not set') . "\n";
    echo "   Period: " . ($config->tokenlimitperiod ?: 'not set') . "\n";
}
echo "\n";

echo "=== Diagnostic Complete ===\n\n";

echo "Recommendations:\n";
echo "1. Ensure all database upgrades have been run\n";
echo "2. Enable debugging to see token usage in error logs\n";
echo "3. Check that the API is returning usage data in responses\n";
echo "4. Verify that logging is enabled in plugin settings\n";