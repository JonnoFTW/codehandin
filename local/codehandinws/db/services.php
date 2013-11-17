<?php

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
 * Web service local plugin template external functions and service definitions.
 *
 * @package    localcodehandinws
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_codehandinws_fetch_assignments' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'fetch_assignments',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Return the programming assignments available for the user',
                'type'        => 'read',
        ),
        'local_codehandinws_test_assignment' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'test_assignment',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Return the results of testing a submission',
                'type'        => 'read',
        ),
        'local_codehandinws_list_files' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'list_files',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Return a list of files in the user area',
                'type'        => 'read',
        ),
        'local_codehandinws_submit_assignment' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'submit_assignment',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Return the results of testing a submission against all tests, including those hidden tests. Will return the results of this test and the number of allowed resubmissions',
                'type'        => 'read',
        ),
         'local_codehandinws_create_checkpoint' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'create_checkpoint',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Creates a template for a given codehandin activity with specified name and description',
                'type'        => 'read',
        ),
         'local_codehandinws_create_test' => array(
                'classname'   => 'local_codehandinws_external',
                'methodname'  => 'create_test',
                'classpath'   => 'local/codehandinws/externallib.php',
                'description' => 'Creates a test for a given codehandin checkpoint',
                'type'        => 'read',
        )
        
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Codehandin Service' => array(
                'functions' => array_keys($functions),
                'restrictedusers' => 0,
                'enabled'=>1,
        )
);
