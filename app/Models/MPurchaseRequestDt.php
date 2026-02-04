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

        $this->builder = $this->db->table('trpurchaserequestdt as prd')
            ->select('prd.*')
            ->select('mp.productname')
            ->select('mu.uomnm')
            ->join('msproduct as mp', 'prd.productid = mp.id', 'left')
            ->join('msuom as mu', 'prd.uomid = mu.id', 'left')
            ->where('prd.isactive', true);
    }

    public function searchable()
    {
        return [
            null,
            'mp.productname',
            'mu.uomnm',
            'prd.qty',
            null
        ];
    }

    public function datatable()
    {
        return $this->builder;
    }

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

    public function store($data)
    {
        $insert = $this->db->table($this->table)->insert($data);
        return $insert ? $this->db->insertID() : false;
    }

    public function storeBatch($data)
    {
        return $this->db->table($this->table)->insertBatch($data);
    }

    public function edit($data, $id)
    {
        return $this->db->table($this->table)
            ->where('id', $id)
            ->update($data);
    }

    /**
     *  REFACTORED: Universal Destroy Method
     * @param mixed $identifier - ID atau headerid
     * @param string $type - 'soft' atau 'hard' (default: 'hard')
     * @param string $by - 'id' atau 'header' (default: 'id')
     * 
     * Contoh penggunaan:
     * - destroy(5, 'hard', 'id') → Hard delete by ID
     * - destroy(5, 'soft', 'id') → Soft delete by ID
     * - destroy(10, 'hard', 'header') → Hard delete by headerid
     * - destroy(10, 'soft', 'header') → Soft delete by headerid
     */
    public function destroy($identifier, $type = 'hard', $by = 'id')
    {
        $builder = $this->db->table($this->table);

        // Tentukan kondisi where
        if ($by === 'header') {
            $builder->where('headerid', $identifier);
        } else {
            $builder->where('id', $identifier);
        }

        // Tentukan jenis delete
        if ($type === 'soft') {
            // Soft delete - update isactive = false
            return $builder->update([
                'isactive' => false,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ]);
        } else {
            // Hard delete - hapus permanent
            return $builder->delete();
        }
    }

    /**
     * Wrapper methods untuk backward compatibility
     */
    public function softDestroy($id)
    {
        return $this->destroy($id, 'soft', 'id');
    }

    public function softDestroyByHeader($headerId)
    {
        return $this->destroy($headerId, 'soft', 'header');
    }

    public function destroyByHeader($headerId)
    {
        return $this->destroy($headerId, 'hard', 'header');
    }

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

    if (!empty($search)) {
        $builder->groupStart()
            ->like('LOWER(mp.productname)', strtolower($search))
            ->orLike('LOWER(mu.uomnm)', strtolower($search))
            ->orLike('CAST(prd.qty AS TEXT)', $search)
            ->groupEnd();
    }

    $totalRecords = $builder->countAllResults(false);
    $builder->limit($length, $start);
    $data = $builder->get()->getResultArray();

    $mappedData = [];
    $currentIndex = $start;

    foreach ($data as $row) {
        $safeProductName = htmlspecialchars($row['productname'], ENT_QUOTES, 'UTF-8');
        $safeUomName = htmlspecialchars($row['uomnm'] ?? '', ENT_QUOTES, 'UTF-8');

        $formattedQtyForInput = (floor($row['qty']) == $row['qty'])
            ? number_format($row['qty'], 0)
            : number_format($row['qty'], 2);

        //  FIX: Encrypt ID untuk edit button
        $encryptedId = encrypting($row['id']);

        $btnEdit = "<button class='btn btn-sm btn-warning btn-edit-detail'
                    data-id='" . $row['id'] . "'
                    data-id-encrypted='" . $encryptedId . "'
                    data-productid='" . $row['productid'] . "'
                    data-uomid='" . ($row['uomid'] ?? '') . "'
                    data-qty='" . $formattedQtyForInput . "'
                    data-productname='" . $safeProductName . "'
                    data-uomname='" . $safeUomName . "'>
                    <i class='bx bx-edit-alt'></i>
                </button>";

        $btnDelete = "<button type='button' class='btn btn-sm btn-danger' 
            onclick=\"modalDelete('Delete Detail - " . $safeProductName . "', {
                'link':'" . getURL('purchase-request/deletedetail') . "', 
                'id':'" . encrypting($row['id']) . "', 
                'pagetype':'detailtable'
            })\">
            <i class='bx bx-trash'></i>
          </button>";

        $formattedQty = (floor($row['qty']) == $row['qty'])
            ? number_format($row['qty'], 0)
            : number_format($row['qty'], 2);

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

    public function getTotalItemsByHeader($headerId)
    {
        return $this->db->table($this->table)
            ->where('headerid', $headerId)
            ->where('isactive', true)
            ->countAllResults();
    }

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
  