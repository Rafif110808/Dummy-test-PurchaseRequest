<?php
namespace App\Models;

use CodeIgniter\Model;

class MPurchaseRequestDt extends Model
{
    protected $db;
    protected $table = 'trpurchaserequestdt';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'headerid',
        'productid',
        'uomid',
        'qty',
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
        $this->builder = $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.isactive', true);
    }

    /**
     * Searchable columns for datatable
     * @return array
     */
    public function searchable()
    {
        return [
            null,               // No
            'mp.productname',   // Product
            'mu.uomnm',         // UOM
            'prd.qty',          // Qty
            null                // Actions
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
     * Get details by header ID
     * @param int $headerId
     * @return array
     */
    public function getByHeader($headerId)
    {
        return $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.headerid', $headerId)
            ->where('prd.isactive', true)
            ->orderBy('prd.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Get one detail by ID
     * @param int $id
     * @return array
     */
    public function getOne($id)
    {
        return $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.id', $id)
            ->where('prd.isactive', true)
            ->get()
            ->getRowArray();
    }

    /**
     * Get details by product
     * @param int $productId
     * @return array
     */
    public function getByProduct($productId)
    {
        return $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->select('prh.transcode, prh.transdate')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->join('trpurchaserequesthd as prh', 'prd.headerid = prh.id', 'left')
            ->where('prd.productid', $productId)
            ->where('prd.isactive', true)
            ->orderBy('prh.transdate', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Store new detail
     * @param array $data
     * @return int|bool Insert ID or false
     */
    public function store($data)
    {
        $insert = $this->db->table($this->table)->insert($data);
        return $insert ? $this->db->insertID() : false;
    }

    /**
     * Store batch details
     * @param array $data
     * @return bool
     */
    public function storeBatch($data)
    {
        return $this->db->table($this->table)->insertBatch($data);
    }

    /**
     * Update detail
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
     * Soft delete detail by header
     * @param int $headerId
     * @return bool
     */
    public function softDestroyByHeader($headerId)
    {
        return $this->db->table($this->table)
            ->where('headerid', $headerId)
            ->update([
                'isactive' => false,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Hard delete details by header
     * @param int $headerId
     * @return bool
     */
    public function destroyByHeader($headerId)
    {
        return $this->db->table($this->table)
            ->where('headerid', $headerId)
            ->delete();
    }

    /**
     * Soft delete detail
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
     * Hard delete detail
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
     * Get details for AJAX datatable (server-side processing)
     * FIXED VERSION - Proper delete button handling
     * @param int $headerId
     * @param string $search
     * @param int $start
     * @param int $length
     * @return array
     */
    public function getDetailsAjaxData($headerId, $search = '', $start = 0, $length = 10)
    {
        $builder = $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.headerid', $headerId)
            ->where('prd.isactive', true);

        // Apply search filter
        if (!empty($search)) {
            $builder->groupStart()
                ->like('LOWER(mp.productname)', strtolower($search))
                ->orLike('LOWER(mu.uomnm)', strtolower($search))
                ->orLike('CAST(prd.qty AS TEXT)', $search)
                ->groupEnd();
        }

        // Get total records
        $totalRecords = $builder->countAllResults(false);

        // Apply pagination
        $builder->limit($length, $start);
        $data = $builder->get()->getResultArray();

        // Map data for datatable
        $mappedData = [];
        $currentIndex = $start;
        
        foreach ($data as $row) {
            // FIXED: Escape untuk prevent XSS dan JS injection
            $safeProductName = htmlspecialchars($row['productname'], ENT_QUOTES, 'UTF-8');
            $safeUomName = htmlspecialchars($row['uomnm'] ?? '', ENT_QUOTES, 'UTF-8');
            
            // Apply same formatting as display column for input field
            $formattedQtyForInput = (floor($row['qty']) == $row['qty'])
                ? number_format($row['qty'], 0)  // Whole number for input
                : number_format($row['qty'], 2); // Fractional with 2 decimals

            // Edit button - menggunakan function editDetail yang sudah ada
            $btnEdit = "<button class='btn btn-sm btn-warning btn-edit-detail'
                            data-id='" . $row['id'] . "'
                            data-productid='" . $row['productid'] . "'
                            data-uomid='" . ($row['uomid'] ?? '') . "'
                            data-qty='" . $formattedQtyForInput . "'
                            data-productname='" . $safeProductName . "'
                            data-uomname='" . $safeUomName . "'>
                            <i class='bx bx-edit-alt'></i>
                        </button>";
            
            // FIXED: Delete button - menggunakan function deleteDetail dengan proper escaping
            $btnDelete = "<button class='btn btn-sm btn-danger btn-delete-detail' 
                            data-id='" . $row['id'] . "' 
                            data-productname='" . $safeProductName . "'>
                            <i class='bx bx-trash'></i>
                          </button>";

            // Dynamic quantity display: no decimals for whole numbers, decimals for fractional
            $formattedQty = (floor($row['qty']) == $row['qty'])
                ? number_format($row['qty'], 0)  // Whole number: display as integer
                : number_format($row['qty'], 2); // Fractional: display with 2 decimals

            $mappedData[] = [
                $currentIndex + 1,
                esc($row['productname']),
                esc($row['uomnm'] ?? '-'),
                $formattedQty,
                "<div class='text-center'>{$btnEdit} {$btnDelete}</div>"
            ];
            $currentIndex++;
        }

        return [
            'totalRecords' => $totalRecords,
            'data' => $mappedData
        ];
    }

    /**
     * Get total quantity by product
     * @param int $productId
     * @return float
     */
    public function getTotalQtyByProduct($productId)
    {
        $result = $this->db->table($this->table)
            ->selectSum('qty')
            ->where('productid', $productId)
            ->where('isactive', true)
            ->get()
            ->getRowArray();
        
        return (float) ($result['qty'] ?? 0);
    }

    /**
     * Get total items count by header
     * @param int $headerId
     * @return int
     */
    public function getTotalItemsByHeader($headerId)
    {
        return $this->db->table($this->table)
            ->where('headerid', $headerId)
            ->where('isactive', true)
            ->countAllResults();
    }

    /**
     * Check if product exists in details
     * @param int $headerId
     * @param int $productId
     * @param int|null $excludeId
     * @return bool
     */
    public function productExists($headerId, $productId, $excludeId = null)
    {
        $builder = $this->db->table($this->table)
            ->where('headerid', $headerId)
            ->where('productid', $productId)
            ->where('isactive', true);
        
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }
        
        return $builder->countAllResults() > 0;
    }
}