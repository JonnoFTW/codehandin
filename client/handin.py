#!/usr/bin/python

import sys,os, getpass,urllib2,urllib,json
import mimetools,mimetypes
import datetime
import re
import xmlrpclib
from poster.encode import multipart_encode
from poster.streaminghttp import register_openers

register_openers()
actions = ["submit","fetch","test","create_test","create_checkpoint"]
help = """Moodle file handin
'fetch': Fetch and display assignments
'submit assignment_id file.zip reallysubmit': submit a file for assessment, normally code won't submit if it doesn't compile, this can overridden by using the final argument, eg
submit 1 badcode.zip reallysubmit
'test assignment_id file.zip': submit a file for testing against a small subset of tests
'help': display help'

'create_test': for a teacher, create a test, usage is:
'create_checkpoint': assignment_id checkpoint_number "checkpoint name" "checkpoint description"

-------------------------------------------------------------

EXAMPLE:
-- create a test for checkpoint 3, no description, not for assessment, use files test.in and test.out
-- no runtime arguments, no stderr input file
create_test 3 "" 0 test.in test.out 0 ""

-- create a test that is for assessment and uses stderr and runtime args
create_test 3 "" 1 test.in test.out 1 "-v -u" test.err

"""

moodle_url = "http://127.0.0.1/moodle/"
#moodle_url = "http://192.168.1.7/moodle/"
#moodle_url = "http://jonnoftw.no-ip.org/moodle/"
moodle_webservice = "webservice/xmlrpc/server.php"
submit_func = "local_wstemplate_submit_assignment"
test_func = "local_wstemplate_test_assignment"
create_test_func = "local_wstemplate_create_test"
create_checkpoint_func = "local_wstemplate_create_checkpoint"
fetch_func =  "local_wstemplate_fetch_assignments"
upload_script = "mod/codehandin/upload.php"
login_script = "login/token.php"
service = "code_handin"

    
def moodle_upload(token,fName, assign_id, test):
    try:
        datagen, headers = multipart_encode({'submission':open(fName,"rb")})
        tokenUrl = urllib.urlencode({'token':token,'assign_id':assign_id,'test':test})
        request = urllib2.Request(moodle_url+upload_script+'?'+tokenUrl,datagen,headers)
        print "Uploading:",fName
        file_result = json.load(urllib2.urlopen(request))
        #  print json.dumps(file_result,indent=4)
        if 'error' in file_result:
            sys.exit("Could not upload file: "+file_result['error'])
        return file_result['id']
   
    except urllib2.HTTPError, e:
        sys.exit(e.reason)
    except urllib2.URLError, e:
        sys.exit(e.reason)
    
def moodle_http(script,opts):
    try:
       # print "doing moodle",script,opts,fName
        data = urllib.urlencode(opts)
        req  = urllib2.Request(moodle_url+script,data)            
    #    print "Requesting:",req.get_full_url()
        result= json.load(urllib2.urlopen(req,timeout=5))
        return result
    except urllib2.HTTPError, e:
        sys.exit(e.reason)
    except urllib2.URLError, e:
        sys.exit(e.reason)

def moodle_rpc(func,token,opts=[]):

    data = urllib.urlencode({'wstoken':token})
    proxy = xmlrpclib.ServerProxy(moodle_url+moodle_webservice+'?'+data,verbose=False)
    #print "Doing",func
    try:
        if opts != []:
            out = getattr(proxy,func)(*opts)
        else:
            out = getattr(proxy,func)()
    except xmlrpclib.Fault, e:
        sys.exit(e.faultString.replace("\\n","\n").replace("\t","    "))
    try:
        return json.loads(out)
    except:
        sys.exit(out)
        
def login():
    print "Logging in "
    return '1262977d74b17afe04a799dcb32792d7' #jane student
    return '5c23041ddd5206a90a8e8bd3b6fd42d9' #admin
    #return 'c8bf652a12f47c26769b423c4e5f3a98'  #web service
    username =raw_input("Moodle username: ") #'webservice_user'# 
    password =getpass.getpass("Moodle password: ") #'123abc'# 
    result = moodle_http(login_script,{'username':username,'password':password,'service':service})
    if 'error' in result:
        sys.exit("Moodle error: "+result['error'])
   # print "Token: ",result['token']
    return result['token']
    
def fetchAssignments(token):
    return moodle_rpc(fetch_func,token)
def submitAssignment(token,aid,fName,really):
    fid = moodle_upload(token,fName,aid,0)
    return moodle_rpc(submit_func,token,[int(aid),fid,really])
def testAssignment(token,aid,fName):
    fid = moodle_upload(token,fName,aid,1)
    return moodle_rpc(test_func,token,[int(aid),fid,0])

def createTest(token,args):
    #cid,descr,assessment,input,output,retval,runtime_args,stderr
    try:
        with open(args[3],'r') as f:
            fin = f.read()
        with open(args[4],'r') as f:
            fout = f.read()
        if len(args) <8:
            stderr  = ""
        else:
            with open(args[7],'r') as f:
                stderr = f.read()
        fout = open(args[4]).read()
                #int         text    bool        file file int        text     file
        opts = [int(args[0]),args[1],int(args[2]),fin,fout,int(args[5]),args[6],stderr]
        return  moodle_rpc(create_test_func,token,opts)
    except IOError, e:
        sys.exit(e)
    except Exception, e:
        sys.exit('Format for test is checkpoint_id "test description"  assessment inputFile.in outputFile.out return_code "runtime arguments" stdError.out')
  
if __name__=="__main__":
    args = sys.argv
    argc = len(args)
    if argc == 1 or args[1] not in actions: 
        sys.exit(help)
    action = args[1].lower()
    if action == "fetch":
        token=login()
        assngs = fetchAssignments(token)
        if 'error' in assngs:
            sys.exit("Error: "+assngs['error'])
        print "Available Assignments are:"
        for i in assngs:   
            print "Name:",assngs[i]['name']
            print "Assignment id:",assngs[i]['id']
            if assngs[i]['duedate'] != None:
                print "Due:",datetime.datetime.fromtimestamp(int(assngs[i]['duedate'])).strftime("%Y-%m-%d %H:%M")
            if 'submitted' in assngs[i]:
                print "Submitted",anngs[i]['submitted']
            else:
                print "Submitted: No"
                print "Description:",re.sub('<[^<]+?>','',assngs[i]['intro'])
                print "Language:",assngs[i]['language']
                print "Checkpoints:"
                for j in assngs[i]['checkpoints']:
                    print "\tTask:",assngs[i]['checkpoints'][j]['task']
                    print "\tID:",assngs[i]['checkpoints'][j]['id']
                    print "\tDescription",assngs[i]['checkpoints'][j]['description']
                    print "\n"
            print "\n"
    elif action in ["submit","test"]:
        if argc <= 3:
             sys.exit("Submitting or testing an assignment requires 3 arguments.\nRun using: "+args[0]+" submit|test assignment_id file.zip")
        fName = args[3]
        if not os.path.exists(fName):
            sys.exit("File '"+fName+"' does not exist")
        token=login()
        if action == "submit":
            really = 0
            if argc > 4:
                really = int(args[4].lower() == "reallysubmit")
            print "really submitting: ",really
            result = submitAssignment(token,args[2],args[3],really)
            if 'note' in result:
                print result['note']
        elif action == "test":
            result = testAssignment(token,args[2],args[3])
        if "error" in result:
            print json.dumps(result,indent=4)
            if 'type' not in result:
                print "Error:",result['error']
            elif result['type'] == "compiler":
                print result["error"]
                print result['output']
            else:
                print result['error']
        else:
            print json.dumps(result,indent=4);
            print "Test Results:\n\n","Checkpoint","Test","Pass"
            for i in result['test_results']:
                if i['pass']:p= "Passed"
                else:p= "Failed"
                print i['checkpoint_id'],i['test_id'],":", p
                if not i['pass'] and action == "test":
                    print "Input:\n",i['input']
                    print "Output:\n",i['given_output']
                    print "Required Output:\n",i['required_output']
                    print "Std Error:\n",i['stderr']
                    print "Required Std Error:\n",i['required_stderr']
                    print "Return Value:\n",i['retval']
                    print "Required Std Error:\n",i['required_retval']
            if action =="submit":
                print "You passed",str(result['grade'])+"%", "(%d/%d)"%(result['checkpoints_passed'],result['checkpoints_total'])
    elif action == "create_checkpoint":
        if argc != 6:
            sys.exit("Expected 3 arguments\nUsage is {} create_checkpoint assignment_id checkpoint_number \"checkpoint name\" \"checkpoint description\"".format(args[0]))
        try:
            args[2] = int(args[2])
            args[3] = int(args[3])
        except:
            sys.exit("Assignment ids are integer")
        print "Creating checkpoint"
        #Check paramters then login
        token = login()
        result =  moodle_rpc(create_checkpoint_func,token,args[2:])
        if 'error' in result:
            print "Error",result['error']
        else:
            print "New checkpoint created with ID:",result['id']
    elif action == "create_test":
        print "Creating test"
        #check parameters and then login
    
        token = login()
        result = createTest(token,args[2:])
        if 'error' in result:
            print "Error",result['error']
        else:
            print "New test created with ID:",result['id']
        
                   
            

