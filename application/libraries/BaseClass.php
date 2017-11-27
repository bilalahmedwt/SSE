<?php
class BaseClass
{

    protected $CI;

    public function __construct()
    {
        //parent::__construct();

        // Assign the CodeIgniter super-object
        $this->CI =& get_instance();

        // Your own constructor code
        $this->CI->load->library('format',[]);

        $this->CI->load->helper('file');
        $this->CI->load->helper('directory');
        $this->CI->load->helper('date');
        Requests::register_autoloader();
    }

    public $headers = array('client-id' => '1006', 'client-secret' => '9384fd12efe5b91eac73fabe70e636ba', 'Content-Type' => 'application/x-www-form-urlencoded');
    public $options = ['timeout'=>'100000','connect_timeout'=>'100000'];

    public function findJob($params = [])
    {
        //$departmentId, $mpan, $woNo, $jobDate = ''
        $url = API_TEST . 'job/FindJob';

        $json = json_encode(['SearchTerms' => $params]);
        //print_r($json); exit;
//        array(
//            'DepartmentId' => $departmentId,
//            'AccountNumber' => $mpan,
//            'JobDate' => null,
//            'CaseNumber' => $woNo
//        )
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);
        //print_r($response);

        if ($response) {

            $response = json_decode($response->body, true);
            //print_r($response);
            //if (isset($response['response']['response'][0])) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            return $response;
            //}
        }

    }

    public function createJob($params = [])
    {
        //createJob($jobDetailsArr, $customerDetailsArr, $fileName)
        $mpan = $flowId = $sseCode = $additionalDetails = $jobDescription = $city = $PhoneNumber1 = $PhoneNumber2 = $PhoneNumber3 = $postCode = $email = '';
        $year = $month = $date = $hour = $minutes = $seconds = $meteringPointAddress = '';
        $minDate = $dueDate = null;

        $customerDetails = $jobDetails = array();
        $jobDetailsArrIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($params['jobDetailsArr']));

        foreach ($jobDetailsArrIterator as $key => $value) {

            if ($key == 'FLOWID') {
                $flowId = trim($value);
            }
            if ($key == 'J0003') {
                $mpan = trim($value);
            }
            if ($key == 'J0174') {
                $appointmentDate = trim($value);
                $year = substr($appointmentDate, 0, 4);
                $month = substr($appointmentDate, 4, 2);
                $date = substr($appointmentDate, 6, 2);
                $appointmentDate = $year . '-' . $month . '-' . $date;
            }
            if ($key == 'J0292') {
                $earliestAppointmentTime = trim($value);
                $hour = substr($earliestAppointmentTime, 0, 2);
                $minutes = substr($earliestAppointmentTime, 2, 2);
                $seconds = substr($earliestAppointmentTime, 4, 2);
                $earliestAppointmentTime = $hour . ':' . $minutes . ':' . $seconds;
            }
            if ($key == 'J0293') {
                $latestAppointmentTime = trim($value);
                $hour = substr($latestAppointmentTime, 0, 2);
                $minutes = substr($latestAppointmentTime, 2, 2);
                $seconds = substr($latestAppointmentTime, 4, 2);
                $latestAppointmentTime = $hour . ':' . $minutes . ':' . $seconds;
            }
            // J0076 (SSC) is a 4 digit numeric code that determines the configuration of a meter
            if ($key == 'J0076') {
                $sseCode = trim($value);
            }
            // J0012 Additional Details
            if ($key == 'J0012') {
                $additionalDetails = trim($value);
            }

            list($jobDetails) = $this->processKeyValuePairs($key, $value, $jobDetails);

        }

        $customerDetailsArrIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($params['customerDetailsArr']));
        foreach ($customerDetailsArrIterator as $key => $value) {
            // Details from DB search by MPAN number
            if ($key == 'CompanyName') {
                $customerName = trim($value);
            }
            if ($key == 'AddressLine1') {
                $Address1 = trim($value);
            }
            if ($key == 'AddressLine2') {
                $Address2 = trim($value);
            }
            if ($key == 'AddressLine3') {
                $Address3 = trim($value);
            }
            if ($key == 'City') {
                $city = trim($value);
            }
            if ($key == 'PhoneNumber1') {
                $PhoneNumber1 = trim($value);
            }
            if ($key == 'PhoneNumber2') {
                $PhoneNumber2 = trim($value);
            }
            if ($key == 'PhoneNumber3') {
                $PhoneNumber3 = trim($value);
            }
            if ($key == 'Email') {
                $email = trim($value);
            }
            if ($key == 'PostCode') {
                $postCode = trim($value);
            }

            // Details from file , NO phone number*
            /*if ($key == 'CustomerName') {
                $customerName = trim($value);
            }
            if ($key == 'ContactName') {
                $contactName = trim($value);
            }
            if ($key == 'MailingAddress') {
                $mailingAddress = trim($value);
            }
            if ($key == 'MeteringPointAddress') {
                $meteringPointAddress = trim($value);
            }*/

            list($customerDetails) = $this->processKeyValuePairs($key, $value, $customerDetails);

        }

        if ($sseCode) {
            $jobDescription = $additionalDetails . '. SSE: ' . $sseCode;
        } else {
            $jobDescription = $additionalDetails;
        }

        if (isset($Address1) && isset($Address2)) {
            $AddressLine1 = $Address1;
            $AddressLine2 = $Address2 . ' ' . $Address3;
        } else {
            $AddressLine1 = 'Address: ' . $Address1 . ' ' . $Address2;
            $AddressLine2 = 'Address: ' . $AddressLine1 . ' ' . $Address3;
        }

        if (!isset($Address1) && !isset($Address2)) {
            $AddressLine1 = 'AddressLine1 is not available';
            $AddressLine2 = 'AddressLine2 is not available';
        }

        /*if (isset($mailingAddress)) {
            $AddressLine1 = 'Mailing Address: ' . $mailingAddress;
            $AddressLine2 = 'Metering Point Address: ' . $meteringPointAddress;
        }*/

        if (isset($appointmentDate) && isset($earliestAppointmentTime) && isset($latestAppointmentTime)) {
            $minDate = $appointmentDate . ' ' . $earliestAppointmentTime; // 2016-11-02 11:10:50
            $dueDate = $appointmentDate . ' ' . $latestAppointmentTime;
        }

        //print_r($jobDetailsArr);
        //print_r($customerDetailsArr);
        //EXIT();

        $jobDetailsJson = json_encode(array('JobDetails' => $jobDetails, 'CustomerDetails' => $customerDetails));

        //print_r($jobDetails);
        //print_r($customerDetails);
        //print_r($jobDetailsJson);
        //EXIT();

        //'DepartmentCode, FirstName, Surname, AddressLine1, AddressLine2 must be defined'
        /*params={"JobDetails":{"DepartmentCode":"", "CaseNumber":"", "SubscriptionCode":"", "CompanySubscriptionCode":"" }}*/

        //$url = CREATE_JOB_URL;
        $url = API_TEST . 'job/CreateJobWeb';

        $json = json_encode(array('JobDetails' => array(
            'DepartmentCode' => 'ElecBAUWeb',
            'CaseNumber' => $flowId,
            //'SubscriptionCode' => '',
            //'CompanySubscriptionCode' => '', // x
            'AccountNumber' => $mpan,
            'FirstName' => $customerName,
            'Surname' => $customerName,
            //'JobDate' => '', // x
            'MinDate' => $minDate, // 2016-11-02 11:10:50 '0000-00-00 00:00:00'
            'DueDate' => $dueDate,   // 2016-11-02 11:10:50 '0000-00-00 00:00:00'
            'AddressLine1' => $AddressLine1,
            'AddressLine2' => $AddressLine2,
            //'AddressLine3' => '',
            //'AddressLine4' => '',
            'City' => $city,
            //'County' => '',
            'PostCode' => $postCode,
            //'MobileNumber' => '',
            'TelephoneNumber' => $PhoneNumber1,
            'TelephoneNumber2' => $PhoneNumber2,
            'TelephoneNumber3' => $PhoneNumber3,
            'EmailAddress' => $email,
            'JobDescription' => $jobDescription,
            //'JobTypeCodes' => '', //expects array
            //'JobNote' => '', //x
            'AdditionalDetails' => $jobDetailsJson
        )));

        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));
        //EXIT();

        //$response = $this->curlRequest($url, $params);
        $response = Requests::put($url, $this->headers, $params);

        if ($response) {

            $response = json_decode($response, true);

            if ($response['status'] == 'E') {

                $params = urldecode($params);
                $errorFunc = __FUNCTION__;

                if ($response['response']['errorCode'] == '2' && $response['response']['errorMessage'] == 'Failed creating job') {
                    //echo 'Duplicate Job';
                    $errorMsg = 'DUPLICATE Job, FileName: ' . $params['fileName'] . ' MPAN: ' . $mpan . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                } else {
                    $errorMsg = 'FAILED to create Job, FileName: ' . $params['fileName'] . ' MPAN: ' . $mpan . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                }
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
                print_r($response);

            }

            return $response;
        }

    }

    public function addCustomer($params = [])
    {
//        addCustomer($jobDetailsArr)
        $Address1 = $Address2 = '';

        $jobDetailsArrIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($params['jobDetailsArr']));
        foreach ($jobDetailsArrIterator as $key => $value) {
            // From flow DO142
            if ($key == 'J0003') {
                $mpan = trim($value);
            }
            if ($key == 'J1036' || $key == 'J1037' || $key == 'J1038' || $key == 'J1039' || $key == 'J1040' || $key == 'J1041') {
                $Address1 .= trim($value) . ' ';
            }
            if ($key == 'J1042' || $key == 'J1043' || $key == 'J1044') {
                $Address2 .= trim($value) . ' ';
            }
            if ($key == 'J0263') {
                $postCode = trim($value);
            }

        }

        $url = API_TEST . 'company/AddCompany';

        $json = json_encode(array('CompanyDetails' => array(
            'CompanyCode' => $mpan, // *
            'CompanyCode2' => null,
            'CompanyName' => 'Customer Name', // *
            'AddressLine1' => $Address1, // *
            'AddressLine2' => $Address2,
            //'AddressLine3' => '',
            //'City' => '',
            //'County' => '',
            'PostCode' => $postCode,
            //'TelephoneNumber1' => '',
            //'TelephoneNumber2' => '',
            //'TelephoneNumber3' => '',
            //'EmailAddress' => '',
            'AdditionalDetails' => ''
        )));

        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params);

        if ($response) {

            $response = json_decode($response, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to add customer,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            } else {
                return $response;
            }
        }

    }

    public function getGeoAreaForPostCode($params = [])
    {

        $url = API_TEST . 'general/GetGeoAreaForPostCode';
        //params={"JobDetails":{"JobId":994868}}
        //$json='{"JobDetails":{"JobId":' . $jobId . '}}';
        $json = json_encode(array('PostCode' => $params['postCode']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params);

        if ($response) {

            $response = json_decode($response, true);

            if (empty($response['response']['response']['GeoArea'])) {
                $params = urldecode($params);
                $errorMsg = 'FAILED to get GeoArea For PostCode,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function getJobEvents($params = [])
    {

        $url = API_TEST . 'job/GetJobEvents';

        //params={"JobDetails":{"JobId":994868}}
        //$json='{"JobDetails":{"JobId":' . $jobId . '}}';
        $json = json_encode(array('JobDetails' => array('JobId' => $params['jobId'])));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            //log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to get Job Event,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function updateJobStatus($params = [])
    {

        //$url = UPDATE_JOB_STATUS_URL;
        $url = API_TEST . 'job/UpdateJobStatus';
        $json = json_encode(array('JobDetails' => array('JobId' => $params['jobId'], 'JobStatusCode' => $params['jobStatusCode'])));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {

            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to update job status,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
                //$this->sendEmail('FAILED to update job status. JobId is ' . $jobId, $response, null);
            }

            return $response;
        }

    }

    public function getJobStatus($params = [])
    {
        /*
        JobStatusCode => CR , JobStatusName => Created
        JobStatusCode => D , JobStatusName => Duplicate
        JobStatusCode => CON , JobStatusName => Confirmed
        JobStatusCode => BTO , JobStatusName => Cancelled
        */
        $jobDetailsResponse = $this->getJobDetails(array('jobId' => $params['jobId']));

        if ($jobDetailsResponse) {

            $jobDetailsResponseIterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($jobDetailsResponse));
            foreach ($jobDetailsResponseIterator as $key => $value) {
                if ($key == 'JobStatus') {
                    $jobStatus = $value;
                }
            }

            return $jobStatus;
        }

    }

    public function getJobDetails($params = [])
    {
        //$url = JOB_DETAILS_URL;
        $url = API_SSE . 'job/GetJobDetails';
        //$json='{"JobDetails":{"JobId":' . $jobId . '}}';
        $json = json_encode(array('JobDetails' => array('JobId' => $params['JobId'])));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            //log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to get Job Details,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function getJobHistory($params = [])
    {
        //$url = JOB_DETAILS_URL;
        $url = API_SSE . 'job/AccountHistory';
        //$json='{"JobDetails":{"JobId":' . $jobId . '}}';
        $json = json_encode(array('JobDetails' => array('AccountNumber' => $params['AccountNumber'],'DepartmentCode' => 'SSE')));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            //log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to get Job Details,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function updateJobEvent($params = [])
    {

        //$url = UPDATE_JOB_EVENTS_URL;
        $url = API_TEST . 'job/UpdateJobEvent';
        $json = json_encode(array('JobEventId' => $params['jobEventId']));
        $params = 'params=' . urlencode($json);
        //echo '<pre>';
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {

            $response = json_decode($response, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorMsg = 'FAILED to update Job Event,' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                $errorFunc = __FUNCTION__;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function getJobEventsDateRange($params = [])
    {

        $url = API_SSE . 'job/GetJobEventsForDateRange';

        $json = json_encode(array('DateFrom' => $params['DateFrom'], 'DateTo' => $params['DateTo']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params));

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            //log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);
            //print_r($response); exit;

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                $errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }

    }

    public function getCompany($params = [])
    {
        $url = API_TEST . 'company/CompanySearch';

        $json = json_encode(array('SearchField' => $params['SearchField'], 'SearchValue' => $params['SearchValue']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));

            $response = json_decode($response->body, true);


            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function getCompanyBranches($params = [])
    {
        $url = API_TEST . 'company/GetCompanyBranches';

        $json = json_encode(array('CompanyId' => $params['CompanyId']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addCompany($params = [])
    {

        $url = API_TEST . 'company/AddCompany';

        $json = json_encode(array('CompanyDetails' => $params['CompanyDetails']));
        $params = 'params=' . urlencode($json);

        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);

                log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addCompanyBranch($params = [])
    {

        $url = API_TEST . 'company/AddCompanyBranch';

        $json = json_encode(array('BranchDetails' => $params['BranchDetails']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addJobCompany($params = [])
    {

        $url = API_TEST . 'job/CreateJobCompany';

        $json = json_encode(array('JobDetails' => $params['JobDetails']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addFormSubmittedForJob($params = [])
    {

        $url = API_TEST . 'job/CreateFormSubmittedForJob';

        $json = json_encode(array('FormId' => $params['FormId'],'JobId' => $params['JobId']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addJobNote($params = []){
        $url = API_TEST . 'job/AddJobNote';

        $json = json_encode(array('JobDetails' => $params['JobDetails']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }

    public function addFormSubmittedSubmitQuestionAnswer($params = []){
        $url = API_TEST . 'form/FormSubmittedSubmitQuestionAnswer';

        $json = json_encode(array('FormQuestionId'=>$params['FormQuestionId'],'FormQuestionAnswer'=>$params['FormQuestionAnswer'],'FormSubmittedId'=>$params['FormSubmittedId']));
        $params = 'params=' . urlencode($json);
        //print_r(urldecode($params)); exit;

        //$response = $this->curlRequest($url, $params);
        $response = Requests::post($url, $this->headers, $params,$this->options);

        if ($response) {
            log_message('debug', __METHOD__.':'.urlencode(json_encode($response)));
            $response = json_decode($response->body, true);

            if ($response['status'] == 'E') {
                $params = urldecode($params);
                $errorFunc = __FUNCTION__;
                //$this->errorHandling(['resp'=>'Workorder exists','data'=>$data]);
                //return false;
                //$errorMsg = 'FAILED to get Events by Date Range' . "\r\n" . 'Params Sent:' . "\r\n" . $params;
                //$this->writeToLogFile($response, $errorFunc, $errorMsg);
            }

            return $response;
        }
    }


    public function checkXmlFile($params = []){

        //print_r($params);
        $file = $params;
        if(date("d/m/Y",$file['date']) != date("d/m/Y",now()) && 'application/xml' == get_mime_by_extension($file['name'])) { // for live ,change condition to equals too
            //echo date("d/m/Y",now()).'--'.date("d/m/Y",$file['date']);
            //echo  get_mime_by_extension($file['name']);
            //print_r( simplexml_load_file($file['server_path']));
            $res = $this->CI->format->factory(($file['server_path']), 'xml')->to_array();
            if ($res != NULL) {
                //print_r($res);
                return $res;
            }else{
                return false;
            }
        }
    }


    public function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }


    public function fileHandling($params = []){
        $resp = $params['resp'];
        $data = $params['data'];
        $details = $params['details'];

        $file = $details['file'];
        $dir = $details['dir'];
        $type = $details['type'];
        $errMsgs = $details['errMsgs'];

        //print_r($details);
        //exit;

        if($dir == 'inbox'){
            if ($type == 'rej'){
                if(write_file(INBOX .$file['name'], "\n".$resp,'a')){
                    return rename(INBOX.$file['name'],INBOX_REJECTED.$file['name']);
                }else{
                    log_message('error',"can't write ".INBOX .$file['name']);
                }
            }else{
                return rename(INBOX.$file['name'],INBOX_PROCESSED.$file['name']);
            }

        }elseif ($dir = 'outbox'){
            $resp = $this->CI->format->factory($resp, 'json')->to_xml();
            if(write_file(OUTBOX .$file['name'],$resp,'w+')){
                if ($type == 'rej'){
                    if(write_file(OUTBOX .$file['name'],$errMsgs,'a')){
                        return rename(OUTBOX.$file['name'],OUTBOX_REJECTED.$file['name']);
                    }else{
                        log_message('error',"can't write ".OUTBOX .$file['name']);
                    }
                }
                return rename(OUTBOX.$file['name'],OUTBOX_PROCESSED.$file['name']);
            }else{
                log_message('error',"can't write ".OUTBOX .$file['name']);
            }

        }

    }

}
