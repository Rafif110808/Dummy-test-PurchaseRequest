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
        
        // Initialize builder for datatable
        $this->builder = $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.isactive', true);
    }
    
    /**
     * Searchable columns for datatable
     * @return array
     */
    public function searchable()
    {
        return [
            null,                   // No
            'pr.transcode',         // PR Number
            'pr.transdate',         // Request Date
            'ms.suppliername',      // Supplier
            'pr.description',       // Description
            null                    // Actions
        ];
    }
    
    /**
     * Get builder for datatable
     * @return object
     */
    public function datatable()
    {
        return $this->builder;
    }
    
    /**
     * Get one Purchase Request by ID
     * @param int $id
     * @return array
     */
    public function getOne($id)
    {
        return $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.id', $id)
            ->where('pr.isactive', true)
            ->get()
            ->getRowArray();
    }
    
    /**
     * Get all active Purchase Requests
     * @return array
     */
    public function getAll()
    {
        return $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.isactive', true)
            ->orderBy('pr.transdate', 'DESC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get Purchase Requests by supplier
     * @param int $supplierId
     * @return array
     */
    public function getBySupplier($supplierId)
    {
        return $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.supplierid', $supplierId)
            ->where('pr.isactive', true)
            ->orderBy('pr.transdate', 'DESC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Get Purchase Requests by date range
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getByDateRange($startDate, $endDate)
    {
        return $this->db->table('trpurchaserequesthd as pr')
            ->select('pr.*')
            ->select('ms.suppliername')
            ->join('mssupplier as ms', 'pr.supplierid = ms.id', 'left')
            ->where('pr.transdate >=', $startDate)
            ->where('pr.transdate <=', $endDate)
            ->where('pr.isactive', true)
            ->orderBy('pr.transdate', 'DESC')
            ->get()
            ->getResultArray();
    }
    
    /**
     * Store new Purchase Request
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function store($data)
    {
        $insert = $this->db->table($this->table)->insert($data);
        return $insert ? $this->db->insertID() : false;
    }
    
    /**
     * Update Purchase Request
     * @param array $data
     * @param int $id
     * @return bool
     */
    public function edit($data, $id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }
    
    /**
     * Soft delete Purchase Request
     * @param int $id
     * @return bool
     */
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
    
    /**
     * Hard delete Purchase Request
     * @param int $id
     * @return bool
     */
    public function destroy($id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->delete();
    }
    
    /**
     * Check if transcode exists
     * @param string $transcode
     * @param int|null $excludeId
     * @return bool
     */
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
    
    /**
     * Get latest transcode
     * @param string $prefix
     * @return string|null
     */
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
    
    /**
     * Generate next transcode
     * @param string $prefix
     * @return string
     */
    public function generateTranscode($prefix = 'PR')
    {
        $latest = $this->getLatestTranscode($prefix);
        
        if ($latest) {
            // Extract number from latest transcode (e.g., PR-2024-0001 -> 0001)
            $parts = explode('-', $latest);
            $number = intval(end($parts)) + 1;
        } else {
            $number = 1;
        }
        
        // Format: PR-YYYY-NNNN
        $year = date('Y');
        $transcode = sprintf('%s-%s-%04d', $prefix, $year, $number);
        
        return $transcode;
    }
    
    /**
     * Get total records count
     * @return int
     */
    public function getTotalRecords()
    {
        return $this->db->table($this->table)
            ->where('isactive', true)
            ->countAllResults();
    }
    
    /**
     * Get Purchase Request with details
     * @param int $id
     * @return array
     */
    public function getWithDetails($id)
    {
        $header = $this->getOne($id);
        
        if (empty($header)) {
            return [];
        }
        
        // Get details
        $details = $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.headerid', $id)
            ->where('prd.isactive', true)
            ->get()
            ->getResultArray();
        
        $header['details'] = $details;
        
        return $header;
    }
}