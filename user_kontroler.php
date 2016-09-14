<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class user_kontroler extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('user_model','mo');
		$this->load->database();
	}

	public function index()
	{
		$this->load->helper('url');

		$data['title']	=	'E - Pasca | Dashboard';

		$this->load->view('user/header',$data);
		$this->load->view('user/index');
		$this->load->view('user/footer');
	}

	public function ajax_list()
	{
		$this->load->helper('url');

		$list = $this->mo->get_datatables();
		$data = array();
		$no = $_POST['start'];
		foreach ($list as $li) {
			$no++;
			$row = array();
			$row[] = $li->no_isbn;
			$row[] = $li->judul_buku;
			$row[] = $li->nama_kategori;
			$row[] = substr($li->isi_buku,0,20).'...';
			$row[] = $li->penulis_buku;
			$row[] = $li->penerbit_buku;
			$row[] = $li->tanggal_terbit;
			$row[] = $li->halaman_buku;
			$row[] = $li->rak;
			if($li->photo)
				$row[] = $li->photo;
			else
				$row[] = '(No photo)';

			//add html for action
			$row[] = '<center><a class="btn btn-sm btn-info" href="javascript:void(0)" title="Edit" onclick="detil('."'".$li->id_buku."'".')"><i class="glyphicon glyphicon-search"></i></a></center>';
		
			$data[] = $row;
		}

		$output = array(
						"draw" => $_POST['draw'],
						"recordsTotal" => $this->mo->count_all(),
						"recordsFiltered" => $this->mo->count_filtered(),
						"data" => $data,
				);
		//output to json format
		echo json_encode($output);
	}

	public function ajax_edit($id)
	{
		$data = $this->mo->get_by_id($id);
		$data->tanggal_terbit = ($data->tanggal_terbit == '0000-00-00') ? '' : $data->tanggal_terbit; // if 0000-00-00 set tu empty for datepicker compatibility
		echo json_encode($data);
	}

	public function ajax_add()
	{
		$this->_validate();
		
		$data = array(
				'no_isbn' => $this->input->post('no_isbn'),
				'judul_buku' => $this->input->post('judul_buku'),
				'isi_buku' => $this->input->post('isi_buku'),
				'penulis_buku' => $this->input->post('penulis_buku'),
				'penerbit_buku' => $this->input->post('penerbit_buku'),
				'tanggal_terbit' => $this->input->post('tanggal_terbit'),
				'halaman_buku'	=>	$this->input->post('halaman_buku'),
				'rak'	=>	$this->input->post('rak'),
			);

		if(!empty($_FILES['photo']['name']))
		{
			$upload = $this->_do_upload();
			$data['photo'] = $upload;
		}

		$insert = $this->mo->save($data);

		echo json_encode(array("status" => TRUE));
	}

	public function ajax_update()
	{
		$this->_validate();
		$data = array(
				'no_isbn' => $this->input->post('no_isbn'),
				'judul_buku' => $this->input->post('judul_buku'),
				'isi_buku' => $this->input->post('isi_buku'),
				'penulis_buku' => $this->input->post('penulis_buku'),
				'penerbit_buku' => $this->input->post('penerbit_buku'),
				'tanggal_terbit' => $this->input->post('tanggal_terbit'),
				'halaman_buku'	=>	$this->input->post('halaman_buku'),
				'rak'	=>	$this->input->post('rak'),
			);

		if($this->input->post('remove_photo')) // if remove photo checked
		{
			if(file_exists('./upload/'.$this->input->post('remove_photo')) && $this->input->post('remove_photo'))
				unlink('./upload/'.$this->input->post('remove_photo'));
			$data['photo'] = '';
		}

		if(!empty($_FILES['photo']['name']))
		{
			$upload = $this->_do_upload();
			
			//delete file
			$gaje = $this->mo->get_by_id($this->input->post('id_buku'));
			if(file_exists('./upload/'.$gaje->photo) && $gaje->photo)
				unlink('./upload/'.$gaje->photo);

			$data['photo'] = $upload;
		}

		$this->mo->update(array('id_buku' => $this->input->post('id_buku')), $data);
		echo json_encode(array("status" => TRUE));
	}

	public function ajax_delete($id)
	{
		//delete file
		$del = $this->mo->get_by_id($id);
		if(file_exists('./upload/'.$del->photo) && $del->photo)
			unlink('./upload/'.$del->photo);
		
		$this->mo->delete_by_id($id);
		echo json_encode(array("status" => TRUE));
	}

	private function _do_upload()
	{
		$config['upload_path']          = 'upload/';
        $config['allowed_types']        = 'gif|jpg|png';
        $config['max_size']             = 1000; //set max size allowed in Kilobyte
        $config['max_width']            = 1000; // set max width image allowed
        $config['max_height']           = 1000; // set max height allowed
        $config['file_name']            = round(microtime(true) * 1000); //just milisecond timestamp fot unique name

        $this->load->library('upload', $config);

        if(!$this->upload->do_upload('photo')) //upload and validate
        {
            $data['inputerror'][] = 'photo';
			$data['error_string'][] = 'Upload error: '.$this->upload->display_errors('',''); //show ajax error
			$data['status'] = FALSE;
			echo json_encode($data);
			exit();
		}
		return $this->upload->data('file_name');
	}

}
