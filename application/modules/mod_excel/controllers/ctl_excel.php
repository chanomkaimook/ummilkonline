<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ctl_excel extends CI_Controller
{

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model(
			array(
				'mdl_excel',
				'mdl_shopee',
			)
		);

		$this->load->library(array(
			'session',
			'Permiss',
		));
		$this->load->helper(array(
			'form',
			'url',
			'myfunction_helper',
			'sql_helper',
		));

		if ($this->session->userdata('useradminid') == '') {
			redirect('mod_admin/ctl_login');
		}
	}
	public function index()
	{
		redirect(site_url('mod_excel/ctl_excel/report'));
	}
	public function report()
	{


		$data = array(
			'mainmenu' 		=> 'retail',
			'submenu' 		=> 'reportexcel',
			'importvalue' 		=> ($_REQUEST['page'] ? $_REQUEST['page'] : 'bu2')
		);

		$data['base_bn'] = base_url() . BASE_BN;
		$data['basepic'] = base_url() . BASE_PIC;
		$this->load->view('report', $data);
	}

	public function get_dataImport()
	{
		$request = $_REQUEST;
		$array = json_decode($request['id']);

		foreach ($array as $key => $row) {
			$arraymain[] = "'" . $key . "'";
			$arraykey[$key]['id'] =  "'" . $key . "'";
			foreach ($row as $val) {
				$arraykey[$key]['detail'][] = $val;
			}
		}
		$querysearch = implode(',', $arraymain);

		$sql = $this->db->select('
			retail_bill.code,
			retail_bill.billstatus,
			retail_bill.ref
		')
			->from('retail_bill')
			->join('retail_billdetail', 'retail_bill.id=retail_billdetail.bill_id', 'left')
			->where('retail_bill.status', 1)
			->where('retail_bill.ref in (' . $querysearch . ')')
			->group_by('retail_bill.code');
		$q = $sql->get();

		$data = array();
		$num = $this->db->count_all_results(null, false);
		if ($num) {
			foreach ($q->result() as $row) {
				$data[] = array(
					'code' 	=> $row->code,
					'ref' 	=> $row->ref,
					'billstatus' 	=> ($row->billstatus == 'T' ? 'ปกติ' : 'เก็บปลายทาง'),
					'total' => ($arraykey[$row->ref] ? count($arraykey[$row->ref]['detail']) : 0)
				);
			}
		}
		$jsonresult = json_encode($data);
		echo $jsonresult;
	}

	public function get_sumTotalAmount()
	{
		$request = $_REQUEST;
		$method = $request['method'];

		switch ($method) {
			case 'bu2':
				$usercodeid = '00002';
				break;
		}

		$sql = $this->db->select('
			sum(retail_bill.net_total) as sumtotal

		')
			->from('retail_bill')
			->where('retail_bill.status', 1)
			->where('date(retail_bill.date_upload)', date('Y-m-d'))
			->where('retail_bill.user_starts', $usercodeid);
		$q = $sql->get();
		$r = $q->row();

		$sumtotal = $r->sumtotal;
		if ($sumtotal) {
			$totalamount = $sumtotal;
		} else {
			$totalamount = 0;
		}

		$result = array(
			'totalamount'	=> $totalamount,
		);

		$jsonresult = json_encode($result);
		echo $jsonresult;
	}

	public function get_datatoday()
	{
		$request = $_REQUEST;
		$method = $request['method'];

		switch ($method) {
			case 'bu2':
				$usercodeid = '00002';
				break;
		}

		$sql = $this->db->select('
			retail_bill.id as rt_id,
			retail_bill.code,
			retail_bill.billstatus,
			
			retail_bill.pos as bill_pos_name,
	
			retail_bill.total_price,
			retail_bill.shor_money,
			retail_bill.net_total,
			retail_bill.name as custname,
			retail_bill.date_starts as bill_date_starts,

			retail_billdetail.id as rtd_id,
			retail_billdetail.total_price as rtd_total,
			retail_billdetail.quantity as rtd_qty,

			retail_productlist.name_th as rp_name,
			retail_productlist.price as rp_price,

			retail_methodorder.topic as receipt_name,
			delivery.name_th as shipping,
			fileupload.name as fileupload_name,
		')
			->from('retail_bill')
			->join('retail_billdetail', 'retail_bill.id=retail_billdetail.bill_id', 'left')
			->join('retail_methodorder', 'retail_bill.methodorder_id=retail_methodorder.id', 'left')
			->join('delivery', 'retail_bill.delivery_formid=delivery.id', 'left')
			->join('retail_productlist', 'retail_billdetail.prolist_id=retail_productlist.id', 'left')
			->join('fileupload', 'retail_bill.fileuploadref_id=fileupload.id', 'left')
			->where('retail_bill.status', 1)
			->where('date(retail_bill.date_upload)', date('Y-m-d'))
			->where('retail_bill.user_starts', $usercodeid);

		$data = array();
		$datain = array();

		$num = $this->db->count_all_results(null, false);
		$q = $sql->get();

		if ($num) {
			foreach ($q->result() as $row) {

				$group[] = $row->code;

				$subarray[] = array(
					'id' 	=> $row->rt_id,
					'code' 	=> $row->code,
					'date_starts' 	=> $row->bill_date_starts,
					'ref' 	=> $row->ref,
					'total_price' 	=> $row->total_price,
					'net_total' 	=> $row->net_total,
					'custname' 	=> $row->custname,
					'pos_name' 		=> $row->bill_pos_name,
					'receipt_name' 	=> $row->receipt_name,
					'shipping' 	=> $row->shipping,
					'billstatus' 	=> ($row->billstatus == 'T' ? 'ปกติ' : 'เก็บปลายทาง'),
					'fileupload_name' 	=> $row->fileupload_name,
					'conflict' 		=> "",

					'rp_name' => $row->rp_name,
					'rp_price' => $row->rp_price,
					'rtd_qty' => $row->rtd_qty,
					'rtd_total' => $row->rtd_total
				);
			}

			$idkey = array_unique($group);
			/* echo "<pre>";
			print_r($idkey);
			echo "===";
			print_r($subarray);
			echo "</pre>"; */

			$id = 0;
			foreach ($idkey as $key => $val) {
				if ($val) {
					$arraycode = array_keys(array_column($subarray, 'code'), $val);
					($arraycode ? $count_detail = count($arraycode) : $count_detail = 0);
					foreach ($arraycode as $keyin => $valin) {
						$datain[] = array(
							'code' 	=> $subarray[$valin]['code'],
							'ref' 	=> $subarray[$valin]['ref'],
							'total_price' 	=> $subarray[$valin]['total_price'],
							'net_total' 	=> $subarray[$valin]['net_total'],
							'custname' 	=> $subarray[$valin]['custname'],
							'receipt_name' 	=> $subarray[$valin]['receipt_name'],
							'shipping' 	=> $subarray[$valin]['shipping'],
							'billstatus' 	=> $subarray[$valin]['billstatus'],
							'conflict' 		=> "ถูก",
							'rp_name' => $subarray[$valin]['rp_name'],
							'rp_price' => $subarray[$valin]['rp_price'],
							'rtd_qty' => $subarray[$valin]['rtd_qty'],
							'rtd_total' => $subarray[$valin]['rtd_total']
						);
					}
				}

				$data[] = array(
					'id' 	=> $subarray[$key]['id'],
					'code' 	=> $subarray[$key]['code'],
					'date_starts' 	=> date('d-m-Y', strtotime($subarray[$key]['date_starts'])),
					'ref' 	=> $subarray[$key]['ref'],
					'total_price' 	=> $subarray[$key]['total_price'],
					'net_total' 	=> $subarray[$key]['net_total'],
					'custname' 	=> $subarray[$key]['custname'],
					'pos_name' 	=> $subarray[$key]['pos_name'],
					'receipt_name' 	=> $subarray[$key]['receipt_name'],
					'shipping' 	=> $subarray[$key]['shipping'],
					'billstatus' 	=> $subarray[$key]['billstatus'],
					'fileupload_name' 	=> $subarray[$key]['fileupload_name'],
					'conflict' 		=> "",
					'total' => "<button class='btn btn-sm btn-primary' data-id='" . $id . "'><span class='small'>" . $count_detail . " รายการ</small></button>"
				);

				$id++;
			}
			/* $data[] = array(
				'code' 	=> $row->code,
				'ref' 	=> $row->ref,
				'total_price' 	=> $row->total_price,
				'net_total' 	=> $row->net_total,
				'custname' 	=> $row->custname,
				'receipt_name' 	=> $row->receipt_name,
				'shipping' 	=> $row->shipping,
				'billstatus' 	=> ($row->billstatus=='T' ? 'ปกติ' : 'เก็บปลายทาง'),
				'conflict' 		=> "asdasd",
				'total' => "5"
			); */
		}

		$result = array(
			'resultdetail'	=> $datain,
			'resulttable'	=> $data
		);
		$jsonresult = json_encode($result);
		echo $jsonresult;
	}

	public function cancelBillDump()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;
			$page = $request['method'];
			$type = $request['type'];

			switch ($page) {
				case 'bu2':
					$usercodeid = '00002';
					break;
			}

			//	process
			if ($type == 'all') {
				$sql = $this->db->from('retail_bill')
					->where('date(date_upload)', date('Y-m-d'))
					->where('user_starts', $usercodeid)
					->where('status', 1);
			} else if ($type == 'file') {
				$id = $request['id'];
				$sql = $this->db->from('retail_bill')
					->where('fileuploadref_id', $id)
					->where('status', 1);
			} else {
				$id = $request['id'];
				$sql = $this->db->from('retail_bill')
					->where('id', $id)
					->where('status', 1);
			}

			$num = $sql->count_all_results(null, false);
			$q = $sql->get();

			if ($num) {
				$dataupdate = array(
					'status_complete'	=> 3,
					'status'			=> 0
				);
				if ($type == 'all') {
					$this->db->where(array('date(date_upload)' => date('Y-m-d'), 'user_starts' => $usercodeid));
					$this->db->update('retail_bill', $dataupdate);

					//	update status fileupload
					$sqlf =	$this->db->select('*')
						->from('fileupload')
						->where('status', 1)
						->where('date(date_starts)', date('Y-m-d'));
					$qf = $sqlf->get();
					$numf = $qf->num_rows();
					if ($numf) {
						foreach ($qf->result() as $rf) {
							$tbfile_id = $rf->ID;
							$name = $rf->CODE;
							if (file_exists(FCPATH . 'asset/upload/' . $name)) {
								unlink(FCPATH . 'asset/upload/' . $name);

								$this->db->where(array('id' => $tbfile_id));
								$this->db->update('fileupload', array('date_update' => date('Y-m-d H:i:s'), 'user_update' => $this->session->userdata('useradminid'), 'status' => 0));
							}
						}
					}
				} else if ($type == 'file') {
					$this->db->where(array('fileuploadref_id' => $id));
					$this->db->update('retail_bill', $dataupdate);

					//	update status fileupload
					$sqlf =	$this->db->select('*')
						->from('fileupload')
						->where('id', $id);
					$qf = $sqlf->get();
					$numf = $qf->num_rows();
					if ($numf) {
						$rf = $qf->row();
						$name = $rf->CODE;
						if (file_exists(FCPATH . 'asset/upload/' . $name)) {
							unlink(FCPATH . 'asset/upload/' . $name);

							$this->db->where(array('id' => $id));
							$this->db->update('fileupload', array('date_update' => date('Y-m-d H:i:s'), 'user_update' => $this->session->userdata('useradminid'), 'status' => 0));
						}
					}
				} else {
					$this->db->where(array('id' => $id));
					$this->db->update('retail_bill', $dataupdate);
				}

				$error_code = 0;
				$txt = 'ลบรายการ ' . $page . ' จำนวน ' . $num . ' บิลสำเร็จ';

				// ============== Log_Detail ============== //
				$log_query = $this->db->last_query();
				$last_id = $this->session->userdata('log_id');
				$detail = "Update cancel bill txt[ " . $txt . "] Code : " . $this->session->userdata('useradminid') . " Name : " . $this->session->userdata('useradminname');
				$type = "Update";
				$arraylog = array(
					'log_id'  		 => $last_id,
					'detail'  		 => $detail,
					'logquery'       => $log_query,
					'type'     	 	 => $type,
					'date_starts'    => date('Y-m-d H:i:s')
				);
				updateLog($arraylog);
			} else {
				$error_code = 1;
				$txt = 'ไม่มีการลบรายการ ' . $page;
			}
			// echo "testselect";
			$array = array(
				'error_code' 	=> $error_code,
				'data' 			=> $num,
				'txt'		 	=> $txt
			);
			$result = json_encode($array);

			echo $result;
		}
	}

	function get_fileUpload()
	{
		$error_code = 1;
		$txt = 'success';
		$data = array();

		$query = $this->mdl_excel->openFile_upload();
		if ($query) {
			foreach ($query->result() as $rowfile) {


				/* $objOpen = opendir('asset/upload');
				while (($file = readdir($objOpen)) !== false) {
					if ($rowfile->CODE == trim($file)) {
						$data[] = array(
							'id'	=> $rowfile->ID,
							'code'	=> $rowfile->CODE,
							'name'	=> $rowfile->NAME
						);
					}
				} */
				if (file_exists(FCPATH . 'asset/upload/' . $rowfile->CODE)) {
					$data[] = array(
						'id'	=> $rowfile->ID,
						'code'	=> $rowfile->CODE,
						'name'	=> $rowfile->NAME
					);
				}
			}
		}

		$array = array(
			'error_code' 	=> $error_code,
			'txt'		 	=> $txt,
			'data' 			=> $data
		);
		$result = json_encode($array);

		echo $result;
	}
}
