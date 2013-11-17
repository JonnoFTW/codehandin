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
 * Redirect the user to the appropriate submission related page
 *
 * @package   mod_codehandin
 * @category  grade
 * @copyright 2013 Jonathan Mackenzie
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "../../config.php");

$id = required_param('id', PARAM_INT);          // Course module ID
$itemnumber = optional_param('itemnumber', 0, PARAM_INT); // Item number, may be != 0 for activities that allow more than one grade per user
$userid = optional_param('userid', 0, PARAM_INT); // Graded user ID (optional)
$context = get_context_instance(CONTEXT_MODULE, $USER->id);
if(has_capability('mod/codehandin:addinstance',$context)) {
    // Teachers are redirected to the student
    redirect("view.php?id=$id&user=$userid");
}  else {
    //in the simplest case just redirect to the view page, their grade will be there
    redirect('view.php?id='.$id);
}