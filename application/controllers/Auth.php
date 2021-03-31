<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email'); //trim = menghilangkan spasi jika ada kesalahan penulisan, required = wajib diisi, valid_email = format email yang benar
        $this->form_validation->set_rules('password', 'Password', 'trim|required');
        if ($this->form_validation->run() == false) {  //jika form_validation tidak berjalan maka       
            $data['title'] = 'Login Page';
            $this->load->view('templates/auth_header', $data); //template header : struktur folder -> file
            $this->load->view('auth/login'); //isi utama
            $this->load->view('templates/auth_footer'); //template footer
        } else { //jika form_validasi berhasil maka lanjut
            $this->_login(); // mengakses method _login (hanya bisa diakses di kontroler ini, tidak bisa diakses kontroler lain)
        }
    }

    private function _login()
    {
        $email = $this->input->post('email'); //mengambil inputan email dan disimpan ke parameter $email
        $password = $this->input->post('password'); //mengambil inputan password dan disimpan ke parameter $password

        $user = $this->db->get_where('user', ['email' => $email])->row_array(); //mengecek di db tabel user kolom email , row_array = untuk menhambil satu baris saja

        if ($user) { //jika usernya ada
            if ($user['is_active'] == 1) { //jika usernya aktif ,  setelah cek di parameter $user pada tabel user kolom is_active 
                if (password_verify($password, $user['password'])) { //cek password , jika di db ada usernya tapi password salah
                    $data = [ //persiapan menyimpan data ke session agar bisa digunakan di halaman lain
                        'email' => $user['email'], //mengambil data dari $user 
                        'role_id' => $user['role_id'] //mengambil data daro $user
                    ];
                    $this->session->set_userdata($data); //menyimpan data ke $session
                    redirect('user'); //diarahkan ke controler user
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Password Salah !
               </div>'); //membuat pesan jika ada data di db tetapi passwordnya salah, yang akan ditampilkan di halaman login
                    redirect('auth'); //meredirect ke halam login struktur : 
                }
            } else {
                // jika usernya ada tapi belum aktif 
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
                Email Belum Teraktivasi !
               </div>'); //membuat pesan jika ada data di db tetapi email belum teraktivasi, yang akan ditampilkan di halaman login
                redirect('auth'); //meredirect ke halam login struktur : 
            }
        } else {
            //jika usernya tidak ada
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">
            Email Tidak Terdaftar ! T_T
           </div>'); //membuat pesan jika tidak ada user di db yang akan ditampilkan di halaman login
            redirect('auth'); //meredirect ke halam login struktur : 
        }
    }

    public function registration()
    {
        $this->form_validation->set_rules('name', 'Name', 'required|trim'); //trim = menghilangkan spasi jika ada kesalahan penulisan, required = wajib diisi
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|is_unique[user.email]', [
            'valid_email' => 'Email Salah ! Gunakan email dengan format lain !!', //mengisi pesan inputan email jika tidak sesuai rule format email
            'is_unique' => 'Email Sudah Terdaftar' //mengisi pesan sesua yang kita inginkan jika inputan tidak sesuai rule yang telah ditentukan
        ]); //is_unique[user.email] = mengecek tabel user kolom email apakah sudah ada email yang terdaftar
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[8]|matches[password2]'); //mengecek agar password = retype password
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]'); //mengecek agar retype password = password
        if ($this->form_validation->run() == false) { //jika form_validasi tidak berjalan 
            $data['title'] = 'User Registration';
            $this->load->view('templates/auth_header', $data);
            $this->load->view('auth/registration');
            $this->load->view('templates/auth_footer');
        } else { //jika form_validation berjalan maka
            $data = [ //menyiapkan data untuk di insert ke database !
                'name' => htmlspecialchars($this->input->post('name', true)),
                'email' => htmlspecialchars($this->input->post('email', true)),
                'image' => 'default.jpg',
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'role_id' => 2,
                'is_active' => 1,
                'date_created' => time()
            ]; //menyiapkan data untuk di insert ke database !

            $this->db->insert('user', $data); //menyimpan data ke dalam database di tabel user (lebih bagus gunakan model)
            $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
            Congratulation ! Your Account Has Been Created . . . (☞ﾟヮﾟ)☞
            </div>'); //membuat pesan setelah data berhasil disimpan ke database yang akan ditampilkan di halaman login
            redirect('auth'); //meredirect ke halam login setelah berhasil menyimpan data ke database
        }
    }

    public function logout() //method untuk logout
    {
        $this->session->unset_userdata('email'); //menghilangkan data yang tersimpan di session
        $this->session->unset_userdata('role_id'); //menghilangkan data yang tersimpan di session
        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">
        Anda Telah Logout !
        </div>'); //membuat pesan setelah data berhasil dihilangkan dari session yang akan ditampilkan di halaman login
        redirect('auth'); //meredirect ke halam login setelah berhasil menghilangkan data session

    }
}
