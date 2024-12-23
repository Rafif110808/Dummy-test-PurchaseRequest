<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Document extends BaseController
{
    protected $bc;
    public function __construct()
    {
        $this->bc = [
            ["setting",
            "Document",]
        ];
    }
    public function index()
    {
        return view('master/document/v_document', [
            'title' => 'Document',
            'akses' => null,
            'breadcrumb' => $this->bc,
            'section' => 'Setting User',
        ]);
    }

    public function form($id = null){
        $form_type = (empty($id) ? 'add' : 'edit');
        $row = [];
        if ($id != ''){
            $id = decrypting($id);
            $row = $this->Document
        }

        }
    }
    
}
