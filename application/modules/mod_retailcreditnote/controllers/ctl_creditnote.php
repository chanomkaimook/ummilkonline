<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Ctl_creditnote extends CI_Controller
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
		$this->load->model('mdl_creditnote');
		$this->load->model('mdl_uplode');
		$this->load->library('session');
		$this->load->library('Permiss');
		$this->load->library('creditnote');
		$this->load->helper(array('form', 'url', 'myfunction_helper', 'sql_helper', 'permiss_helper'));

		$this->set	= array(
			'max_upload_image'		=> 1000000,		// 1 k = 1000
			'max_size_image'		=> 1920,
			'ctl_name'				=> 'ctl_createorder',
			'username_session'		=> $this->session->userdata('useradminname'),
			'userid_session'		=> $this->session->userdata('useradminid')
		);
		if ($this->session->userdata('useradminid') == '') {
			redirect('mod_admin/ctl_login');
		}
	}

	public function creditnote()
	{

		$data = array(
			'mainmenu' 		=> 'retail',
			'submenu' 		=> 'retailcreditnote'
		);

		$data['base_bn'] = base_url() . BASE_BN;
		$data['basepic'] = base_url() . BASE_PIC;
		$this->load->view('creditnote', $data);
	}

	public function editbill()
	{

		$data = array(
			'mainmenu' 		=> 'retail',
			'submenu' 		=> 'retailcreditnote'
		);

		$data['method'] = $this->uri->segment(3);

		$data['base_bn'] = base_url() . BASE_BN;
		$data['basepic'] = base_url() . BASE_PIC;

		$sql = $this->db->select('complete,approve,approve_store')
			->from('retail_creditnote')
			->where('retail_creditnote.id', $this->input->get('id'));
		$q = $sql->get();
		$num = $q->num_rows();
		if ($num) {
			$r = $q->row();

			if ($r->complete == 0) {
				$this->load->view('editbill', $data);
			} else {
				redirect('mod_admin/ctl_login/pathadmin');
			}
		}
	}

	public function viewbill()
	{

		$data = array(
			'mainmenu' 		=> 'retail',
			'submenu' 		=> 'retailcreditnote'
		);

		$data['method'] = $this->uri->segment(3);

		$data['base_bn'] = base_url() . BASE_BN;
		$data['basepic'] = base_url() . BASE_PIC;
		$this->load->view('viewbill', $data);
	}

	public function getDataCreditNote()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$total = $this->mdl_creditnote->alldata();
			$sql = $this->mdl_creditnote->makedata();

			$data = array();
			$subdata = array();



			if ($sql->result()) {
				$index = $request['start'] + 1;
				foreach ($sql->result() as $row) {

					// $textdisplay = "<font class='text-bold'>".$row->cn_code." <i class='fas fa-search text-muted'></i></font>";
					$textdisplay = "<a href='" . site_url('mod_retailcreditnote/ctl_creditnote/viewbill?id=' . $row->cn_id) . "' target=_blank class='text-bold text-secondary text-md' >" . $row->cn_code . " <i class='fas fa-search text-muted'></i></a>";

					//	สถานะบิล
					$bill_status = $this->creditnote->get_creditnoteComplete($row->cn_complete);
					switch ($row->cn_complete) {
						case 0:
							$statustext = $bill_status['data'];
							break;
						case 1:
							$statustext = "<font class='text-primary'>" . $bill_status['data'] . "</font>";
							break;
						case 2:
							$statustext = "<font class='text-success'>" . $bill_status['data'] . "</font>";
							break;
						case 3:
							$statustext = "<font class='text-danger'>" . $bill_status['data'] . "</font>";
							break;
					}

					//	product loss
					$prod_loss = $this->creditnote->get_creditnoteLoss($row->cn_loss);

					$textdisplay .= "<br>บัญชี " . ($row->cn_approve ? "<font class='badge badge-success'>อนุมัติ</font>" : null);
					$textdisplay .= " คลัง " . ($row->cn_approve_store ? "<font class='badge badge-success'>อนุมัติ</font>" : null);

					$textdisplay .= "<span class='text-xs'>";
					$textdisplay .= "<br>เมื่อ " . thai_date_indent(date('Y-m-d', strtotime($row->cn_date_starts))) . " " . date('H:i:s', strtotime($row->cn_date_starts));

					($row->cn_name_th ? $user = $row->cn_name_th . " " . $row->cn_lastname_th : $user = $row->cn_name . " " . $row->cn_lastname);
					$textdisplay .= "<br>โดย " . $user;
					$textdisplay .= "</span>";

					//	code ref
					if ($row->cn_rt_bill_code) {
						$bill_ref = "<a href='" . site_url('mod_retailcreateorder/ctl_createorder/viwecreatebill?id=' . $row->cn_rt_id . '&mdl=mdl_createorder') . "' target=_blank class='' >อิงจาก " . $row->cn_rt_bill_code . "</a>";
						$textdisplay .= "<br>" . $bill_ref;
					} else {
						$bill_ref = "";
					}

					$rowarray = array();
					$rowarray['DT_RowId'] = $row->cn_id;	//	set row id
					$rowarray['id'] = $index;
					$rowarray['code'] = $textdisplay;
					$rowarray['net'] = number_format(get_ValueNullToZero($row->cn_net), 2);
					$rowarray['loss'] = "<font class='badge badge-warning text-sm'>" . $prod_loss['data'] . "</font>";
					$rowarray['complete'] = $statustext;

					$subdata[] = $rowarray;
					$index++;
				}
			}

			$data['draw'] = intval($request['draw']);
			$data['recordsTotal'] = $total;
			$data['recordsFiltered'] = $total;
			$data['data'] = $subdata;

			$result = json_encode($data);
			echo $result;
		}
	}

	//	add
	public function add_Creditnote()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$q = $this->creditnote->add_bill($request, $_FILES);

			$result = json_encode($q);
			echo $result;
		}
	}

	//	update
	public function update_Creditnote()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$q = $this->creditnote->update_bill($request, $_FILES);

			$result = json_encode($q);
			echo $result;
		}
	}

	//	update bill approve from store
	public function confirmStore()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$q = $this->creditnote->confirmStore($request);

			$result = json_encode($q);
			echo $result;
		}
	}

	//	update bill approve from finance
	public function confirmFinance()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$q = $this->creditnote->confirmFinance($request);

			$result = json_encode($q);
			echo $result;
		}
	}

	//	cancel bill
	public function cancelBill()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;

			$q = $this->creditnote->cancelBill($request);

			$result = json_encode($q);
			echo $result;
		}
	}

	//	download
	public function doc_creditnote()
	{
		$this->load->helper(array('array', 'report'));

		$data = array(
			'get_id'	=> $this->input->get('id'),
			'query'		=> $this->creditnote->get_creditnoteBillDetail($this->input->get('id'))
		);
		$this->load->view('doc_creditnote', $data);
	}

	//	get data bill to add
	public function get_orderBill()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;
			$text = trim($request['bill_id']);

			$result = "";
			$data = array();
			$datadetail = array();

			if ($text) {
				$q = $this->creditnote->read_bill($text);

				if ($q->result()) {
					foreach ($q->result() as $r) {

						//	ชำระ
						if ($r->rt_billstatus == 'T') {
							$billstatus = 'โอนเงิน';
						} else if ($r->rt_billstatus == 'C') {
							$billstatus = 'เก็บปลายทาง';
						} else {
							$billstatus = 'ฟรี';
						}

						//	สถานะบิล
						if ($r->rt_complete == '2' || $r->rt_complete == '5') {
							$complete = 'อนุมัติ';
						} else {
							$complete = 'รออนุมัติ';
						}

						$data	= array(
							'id'	=> trim($r->rt_id),

							'name'	=> trim($r->rt_name),
							'tel'	=> trim($r->rt_tel),
							'citizen'	=> trim($r->rt_citizen),
							'address'		=> trim($r->rt_address),
							'zipcode'		=> trim($r->rt_zipcode),

							'code'	=> trim($r->rt_code),
							'receive'	=> trim($r->rtm_name),
							'delivery'	=> trim($r->rtd_name),
							'textcode'		=> (trim($r->rt_textcode) ? trim($r->rt_textcode) : trim($r->rt_ref)),
							'billstatus'	=> trim($billstatus),
							'complete'		=> trim($complete),

							'price'		=> trim($r->rt_total_price),
							'parcel'		=> trim($r->rt_parcel_cost),
							'logis'		=> trim($r->rt_delivery_fee),
							'shor'		=> trim($r->rt_shor_money),
							'discount'		=> trim($r->rt_discount_price),
							'tax'		=> trim($r->rt_tax),
							'net'		=> trim($r->rt_net_total),

							'bank'		=> trim($r->b_name),
							'bank_daytime'		=> (trim($r->rt_bank_daytime) ? thai_date(date('Y-m-d', strtotime(trim($r->rt_bank_daytime)))) . " - " . date('H:i', strtotime(trim($r->rt_bank_daytime))) : ""),
							'bank_amount'		=> trim($r->rt_bank_amount),
							'bank_remark'		=> trim($r->rt_bank_remark),

							'datecreate'		=> (trim($r->rt_datestarts) ? thai_date(date('Y-m-d', strtotime(trim($r->rt_datestarts)))) : ""),

							'staffcreate'		=> trim($r->sf_name) . " " . trim($r->sf_lastname),
							'remark'	=> trim($r->rt_remark)
						);

						$datadetail[]	= array(
							'product_name'	=> $r->rtp_name,
							'product_qty'	=> $r->rtd_qty,
							'product_price'	=> $r->rtp_price,
							'product_totalprice'	=> $r->rtd_price,

							'promain'	=> trim($r->rtd_productmain),
							'prolist'	=> trim($r->rtd_productid),
							'list'		=> trim($r->rtd_productlist)
						);
					}

					$dataresult = array('data' => $data, 'datadetail' => $datadetail);
				}


				$result = json_encode($dataresult);
			}

			echo $result;
		}
	}

	//	get data bill to add
	public function get_creditnoteBill()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;
			$text = trim($request['bill_id']);

			$result = "";
			$data = array();
			$datadetail = array();

			if ($text) {
				$q = $this->creditnote->read_creditnoteBill($text);

				if ($q->result()) {
					foreach ($q->result() as $r) {

						//	สถานะบิล
						$complete = $this->creditnote->get_creditnoteComplete($r->cn_complete);
						switch ($r->cn_complete) {
							case 0:
								$statustext = $complete['data'];
								break;
							case 1:
								$statustext = "<font class='text-primary'>" . $complete['data'] . "</font>";
								break;
							case 2:
								$statustext = "<font class='text-success'>" . $complete['data'] . "</font>";
								break;
							case 3:
								$statustext = "<font class='text-danger'>" . $complete['data'] . "</font>";
								break;
						}
						$appr_username = "";
						$apst_username = "";

						if (trim($r->cn_appr_user)) {
							$appr_username = $this->mdl_creditnote->findUsernameByCode(trim($r->cn_appr_user));
						}

						if (trim($r->cn_apst_user)) {
							$apst_username = $this->mdl_creditnote->findUsernameByCode(trim($r->cn_apst_user));
						}

						$userupdate = "";
						if(trim($r->cn_user_update)){

							$sqluser = $this->db->select('name,name_th,lastname,lastname_th')
							->from('staff')
							->where('code',trim($r->cn_user_update));
							$quser = $sqluser->get();
							$numuser = $quser->num_rows();
							if($numuser){
								$rowuser = $quser->row();
								($rowuser->name_th ? $userupdate = $rowuser->name_th." ".$rowuser->lastname_th : $userupdate = $rowuser->name." ".$rowuser->lastname);
							}else{
								$userupdate = "ไม่มีชื่อ";
							}
						}

						(trim($r->cn_date_update) ? $dateupdate = "(".thai_date(date('Y-m-d', strtotime(trim($r->cn_date_update))))." ".date('H:i:s', strtotime(trim($r->cn_date_update)))." น.)" : $dateupdate = "");

						$userupdate .= "<br>".$dateupdate;

						$data	= array(
							'id'	=> trim($r->cn_id),

							'retail_code'	=> trim($r->rt_code),
							'name'	=> trim($r->rt_name),
							'tel'	=> trim($r->rt_tel),
							'citizen'	=> trim($r->rt_citizen),
							'address'		=> trim($r->rt_address),
							'zipcode'		=> trim($r->rt_zipcode),

							'code'	=> trim($r->cn_code),

							'codereport'		=> trim($r->cn_codereport),
							'complete'		=> $statustext,
							'codecomplete'		=> trim($r->cn_complete),
							'loss'		=> trim($r->cn_loss),

							'price'		=> trim($r->cn_total_price),
							'parcel'		=> trim($r->cn_parcel_cost),
							'logis'		=> trim($r->cn_delivery_fee),
							'shor'		=> trim($r->cn_shor_money),
							'discount'		=> trim($r->cn_discount_price),
							'tax'		=> trim($r->cn_tax),
							'net'		=> trim($r->cn_net_total),

							'approve'		=> trim($r->cn_approve),
							'approve_store'	=> trim($r->cn_approve_store),
							'appr_date'	=> trim($r->cn_appr_date),
							'appr_user'	=> trim($r->cn_appr_user),
							'apst_date'	=> trim($r->cn_apst_date),
							'apst_user'	=> trim($r->cn_apst_user),
							'appr_username'	=> $appr_username,
							'apst_username'	=> $apst_username,

							'datecreate'		=> (trim($r->cn_date_starts) ? thai_date(date('Y-m-d', strtotime(trim($r->cn_date_starts)))) : ""),
							'staffcreate'		=> ($r->sf_nameth ? $user = $r->sf_nameth . " " . $r->sf_lastnameth : $user = $r->sf_name . " " . $r->sf_lastname),
							
							'dateupdate'		=> $dateupdate,
							'staffupdate'		=> $userupdate,
							
							'remark'	=> trim($r->cn_remark),
							'remark_order'	=> trim($r->cn_remark_order)
						);

						if ($r->cnd_id) {
							$datadetail[]	= array(
								'product_rowid'	=> $r->cnd_id,
								'product_name'	=> $r->rtp_name,
								'product_qty'	=> $r->cnd_qty,
								'product_price'	=> $r->rtp_price,
								'product_totalprice'	=> $r->cnd_price,

								'promain'	=> trim($r->cnd_productmain),
								'prolist'	=> trim($r->cnd_productid),
								'list'		=> trim($r->cnd_productlist)
							);
						}
					}

					$dataresult = array('data' => $data, 'datadetail' => $datadetail);
				}

				$result = json_encode($dataresult);
			}

			echo $result;
		}
	}

	//	get data bill to add
	public function get_orderSearchBill()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;
			$text = trim($request['searchorder']);

			$result = "";

			if ($text) {
				$r = $this->creditnote->read_billSearch($text);

				$dataresult = array(
					'data'	=> $r
				);

				$result = json_encode($dataresult);
			}

			echo $result;
		}
	}

	//	get data bill image
	public function get_creditnoteImg()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$request = $_REQUEST;
			$text = trim($request['id']);

			$result = "";

			if ($text) {
				$r = $this->creditnote->get_image($text);

				if ($r) {
					foreach ($r->result() as $row) {
						$subdata['id'] = $row->cni_id;
						$subdata['path'] = site_url() . $row->cni_path;

						$data[] = $subdata;
					}
				}

				$dataresult = array(
					'data'	=> $data
				);

				$result = json_encode($dataresult);
			}

			echo $result;
		}
	}

	//	get data bill to add
	public function countcreditnote()
	{
		if ($this->input->server('REQUEST_METHOD')) {
			$count = countCreditnote();
			$dataresult = array(
				'error_code' => 0,
				'txt' => 'success',
				'data' => array('count' => $count)
			);
			$result = json_encode($dataresult);

			echo $result;
		}
	}

	function get_countMenu(){
		$dataresult = get_countMenu();

		$data = array(
			'data'			=> $dataresult
		);
		$result = json_encode($data);

		echo $result;
	}
}
