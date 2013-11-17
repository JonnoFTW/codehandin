codehandin
==========

A new activity type for Moodle that allows students to submit and have their code tested through moodle. Currently only compatible with GNU/Linux

Installation
------------
Clone this project to your moodle root directory and login to Moodle as Administrator and the plugin should be installed. Then follow these steps:


* Ensure that your system has the following utilities installed: gcc, jdk, python2.7, python3, rhino, swipl and octave. Octave is GNU mathematics system that is the nearly identical to Matlab.
* Login with the admin user. 
* If your Moodle site isn't already set up, under Site administration -> Users -> Accounts -> Add  a new user. Create a new student with whatever details you want, record the username and password for later reference.
* Under Site administration -> plugins -> Web Services -> Overview, follow steps 1, 2 (ensuring XML-RPC is enabled), 5 (ensuring the codehandin webservice is enabled), 8 (ensuring all users that need to use the codehandin services have a token).
* If it hasn't been fixed as of writing, there is a bug in the Moodle administration interface that prevents you from giving a webservice a shortname (which is required to use any webservice). You can fix this by adding the following lines into the file `admin/webservice/forms.php` at line 69:

`
    $mform->addElement('text', 'shortname', get_string('shortname'));
    $mform->addRule('shortname', get_string('required'), 'required', null, 'client');
    $mform->setType('shortname', PARAM_TEXT);
`

Supported Languages
-------------------
The following languages are supported and use the following runtimes:

* Java - JDK
* C/C++ - GCC
* Python2
* Python3
* Javascript - Rhino
* Prolog - SWIPL
* Matlab
* GNU Octave

For more information see

Usage
-----

A python client is provided that interacts with the webservice. This is in the client folder and will need to be changed so that it uses the URL of your Moodle installation.

Read the instructions in handin.py to find out more.