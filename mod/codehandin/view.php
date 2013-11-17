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
 * Prints a particular instance of codehandin
 *
 *This shows the checkpoints and tests for a particular 
 * codehandin assignment. Should probably use a renderer to show specific. things.
 *
 * @package    mod_codehandin
 * @copyright  2013 Jonathan Mackenzie
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // codehandin instance ID - it should be named as the first character of the module
$show = optional_param("show", 0, PARAM_INT);
$student_id = optional_param('user',0,PARAM_INT);

if ($id) {
    $cm          = get_coursemodule_from_id('codehandin', $id, 0, false, MUST_EXIST);
    $course      = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $codehandin  = $DB->get_record('codehandin', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
    $codehandin  = $DB->get_record('codehandin', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $codehandin->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('codehandin', $codehandin->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = get_context_instance(CONTEXT_MODULE, $cm->id);

add_to_log($course->id, 'codehandin', 'view', "view.php?id={$cm->id}", $codehandin->name, $cm->id);

require_capability('mod/codehandin:view', $context);
/// Print the page header

$PAGE->set_url('/mod/codehandin/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($codehandin->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// other things you may want to set - remove if not needed
//$PAGE->set_cacheable(false);
//$PAGE->set_focuscontrol('some-html-id');
//$PAGE->add_body_class('codehandin-'.$somevar);

// Output starts here
echo $OUTPUT->header();

echo $OUTPUT->heading($codehandin->name);
if ($codehandin->intro) { // Conditions to show the intro can change to look for own settings or whatever
    echo $OUTPUT->box(format_module_intro('codehandin', $codehandin, $cm->id), 'generalbox mod_introbox', 'codehandinintro');
}

echo $OUTPUT->heading('Submission');
// Replace the following lines with your own code
// Show tests, show assessment tests if they are a teacher
// should probably denote which ones are assessment
$teacher = has_capability("mod/codehandin:addinstance",$context);
//show the submission box
if($teacher && $student_id)
    $submission_user_id = $student_id;
else 
    $submission_user_id = $USER->id;
$submission = $DB->get_record("codehandin_submission",["aid"=>$codehandin->id,"userid"=>$submission_user_id]);
$subbox = '';

//Make a table of submission details
$table = new html_table();

$timeremaining = (time() > $codehandin->duedate)?"This assignment is now overdue":format_time($codehandin->duedate);
$table->data = array(
    array('Submitted',$submission?"Yes":"No"),
    array('Due Date', userdate($codehandin->duedate)),
    array('Language', $codehandin->language),
    array('Time Remaining', $timeremaining),
    array('Codehandin ID', $cm->instance)
);
if($submission) {
    $compiling = $submission->compiles ? "" : " (non compiling)";
    $table->data[0][1] .=  $submission->timecreated > $codehandin->duedate ? " (submitted late)" : "";
    $table->data[]  = array("Submitted",userdate($submission->timecreated));
    if(!$teacher) {
        $table->data[] = array("Automatic Grade",$submission->auto_grade. $compiling);
        $table->data[] = array("Teacher Grade",$submission->teacher_grade);
        $table->data[] = array("Teacher Comment",$submission->teacher_comment);
    } else {
        $student = $DB->get_record("user",["id"=>$student_id]);
        // Should insert <form>
        $table->data[] = array("Student","$student->firstname $student->lastname (<a href='mailto:$student->email'>$student->email</a>)");
        $table->data[] = array("Automatic Grade","<form method='post' action='gradeSubmission.php?id=$id&userid=$submission->userid'><input type='hidden' value='".sesskey()."' name='sesskey' /><input type='hidden' name='submit_id' value='$submission->id'/><input type='number' name='auto_grade' value='$submission->auto_grade'/>" . $compiling);
        $table->data[] = array("Teacher Grade","<input name='teacher_grade' type='number' value='$submission->teacher_grade'>");
        $table->data[] = array("Teacher Comment","<textarea name='teacher_comment'>$submission->teacher_comment</textarea>");
        $table->data[] = array("Save","<input type='submit'/></form>");
    }
}
if($teacher) {
    unset($table->data[0]);
    $table->data[] = ["View Submissions","<a href='view.php?id=$id&show=1'>View</a>"];
}
echo html_writer::table($table);

function showCode($fileContent,$fileName) {
    global $OUTPUT,$codehandin;
    echo $OUTPUT->heading($fileName);
    echo "<pre class='prettyprint lang-{$codehandin->language}'>";
    echo htmlentities($fileContent);
    echo "</pre>";
}
// Show the submissions
if($teacher && $show) {
    $subs = $DB->get_records("codehandin_submission",["aid"=>$codehandin->id]);
    $table = new html_table();
    $table->head = ["Student","Auto Grade", "Teacher Grade", "Link"];
    foreach($subs as $s) {
        // Get the student thath this belongs to, could probably be done all in one
        // query above using join
        $student = $DB->get_record("user",["id"=>$s->userid]);
        $table->data[] = array("$student->firstname $student->lastname",// should probably use that thing that renames students to something local
                            $s->auto_grade,
                            $s->teacher_grade,
                            "<a href='view.php?id=$id&user=$student->id'>View</a>",
                            ); 
    }
    echo html_writer::table($table);
}


// Show the student's submission
elseif($teacher && $student_id) {
    // Prepare for the teacher's grading
    if(!$submission) {
        echo "This student hasn't submitted anything yet";
    } else {
    // If it's a single file, show it on the page and syntax highlighit it
        $fs = get_file_storage();
        $f = $fs->get_file_by_id($submission->fileid);
        if(!$f) {
            echo "Could not retrieve submitted file";
        } else {
            $PAGE->requires->js('/mod/codehandin/google-code-prettify/run_prettify.js');
            if($f->get_mimetype() == "text/plain") {
                // If the 
               showCode($f->get_content(),$f->get_filename());
            } else if($f->get_mimetype() == "application/zip") {
                $zip = new ZipArchive;
                $zipfile = $f->get_content_to_temp();
                $res = $zip->open($zipfile);
                $files = array();
                if($res === TRUE) {
                    $finfo = new finfo(FILEINFO_MIME);
                    for($i = 0; $i < $res->numFiles; $i++) {
                        $s = $zip->statIndex($i);
                        $fp = $zip->getStream($s['name']);
                        $files[] = $s['name'];
                        // We should probably only open source code files... since they might have included 
                        // a word doc or binary etc in their zip. check by file extension? we should also check
                        // mimetypes, because some cheeky student might rename their 10MB data file with .cpp
                        if(!$fp) {
                            echo "could not open ". $s['name']. "from zipfile</br>";
                        }  else {
                            $content = '';
                            while(!feof($fp)) {
                                $content .= fread($fp,1024);
                            }
                            fclose($fp);
                            $mime =explode(';', $finfo->buffer($content))[0];
                            $source_extensions = array('cpp','c','java','cxx','hxx','h','m','py','js','pl');
                            if($mime === "text/plain" && in_array(strtolower(end(explode('.',$s['name']))),$source_extensions))
                                showCode($content, $s['name']);
                        }
                    }
                    finfo_close($finfo);
                    echo "The submission contains the following files: <ul>";
                    $list = new html_list();
                    $list->type = 'unordered';
                    $list->load_data($files);
                    echo $OUTPUT->htmllist($list);
                } else {
                    echo "Could not open zip file: ".$f->get_filename();
                }
            }   
            // show a link to the file
          //  echo moodle_url::make_file_url();
            
           // $form = new html_form();
         //   $form->url = new moodle_url('grade.php',array('id'=>$codehandin->id,'student'=>$student_id);
            //$contents = $OUTPUT->
         //   echo $OUTPUT->form($form,$contents);
        }
    }
} else {
    // Show the checkpoints otherwise
    echo $OUTPUT->heading('Checkpoints:');
    $sql = "SELECT * 
              FROM {codehandin_checkpoint}
             WHERE {codehandin_checkpoint}.assign_id = {$cm->instance}";
    if(!$teacher)
       $sql .= " AND {codehandin_test}.assessment = 0";
    $sql .= " ORDER BY {codehandin_checkpoint}.ordering ASC";
    $checkpoints =  $DB->get_records_sql($sql);
    if(count($checkpoints) == 0) {
        $text = "There doesn't appear to be any checkpoints for this code handin project";
        echo $OUTPUT->box($text);
    } else {
        ?>
    <script type="text/javascript">// <![CDATA[
    // Toggle checkpoints on or off
    function showHide(divId) {
        if(document.getElementById(divId).style.display == 'block') {
            document.getElementById(divId).style.display = 'none';
        } else {
            document.getElementById(divId).style.display='block';
        }
    }
    // ]]>
    </script>
    <style type="text/css">
    .checkpoint {
        display:none;
    }
    .checkpoint_toggle {
        cursor:pointer;
    }
    </style>
        <?
        foreach($checkpoints as $v) {
            $checkpoint_id =  $v->ordering;
            $cp1 = $OUTPUT->heading("Checkpoint ".$checkpoint_id. ": ".$v->task );
            $cp1 .= $v->description;
            $cp1 .= "<br/><a class='checkpoint_toggle' onclick=\"javascript:showHide('check_".$checkpoint_id."')\">Show/hide tests</a>";
            $cp = $OUTPUT->heading("Tests",3);
            $tests = $DB->get_records("codehandin_test",array('checkpoint_id'=>$v->id));
            if (!$tests) $cp .= "There doesn't appear to be any tests for this checkpoint.";
            else
            foreach($tests as $test) {
                // These boxes might need a show/hide button when the input or output gets lengthy
                $t='';
                $fields = array('descr'=>"Description",
                            'input'=>"Input",
                            'output'=>"Output",
                            'stderr'=>"Standard Error",
                            'retval'=>"Return Value",
                            'runtime_args'=>"Runtime Arguments");
                foreach($fields as $f=>$full) {
                    if($test->{$f}) {
                        $t .= $OUTPUT->heading($full,4);
                        $t .= "<pre>".htmlentities($test->{$f})."</pre>";
                        $t .= "<hr/>";
                    }
                }
                $cp .= $OUTPUT->box($t);
            }
            echo $OUTPUT->box($cp1.$OUTPUT->box($cp,'checkpoint','check_'.$checkpoint_id));
        }
    }
}
// Finish the page
echo $OUTPUT->footer();
