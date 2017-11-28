<?php
/**
 * Created by PhpStorm.
 * User: bilal.ahmed
 * Date: 11/27/2017
 * Time: 12:13 PM
 */

class Outbox extends CI_Controller
{

    /**
     * Index Page for this controller.
     *
     * Maps to the following URL
     *        http://example.com/index.php/welcome
     *    - or -
     *        http://example.com/index.php/welcome/index
     *    - or -
     * Since this controller is set as the default controller in
     * config/routes.php, it's displayed at http://example.com/
     *
     * So any other public methods not prefixed with an underscore will
     * map to /index.php/welcome/<method_name>
     * @see https://codeigniter.com/user_guide/general/urls.html
     */
    public function __construct()
    {
        parent::__construct();
        // Your own constructor code

        $this->load->helper('file');
        $this->load->helper('directory');
        $this->load->helper('date');
        //Requests::register_autoloader();
    }

    private $qaS = [],$outWorkorder,$cnti;

//    private $deptCodes = [
//        "Electrical Repair"=>"Electrical Repair",
//        "Boiler Repair"=>"Breakdowns",
//        "Electrical Inspection"=>"Fit Parts",
//        "Boiler Service & Inspection"=>"Installations",
//        "Electrical Fit Parts"=>"Electric Fit Parts" ,
//        "Gas Fit Parts"=>"Gas Fit Parts"
//    ];

    private $msgId = [
        'None'=>[],
        'A1_0'=>["A1.0",""],
        'A2_0'=>["A2.0","Revisit Confirmation"],
        'A3_0'=>["A3.0","Customer Initiated Rebook"],
        'A3_1'=>["A3.1","OP Initiated Rebook"],
        'A4_0'=>["A4.0","Customer Initiated Cancellation"],
        'A_5_1'=>["A5.1","Cancellation due to no customer contact"],
        'V1_0'=>["V1.0","Customer not present for visit"],
        'V1_2'=>["V1.2","No answer at door"],
        'V1_3'=>["V.3","Customer refused access to property"],
        'V2_0'=>["V2.0","Evidence of asbestos found"],
        'V2_1'=>["V2.1","Boiler excluded from contract"],
        'V2_2'=>["V2.2","Customer declined required product switch"],
        'V2_3'=>["V2.3","Customer declined required remedial works"],
        'V2_4'=>["V2.4","Customer declined required remedial works"],
        'V2_5'=>["V2.5","System is sub standard, unable to bring on contract"],
        'V2_6'=>["V2.6","System is unsafe, contract rejected"],
        'V3_0'=>["V3.0","Job completed, no further action required"],
        'V4_0'=>["V4.0","Parts required on subsequent visit"],
        'V4_2'=>["V4.2","Revisit to customer required"],
        'V4_3'=>["V4.3","Revisit required as unsafe to continue working"],
        'V4_4'=>["V4.4","Product switch carried out, but unable to complete task at this time"],
        'A6_0'=>["V6.0","Closing due to multiple no accesses to property"]
    ];

    private $woTypes = [["WorkOrderStatus" => "OP-Scheduled","MessageId" => "A1_0", "Type" => "Appointment", "SubType" => "Confirmation", "SubTypeReason" => "Initial"],
        ["WorkOrderStatus" => "OP-Scheduled","MessageId" => "A2_0", "Type" => "Appointment", "SubType" => "Confirmation", "SubTypeReason" => "Revisit"],
        ["WorkOrderStatus" => "OP-Scheduled","MessageId" => "A3_0", "Type" => "Appointment", "SubType" => "Rebook", "SubTypeReason" => "CustomerInitiated"],
        ["WorkOrderStatus" => "OP-Scheduled","MessageId" => "A3_1", "Type" => "Appointment", "SubType" => "Rebook", "SubTypeReason" => "OperatingPartnerInitiated"],
        ["WorkOrderStatus" => "OP-Cancelled","MessageId" => "A4_0", "Type" => "Appointment", "SubType" => "Cancellation", "SubTypeReason" => "CustomerInitiated"],
        ["WorkOrderStatus" => "OP-Cancelled","MessageId" => "A_5_1"," Type" >= "Appointment", "SubType" => "Cancellation", "SubTypeReason" => "UnabletoContactCustomer"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V1_0", "Type" => "Visit", "SubType" => "NoAccess", "SubTypeReason" => "CustomerNotPresent"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V1_2", "Type" => "Visit", "SubType" => "NoAccess", "SubTypeReason" => "NoAnswerAtDoor"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V1_3", "Type" => "Visit", "SubType" => "NoAccess", "SubTypeReason" => "RefusedAccessbyCustomer"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_0", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "Asbestos"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_1", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "ExcludedBoiler"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_2", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "ProductSwitchDeclined"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_3", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "RemedialWorksDeclined"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_4", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "RestrictedParts"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_5", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "SubStandardQuality"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V2_6", "Type" => "Visit", "SubType" => "ContractRejected", "SubTypeReason" => "SystemSafetyIssue"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V3_0", "Type" => "Visit", "SubType" => "Completed", "SubTypeReason" => "JobCompleted"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V4_0", "Type" => "Visit", "SubType" => "Incomplete", "SubTypeReason" => "PartsOrdered"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V4_2", "Type" => "Visit", "SubType" => "Incomplete", "SubTypeReason" => "RevisitRequired"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "V4_3", "Type" => "Visit", "SubType" => "Incomplete", "SubTypeReason" => "SiteSafetyIssues"],
        ["WorkOrderStatus" => "OP-In-Progress","MessageId" => "V4_4", "Type" => "Visit", "SubType" => "Incomplete", "SubTypeReason" => "ProductSwitchCompletednotqualifiedfortask"],
        ["WorkOrderStatus" => "OP-Completed","MessageId" => "A6_0", "Type" => "Appointment", "SubType" => "Failed", "SubTypeReason" => "Multiplenoaccess"]];

    private $questions = [
        '7059'=>['CustomerInformation'],
        '7060'=>['FaultRectified'],
        '7063'=>['BreakdownIdentified'],
        '7064'=>['IsCentralHeatingOperational'],
        '7065'=>['IsHotWaterOperational'],
        '7066'=>['IsProductSwitchRequiredied'],
        '7067'=>['RemedialWorkDeclined'],
        '7068'=>['WasInspectionOrInitialCompleted'],
        '7093'=>['CompletionNotes'],
        '7264'=>['AuthorisedPersonName'],
        '7265'=>['SwitchReason'],
        //'7227'=>['EngineerSignature'],
        //'7228'=>['CustomerSignature'
    ];

    public function index(){
        if(is_cli() == true){
            $this->outbox();
        }else{
            echo "Cannot access direct";
            return false;
        }
    }

    private function outbox(){

        $date = new DateTime('2017-10-09'); //change to now on Live
        $endDate = $date->format('Y-m-d H:i:s');
        $startDate = $date->modify('-2 minutes')->format('Y-m-d H:i:s');

        $events = $messages = $existingEvents = NULL;

        $resp = $this->baseclass->getJobEventsDateRange(['DateFrom'=>$startDate,'DateTo'=> $endDate]);

        if($resp == NULL || !array_key_exists('response',$resp['response']) || !array_key_exists('JobEvents',$resp['response']['response'])){
            //log
            log_message('debug','992:');
            return null;
        }

        foreach ($resp['response']['response']['JobEvents'] as $k=>$val){
            //To be ask
            $val['EventData'] = json_decode($val['EventDataJson'],true);
            //each.EventDataJson = each.EventDataJson.Replace(@"""{", "{").Replace(@"}"",", "},");
            //each.EventData = each.DateCreated<DateTime.Parse("2017-06-01")? each.EventDataJson.Replace(@"\\""", @"\\u022").Replace(@"/2""", @"/2\\u022").FromJSON<EventData>() : each.EventDataJson.Replace(@"\\""", @"\\u022").FromJSON<EventData>();

            if(array_key_exists($val['EventData']['DepartmentName'],$this->baseclass->deptCodes)){
                $existingEvents[] = $val;
            }
        }

        $events = $existingEvents;

        foreach ($events as $k=>$val){
            if($val == NULL){
                continue;
            }
            $message = $this->getMessage(['jobEvent'=>$val]);
            if($message != NULL) {
                if (!empty($messages)) {
                    if($this->chkMessages(['message'=>$message,'messages'=>$messages]) == false){
                        $messages[] = $message;
                    }
                    //for testing
                    if(count($messages) == '30'){
                        break;
                    } /////****
                } else {
                    $messages[] = $message;
                }
            }else{
                //log exception
                log_message('error','Strdate :'.$startDate.'::End date:'.$endDate.'::msgid'.$message['MessageId']);
            }
        }

        foreach ($messages as $message){
            $date = new DateTime($message['MessageHeader']['senderTimeStamp']);
            $errMsgs = $this->IsValidInboundData(['message'=>$message]);
            $filename = $message['Response']['WorkOrders']['WorkOrder']['woNumber'].'_ACT_'.$date->format('dmYhis');
            $my_file = $filename.'.xml';
            $message = json_encode($message,true);

            if($errMsgs == NULL){
                $param = ['resp'=>$message,'dir'=>'outbox','details'=>['file'=>['name'=>$my_file]]];
            }else{
                $param = ['resp'=>$message,'dir'=>'outbox','details'=>['file'=>['name'=>$my_file],'type'=>'rej','errMsgs'=>$errMsgs]];
            }
            $this->baseclass->fileHandling($param);
        }
    }

    private function getMessage($params = []){

        $jobEvent = $params['jobEvent'];
        $jobId = $jobEvent['EventData']['JobId'];
        $msgId = NULL;
        if($jobId == null){
            log_message('debug','1061:');
            return null;
        }

        $woNumber = $jobEvent['EventData']['CaseNumber'];

        if(empty($woNumber) || strpos($woNumber, 'W') === true){
            log_message('debug','1068:');
            return null;
        }

        $msgId = "None";//array_search('None', array_keys($this->msgId));
        //Set message
        $setMsg = $this->setMessage(['jobId'=>$jobId,'msgId'=>$msgId,'jobEvent'=>$jobEvent,'woNumber'=>$woNumber]);

        /// end set message
        ///
        if($setMsg != NULL){
            $msgId = $setMsg['msgId'];
        }


        if($msgId == 'None' || $msgId == null){
            log_message('debug','1083:');
            return null;
        }
        if(!array_key_exists('jobDetails',$setMsg) && $setMsg['jobDetails'] == NULL){
            $jobDetails = $this->baseclass->getJobDetails(['JobId'=>$jobId]);
            if($jobDetails['response']['response'] == NULL){
                log_message('debug','1089:');
                return null;
            }else{
                $jobDetails = $jobDetails['response']['response'];
            }
        }else{
            $jobDetails = $setMsg['jobDetails'];
        }

        //$workOrder = NULL;
        foreach ($this->woTypes as $k=>$val){
            if(($val['MessageId'] == $msgId) ){
                $this->outWorkorder = $val;
            }
        }


        //exit;
        $cnt = 0;
        $accHistory = $this->baseclass->getJobHistory(['AccountNumber'=>$jobEvent['EventData']['AccountNumber']]);
        if($accHistory != NULL && $accHistory['status'] != "E"){
            foreach ($accHistory['response']['response'] as $k=>$val){
                if($val['id'] < $jobId && $val['JobStatus'] != "BTO" && $val['CaseNumber'] == $woNumber){
                    $cnt++;
                }
            }
            $this->outWorkorder['visitNumber'] = $cnt;
            $this->outWorkorder['woNumber'] = $woNumber;

            ////setAnswers
            $result = $this->setAnswers(['jobId'=>$jobId,'jobDetails'=>$jobDetails]);
            ///
            //print_r($this->outWorkorder);
            //print_r($this->qaS);
            //exit;

            $param = ['msgId'=>$msgId,'jobDetails'=>$jobDetails,'jobEvent'=>$jobEvent];

            $this->WorkOrderRevisitRequestResponseHandler($param);
            $this->WorkOrderReVisitRefusedAccessByCustomerResponseHandler($param);
            $this->WorkOrderReVisitIncompleteRequestResponseHandler($param);
            $this->WorkOrderReVisitConfirmedRequestResponseHandler($param);
            $this->WorkOrderJobCompletedRequestResponseHandler($param);
            $this->WorkOrderConfirmResponseHandler($param);
            $this->WorkOrderCompleteRequestResponseHandler($param);
            $this->WorkOrderCancelledUnableToContactCustomerResponseHandler($param);
            $this->WorkOrderCancelledRequestResponseHandler($param);


            //WO fields
            $this->setAppliances($param);
            $this->SetExclusions($param);

            //Create Message
            $dta = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];
            $message['MessageHeader'] = [
                'contractId'=>$dta['Contract']['contractNumber'],
                'senderOrgId'=>"Actavo",
                'senderTimeStamp'=>date('Y-m-d H:i:s')
            ];

            $message['Response'] = [
                'WorkOrders'=>['WorkOrder'=>$this->outWorkorder]
            ];

            $message['MessageId'] = $msgId;
            if(strpos($msgId, 'A')){
                $message['Response']['WorkOrders']['WorkOrder']['visitNumber'] = NULL;
            }
            $this->cnti++;
            return $message;
        }

    }

    private function setMessage($params = []){

        $jobEvent = $params['jobEvent'];
        $msgId = $params['msgId'];
        $jobId = $params['jobId'];
        $woNumber = $params['woNumber'];

        if($woNumber == NULL){
            $woNumber = $jobEvent['EventData']['CaseNumber'];
        }

        if($jobEvent['EventCode'] == "JOB_STATUS_UPDATE" && $jobEvent['EventData']['JobStatus'] == "CON"){

            log_message('debug','1106:');
            $jobDetails = $this->baseclass->getJobDetails(['JobId'=>$jobId]);

            if(array_key_exists('errorMessage',$jobDetails['response'])){
                log_message('debug','1181:');
                return null;
            }else{
                $jobDetails = $jobDetails['response']['response'];
                $resp['jobDetails'] = $jobDetails;
            }
            if($jobDetails['JobDetails']['JobDate'] == NULL){
                log_message('debug','1187:');
                return null;
            }

            if($jobEvent['EventData']['JobDate'] == NULL){
                $accHistory = $this->baseclass->getJobHistory(['AccountNumber'=>$jobEvent['EventData']['AccountNumber']]);

                //print_r($accHistory);
                $cnt = 0;
                if($accHistory != NULL && ($accHistory['status'] != "E")){
                    foreach ($accHistory['response']['response'] as $k1=>$v1){
                        if($v1['id'] < $jobId && $v1['JobStatusCode'] != "BTO" && $v1['CaseNumber'] == $woNumber){
                            $cnt++;
                        }
                    }
                    log_message('debug','1129:'.$cnt);
                    $resp['msgId'] = (($cnt == 0)?"A1_0":"A2_0");
                    //return $msgId = (($cnt == 0)?array_search('A1_0', array_keys($this->msgId)):array_search('A2_0', array_keys($this->msgId)));

                }else{
                    $resp['msgId'] = "A1_0";//array_search('A1_0', array_keys($this->msgId));
                }

            }else{
                $jobDate = $jobEvent['EventData']['JobDate'];
                if($jobDate == $jobDetails['JobDetails']['JobDate']){
                    log_message('debug','1214:');
                    return null;
                }
                if(array_key_exists('RebookReason',($jobEvent['EventData']['JobDataJson']))){
                    $chk = $jobEvent['EventData']['JobDataJson']['RebookReason'];
                    if(strtolower($chk) == 'operatingpartnerinitiated'){

                        $resp['msgId'] = "A3_1";//array_search('A3_1', array_keys($this->msgId));
                    }else{
                        $resp['msgId'] = "A3_0";
                    }
                }else{
                    $resp['msgId'] = "A3_0";//array_search('A3_0', array_keys($this->msgId));
                }
            }
        }
        elseif ($jobEvent['EventCode'] == "JOB_STATUS_UPDATE" && $jobEvent['EventData']['JobStatus'] == "BTO"){
            log_message('debug','1228:');
            if(array_key_exists('CancelReason',$jobEvent['EventData']['JobDataJson'])){
                $chk = $jobEvent['EventData']['JobDataJson']['CancelReason'];
                //return $msgId = (($chk == "Unable to Contact Customer")?array_search('A_5_1', array_keys($this->msgId)):array_search('A4_0', array_keys($this->msgId)));
                $resp['msgId'] = (($chk == "Unable to Contact Customer")?'A_5_1':'A4_0');//array_search('A_5_1', array_keys($this->msgId)):array_search('A4_0', array_keys($this->msgId)));
            }else{
                $resp['msgId'] = 'A4_0';
            }
        }
        elseif ($jobEvent['EventCode'] == "JOB_FAIL"){
            $accHistory = $this->baseclass->getJobHistory(['AccountNumber'=>$jobEvent['EventData']['AccountNumber']]);
            log_message('debug','1236:');
            //print_r($accHistory);
            $cnt = 0;
            if($accHistory != NULL && ($accHistory['status'] != "E")){
                foreach ($accHistory['response']['response'] as $k1=>$v1){
                    if($v1['JobStatus'] == "F"){
                        $cnt++;
                    }

                }
                log_message('debug','1170:'.$cnt);
                if($cnt > 3){
                    $resp['msgId'] = 'A6_0';//array_search('A6_0', array_keys($this->msgId));
                }

                if($resp['msgId'] == NULL){
                    $jobDetails = $this->baseclass->getJobDetails(['JobId'=>$jobId]);
                }
                if(array_key_exists('errorMessage',$jobDetails['response'])){
                    log_message('debug','1255:');
                    return null;
                }else{
                    $jobDetails = $jobDetails['response']['response'];
                    $resp['jobDetails'] = $jobDetails;
                }
                if($jobDetails['JobTasks'] == NULL){
                    log_message('debug','1262:');
                    $resp['msgId'] = "";
                }

                if($jobDetails['JobTasks'] != NULL && is_array($jobDetails['JobTasks'])){
                    $jobTask = $jobDetails['JobTasks'][0];
                    $jobTaskCode = str_replace('.', '_',$jobTask['JobTaskCode']);
                    if($jobTask != NULL && array_key_exists($jobTaskCode,$this->msgId)){
                        //$jobTask['JobTaskCode'] = substr($jobTask['JobTaskCode'], 0, 4);
                        $resp['msgId'] = $jobTaskCode;
                    }
                }else{
                    if(strpos($this->msgId,$jobDetails['JobTasks']) == true){
                        $jobTask['JobTaskCode'] = substr($jobDetails['JobTasks'], 0, 4);
                        $resp['msgId'] = str_replace('.', '_',$jobTask['JobTaskCode']);
                    }
                }



            }
        }
        elseif ($jobEvent['EventCode'] == "JOB_COMPLETE" && $jobEvent['EventData']['JobStatus'] == "C"){
            $resp['msgId'] = 'V3_0';//array_search('V3_0', array_keys($this->msgId));
        }
        elseif ($jobEvent['EventCode'] == "JOB_REFER" && $jobEvent['EventData']['JobStatus'] == "REF"){
            log_message('debug','1194:');
            $jobDetails = $this->baseclass->getJobDetails(['JobId'=>$jobId]);
            if(array_key_exists('errorMessage',$jobDetails['response'])){
                log_message('debug','1280:');
                return null;
            }else{
                $jobDetails = $jobDetails['response']['response'];
                $resp['jobDetails'] = $jobDetails;
            }
            if($jobDetails['JobTasks'] == NULL){
                log_message('debug','1287:');
                $resp['msgId'] = "";
            }else{

                $jobTask = $jobDetails['JobTasks'][0];


                if($jobDetails['JobTasks'] != NULL && is_array($jobDetails['JobTasks'])){
                    $jobTask = $jobDetails['JobTasks'][0];
                    $jobTaskCode = str_replace('.', '_',$jobTask['JobTaskCode']);
                    if($jobTask != NULL && array_key_exists($jobTaskCode,$this->msgId)){
                        $resp['msgId'] = str_replace('.', '_',$jobTask['JobTaskCode']);

                    }

                }else{
                    if(strpos($this->msgId,$jobDetails['JobTasks']) == true){
                        $jobTask['JobTaskCode'] = substr($jobDetails['JobTasks'], 0, 4);
                        $resp['msgId'] = str_replace('.', '_',$jobDetails['JobTasks']);
                    }
                }
            }
        }

        return $resp;
    }

    private function setAnswers($params = []){

        $jobId = $params['jobId'];
        $jobDetails = $params['jobDetails'];

        $result = [];

        //if(array_key_exists('queAns',$result)){
        if(array_key_exists($jobId,$this->qaS)){
            return $this->qaS = $result['queAns']['jobId'];
            //return $result['queAns']['jobId'];
        }
        //}

        if($jobDetails == NULL || $jobDetails['JobForms'] == NULL ){
            log_message('debug','1314:');
            return null;
        }
        foreach ($jobDetails['JobForms'] as $key=>$value){
            if($value['Sections'] != NULL){
                foreach ($value['Sections'] as $k1=>$v1){
                    if($v1['Questions'] != NULL){
                        foreach ($v1['Questions'] as $k2=>$v2){
                            if(array_key_exists($v2['QuestionId'],$this->questions) && !array_key_exists($this->questions[$v2['QuestionId']][0],$result)){
                                $result[$this->questions[$v2['QuestionId']][0]] = $v2;
                            }
                        }
                    }
                }
            }
        }


        if($result != NULL){
            //$queAns[$jobId] = $result;
            $this->qaS[$jobId] = $result;
        }

        return ['result'=>$result];

    }

    //Handlers
    private function WorkOrderRevisitRequestResponseHandler($params = []){

        if($params['msgId'] == 'V1_0' || $params['msgId'] == 'V1_2'){
            $msgId = $params['msgId'];
        }else{
            log_message('debug','1347:');
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];
        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];
        $this->outWorkorder['visitCompletionDate'] = $jobEvent['EventData']['DateCompleted'];
        $this->outWorkorder['visitOnsiteDate'] = !empty($jobEvent['EventData']['DateStarted'])?$jobEvent['EventData']['SignOffDateRequested']:$jobEvent['EventData']['DateStarted'];

        //if($jobDetails['JobTaskSelectedValue'])
        if($jobDetails['JobTaskSelectedValue'] == NULL) {
            if (array_key_exists($msgId, $this->msgId)) {
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            } else {
                $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
        }

        //return $workOrder;
    }

    private function WorkOrderReVisitRefusedAccessByCustomerResponseHandler($params = []){
        if($params['msgId'] == 'V1_3'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        $workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];
        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];


        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1392:');
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        $this->outWorkorder['visitCompletionDate'] = $jobEvent['EventData']['SignOffDateRequested'];
        $this->outWorkorder['visitOnsiteDate'] = !empty($jobEvent['EventData']['DateStarted'])?$jobEvent['EventData']['SignOffDateRequested']:$jobEvent['EventData']['DateStarted'];
        //Wo order
        if($jobDetails['JobTaskSelectedValue'] == NULL) {
            if (array_key_exists($msgId, $this->msgId)) {
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            } else {
                $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
        }

        //return $workOrder;
    }

    private function WorkOrderReVisitIncompleteRequestResponseHandler($params = []){
        if($params['msgId'] == 'V4_0' || $params['msgId'] == 'V4_2' || $params['msgId'] == 'V4_3' || $params['msgId'] == 'V4_4'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];

        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1441:');
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        //$custInfo = $this->qaS['FaultRectified']['QuestionAnswer'];
        if(array_key_exists('FaultRectified',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['FaultRectified']['QuestionAnswer'])){
                $this->outWorkorder['faultRectified'] = 'Yes';
            }else{
                $this->outWorkorder['faultRectified'] = 'No';
            }
        }else{
            $this->outWorkorder['faultRectified'] = null;
        }

        if(array_key_exists('IsCentralHeatingOperational',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsCentralHeatingOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsCentralHeatingOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsCentralHeatingOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsCentralHeatingOperational'] = null;
        }

        if(array_key_exists('IsHotWaterOperational',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsHotWaterOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsHotWaterOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsHotWaterOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsHotWaterOperational'] = null;
        }

        if(array_key_exists('IsProductSwitchRequired',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsProductSwitchRequired']['QuestionAnswer'])){
                $this->outWorkorder['IsProductSwitchRequired'] = 'Yes';
            }else{
                //$this->outWorkorder['IsProductSwitchRequired'] = 'No';
                $this->outWorkorder['IsProductSwitchRequired'] = null;
            }
        }else{
            $this->outWorkorder['IsProductSwitchRequired'] = null;
        }

        if(array_key_exists('AuthorisedPersonName',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['AuthorisedPersonName']['QuestionAnswer'])){
                $this->outWorkorder['AuthorisedPersonName'] = $this->qaS[$jobId]['AuthorisedPersonName']['QuestionAnswer'];
            }else{
                $this->outWorkorder['AuthorisedPersonName'] = null;
            }
        }else{
            $this->outWorkorder['AuthorisedPersonName'] = null;
        }

        if(array_key_exists('SwitchReason',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['SwitchReason']['QuestionAnswer'])){
                $this->outWorkorder['SwitchReason'] = $this->qaS[$jobId]['SwitchReason']['QuestionAnswer'];
            }else{
                $this->outWorkorder['SwitchReason'] = null;
            }
        }else{
            $this->outWorkorder['SwitchReason']  = null;
        }

        $this->outWorkorder['visitCompletionDate'] = $jobEvent['EventData']['SignOffDateRequested'];
        $this->outWorkorder['visitOnsiteDate'] = !empty($jobEvent['EventData']['DateStarted'])?$jobEvent['EventData']['SignOffDateRequested']:$jobEvent['EventData']['DateStarted'];

        if(array_key_exists('WasInspectionOrInitialCompleted',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['WasInspectionOrInitialCompleted']['QuestionAnswer'])){
                $this->outWorkorder['WasInspectionOrInitialCompleted'] = 'Yes';
            }else{
                $this->outWorkorder['WasInspectionOrInitialCompleted'] = 'No';
            }
        }else{
            $this->outWorkorder['WasInspectionOrInitialCompleted'] = null;
        }

        if(array_key_exists('BreakdownIdentified',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['BreakdownIdentified']['QuestionAnswer'])){
                $this->outWorkorder['breakdownIdentified'] = 'Yes';
            }else{
                $this->outWorkorder['breakdownIdentified'] = 'No';
            }
        }else{
            $this->outWorkorder['breakdownIdentified'] = null;
        }
        //Wo notes
        if($jobDetails['JobTaskSelectedValue'] == NULL) {
            if (array_key_exists($msgId, $this->msgId)) {
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            } else {
                $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
        }
    }

    private function WorkOrderReVisitConfirmedRequestResponseHandler($params = []){
        if($params['msgId'] == 'A2_0' || $params['msgId'] == 'A3_0' || $params['msgId'] == 'A3_1'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];
        $this->outWorkorder['appointmentProfile'] = (!empty($jobDetails['JobDetails']['JobTime']))?'AllDay':$jobDetails['JobDetails']['JobTime'];
        $this->outWorkorder['appointmentDate'] = $jobDetails['JobDetails']['JobDate'];

        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1571:');
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        $c = NULL;
        foreach($jobDetails['JobDetails']['Callaheads'] as $k=>$v){
            if($v['JobStatusCode'] == 'BTO'){
                $c = $v;
            }
        }

        //Wo notes
        if($msgId == 'A2_0'){
            $this->outWorkorder['woNotes'] = !empty($c)? (!empty($c['CallaheadDescription'])?'Revisit job confirmation':''):'';
        }else{
            $this->outWorkorder['woNotes'] = !empty($c)? (!empty($c['CallaheadDescription'])?'"Job rebooked"':''):'';
        }
        if($this->outWorkorder['woNotes'] == NULL){
            if(array_key_exists($msgId,$this->msgId)){
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            }
        }


    }

    private function WorkOrderJobCompletedRequestResponseHandler($params = []){
        if($params['msgId'] == 'V3_0'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];

        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1624:');
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        if(array_key_exists('FaultRectified',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['FaultRectified']['QuestionAnswer'])){
                $this->outWorkorder['faultRectified'] = 'Yes';
            }else{
                $this->outWorkorder['faultRectified'] = 'No';
            }
        }else{
            $this->outWorkorder['faultRectified'] = null;
        }

        if(array_key_exists('IsCentralHeatingOperational',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsCentralHeatingOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsCentralHeatingOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsCentralHeatingOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsCentralHeatingOperational'] = null;
        }

        if(array_key_exists('IsHotWaterOperational',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsHotWaterOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsHotWaterOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsHotWaterOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsHotWaterOperational'] = null;
        }

        if(array_key_exists('RemedialWorkDeclined',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['RemedialWorkDeclined']['QuestionAnswer'])){
                $this->outWorkorder['remedialWorkDeclined'] = 'Yes';
            }else{
                $this->outWorkorder['remedialWorkDeclined'] = 'No';
            }
        }else{
            $this->outWorkorder['remedialWorkDeclined'] = null;
        }

        if(array_key_exists('IsProductSwitchRequired',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsProductSwitchRequired']['QuestionAnswer'])){
                $this->outWorkorder['IsProductSwitchRequired'] = 'Yes';
            }else{
                //$this->outWorkorder['IsProductSwitchRequired'] = 'No';
                $this->outWorkorder['IsProductSwitchRequired'] = null;
            }
        }else{
            $this->outWorkorder['IsProductSwitchRequired'] = null;
        }

        $this->outWorkorder['visitCompletionDate'] = $jobEvent['EventData']['DateCompleted'];
        $this->outWorkorder['visitOnsiteDate'] = !empty($jobEvent['EventData']['DateStarted'])?$jobEvent['EventData']['SignOffDateRequested']:$jobEvent['EventData']['DateStarted'];

        if(array_key_exists('wasInspectionOrInitialCompleted',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['wasInspectionOrInitialCompleted']['QuestionAnswer'])){
                $this->outWorkorder['wasInspectionOrInitialCompleted'] = 'Yes';
            }else{
                $this->outWorkorder['wasInspectionOrInitialCompleted'] = 'No';
            }
        }else{
            $this->outWorkorder['wasInspectionOrInitialCompleted'] = null;
        }

        //Wo notes
        if($this->qaS[$jobId]['CompletionNotes']['QuestionAnswer'] == NULL){

            if(array_key_exists($msgId,$this->msgId)){
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            }else{
                $this->outWorkorder['woNotes'] = $this->qaS['CompletionNotes']['QuestionAnswer'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $this->qaS['CompletionNotes']['QuestionAnswer'];
        }
    }

    private function WorkOrderConfirmResponseHandler($params = []){
        if($params['msgId'] == 'A1_0'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];
        $this->outWorkorder['appointmentProfile'] = (!empty($jobDetails['JobDetails']['JobTime']))?'AllDay':$jobDetails['JobDetails']['JobTime'];
        $this->outWorkorder['appointmentDate'] = $jobDetails['JobDetails']['JobDate'];

        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1735:');
            return null;
        }

        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

    }

    private function WorkOrderCompleteRequestResponseHandler($params = []){
        if($params['msgId'] == 'V2_0' || $params['msgId'] == 'V2_1' || $params['msgId'] == 'V2_2' || $params['msgId'] == 'V2_3' || $params['msgId'] == 'V2_4' || $params['msgId'] == 'V2_6'|| $params['msgId'] == 'V2_5'){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];

        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            log_message('debug','1770:');
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        if(array_key_exists('FaultRectified',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['FaultRectified']['QuestionAnswer'])){
                $this->outWorkorder['faultRectified'] = 'Yes';
            }else{
                $this->outWorkorder['faultRectified'] = 'No';
            }
        }else{
            $this->outWorkorder['faultRectified'] = null;
        }

        if(array_key_exists('IsCentralHeatingOperational',$this->qaS) ){
            if(!empty($this->qaS[$jobId]['IsCentralHeatingOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsCentralHeatingOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsCentralHeatingOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsCentralHeatingOperational'] = null;
        }

        if(array_key_exists('IsHotWaterOperational',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['IsHotWaterOperational']['QuestionAnswer'])){
                $this->outWorkorder['IsHotWaterOperational'] = 'Yes';
            }else{
                $this->outWorkorder['IsHotWaterOperational'] = 'No';
            }
        }else{
            $this->outWorkorder['IsHotWaterOperational'] =  null;
        }

        if(array_key_exists('RemedialWorkDeclined',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['RemedialWorkDeclined']['QuestionAnswer'])){
                $this->outWorkorder['remedialWorkDeclined'] = 'Yes';
            }else{
                $this->outWorkorder['remedialWorkDeclined'] = 'No';
            }
        }else{
            $this->outWorkorder['remedialWorkDeclined'] =  null;
        }

        $this->outWorkorder['visitCompletionDate'] = $jobEvent['EventData']['DateCompleted'];
        $this->outWorkorder['visitOnsiteDate'] = !empty($jobEvent['EventData']['DateStarted'])?$jobEvent['EventData']['SignOffDateRequested']:$jobEvent['EventData']['DateStarted'];

        if(array_key_exists('wasInspectionOrInitialCompleted',$this->qaS[$jobId]) ){
            if(!empty($this->qaS[$jobId]['wasInspectionOrInitialCompleted']['QuestionAnswer'])){
                $this->outWorkorder['wasInspectionOrInitialCompleted'] = 'Yes';
            }else{
                $this->outWorkorder['wasInspectionOrInitialCompleted'] = 'No';
            }
        }else{
            $this->outWorkorder['wasInspectionOrInitialCompleted'] = null;
        }

        if($jobDetails['JobTaskSelectedValue'] == NULL){
            if(array_key_exists($msgId,$this->msgId)){
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            }else{
                $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobDetails['JobTaskSelectedValue'];
        }


    }

    private function WorkOrderCancelledUnableToContactCustomerResponseHandler($params = []){
        if($params['msgId'] == 'A_5_1' || $params['msgId'] == 'A6_0' ){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];

        if($jobEvent['EventData']['Notes'] == NULL){
            if(array_key_exists($msgId,$this->msgId)){
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            }else{
                $this->outWorkorder['woNotes'] = $jobEvent['EventData']['Notes'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobEvent['EventData']['Notes'];
        }
    }

    private function WorkOrderCancelledRequestResponseHandler($params = []){
        if($params['msgId'] == 'A4_0' ){
            $msgId = $params['msgId'];
        }else{
            return null;
        }
        $jobEvent = $params['jobEvent'];

        $jobDetails = $params['jobDetails'];
        //$workOrder = $params['workOrder']; //return

        $jobId = $jobEvent['EventData']['JobId'];
        $jobCode = 'CustomerInformation';
        $data = $jobEvent['EventData']['JobDataJsonConcealed']['workorder'];

        $this->outWorkorder['appointmentNumber'] = $data['appointmentNumber'];

        //Set Customer info
        if(!array_key_exists($jobId,$this->qaS)){
            return null;
        }
        if(array_key_exists($jobCode,$this->qaS[$jobId])){
            $custInfo = $this->qaS[$jobId][$jobCode]['QuestionAnswer'];
            $this->outWorkorder['customerInformation'] = 'Yes';
            if(strlen($custInfo) > 200){
                $this->outWorkorder['additionalCustomerDetails'] = substr($custInfo, 0, 200);
            }
        }else{
            $this->outWorkorder['customerInformation'] = 'No';
        }

        if($jobEvent['EventData']['Notes'] == NULL){
            if(array_key_exists($msgId,$this->msgId)){
                $this->outWorkorder['woNotes'] = $this->msgId[$msgId][1];
            }else{
                $this->outWorkorder['woNotes'] = $jobEvent['EventData']['Notes'];
            }
        }else{
            $this->outWorkorder['woNotes'] = $jobEvent['EventData']['Notes'];
        }
    }

    //

    private function setAppliances($params = []){
        $jobEvent = $params['jobEvent'];
        $jobDetails = $params['jobDetails'];
        $msgId = $params['msgId'];

        $forms = $appliances = $quesData = NULL;
        //Appliance Form ID 149

        $names = ["Status", "Is Under Contract", "Make", "Model", "Model Qualifier",
            "Appliance Location", "Other Appliance Location", "Appliance Type", "Chimney Type", "System Type",
            "Gas Council Number", "Serial Number", "Appliance Stored in Compartment",
            "Is this the Landlord's appliance?", "ID"];

        foreach ($jobDetails['JobForms'] as $k=>$val){
            if($val['FormId'] == '149'){
                $forms[] = $val;
            }
        }
        if($forms == NULL){
            log_message('debug','1941:');
            return null;

        }

        //print_r($forms);

        foreach ($forms as $form){
            if($form['DateSubmitted'] == null){
                continue;
            }
            if(array_key_exists('Sections',$form)){
                foreach ($form['Sections'] as $k1=>$v1){
                    if($v1['SectionName'] == 'Appliance Information'){
                        if(array_key_exists('Questions',$v1)){
                            foreach ($v1['Questions'] as $k2=>$v2){
                                if(in_array($v2['Question'],$names)){
                                    $quesData[] = $v2;
                                }

                            }
                        }
                    }

                }
            }

            //print_r($quesData);

            if($quesData != NULL){
                $appl = NULL;
                foreach ($quesData as $k3=>$v3){
                    if($v3['Question'] == $names[14]){
                        $appl['applianceId'] = $v3['QuestionAnswer'];
                    }else{
                        $appl['applianceId'] = null;
                    }

                    if($v3['Question'] == $names[0]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['status'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($appl['applianceId'] == NULL && $appl['status'] == 'InActive'){
                        continue;
                    }

                    if($v3['Question'] == $names[1]){
                        if($v3['QuestionAnswer'] != NULL) {
                            $appl['isUnderContract'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }

                    }

                    if($v3['Question'] == $names[2]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['make'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($v3['Question'] == $names[3]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['model'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($v3['Question'] == $names[4]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['modelQualifier'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($v3['Question'] == $names[5]){
                        $appl['applianceLocation'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                    }
                    if($v3['Question'] == $names[6]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['otherApplianceLocation'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($v3['Question'] == $names[7]){
                        $appl['applianceType'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                    }
                    if($v3['Question'] == $names[8]){
                        $appl['chimneyType'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                    }
                    if(!empty($appl['applianceType'])){
                        if($v3['Question'] == $names[9]){
                            $appl['systemType'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }
                        if($v3['Question'] == $names[10]){
                            if($v3['QuestionAnswer'] != NULL){
                                $appl['gasCouncilNumber'] = $v3['QuestionAnswer'];
                            }
                        }
                    }

                    if($v3['Question'] == $names[11]){
                        if($v3['QuestionAnswer'] != NULL){
                            $appl['serialNo'] = $v3['QuestionAnswer'];
                        }
                    }
                    if($v3['Question'] == $names[12]){
                        $appl['isStoredInCompartment'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                    }
                    if($v3['Question'] == $names[13]){
                        $appl['landlordAppliance'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                    }
                }

                $appliances[] = $appl;
            }

        }

        //print_r($appliances);

        if(empty($appliances)){
            log_message('debug','2055:');
            return null;
        }
        //if($this->outWorkorder['Property'] = null){
        $this->outWorkorder['Property']['AppliancesAtProperty'] = $appliances;
        //}
    }

    private function SetExclusions($params = []){
        $jobEvent = $params['jobEvent'];
        $jobDetails = $params['jobDetails'];
        $msgId = $params['msgId'];

        $forms = $exclusions = $quesData = NULL;
        //$exclusions Form ID 150

        $names = ["Exclusion Location", "Exclusion Location Other", "Affected Appliance", "Other (Affected Appliance)",
            "Excluded Component", "Other (Excluded Component)", "Exclusion Reason", "Exclusion Reason Other",
            "Reason For Amendment", "Other Reason for Amendment", "Status","ID"];

        foreach ($jobDetails['JobForms'] as $k=>$val){
            if($val['FormId'] == '150'){
                $forms[] = $val;
            }
        }
        if($forms == NULL){
            return null;
        }

        foreach ($forms as $form) {
            if ($form['DateSubmitted'] == null) {
                continue;
            }
            if(array_key_exists('Sections',$form)){
                foreach ($form['Sections'] as $k1=>$v1){
                    if($v1['SectionName'] == 'Exclusions Information'){
                        if(array_key_exists('Questions',$v1)){
                            foreach ($v1['Questions'] as $k2=>$v2){
                                if(in_array($v2['Question'],$names)){
                                    $quesData[] = $v2;
                                }
                            }
                        }
                    }
                }
            }

            if($quesData != NULL){
                $exc = NULL;
                foreach ($quesData as $k3=>$v3){
                    if($v3['Question'] == $names[11]){
                        $exc['exclusionId'] = $v3['QuestionAnswer'];
                    }else{
                        $exc['exclusionId'] = null;
                    }

                    if($v3['Question'] == $names[0]){
                        if($v3['QuestionAnswer'] != NULL){
                            $exc['status'] = $v3['QuestionAnswer'];
                        }
                    }

                    if($exc['status'] == 'InActive'){

                        if($v3['Question'] == $names[8]){
                            if($v3['QuestionAnswer'] != NULL){
                                $exc['reasonForAmendment'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                                $exc['otherReasonForAmendment'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                            }
                        }

                        if ($form['DateSubmitted'] != null) {
                            $exc['dateLastAmended'] = $form['DateSubmitted'];
                        }
                    }


                    if($exc['exclusionId'] == NULL && $exc['status'] == 'InActive'){
                        continue;
                    }

                    if($v3['Question'] == $names[0]){
                        if($v3['QuestionAnswer'] != NULL){
                            $exc['location'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }

                    }
                    if($exc['location'] == 'Other'){
                        if($v3['Question'] == $names[1]){
                            if($v3['QuestionAnswer'] != NULL){
                                $exc['locationOther'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                            }
                        }
                    }

                    if($v3['Question'] == $names[2]){
                        if($v3['QuestionAnswer'] != NULL){
                            $exc['affectedAppliance'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }

                    }
                    if($exc['affectedAppliance'] == 'Other'){
                        if($v3['Question'] == $names[3]){
                            if($v3['QuestionAnswer'] != NULL){
                                $exc['affectedApplianceOther'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                            }
                        }
                    }

                    if($v3['Question'] == $names[4]){
                        if($v3['QuestionAnswer'] != NULL){
                            $exc['excludedComponent'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }
                    }
                    if($exc['excludedComponent'] == 'Other'){
                        if($v3['Question'] == $names[5]){
                            if($v3['QuestionAnswer'] != NULL){
                                $exc['excludedComponentOther'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                            }
                        }
                    }

                    if($v3['Question'] == $names[6]){
                        if($v3['QuestionAnswer'] != NULL){
                            $exc['exclusionReason'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                        }
                    }
                    if($exc['exclusionReason'] == 'Other'){
                        if($v3['Question'] == $names[7]){
                            if($v3['QuestionAnswer'] != NULL){
                                $exc['exclusionReasonOther'] = preg_replace("/[^a-zA-Z]+/", "", $v3['QuestionAnswer']);
                            }
                        }
                    }

                }
                $exclusions[] = $exc;
            }
        }

        if(empty($exclusions)){
            log_message('debug','2196:');
            return null;
        }
        //if($this->outWorkorder['Property'] = null){
        $this->outWorkorder['Property']['SiteExclusions'] = $exclusions;
        //}
    }

    private function IsValidInboundData($params =[]){
        $message = $params['message'];
        $wo = $message['Response']['WorkOrders']['WorkOrder'];

        $msgs = NULL;

        $msgIdss = ['A1_0', 'A2_0', 'A3_0', 'A3_1'];

        if($message['MessageId'] != 'A1_0' && empty($wo['woNumber'])){
            $msgs .= "woNotes is missing.</br>";
        }
        if(in_array($message['MessageId'],$msgIdss)){
            if($wo['appointmentDate'] == null){
                $msgs .= "appointmentDate is missing.</br>";
            }
            if($wo['appointmentProfile'] == null){
                $msgs .= "appointmentProfile is missing.</br>";
            }
        }

        if(strpos($message['MessageId'],'V')){
            if($wo['visitNumber'] == null){
                $msgs .= "visitNumber is missing.</br>";
            }
            if($wo['visitOnsiteDate'] == null){
                $msgs .= "visitOnsiteDate is missing.</br>";
            }
            if($wo['visitCompletionDate'] == null){
                $msgs .= "visitCompletionDate is missing.</br>";
            }
            $skipmsgIdss = ['V1_0', 'V1_2', 'V1_3'];

            if(in_array($message['MessageId'],$skipmsgIdss)){
                if($wo['wasInspectionOrInitialCompleted'] == null){
                    $msgs .= "wasInspectionOrInitialCompleted is missing.</br>";
                }
                if($wo['isCentralHeatingOperational'] == null){
                    $msgs .= "isCentralHeatingOperational is missing.</br>";
                }
                if($wo['isHotWaterOperational'] == null){
                    $msgs .= "isHotWaterOperational is missing.</br>";
                }
                if($wo['faultRectified'] == null){
                    $msgs .= "faultRectified is missing.</br>";
                }
            }
            $skipmsgIdss = ['V1_0', 'V1_2'];
            if(in_array($message['MessageId'],$skipmsgIdss) && $wo['customerInformation'] == null){
                $msgs .= "CustomerInformation is missing.</br>";
            }
            if($message['MessageId'] == 'V4_3' && !empty($wo['customerInformation']) == 'No'  ){
                $msgs .= "CustomerInformation must be Yes.</br>";
            }

            $submsgIds = ['V3_0', 'V4_0', 'V4_2', 'V4_3', 'V4_4'];
            if(in_array($message['MessageId'],$submsgIds)){
                if($wo['isProductSwitchRequired'] == null){
                    $msgs .= "isProductSwitchRequired is missing.</br>";
                }else{
                    if($wo['isProductSwitchRequired'] == 'Yes'){
                        if($wo['authorisedPersonName'] == null){
                            $msgs .= "authorisedPersonName is missing.</br>";
                        }
                        if($message['MessageId'] == 'V3_0' && $wo['productSwitchReason'] == null){
                            $msgs .= "productSwitchReason is missing.</br>";
                        }
                    }
                }
            }
            $remedialWorkDeclinedMsgs = ['V3_0', 'V2_0', 'V2_1', 'V2_2', 'V2_3', 'V2_4', 'V2_5', 'V2_6'];
            if(in_array($message['MessageId'],$remedialWorkDeclinedMsgs)){
                if($wo['remedialWorkDeclined'] == null){
                    $msgs .= "remedialWorkDeclined is missing.</br>";
                }
            }
            $breakDownMsgs = ['V4_0', 'V4_2', 'V4_3','V4_4'];
            if(in_array($message['MessageId'],$breakDownMsgs)){
                if($wo['breakdownIdentified'] == null){
                    $msgs .= "breakdownIdentified is missing.</br>";
                }
            }
        }
        if(!empty($wo['customerInformation']) == 'Yes' && empty($wo['additionalCustomerDetails'])){
            $msgs .= "AdditionalCustomerDetails is missing.</br>";
        }

        return $msgs;
    }

    private function chkMessages($params = []){

        $messages = $params['messages'];
        $message = $params['message'];
        foreach ($messages as $m) {
            if ($m['Response']['WorkOrders']['WorkOrder']['woNumber'] == $message['Response']['WorkOrders']['WorkOrder']['woNumber'] && $m['MessageId'] == $message['MessageId']) {
                return true;
            }
        }
        return false;
    }


}