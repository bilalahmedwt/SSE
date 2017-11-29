<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Inbox extends CI_Controller
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

        //$this->load->library('format', array());

        $this->load->helper('file');
        $this->load->helper('directory');
        $this->load->helper('date');
        //$this->load->library('baseclass');

    }

    private $depts = [
        array("GasWork","InitialInspection","Boiler Service & Inspection","Gas Inspection"),
        array("GasWork","Service","Boiler Service & Inspection","Gas Service"),
        array("GasWork","Emergency","Boiler Repair","Gas Emergency"),
        array("GasWork","SameDayBreakdown","Boiler Repair","Gas Same-Day"),
        array("GasWork","NextDayBreakdown","Boiler Repair","Gas Next-Day"),
        array("GasWork","NextWorkingDayBreakdown","Boiler Repair","Gas Next Working Day"),
        array("GasWork","NonUrgentBreakdown","Boiler Repair","Gas Non-Urgent"),
        array("GasWork","EmergencyService","Boiler Repair","Gas Emergency, Gas Service"),
        array("GasWork","SameDayBreakdownService","Boiler Repair","Gas Same-Day, Gas Service"),
        array("GasWork","NextDayBreakdownService","Boiler Repair","Gas Next-Day, Gas Service"),
        array("GasWork","NextWorkingDayBreakdownService","Boiler Repair","Gas Next Working Day, Gas Service"),
        array("GasWork","NonUrgentBreakdownService","Boiler Repair","Gas Non-Urgent, Gas Service"),
        array("ElectricalWork","InitialInspection","Electrical Inspection","Elec Inspection"),
        array("ElectricalWork","Service","Electrical Inspection","Elec Service"),
        array("ElectricalWork","Emergency","Electrical Repair","Elec Emergency"),
        array("ElectricalWork","SameDayBreakdown","Electrical Repair","Elec Same-Day"),
        array("ElectricalWork","NextDayBreakdown","Electrical Repair","Elec Next-Day"),
        array("ElectricalWork","NextWorkingDayBreakdown","Electrical Repair","Elec Next Working Day"),
        array("ElectricalWork","NonUrgentBreakdown","Electrical Repair","Elec Non-Urgent"),
        array("ElectricalWork","EmergencyService","Electrical Repair","Elec Emergency, Elec Service"),
        array("ElectricalWork","SameDayBreakdownService","Electrical Repair","Elec Same-Day, Elec Service"),
        array("ElectricalWork","NextDayBreakdownService","Electrical Repair","Elec Next-Day, Elec Service"),
        array("ElectricalWork","NextWorkingDayBreakdownService","Electrical Repair","Elec Next Working Day, Elec Service"),
        array("ElectricalWork","NonUrgentBreakdownService","Electrical Repair","Elec Non-Urgent, Elec Service")
    ];



    public function index(){

        //$this->load->view('welcome_message');
        if(is_cli()){
            $this->inbox();

        }else{
            echo "Cannot access direct";
            return false;
        }
    }

    private function inbox()
    {
        //from FTP download
        $this->baseclass->ftpFile(['dir'=>['ftp'=>OUTBOX_FTP,'local'=>INBOX],'method'=>'down']);

        $files = get_dir_file_info(INBOX);
        //print_r($files);
        exit;
        foreach ($files as $file){
            if($data = $this->baseclass->checkXmlFile(['file'=>$file]) && date("d/m/Y",$file['date']) != date("d/m/Y",now())){ //Check Valid XML --need to change when live
                //print_r($data); exit;
                if(($wo = $data['Request']['WorkOrders']['WorkOrder']) == NULL){
                    log_message('error','Workorder not found');
                    $this->baseclass->fileHandling(['resp'=>'Workorder not found','data'=>$wo,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                    continue;
                }

                $param['CaseNumber'] = $wo['woNumber'];
                $resp = $this->baseclass->findJob($param); //Find Job API

                if($resp['response']['response'] == NULL && $resp['status'] != 'E' ){ //check if WO not exists && ($resp['response']['response'][0] ==  NULL && $resp['response']['response'][0]['id'] == NULL
                    //print_r($resp); exit;
                    if($wo['Property']['addressLine2'] == NULL){
                        if($wo['Property']['addressLine3'] != NULL){
                            $wo['Property']['addressLine2'] = $wo['Property']['addressLine3'];
                            $wo['Property']['addressLine3'] = NULL;
                        }elseif ($wo['Property']['addressLine4'] != NULL){
                            $wo['Property']['addressLine2'] = $wo['Property']['addressLine4'];
                            $wo['Property']['addressLine4'] = NULL;
                        }
                    }

                    $addValues = $wo;
                    $jobDetails = array();
                    $jobDetails['AccountNumber'] = $wo['Customer']['customerId'];
                    $jobDetails['AddressLine1'] = $wo['Property']['addressLine1'];
                    $jobDetails['AddressLine2'] = $wo['Property']['addressLine2'];
                    $jobDetails['AddressLine3'] = $wo['Property']['addressLine3'];
                    $jobDetails['AddressLine4'] = $wo['Property']['addressLine4'];
                    $jobDetails['CaseNumber'] = $wo['woNumber'];
                    $jobDetails['CompanySubscriptionCode'] = NULL;
                    $jobDetails['City'] = NULL;
                    $jobDetails['County'] = NULL;
                    $jobDetails['EmailAddress'] = $wo['Customer']['customerEmail'];
                    $jobDetails['MobileNumber'] = $wo['Customer']['mobilePhone'];
                    $jobDetails['PostCode'] = $wo['Property']['postCode'];
                    $jobDetails['TelephoneNumber'] = $wo['Customer']['homePhone'];
                    $jobDetails['TelephoneNumber2'] = $wo['Customer']['businessPhone'];
                    $jobDetails['TelephoneNumber3'] = $wo['Customer']['mobilePhone'];
                    $jobDetails['JobDescription'] = $wo['woInstruction'];
                    $jobDetails['AdditionalDetails'] = $addValues;
                    $jobDetails['DueDate'] = date('Y-m-d', strtotime($wo['dueDate'])); //mysql format change
                    $jobDetails['MinDate'] = date('Y-m-d', ($file['date'])); ////mysql format change
                    $jobDetails['AdditionalDetailsVisible']['baseProductName'] = $wo['Contract']['baseProductName'];

                    $pieces = explode(" ", $wo['Customer']['fullName']);
                    $jobDetails['Surname'] = $pieces[1];
                    $jobDetails['FirstName'] = $pieces[0];

                    $dept = $this->checkDept(array('woGroup'=>$wo['woGroup'],'woType'=>$wo['woType']));
                    //print_r($dept);
                    if($dept == NULL){
                        log_message('error','Code & Dept not found');
                        $this->baseclass->fileHandling(['resp'=>'Code & Dept not found','data'=>$data,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }
                    elseif ($dept['code'] == NULL){
                        log_message('error','Code not found');
                        $this->baseclass->fileHandling(['resp'=>'Code not found','data'=>$data,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }
                    elseif ($dept['dept'] == NULL){
                        log_message('error','Dept not found');
                        $this->baseclass->fileHandling(['resp'=>'Dept not found','data'=>$data,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }
                    else{
                        $jobDetails['DepartmentCode'] = $dept['code'];
                        $jobDetails['SubscriptionCode'] = APP_SUBCODE;

                        if($wo['Property']['ownershipType'] == 'Land Lord'){

                            $dept['swfType'][count($dept['swfType'])+1] = "CP12";
                        }
                        $jobDetails['JobTypeCodes'] = $dept['swfType'];


                    }
                    //print_r($jobDetails);
                    log_message('debug', __METHOD__.':178:'.urlencode(json_encode($jobDetails)));
                    $Customerdata = $this->setCustomerData(array('data'=>$jobDetails,'workorder'=>$wo));
                    if(array_key_exists('errorCode',$Customerdata['response']) ){
                        log_message('error',$Customerdata['response']['errorMessage']);
                        $this->baseclass->fileHandling(['resp'=>$Customerdata['response']['errorMessage'],'data'=>$wo,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;

                    }

                    $jobDetails['CompanyId'] = $Customerdata['CompanyId'];
                    $jobDetails['CompanyBranchId'] = $Customerdata['CompanyBranchId'];

                    log_message('debug', __METHOD__.':166:'.urlencode(json_encode($jobDetails)));


                    $resp = $this->baseclass->addJobCompany(array('JobDetails'=>$jobDetails));
                    if($resp['response']['response'] ==  NULL && $resp['status'] == 'E'){
                        log_message('error',$resp['response']['errorMessage']);
                        $this->baseclass->fileHandling(['resp'=>$resp['response']['errorMessage'],'data'=>$wo,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }

                    $jobId = $resp['response']['response']['id'];

                    if($jobId == NULL){
                        log_message('error','No job Id');
                        $this->baseclass->fileHandling(['resp'=>$resp['response']['errorMessage'],'data'=>$wo,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }

                    if($wo['Property']['AppliancesAtProperty'] != NULL || $wo['Property']['SiteExclusions'] != NULL  ){
                        $this->addAppliances(array('workOrder'=>$wo,'jobId'=>$jobId));
                        $this->addExclusion(array('workOrder'=>$wo,'jobId'=>$jobId));
                    }

                    $this->addNotes(array('workOrder'=>$wo,'jobId'=>$jobId));
                    $jobComp = $this->addJobCompletion(array('workOrder'=>$wo,'jobId'=>$jobId));
                    if(array_key_exists('errorMessage',$jobComp['response'])){
                        log_message('error',$jobComp['response']['errorMessage']);
                        $this->baseclass->fileHandling(['resp'=>$resp['response']['errorMessage'],'data'=>$wo,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                        continue;
                    }else{
                        $this->baseclass->fileHandling(['resp'=>$resp['response']['response'],'data'=>$wo,'details'=>['dir'=>'inbox','type'=>'process','file'=>$file]]);
                        continue;
                    }


                }else{
                    log_message('error','Workorder exists');
                    $this->baseclass->fileHandling(['resp'=>'Workorder exists','data'=>$data,'details'=>['dir'=>'inbox','type'=>'rej','file'=>$file]]);
                    continue;

                }
                //exit;
            }else{
                log_message('error','wrong format of file');
                //$this->baseclass->fileHandling(['resp'=>'wrong format of file','data'=>$data,'details'=>['dir'=>'inboxt,ype'=>'rejf,ile'=>$file]]);
                //return false;
            }
        }
    }

    private function checkDept($params = []){

        $params['woGroup'] = preg_replace("/[^a-zA-Z]+/", "", $params['woGroup']);
        $params['woType'] = preg_replace("/[^a-zA-Z]+/", "", $params['woType']);

        $dept = [];
        foreach ($this->depts as $k=>$v){
            if($v[0] == $params['woGroup'] && $v[1] == $params['woType']){
                $dept['dept'] = $v;
                if(array_key_exists($v[2],$this->baseclass->deptCodes)){
                    $dept['code'] = $this->baseclass->deptCodes[$v[2]];
                }else{
                    $dept['code'] = NULL;
                    //log_message('error','No Code found');
                    //return false;
                }

                $swfTypes = explode(",",$v[3]);
                $dept['swfType'] = $swfTypes;
                break;
            }else{

            }
        }
        return $dept;
    }

    private function setCustomerData($params = []){
        if($params['workorder'] == NULL)return;
        $wo = $params['workorder'];
        $data = [];
        $company = $companyBranch = NULL;
        $respComp = $this->baseclass->getCompany(array('SearchField'=>'companyCode','SearchValue'=>$wo['Customer']['customerId']));
        //print_r($resp);


        if(($respComp['response']['response']) ==  NULL ){
            //$company = $resp['response']['response'];
            //echo "No Company"; //Add Company here
            $param = [
                "Title"=>$wo['Customer']['title'],
                "CompanyName"=>$wo['Customer']['fullName'],
                "CompanyCode"=>$wo['Customer']['customerId'],
                "AddressLine1"=>$wo['Customer']['addressLine1'],
                "AddressLine2"=>$wo['Customer']['addressLine2'],
                //"AddressLine3"=>$appliances['Customer']['addressLine3'].$appliances['Customer']['addressLine4'],
                "City"=>"",
                "County"=>"",
                "Email"=>$wo['Customer']['customerEmail'],
                "PhoneNumber1"=>$wo['Customer']['mobilePhone'],
                "PostCode"=>$wo['Customer']['postCode'],
                "PhoneNumber2"=>$wo['Customer']['homePhone'],
                "PhoneNumber3"=>$wo['Customer']['businessPhone']
            ];
            $company = $this->baseclass->addCompany(array('CompanyDetails'=>$param));
            //print_r($company); exit;
            if(array_key_exists("errorCode",$company['response'])){
                return $data = $company;
            }

            $data['CompanyId'] = $company['response']['response']['id'];
            //to be done
        }
        else{
            $data['CompanyId'] = $respComp['response']['response'][0]['id'];
        }


        $respCompBrnc = $this->baseclass->getCompanyBranches(array('CompanyId'=>$data['CompanyId']));
        if(($respCompBrnc['response']['response']) ==  NULL){
            //$companyBranch = $resp['response']['response'];
            $param = [
                "Address1" => $wo['Property']['addressLine1'],
                "Address2" => $wo['Property']['addressLine2'],
                "Address3" => $wo['Property']['addressLine3'].' '.$wo['Property']['addressLine4'],
                "PostCode" => $wo['Property']['postCode'],
                "Name" => $wo['Customer']['fullName'],// wo['Property']['addressLine1']+ wo['Property']['addressLine2']+ wo['Property']['addressLine3']+ wo['Property']['addressLine4'],
                "CompanyId" => $data['CompanyId']
            ];
            $branch = $this->baseclass->addCompanyBranch(array('BranchDetails'=>$param));
            //to be done
            if(array_key_exists("errorCode",$branch['response'])){
                return $data = $branch;
            }
            $data['CompanyBranchId'] = $branch['response']['response']['id'];

            //echo "No Company Branch"; //Add Company Branch here
        }else{
            $data['CompanyBranchId'] = $respCompBrnc['response']['response'][0]['id'];
        }

        return $data;

    }

    private function addNotes($params = []){
        $wo = $params['workOrder'];
        $jobId = $params['jobId'];

        $notes = [];
        if($wo['isCentralHeatingOperational'] != NULL){
            $notes[] = "isCentralHeatingOperational: ".$wo['isCentralHeatingOperational'];
        }
        if($wo['isHotWaterOperational'] != NULL){
            $notes[] = "isHotWaterOperational: ".$wo['isHotWaterOperational'];
        }
        if($wo['Customer']['doNotAllowSms'] != NULL){
            $notes[] = "Do not allow Sms:: ".$wo['Customer']['doNotAllowSms'];
        }
        if($wo['Customer']['vipMarker'] != NULL){
            $notes[] = "Is VIP Maker:: ".$wo['Customer']['vipMarker'];
        }
        if($wo['Property']['ownershipType'] != NULL){
            $notes[] = "OwnershipType: ".$wo['Property']['ownershipType'];
        }
        if($wo['isLabourChargeable'] != NULL){
            $notes[] = "isLabourChargeable: ".$wo['isLabourChargeable'];
        }
        if($wo['isCallOutChargeable'] != NULL){
            $notes[] = "isCallOutChargeable: ".$wo['isCallOutChargeable'];
        }
        if($wo['isPartsChargeable'] != NULL){
            $notes[] = "isPartsChargeable: ".$wo['isPartsChargeable'];
        }
        if($wo['Customer']['addressLine1'] != NULL){
            $notes[] = "Customer addressLine1: ".$wo['Customer']['addressLine1'];
        }
        if($wo['Customer']['addressLine2'] != NULL){
            $notes[] = "Customer addressLine2: ".$wo['Customer']['addressLine2'];
        }
        if($wo['Customer']['addressLine3'] != NULL){
            $notes[] = "Customer addressLine3: ".$wo['Customer']['addressLine3'];
        }
        if($wo['Customer']['addressLine4'] != NULL){
            $notes[] = "Customer addressLine4: ".$wo['Customer']['addressLine4'];
        }
        if($wo['Customer']['postCode'] != NULL){
            $notes[] = "Customer postCode: ".$wo['Customer']['postCode'];
        }
        log_message('debug', __METHOD__.':'.urlencode(json_encode($notes)));
        foreach ($notes as $k=>$v){
            $this->baseclass->addJobNote(array('JobDetails'=>['JobId'=>$jobId,'JobNote'=>$v]));
        }
    }

    private function addJobCompletion($params = [])
    {
        $wo = $params['workOrder'];
        $jobId = $params['jobId'];

        //$jobComplId = '148';
        $jobComplId = '102';
        $resp = $this->baseclass->addFormSubmittedForJob(array('FormId'=>$jobComplId,'JobId'=>$jobId));

        $ques = [
            "7060"=>[["Yes","64008"],["No","64009"]],
            "7061"=>[["Yes","63837"],["No","63838"]],
            "7062"=>[["Yes","63839"],["No","63840"]],
            "7063"=>[["Yes","63841"],["No","63842"]],
            "7064"=>[["Yes","63843"],["No","63844"]],
            "7065"=>[["Yes","63845"],["No","63846"]],
            "7066"=>[["Yes","63847"],["No","63848"]],
            "7067"=>[["Yes","63849"],["No","63850"]],
            "7068"=>[["Yes","63851"],["No","63852"]]
        ];

        $ans = [
            '7060'=>['FaultRectified'],
            '7063'=>['BreakdownIdentified'],
            '7064'=>['IsCentralHeatingOperational'],
            '7065'=>['IsHotWaterOperational'],
            '7066'=>['IsProductSwitchRequiredied'],
            '7067'=>['RemedialWorkDeclined'],
            '7068'=>['WasInspectionOrInitialCompleted'],
            '7093'=>['CompletionNotes'],
            '7227'=>['EngineerSignature'],
            '7228'=>['CustomerSignature']];

        $answerss = NULL;

        if($resp['response']['response'] !=  NULL && !empty($resp['response']['response']['FormSubmittedId']) ) {
            foreach ($ans as $k => $v) {
                foreach ($ques[$k] as $val) {
                    //print_r($val);
                    switch ($v[0]) {
                        case "IsCentralHeatingOperational":
                            //print_r($val);
                            if (in_array($wo['isCentralHeatingOperational'], $val)) {
                                $answerss[] = ['ans'=>$val[1],'ques'=>$k];
                            }
                            break;
                        case "IsHotWaterOperational":
                            //print_r($val);
                            if (in_array($wo['IsHotWaterOperational'], $val)) {
                                $answerss[] = ['ans'=>$val[1],'ques'=>$k];
                            }
                            break;
                        default:
                            //$answerss;
                    }

                }
            }

            //FormQuestionId = 7064
            //FormQuestionAnswer == 63844
            //log_message('debug', __METHOD__.':'.urlencode(json_encode($answerss)));
            if ($answerss != NULL) {
                foreach ($answerss as $k => $answers) {
                    $resp2 = $this->baseclass->addFormSubmittedSubmitQuestionAnswer(array('FormQuestionId' => $answerss['ques'], 'FormQuestionAnswer' => $answerss['ans'], 'FormSubmittedId' => $resp['response']['response']['FormSubmittedId']));
                }
            }
        }
        else{
            return $resp;
        }

    }


    private function addAppliances($params = []){
        $wo = $params['workOrder'];
        $jobId = $params['jobId'];

        $applianceUpd = 149;
        $answers = NULL;

        $ans = [
            '7069'=>['IsUnderContract'],
            '7071'=>['Make'],
            '7072'=>['Model'],
            '7073'=>['ModelQualifier'],
            '7074'=>['ApplianceLocation'],
            '7075'=>['OtherApplianceLocation'],
            '7076'=>['ApplianceType'],
            '7077'=>['ChimneyType'],
            '7078'=>['SystemType'],
            '7079'=>['GasCouncilNumber'],
            '7080'=>['SerialNumber'],
            '7081'=>['ApplianceStoredinCompartment'],
            '7094'=>['IsthistheLandlordsappliance'],
            '7096'=>['ID'],
            '7098'=>['IsStoredinCompartment']
        ];

        $ques = [
            "7069"=>[
                ["Change","63853"],
                ["Inactive","63854"],
                ["Active","64018"]
            ],
            "7070"=>[
                ["Yes","63855"],
                ["No","63856"]
            ],
            "7074"=>[
                ["Airing","63857"],
                ["Basement","63858"],
                ["Bathroom","63859"],
                ["Bedroom","63860"],
                ["Boiler Room","63861"],
                ["Carcass","63862"],
                ["Cloakroom","63863"],
                ["Conservatory","63864"],
                ["Dining Room","63865"],
                ["External","63866"],
                ["Garage","63867"],
                ["Hall","63868"],
                ["Kitchen","63869"],
                ["Loft","63870"],
                ["Lounge","63871"],
                ["Office","63872"],
                ["Other","63873"],
                ["Out House","63874"],
                ["Porch","63875"],
                ["Store","63876"],
                ["Toilet","63877"],
                ["Under Stairs","63878"],
                ["Utility","63879"]
            ],
            "7076"=>[
                ["Gas Tumble Dryer","63882"],
                ["Back Boiler Unit","63883"],
                ["Condensing Combi Boiler","63884"],
                ["Combi Boiler","63885"],
                ["Gas Fire (BBU)","63886"],
                ["Gas Fire","63887"],
                ["Gas Hob","63888"],
                ["Gas Oven","63889"],
                ["Gas Range Cooker","63890"],
                ["Gas Water Heater","63891"],
                ["Powermax","63892"],
                ["Standard Condensing Boiler","63893"],
                ["Standard Non-Condensing Boiler","63894"],
                ["Warm Air Unit","63895"]
            ],
            "7077"=>[
                ["Room Sealed","63896"],
                ["Open Flue","63897"],
                ["Flueless","63898"]
            ],
            "7078"=>[
                ["Warm Air","64010"],
                ["Sealed System","64011"],
                ["Other","64012"],
                ["Open Vented","64013"]
            ],
            "7081"=>[
                ["Yes","63899"],
                ["No","63900"]
            ],
            "7094"=>[
                ["Yes", "64014"],
                ["No", "64015"]
            ],
            "7098"=>[
                ["Yes", "64020"],
                ["No", "64021"]
            ],
        ];

        $appliances = $wo['Property']['AppliancesAtProperty'];
        if($appliances != NULL) {
            foreach ($appliances as $k => $v) {
                $resp = $this->baseclass->addFormSubmittedForJob(array('FormId' => $applianceUpd, 'JobId' => $jobId));
                if ($resp['response']['response'] != NULL && $resp['response']['response']['FormSubmittedId'] != NULL) {
                    foreach ($ans as $k => $v) {
                        foreach ($ques[$k] as $val) {
                            switch ($v[0]) {
                                case "ApplianceLocation":
                                    if (in_array($appliances['ApplianceLocation'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "ApplianceType":
                                    if (in_array($appliances['ApplianceType'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "ChimneyType":
                                    if (in_array($appliances['ChimneyType'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "GasCouncilNumber":
                                    if (in_array($appliances['GasCouncilNumber'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "ID":
                                    if (!empty($appliances['ID'])) {
                                        $answers[] = ['ans' => $appliances['ID'], 'ques' => $k];
                                    }
                                    break;
                                case "IsUnderContract":
                                    if (in_array($appliances['IsUnderContract'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "IsthistheLandlordsappliance":
                                    if (in_array($appliances['IsthistheLandlordsappliance'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "Make":
                                    if (!empty($appliances['Make'])) {
                                        $answers[] = ['ans' => $appliances['Make'], 'ques' => $k];
                                    }
                                    break;
                                case "Model":
                                    if (!empty($appliances['Model'])) {
                                        $answers[] = ['ans' => $appliances['Model'], 'ques' => $k];
                                    }
                                    break;
                                case "ModelQualifier":
                                    if (!empty($appliances['ModelQualifier'])) {
                                        $answers[] = ['ans' => $appliances['ModelQualifier'], 'ques' => $k];
                                    }
                                    break;
                                case "OtherApplianceLocation":
                                    if (in_array($appliances['OtherApplianceLocation'], $val)) {
                                        $answers[] = ['ans' => $appliances['OtherApplianceLocation'], 'ques' => $k];
                                    }
                                    break;
                                case "SerialNumber":
                                    if (in_array($appliances['SerialNumber'], $val)) {
                                        $answers[] = ['ans' => $appliances['SerialNumber'], 'ques' => $k];
                                    }
                                    break;
                                case "Status":
                                    if (in_array($appliances['Status'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "SystemType":
                                    if (in_array($appliances['SystemType'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;
                                case "ApplianceStoredinCompartment":
                                    if (in_array($appliances['ApplianceStoredinCompartment'], $val)) {
                                        $answers[] = ['ans' => $val[1], 'ques' => $k];
                                    }
                                    break;


                            }
                        }
                    }
                    if ($answers != NULL) {
                        foreach ($answers as $answer) {
                            $resp2 = $this->baseclass->addFormSubmittedSubmitQuestionAnswer(array('FormQuestionId' => $answer['ques'], 'FormQuestionAnswer' => $answer['ans'], 'FormSubmittedId' => $resp['response']['response']['FormSubmittedId']));

                        }
                    }
                }
            }
        }
    }

    private function addExclusion($params = []){
        $wo = $params['workOrder'];
        $jobId = $params['jobId'];

        $exclusionId = 150;
        $answers = NULL;

        $ans = [
            '7082'=>['Status'],
            '7083'=>['ExclusionLocation'],
            '7084'=>['ExclusionLocationOther'],
            '7085'=>['AffectedAppliance'],
            '7086'=>['OtherAffectedAppliance'],
            '7087'=>['ExcludedComponent'],
            '7088'=>['OtherExcludedComponent'],
            '7089'=>['ExclusionReason'],
            '7090'=>['ExclusionReasonOther'],
            '7091'=>['ReasonForAmendment'],
            '7092'=>['OtherReasonforAmendment'],
            '7095'=>['Landlordsappliance']
        ];

        $ques = [
            "7082"=>[
                ["Change","63901"],
                ["Inactive","63902"],
                ["Active","64019"]],
            "7083"=>[
                ["Lounge","63903"],
                ["Utility","63904"],
                ["Bedroom","63905"],
                ["Garage","63906"],
                ["Hall","63907"],
                ["Kitchen","63908"],
                ["Airing","63909"],
                ["Bathroom","63910"],
                ["Loft","63911"],
                ["Dining Room","63912"],
                ["Outhouse","63913"],
                ["Under Stairs","63914"],
                ["Basement","63915"],
                ["Office","63916"],
                ["Toilet","63917"],
                ["Cloakroom","63918"],
                ["Boiler Room","63919"],
                ["Store","63920"],
                ["Porch","63921"],
                ["Conservatory","63922"],
                ["External","63923"],
                ["System","63924"],
                ["Appliance","63925"],
                ["Not Known","63926"],
                ["Carcass","63927"],
                ["Other","63928"],
                ["All rooms excluding the Hallway","63929"]],
            "7085"=>[
                ["Boiler","63930"],
                ["Central Heating-Hot Water Controls","63931"],
                ["Central Heating-Hot Water System","63932"],
                ["Hot Water Cylinder","63933"],
                ["Other","63934"]],
            "7087"=>[
                ["Cylinder Thermostat","63935"],
                ["Other","63936"],
                ["Three Port Valve","63937"],
                ["Two Port Valve (Hot Water)","63938"],
                ["Room Thermostat","63939"],
                ["External Pump","63940"],
                ["Programmer","63941"],
                ["Frost Thermostat","63942"],
                ["Fuse Spur","63943"],
                ["Auto Air Valve","63944"],
                ["Ball Valve","63945"],
                ["Decorative Radiator","63946"],
                ["Lock Shield Valve","63947"],
                ["Bypass Valve","63948"],
                ["Pump Valves","63949"],
                ["Heating Pipe Work","63950"],
                ["Cold Feed & Expansion Tank","63951"],
                ["Gas Pipe Work","63952"],
                ["Boiler Pump (Unvented)","63953"],
                ["DHW Primary Pump (Unvented)","63954"],
                ["Pressure Relief (Unvented)","63955"],
                ["Blending Valve (Unvented)","63956"],
                ["Twin Coiled Cylinder","63957"],
                ["Display PCB","63958"],
                ["Boiler Case/Fascia","63959"],
                ["Ignition Lead","63960"],
                ["Flue Terminal","63961"],
                ["Service Valve","63962"],
                ["Control","63963"],
                ["Air Point","63964"],
                ["PCB (Unvented)","63965"],
                ["Thermostatic Valve","63966"],
                ["Immersion Heater","63967"],
                ["Pressure Reducing valve (Unvented)","63968"],
                ["Space Heating Pump (Unvented)","63969"],
                ["Hot Water Cylinder","63970"],
                ["Sacrificial Anode (Unvented)","63971"],
                ["Expansion Vessel","63972"],
                ["DHW Heat Exchanger","63973"],
                ["Pressure Relief Valve","63974"],
                ["Reset LED","63975"],
                ["Flue Seals/Band","63976"],
                ["Radiator","63977"],
                ["Filling Loop/Link","63978"],
                ["Twin Entry Valves","63979"]],
            "7089"=>[
                ["Passing","63980"],
                ["Noisy","63981"],
                ["Unchecked","63982"],
                ["Leaking","63983"],
                ["Component Broken","63984"],
                ["Broken","63985"],
                ["Restricted Parts","63986"],
                ["PRV Termination","63987"],
                ["Manufacturers Defect","63988"],
                ["Other","63989"],
                ["Burst","63990"],
                ["Blocked","63991"],
                ["Sludge","63992"],
                ["Unsecured","63993"],
                ["Worn","63994"],
                ["Water Ingress","63995"],
                ["Asbestos","63996"],
                ["Access Issue","63997"],
                ["Pressure Loss & Related","63998"],
                ["Not Covered - T&Cs","63999"],
                ["Missing","64000"],
                ["Not Working","64001"],
                ["Corroded","64002"]],
            "7091"=>[
                ["3rd Party work carried out","64003"],
                ["Change of Cover","64004"],
                ["Exclusion added in error ","64005"],
                ["Upgrade work carried out by engineer","64006"],
                ["Other","64007"]],
            "7095"=>[
                ["Yes","64016"],
                ["No","64017"]]
        ];

        $exclusions = $wo['Property']['SiteExclusions'];
        if($exclusions != NULL){
            foreach ($exclusions as $k=>$v){
                $resp = $this->baseclass->addFormSubmittedForJob(array('FormId'=>$exclusionId,'JobId'=>$jobId));
                if($resp['response']['response'] !=  NULL && $resp['response']['response']['FormSubmittedId'] != NULL) {
                    foreach ($ans as $k => $v) {
                        foreach ($ques[$k] as $val) {
                            switch ($v[0]) {
                                case "AffectedAppliance":
                                    if (in_array($exclusions['AffectedAppliance'], $val)) {
                                        $answers[] = ['ans'=>$val[1], 'ques'=>$k];
                                    }
                                    break;
                                case "ExcludedComponent":
                                    if (in_array($exclusions['ExcludedComponent'], $val)) {
                                        $answers[] = ['ans'=>$val[1], 'ques'=>$k];
                                    }
                                    break;
                                case "ExclusionLocation":
                                    if (in_array($exclusions['ExclusionLocation'], $val)) {
                                        $answers[] = ['ans'=>$val[1], 'ques'=>$k];
                                    }
                                    break;
                                case "ExclusionReason":
                                    if (in_array($exclusions['ExclusionReason'], $val)) {
                                        $answers[] = ['ans'=>$val[1],'ques'=> $k];
                                    }
                                    break;
                                case "ID":
                                    if (!empty($exclusions['ID'])) {
                                        $answers[] = ['ans'=>$exclusions['ID'], 'ques'=>$k];
                                    }
                                    break;
                                case "OtherAffectedAppliance":
                                    if (!empty($exclusions['OtherAffectedAppliance'])) {
                                        $answers[] = ['ans'=>$exclusions['OtherAffectedAppliance'], 'ques'=>$k];
                                    }
                                    break;
                                case "OtherExcludedComponent":
                                    if (!empty($exclusions['OtherExcludedComponent'])) {
                                        $answers[] = ['ans'=>$exclusions['OtherExcludedComponent'],'ques'=> $k];
                                    }
                                    break;
                                case "Status":
                                    if (in_array($exclusions['Status'], $val)) {
                                        $answers[] = ['ans'=>$val[1], 'ques'=>$k];
                                    }
                                    break;
                                case "ExclusionLocationOther":
                                    if (!empty($exclusions['ExclusionLocationOther'])) {
                                        $answers[] = ['ans'=>$exclusions['ExclusionLocationOther'],'ques'=> $k];
                                    }
                                    break;
                                case "ExclusionReasonOther":
                                    if (!empty($exclusions['ExclusionReasonOther'])) {
                                        $answers[] = ['ans'=>$exclusions['ExclusionReasonOther'],'ques'=> $k];
                                    }
                                    break;
                            }
                        }
                    }
                    if($answers != NULL){
                        foreach ($answers as $answer){
                            $resp2 = $this->baseclass->addFormSubmittedSubmitQuestionAnswer(array('FormQuestionId' => $answer['ques'], 'FormQuestionAnswer' => $answer['ans'], 'FormSubmittedId' => $resp['response']['response']['FormSubmittedId']));

                        }
                    }
                }
            }
        }
    }
}