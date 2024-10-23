<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     ltisource_supply_completed_activity_info
 * @category    string
 * @copyright   2024 Vincent Nys <vincent.nys@ap.be>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds information regarding completed activities to the LTI tool launch request.
 *
 * This callback function is triggered before launching an LTI tool.
 * It only adds the information if the LTI tool supplies the custom parameter 'supply_completed_activities' with a value of 'true'.
 *
 * @param stdClass $tool_instance The LTI tool instance object, containing the course ID and other metadata.
 * @param string $tool_endpoint The URL of the LTI tool endpoint.
 * @param array $request_params The array of request parameters that will be sent to the LTI tool, including custom parameters.
 * 
 * @global moodle_database $DB The global Moodle database object for accessing course and assignment data.
 * 
 * @return array The modified request parameters, potentially including the user's completed activities in the 'custom_completed_activities' field.
 */
function ltisource_supply_completed_activity_info_before_launch($tool_instance, $tool_endpoint, $request_params)
{
    global $DB;
    // don't perform additional work if the LTI tool does not need the info
    if ($request_params['custom_supply_completed_activities'] == "true") {
        $course_id = $tool_instance->course;
        $user_id = $request_params['user_id'];
        $course = $DB->get_record('course', ['id' => $course_id]);
        $completion = new completion_info($course);
        $modules = get_coursemodules_in_course('assign', $course_id);
        $completed_activities = [];
        foreach ($modules as $cm) {
            $data = $completion->get_data($cm, true, $user_id);
            $assign = $DB->get_record('assign', ['id' => $cm->instance]);
            if ($data->completionstate == COMPLETION_COMPLETE) {
                $completed_activities[] = [
                    'id' => $cm->id,
                    'description' => $assign->intro,
                    'title' => $assign->name
                ];
            }
        }
        $request_params['custom_completed_activities'] = json_encode($completed_activities);
    }
    return $request_params;
}
