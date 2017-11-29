<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        // Your own constructor code

        $this->load->helper('file');
        $this->load->helper('directory');
        $this->load->helper('date');
    }

    private $invoice,$poGas,$poElecric,$endDate,$startDate,$workOrders;

    private $rates = [
        ["GasWork","InitialInspection",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"59.01m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"59.01m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"73.42m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"67.88m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"73.42m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"59.01m"],
            ]
        ],
        ["GasWork","Service",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"59.01m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"59.01m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"73.42m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"67.88m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"73.42m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"59.01m"],
            ]
        ],
        ["GasWork","Emergency",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"107.29m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"107.29m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"121.92m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"116.35m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"121.92m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"107.29m"],
            ]
        ],
        ["GasWork","SameDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"107.29m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"107.29m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"121.92m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"116.35m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"121.92m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"107.29m"],
            ]
        ],
        ["GasWork","NextDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"107.29m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"107.29m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"121.92m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"116.35m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"121.92m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"107.29m"],
            ]
        ],
        ["GasWork","NextWorkingDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"107.29m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"107.29m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"121.92m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"116.35m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"121.92m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"107.29m"],
            ]
        ],
        ["GasWork","NonUrgentBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"107.29m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"107.29m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"121.92m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"116.35m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"121.92m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"107.29m"],
            ]
        ],
        ["GasWork","EmergencyService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"134.97m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"134.97m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"149.56m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"143.96m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"139.65m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"149.56m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"134.97m"],
            ]
        ],
        ["GasWork","SameDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"134.97m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"134.97m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"149.56m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"143.96m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"139.65m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"149.56m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"134.97m"],
            ]
        ],
        ["GasWork","NextDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"134.97m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"134.97m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"149.56m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"143.96m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"139.65m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"149.56m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"134.97m"],
            ]
        ],
        ["GasWork","NextWorkingDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"134.97m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"134.97m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"149.56m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"143.96m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"139.65m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"149.56m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"134.97m"],
            ]
        ],
        ["GasWork","NonUrgentBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"134.97m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"134.97m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"149.56m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"143.96m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"139.65m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"149.56m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"134.97m"],
            ]
        ],
        ["ElectricalWork","InitialInspection",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"72.06m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"72.06m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"72.06m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"72.06m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"72.06m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"72.06m"],
            ]
        ],
        ["ElectricalWork","Service",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"72.06m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"72.06m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"72.06m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"72.06m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"72.06m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"72.06m"],
            ]
        ],
        ["ElectricalWork","Emergency",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","SameDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NextDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NextWorkingDayBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NonUrgentBreakdown",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"79.8m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","EmergencyService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","SameDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NextDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NextWorkingDayBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"78.61m"],
            ]
        ],
        ["ElectricalWork","NonUrgentBreakdownService",
            "Rates"=>[
                ["PostCodes"=>["LN","NG","DE","ST"],"Rate"=>"78.61m"],
                ["PostCodes"=>["BD","DN","HD","HG","HU","HX","LS","S","WF","YO","DH","DL","NE","SR","TS"],"Rate"=>"78.61m"],
                ["PostCodes"=>["CH","LL","SY","TF","LD","HR"],"Rate"=>"96.98m"],
                ["PostCodes"=>["BB","BL","CA","CW","FY","L","LA","M","OL","PR","SK","WA","WN"],"Rate"=>"89.93m"],
                ["PostCodes"=>["EX","PL","TA","TQ","TR"],"Rate"=>"99.75m"],
                ["PostCodes"=>["TD","DG","KW","IV","PH","PA"],"Rate"=>"96.98m"],
                ["PostCodes"=>["B","CV","DY","WR","WS","WV"],"Rate"=>"76.61m"],
            ]
        ]
    ];

    public function index(){
        //15 days bydefault
        if(!is_cli()){
            echo "Direct access not allowed";
            return false;
        }
        $date = new DateTime(); //change to now on Live
        $this->endDate = $date->format('Y-m-d H:i:s');
        $this->startDate = $date->modify('-15 days')->format('Y-m-d H:i:s');

        $isMonth = false;
        $existingEvents = $events = $workOrders = $complEvents = $monthName = NULL;

        if(!$isMonth){
            $this->LoadWorkOrders();
        }


        $resp = $this->baseclass->getJobEventsDateRange(['DateFrom'=>$this->startDate,'DateTo'=> $this->endDate]);

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

        if($events == NULL){
            return false;
        }

        $this->invoice = ['Date'=>now('Y-m-d'),'PO'=>NULL];

        $this->poGas = ['Number'=>'POGas','Type'=>'gas','WO'=>NULL];
        $this->poElecric = ['Number'=>'POElecric','Type'=>'electric','WO'=>NULL];

        foreach ($events as $k=>$v){
            if($v['EventCode'] == "JOB_COMPLETE" && $v['EventData']['JobStatus'] == "C"){
                $complEvents[] = $v;
            }
        }

        if($complEvents != NULL){
            foreach ($complEvents as $key=>$val){
                $workOrder = $val['EventData']['JobDataJsonConcealed']['workorder'];
                if($workOrder == NULL || $this->chkPoBool(['workOrder'=>$workOrder,'po'=>$this->poGas]) == true || $this->chkPoBool(['workOrder'=>$workOrder,'po'=>$this->poElecric]) == true){
                    continue;
                }

                $this->workOrders[] = $workOrder['woNumber'];

                $jobId = $v['EventData']['JobId'];
                $jobDetails = $this->baseclass->getJobDetails(['JobId'=>$jobId]);
                if($jobDetails['response']['response'] == NULL){
                    log_message('debug','87:');
                    continue;
                }else{
                    $jobDetails = $jobDetails['response']['response'];
                }

                $wo['Completed_date'] = $jobDetails['JobDetails']['DateCompleted'];
                $wo['Postcode'] = !empty($workOrder['Property']['postCode'])?$workOrder['Property']['postCode']:$workOrder['Customer']['postCode'];
                $wo['WO_number'] = $workOrder['woNumber'];
                $wo['Group_type'] = preg_replace("/[^a-zA-Z]+/", "", $workOrder['woGroup']);

                if($wo['Postcode'] != NULL){
                    $rate = $this->GetRates(['postCode'=>$wo['Postcode'],'woGroup'=>$workOrder['woGroup'],'woType'=>$workOrder['woType']]);
                    $wo['Cost'] = $rate;
                }

                if($wo['Group_type'] == 'ElectricalWork'){
                    $this->poElecric['WO'][] = $wo;
                }else{
                    $this->poGas['WO'][] = $wo;
                }
            }
        }

        if($this->poElecric['WO'] != NULL){
            $this->invoice['PO'][] = $this->poElecric;
        }
        if($this->poGas['WO'] != NULL){
            $this->invoice['PO'][] = $this->poGas;
        }

        if($isMonth == true){
            //
            //
            $monthName = "_monthly";
        }

        $filename = "Invoice_".$date->format('dmYhis').$monthName;
        $my_file = $filename.'.xml';
        $message = json_encode($this->invoice,true);
        $param = ['resp'=>$message,'dir'=>'invoice','details'=>['file'=>['name'=>$my_file]]];
        $this->baseclass->fileHandling($param);
    }

    private function LoadWorkOrders($params = []){

        //from FTP download
        //$this->baseclass->ftpFile(['dir'=>['ftp'=>OUTBOX_FTP_PROCESSED,'local'=>INBOX_PROCESSED],'method'=>'down']);
        $proFiles = get_dir_file_info(INBOX_PROCESSED);

        if($proFiles != NULL){
            foreach ($proFiles as $file){
                if($data = $this->baseclass->checkXmlFile(['file'=>$file]) && date("d/m/Y",$file['date']) >= $this->startDate && date("d/m/Y",$file['date']) < $this->endDate) {
                    $files[] = $data;
                }
            }
        }

        //from FTP download
        //$this->baseclass->ftpFile(['dir'=>['ftp'=>OUTBOX_FTP_REJECTED,'local'=>INBOX_REJECTED],'method'=>'down']);
        $rejFiles = get_dir_file_info(INBOX_REJECTED);

        if($rejFiles != NULL){
            foreach ($rejFiles as $file){
                if($data = $this->baseclass->checkXmlFile(['file'=>$file]) && date("d/m/Y",$file['date']) >= $this->startDate && date("d/m/Y",$file['date']) < $this->endDate) {
                    $files[] = $data;
                }
            }
        }

        foreach ($data as $k=>$val){
            if($val['Response']['WorkOrders']['WorkOrder']['woNumber'] != null){
                $this->workOrders[] = $val['Response']['WorkOrders']['WorkOrder']['woNumber'];
            }
        }
    }

    private function GetRates($params = []){

        $woGroup = preg_replace("/[^a-zA-Z]+/", "", $params['woGroup']);
        $woType = preg_replace("/[^a-zA-Z]+/", "", $params['woType']);
        $postCode = $params['postCode'];

        $woRates = $rate = NULL;
        foreach ($this->rates as $k=>$v){
            if($v[0] == $woGroup && $v[1] == $woType ){
                $woRates = $v['Rates'];
            }
        }

        $len = is_int(substr($postCode,0,1))?'1':'2';
        $code = substr($postCode,0,$len);

        if($woRates == NULL){
            foreach ($woRates as $key=>$val){
                foreach ($val['PostCodes'] as $k1=>$v1){
                    if(in_array($code,$v1[0])){
                        $rate = $v1['Rate'];
                    }
                }
            }
        }

        return $rate;
    }

    private function chkPoBool($params = []){
        $workOrder = $params['workOrder'];
        $po = $params['po'];

        if($po['WO'] != NULL){
            foreach ($po['WO'] as $k=>$v){
                if($v['WO_number'] == $workOrder['woNumber']){
                    return true;
                }
            }
        }
        return false;

    }

}