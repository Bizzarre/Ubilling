<?php

if (cfr('SCREPORT')) {

    class ReportSelfCredit {

        private $data = array();
        private $chartdata = '';
        private $tabledata='';

        public function __construct() {
            //load actual month data
            $this->loadData();
        }

        /*
         * parse data from payments table and stores it into private data prop
         * 
         * @return void
         */

        private function loadData() {
            $curmonth = curmonth();
            $query = "SELECT * from `payments` WHERE `note` LIKE 'SCFEE' AND `date` LIKE '" . $curmonth . "%' ORDER BY `id` DESC";
            $alldata = simple_queryall($query);

            if (!empty($alldata)) {
                foreach ($alldata as $io => $each) {
                    $this->data[$each['id']]['id'] = $each['id'];
                    $this->data[$each['id']]['date'] = $each['date'];
                    $newSum = abs($each['summ']);
                    $this->data[$each['id']]['summ'] = $newSum;
                    $this->data[$each['id']]['login'] = $each['login'];
                }
            }
        }

        /*
         * returns private propert data
         * 
         * @return array
         */

        public function getData() {
            $result = $this->data;
            return ($result);
        }

        /*
         * returns summ of self credit payments by year/month
         * 
         * @param $year target year
         * @param $month month number
         * 
         * @return string
         */

        private function getMonthSumm($year, $month) {
            $year = vf($year);
            $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result = abs($result['SUM(`summ`)']);
            return($result);
        }

        /*
         * returns count of self credit payments by year/month
         * 
         * @param $year target year
         * @param $month month number
         * 
         * @return string
         */

        private function getMonthCount($year, $month) {
            $year = vf($year);
            $query = "SELECT COUNT(`id`) from `payments` WHERE `date` LIKE '" . $year . "-" . $month . "%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result = $result['COUNT(`id`)'];
            return($result);
        }
        
        /*
         * returns summ of self credit payments by year
         * 
         * @param $year target year
         * 
         * @return string
         */
        private function getYearSumm($year) {
            $year=vf($year);
            $query = "SELECT SUM(`summ`) from `payments` WHERE `date` LIKE '" . $year . "-%' AND `note` LIKE 'SCFEE'";
            $result = simple_query($query);
            $result=$result['SUM(`summ`)'];
            $result=abs($result);
            return ($result);
        }

        /*
         * parse data from payments table and stores it into private monthdata prop
         * 
         * @return void
         */

        private function loadMonthData() {
            $months = months_array();
            $year = curyear();
            $yearSumm=$this->getYearSumm($year);
            
            $this->chartdata = __('Month') . ',' . __('Count') . ',' . __('Cash') . "\n";
            
            $cells=wf_TableCell('');
            $cells.=wf_TableCell(__('Month'));
            $cells.=wf_TableCell(__('Payments count'));
            $cells.=wf_TableCell(__('Our final profit'));
            $cells.=wf_TableCell(__('Visual'));
            $this->tabledata=wf_TableRow($cells,'row1');

            foreach ($months as $eachmonth => $monthname) {
                $month_summ = $this->getMonthSumm($year, $eachmonth);
                $paycount = $this->getMonthCount($year, $eachmonth);
                $this->chartdata.=$year . '-' . $eachmonth . '-30,' . $paycount .','.$month_summ. "\n";
                
            $cells=wf_TableCell($eachmonth);
            $cells.=wf_TableCell(rcms_date_localise($monthname));
            $cells.=wf_TableCell($paycount);
            $cells.=wf_TableCell($month_summ);
            $cells.=wf_TableCell(web_bar($paycount, $yearSumm));
            $this->tabledata.=wf_TableRow($cells,'row3');
                
            }
        }

        /*
         * renders aself credit report using private data property
         * 
         * @return string
         */

        public function render() {
            $allAddress = zb_AddressGetFulladdresslist();
            $allRealNames = zb_UserGetAllRealnames();
            $totalCount = 0;
            $totalSumm = 0;

            $cells = wf_TableCell(__('ID'));
            $cells.= wf_TableCell(__('Date'));
            $cells.= wf_TableCell(__('Cash'));
            $cells.= wf_TableCell(__('Login'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Full address'));
            $rows = wf_TableRow($cells, 'row1');

            if (!empty($this->data)) {
                foreach ($this->data as $io => $each) {
                    $totalCount++;
                    $totalSumm = $totalSumm + $each['summ'];
                    $cells = wf_TableCell($each['id']);
                    $cells.= wf_TableCell($each['date']);
                    $cells.= wf_TableCell($each['summ']);
                    $loginLink = wf_Link("?module=userprofile&username=" . $each['login'], web_profile_icon() . ' ' . $each['login'], false, '');
                    $cells.= wf_TableCell($loginLink);
                    $cells.= wf_TableCell(@$allAddress[$each['login']]);
                    $cells.= wf_TableCell(@$allRealNames[$each['login']]);
                    $rows.= wf_TableRow($cells, 'row3');
                }
            }
            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.= wf_tag('div', false, 'glamour') . __('Count') . ': ' . $totalCount . wf_tag('div', true);
            $result.= wf_tag('div', false, 'glamour') . __('Our final profit') . ': ' . $totalSumm . wf_tag('div', true);

            return ($result);
        }

        /*
         * renders aself credit report using private data property
         * 
         * @return string
         */

        public function renderMonthGraph() {
            $this->loadMonthData();
            $result='';
            $result.=wf_TableBody($this->tabledata, '100%', '0', 'sortable');
            $result.= wf_tag('div', false, 'dashtask', '');
            $result.= wf_Graph($this->chartdata, '800', '400', false);
            $result.= wf_tag('div', true);
            return ($result);
        }

    }

    /*
     * controller & view
     */

    $screport = new ReportSelfCredit();
    if (!wf_CheckGet(array('showgraph'))) {
        show_window('', wf_Link('?module=report_selfcredit&showgraph=true', __('Self credit dynamic over the year'), false, 'ubButton'));
        show_window(__('Self credit report'), $screport->render());
    } else {
        show_window('', wf_Link('?module=report_selfcredit', __('Back'), false, 'ubButton'));
        show_window(__('Self credit dynamic over the year'),$screport->renderMonthGraph());
    }
    
    
    
} else {
    show_error(__('You cant control this module'));
}
?>