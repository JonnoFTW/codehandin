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
 * Store the users grade if they have made a submission
 * This code probably needs to be redone using a form api
 *
 * @package   mod_codehandin
 * @category  grade
 * @copyright 2013 Jonathan Mackenzie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

$id = required_param('id', PARAM_INT);          // Course module ID
$userid = required_param('userid', PARAM_INT); // Graded user ID 
$context = get_context_instance(CONTEXT_MODULE, $USER->id);
$submit_id = required_param("submit_id", PARAM_INT);
$auto_grade = required_param("auto_grade", PARAM_INT);
$teacher_grade = required_param("teacher_grade", PARAM_INT);
$teacher_comment = required_param("teacher_comment", PARAM_TEXT);
if(has_capability('mod/codehandin:addinstance',$context)) {
    // Teachers are allowed to grade
    // Check if the submission actually belongs to the user we are grading
    $sub = $DB->get_record("codehandin_submission",array("id"=>$submit_id));
    $ass = $DB->get_record("codehandin",array("id"=>$sub->aid));
    if($userid !== $sub->userid) {
        redirect("view.php?id=$id&user=$userid","This user does not own that submission");
    }
    // Finally, take the teacher back to the list of submissions, with a message noting that 
    // the submission was successfully graded
    
    $data = array("id"=> $submit_id ,// id of the submission to update
                  "auto_grade"=>$auto_grade,
                  "teacher_comment"=>$teacher_comment,
                  "teacher_grade"=>$teacher_grade
    );
    $s = $DB->update_record("codehandin_submission",$data);
    redirect("view.php?id=$id&show=1",$s?"Successfully graded":"Grading not successful");
}  else {
    // students are redirected back the view
    redirect('view.php?id='.$id);
}