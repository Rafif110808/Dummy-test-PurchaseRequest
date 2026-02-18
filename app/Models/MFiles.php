<?php

namespace App\Models;

use CodeIgniter\Model;

class MFiles extends Model
{
    protected $db;
    protected $table = 'msfiles';
    protected $primaryKey = 'fileid';
    protected $allowedFields = [
        'filename',
        'filerealname',
        'filedirectory',
        'created_date',
        'created_by',
        'update_date',
        'update_by',
        'isactive'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
    }

    public function searchable()
    {
        return [
            null,
            'filename',
            'filedirectory',
            'created_date',
            null
        ];
    }

    public function datatable()
    {
        return $this->db->table('msfiles as f')
            ->select('f.*, u.fullname as created_by_name')
            ->join('msuser as u', 'f.created_by = u.id', 'left')
            ->where('f.isactive', true);
    }

    public function getAll()
    {
        return $this->db->table($this->table)
            ->where('isactive', true)
            ->orderBy('fileid', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getById($id)
    {
        return $this->db->table($this->table)
            ->where('fileid', $id)
            ->where('isactive', true)
            ->get()
            ->getRowArray();
    }

    public function getByFilename($filename, $excludeId = null)
    {
        $builder = $this->db->table($this->table)
            ->where('filename', $filename)
            ->where('isactive', true);

        if ($excludeId) {
            $builder->where('fileid !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function generateUniqueFilename($originalFilename)
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);

        $filename = $nameWithoutExt . '.' . $extension;
        $counter = 1;

        while ($this->getByFilename($filename)) {
            $filename = $nameWithoutExt . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $filename;
    }

    public function store($data)
    {
        $insert = $this->db->table($this->table)->insert($data);
        return $insert ? $this->db->insertID() : false;
    }

    public function edit($data, $id)
    {
        return $this->db->table($this->table)
            ->where('fileid', $id)
            ->update($data);
    }

    public function destroy($id)
    {
        return $this->db->table($this->table)
            ->where('fileid', $id)
            ->update([
                'isactive' => false,
                'update_by' => getSession('userid'),
                'update_date' => date('Y-m-d H:i:s')
            ]);
    }

    public function hardDelete($id)
    {
        return $this->db->table($this->table)
            ->where('fileid', $id)
            ->delete();
    }
}
