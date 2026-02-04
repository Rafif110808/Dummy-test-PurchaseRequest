<?php
namespace App\Models;

use CodeIgniter\Model;

class MPurchaseRequestHd extends Model
{
    protected $db;
    protected $table = 'trpurchaserequesthd';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'transcode',
        'transdate',
        'supplierid',
        'description',
        'createdby',
        'createddate',
        'updatedby',
        'updateddate',
        'isactive'
    ];
    protected $builder;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();

        $this->builder = $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.isactive', true);
    }

    public function searchable()
    {
        return [
            null,
            'pr.transcode',
            'pr.transdate',
            'ms.suppliername',
            'pr.description',
            null
        ];
    }

    public function datatable()
    {
        return $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*, ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.isactive', true);
    }

    /**
     * 
     * @param mixed $filter - ID, supplierid, atau null (untuk getAll)
     * @param string $filterType - 'id', 'supplier', 'daterange', atau 'all'
     * @param mixed $endDate - Untuk daterange (optional)
     * @param bool $withDetails - Include details atau tidak
     * 
     * Contoh penggunaan:
     * - get(5, 'id') → Get by ID (sama dengan getOne(5))
     * - get(10, 'supplier') → Get by supplier (sama dengan getBySupplier(10))
     * - get('2024-01-01', 'daterange', '2024-12-31') → Get by date range
     * - get(null, 'all') → Get all (sama dengan getAll())
     * - get(5, 'id', null, true) → Get by ID with details (sama dengan getWithDetails(5))
     */
    public function get($filter = null, $filterType = 'all', $endDate = null, $withDetails = false)
    {
        $builder = $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.isactive', true);

        // Apply filter berdasarkan type
        switch ($filterType) {
            case 'id':
                $builder->where('pr.id', $filter);
                $result = $builder->get()->getRowArray();
                
                // Jika withDetails = true, ambil juga detail
                if ($withDetails && !empty($result)) {
                    $result['details'] = $this->db->table('trpurchaserequestdt as prd')
                        ->select('prd.*')
                        ->select('mp.productname')
                        ->select('mu.uomnm')
                        ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
                        ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
                        ->where('prd.headerid', $filter)
                        ->where('prd.isactive', true)
                        ->get()
                        ->getResultArray();
                }
                return $result;

            case 'supplier':
                $builder->where('pr.supplierid', $filter);
                $builder->orderBy('pr.transdate', 'DESC');
                return $builder->get()->getResultArray();

            case 'daterange':
                if (empty($filter) || empty($endDate)) {
                    return [];
                }
                $builder->where('pr.transdate >=', $filter);
                $builder->where('pr.transdate <=', $endDate);
                $builder->orderBy('pr.transdate', 'DESC');
                return $builder->get()->getResultArray();

            case 'all':
            default:
                $builder->orderBy('pr.transdate', 'DESC');
                return $builder->get()->getResultArray();
        }
    }

    /**
     * Wrapper methods untuk backward compatibility
     */
    public function getOne($id)
    {
        return $this->get($id, 'id');
    }

    public function getAll()
    {
        return $this->get(null, 'all');
    }

    public function getBySupplier($supplierId)
    {
        return $this->get($supplierId, 'supplier');
    }

    public function getByDateRange($startDate, $endDate)
    {
        return $this->get($startDate, 'daterange', $endDate);
    }

    public function getWithDetails($id)
    {
        return $this->get($id, 'id', null, true);
    }

    public function store($data)
    {
        $insert = $this->db->table($this->table)->insert($data);
        return $insert ? $this->db->insertID() : false;
    }

    public function edit($data, $id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    public function softDestroy($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update([
                'isactive' => false,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ]);
    }

    public function transcodeExists($transcode, $excludeId = null)
    {
        $builder = $this->db->table($this->table)
            ->where('transcode', $transcode)
            ->where('isactive', true);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function getLatestTranscode($prefix = 'PR')
    {
        $result = $this->db->table($this->table)
            ->select('transcode')
            ->like('transcode', $prefix, 'after')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $result['transcode'] ?? null;
    }

    public function generateTranscode($prefix = 'PR')
    {
        $latest = $this->getLatestTranscode($prefix);

        if ($latest) {
            $parts = explode('-', $latest);
            $number = intval(end($parts)) + 1;
        } else {
            $number = 1;
        }

        $year = date('Y');
        $transcode = sprintf('%s-%s-%04d', $prefix, $year, $number);

        return $transcode;
    }

    public function getTotalRecords()
    {
        return $this->db->table($this->table)
            ->where('isactive', true)
            ->countAllResults();
    }

    public function destroy($column, $value)
    {
        return $this->db->table('trpurchaserequesthd')->delete([$column => $value]);
    }

    public function getChunk($limit, $offset)
{
    return $this->db->table('trpurchaserequesthd as pr')
        ->select('pr.transcode, pr.transdate, ms.suppliername, pr.description')
        ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
        ->where('pr.isactive', true)
        ->orderBy('pr.id', 'ASC')
        ->limit($limit, $offset)
        ->get()
        ->getResultArray();
}

}