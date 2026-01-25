<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MPurchaseRequestHd;
use App\Models\MPurchaseRequestDt;
use Exception;

class PurchaseRequest extends BaseController
{
    protected $mHeader;
    protected $mDetail;
    protected $db;
    protected $bc;

    public function __construct()
    {
        $this->mHeader = new MPurchaseRequestHd();
        $this->mDetail = new MPurchaseRequestDt();
        $this->db = db_connect();
        $this->bc = [['Transaction', 'Purchase Request']];
    }

    /**
     * Index page - List all Purchase Requests
     */
    public function index()
    {
        return view('master/purchase_request/v_purchaserequest', [
            'title' => 'Purchase Request',
            'breadcrumb' => $this->bc,
            'section' => 'Transaction'
        ]);
    }

    /**
     * Get datatable data for Purchase Request list
     */
    public function table()
    {
        $table = Datatables::method([MPurchaseRequestHd::class, 'datatable'], 'searchable')->make();
        $table->updateRow(function ($db, $no) {
            $btn_edit = "<button class='btn btn-sm btn-warning' onclick=\"modalForm('Edit Purchase Request', 'modal-lg', '" . getURL('purchase-request/form/' . encrypting($db->id)) . "', {identifier:this})\" data-id='" . encrypting($db->id) . "'><i class='bx bx-edit-alt'></i></button>";
            $btn_hapus = "<button class='btn btn-sm btn-danger btn-delete-pr'data-id='" . encrypting($db->id) . "'data-url='" . getURL('purchase-request/delete') . "'><i class='bx bx-trash'></i></button>";

            return [
                $no,
                $db->transcode,
                date('d-m-Y', strtotime($db->transdate)),
                $db->suppliername ?? '-',
                $db->description ?? '-',
                "<div class='text-center'>$btn_edit&nbsp;$btn_hapus</div>"
            ];
        });
        return $table->toJson();
    }

    /**
     * Show form for add/edit Purchase Request
     */
    public function form($id = '')
    {
        try {
            $header = [];
            $detail = [];
            $form_type = 'add';

            if (!empty($id)) {
                $id = decrypting($id);
                $header = $this->mHeader->getOne($id);
                $detail = $this->mDetail->getByHeader($id);
                $form_type = 'edit';

                if (empty($header)) {
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'Purchase Request tidak ditemukan',
                        'csrfToken' => csrf_hash()
                    ]);
                    return;
                }
            }

            // Render view
            $viewContent = view('master/purchase_request/v_form', [
                'form_type' => $form_type,
                'header' => $header,
                'detail' => $detail
            ]);

            // Return response
            echo json_encode([
                'view' => $viewContent,
                'csrfToken' => csrf_hash()
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Error: ' . $e->getMessage(),
                'csrfToken' => csrf_hash()
            ]);
        }
    }

    /**
     * Add new Purchase Request
     */
    public function add()
    {
        $res = [];
        $this->db->transBegin();

        try {
            // Get post data
            $transcode = $this->request->getPost('transcode');
            $transdate = $this->request->getPost('transdate');
            $supplierid = $this->request->getPost('supplierid');
            $description = $this->request->getPost('description');
            $items = json_decode($this->request->getPost('items'), true);

            // Validasi
            if (empty($transcode)) {
                throw new Exception('Transcode wajib diisi');
            }
            if (empty($transdate)) {
                throw new Exception('Tanggal transaksi wajib diisi');
            }
            if (empty($supplierid)) {
                throw new Exception('Supplier wajib dipilih');
            }

            // Check duplicate transcode
            $checkCode = $this->db->table('trpurchaserequesthd')
                ->where('transcode', $transcode)
                ->countAllResults();
            if ($checkCode > 0) {
                throw new Exception('Transcode sudah terdaftar');
            }

            // Insert header
            $headerData = [
                'transcode' => $transcode,
                'transdate' => $transdate,
                'supplierid' => $supplierid,
                'description' => $description,
                'createdby' => getSession('userid'),
                'createddate' => date('Y-m-d H:i:s'),
                'isactive' => true
            ];
            $headerId = $this->mHeader->store($headerData);

            if (!$headerId) {
                throw new Exception('Gagal menyimpan header');
            }

            // Insert detail items (only if provided)
            if (!empty($items) && count($items) > 0) {
                $detailData = [];
                foreach ($items as $row) {
                    if (empty($row['productid']) || empty($row['qty'])) {
                        throw new Exception('Product dan Qty tidak boleh kosong');
                    }
                    $detailData[] = [
                        'headerid' => $headerId,
                        'productid' => $row['productid'],
                        'uomid' => $row['uomid'] ?? null,
                        'qty' => (float) $row['qty'],
                        'createdby' => getSession('userid'),
                        'createddate' => date('Y-m-d H:i:s'),
                        'isactive' => true
                    ];
                }
                $this->mDetail->storeBatch($detailData);
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Purchase Request berhasil disimpan',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }

    /**
     * Update existing Purchase Request
     */
    public function update()
    {
        $res = [];
        $this->db->transBegin();

        try {
            // Get post data
            $id = decrypting($this->request->getPost('id'));
            $transcode = $this->request->getPost('transcode');
            $transdate = $this->request->getPost('transdate');
            $supplierid = $this->request->getPost('supplierid');
            $description = $this->request->getPost('description');

            // Validasi
            if (empty($id)) {
                throw new Exception('ID tidak valid');
            }
            if (empty($transcode)) {
                throw new Exception('Transcode wajib diisi');
            }
            if (empty($transdate)) {
                throw new Exception('Tanggal transaksi wajib diisi');
            }
            if (empty($supplierid)) {
                throw new Exception('Supplier wajib dipilih');
            }

            // Check if header exists
            $header = $this->mHeader->getOne($id);
            if (empty($header)) {
                throw new Exception('Purchase Request tidak ditemukan');
            }

            // Check duplicate transcode (exclude current id)
            $checkCode = $this->db->table('trpurchaserequesthd')
                ->where('transcode', $transcode)
                ->where('id !=', $id)
                ->countAllResults();
            if ($checkCode > 0) {
                throw new Exception('Transcode sudah terdaftar');
            }

            // Update header with audit fields (TIDAK BOLEH KOSONG)
            $headerData = [
                'transcode' => $transcode,
                'transdate' => $transdate,
                'supplierid' => $supplierid,
                'description' => $description,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ];
            $this->mHeader->edit($headerData, $id);

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Purchase Request berhasil diupdate',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }

    /**
     * Delete Purchase Request
     */
    public function delete()
    {
        $res = [];
        $this->db->transBegin();

        try {
            $id = decrypting($this->request->getPost('id'));

            if (empty($id)) {
                throw new Exception('ID tidak valid');
            }

            // Check if header exists
            $header = $this->mHeader->getOne($id);
            if (empty($header)) {
                throw new Exception('Purchase Request tidak ditemukan');
            }

            // Delete detail items
            $this->mDetail->destroyByHeader($id);

            // Delete header
            $this->mHeader->destroy($id);

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Purchase Request berhasil dihapus',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }



    /**
     * Store method - Auto detect add or update
     */
    public function store()
    {
        $form_type = !empty($this->request->getPost('id')) ? 'update' : 'add';

        if ($form_type == 'update') {
            return $this->update();
        } else {
            return $this->add();
        }
    }

    /**
     * Search Supplier - AJAX endpoint for Select2
     */
    public function searchSupplier()
    {
        try {
            $term = $this->request->getGet('term') ?? $this->request->getPost('term') ?? '';

            // Log untuk debugging
            log_message('debug', 'Search Supplier - Term: ' . $term);

            $builder = $this->db->table('mssupplier');
            $builder->select('id, suppliername as text');

            // Cek apakah ada kolom isactive
            if ($this->db->fieldExists('isactive', 'mssupplier')) {
                $builder->where('isactive', true);
            }

            // Apply search filter
            if (!empty($term)) {
                $builder->groupStart()
                    ->like('LOWER(suppliername)', strtolower($term))
                    ->orLike('suppliername', $term)
                    ->groupEnd();
            }

            $builder->orderBy('suppliername', 'ASC');
            $builder->limit(50);

            $results = $builder->get()->getResultArray();

            // Log hasil
            log_message('debug', 'Search Supplier - Results: ' . count($results) . ' found');

            // Return JSON
            return $this->response->setJSON($results);

        } catch (\Exception $e) {
            log_message('error', 'Search Supplier Error: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }

    /**
     * Search Product - AJAX endpoint for Select2
     */
    public function searchProduct()
    {
        try {
            $term = $this->request->getGet('term') ?? $this->request->getPost('term') ?? '';

            log_message('debug', 'Search Product - Term: ' . $term);

            $builder = $this->db->table('msproduct');
            $builder->select('id, productname as text');

            // Cek apakah ada kolom isactive
            if ($this->db->fieldExists('isactive', 'msproduct')) {
                $builder->where('isactive', true);
            }

            // Apply search filter
            if (!empty($term)) {
                $builder->groupStart()
                    ->like('LOWER(productname)', strtolower($term))
                    ->orLike('productname', $term)
                    ->groupEnd();
            }

            $builder->orderBy('productname', 'ASC');
            $builder->limit(50);

            $results = $builder->get()->getResultArray();

            log_message('debug', 'Search Product - Results: ' . count($results) . ' found');

            return $this->response->setJSON($results);

        } catch (\Exception $e) {
            log_message('error', 'Search Product Error: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }

    /**
     * Search UOM - AJAX endpoint for Select2
     */
    public function searchUom()
    {
        try {
            $term = $this->request->getGet('term') ?? $this->request->getPost('term') ?? '';

            log_message('debug', 'Search UOM - Term: ' . $term);

            $builder = $this->db->table('msuom');
            $builder->select('id, uomnm as text');

            // Cek apakah ada kolom isactive
            if ($this->db->fieldExists('isactive', 'msuom')) {
                $builder->where('isactive', true);
            }

            // Apply search filter
            if (!empty($term)) {
                $builder->groupStart()
                    ->like('LOWER(uomnm)', strtolower($term))
                    ->orLike('uomnm', $term)
                    ->groupEnd();
            }

            $builder->orderBy('uomnm', 'ASC');
            $builder->limit(50);

            $results = $builder->get()->getResultArray();

            log_message('debug', 'Search UOM - Results: ' . count($results) . ' found');

            return $this->response->setJSON($results);

        } catch (\Exception $e) {
            log_message('error', 'Search UOM Error: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }

    /**
     * Get Details AJAX - Server-side processing for detail datatable
     */
    public function getDetailsAjax()
    {
        try {
            $headerIdEncrypted = $this->request->getPost('headerId');
            if (empty($headerIdEncrypted)) {
                return $this->response->setJSON(['error' => 'headerId tidak ditemukan'], 400);
            }

            $headerId = decrypting($headerIdEncrypted);
            if (!$headerId || !is_numeric($headerId)) {
                return $this->response->setJSON(['error' => 'ID header tidak valid'], 400);
            }

            $draw = $this->request->getPost('draw') ?? 1;
            $start = $this->request->getPost('start') ?? 0;
            $length = $this->request->getPost('length') ?? 10;
            $search = $this->request->getPost('search')['value'] ?? '';

            $result = $this->mDetail->getDetailsAjaxData($headerId, $search, $start, $length);

            return $this->response->setJSON([
                'draw' => intval($draw),
                'recordsTotal' => $result['totalRecords'],
                'recordsFiltered' => $result['totalRecords'],
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Error di getDetailsAjax: ' . $e->getMessage());
            return $this->response->setJSON([
                'error' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add Detail Item
     */
    public function addDetail()
    {
        $res = [];
        $this->db->transBegin();

        try {
            $headerId = decrypting($this->request->getPost('headerId'));
            $productId = $this->request->getPost('productId');
            $uomId = $this->request->getPost('uomId');
            $qty = $this->request->getPost('qty');

            // Validasi
            if (empty($headerId) || empty($productId) || empty($qty)) {
                throw new Exception('Header ID, Product dan Qty wajib diisi');
            }

            // Check if header exists and is active
            $header = $this->mHeader->getOne($headerId);
            if (empty($header)) {
                throw new Exception('Purchase Request tidak ditemukan');
            }

            $data = [
                'headerid' => $headerId,
                'productid' => $productId,
                'uomid' => $uomId,
                'qty' => (float) $qty,
                'createdby' => getSession('userid'),
                'createddate' => date('Y-m-d H:i:s'),
                'isactive' => true
            ];

            $insertId = $this->mDetail->store($data);

            if (!$insertId) {
                throw new Exception('Gagal menambahkan detail');
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Detail berhasil ditambahkan',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }

    /**
     * Update Detail Item
     */
    public function updateDetail()
    {
        $res = [];
        $this->db->transBegin();

        try {
            $id = $this->request->getPost('id');
            $productId = $this->request->getPost('productId');
            $uomId = $this->request->getPost('uomId');
            $qty = $this->request->getPost('qty');

            // Validasi
            if (empty($id) || empty($productId) || empty($qty)) {
                throw new Exception('ID, Product dan Qty wajib diisi');
            }

            // Check if detail exists
            $detail = $this->mDetail->getOne($id);
            if (empty($detail)) {
                throw new Exception('Detail tidak ditemukan');
            }

            $data = [
                'productid' => $productId,
                'uomid' => $uomId,
                'qty' => (float) $qty,
                'updatedby' => getSession('userid'),
                'updateddate' => date('Y-m-d H:i:s')
            ];

            $updated = $this->mDetail->edit($data, $id);

            if (!$updated) {
                throw new Exception('Gagal mengupdate detail');
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Detail berhasil diupdate',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }

    /**
     * Delete Detail Item
     */
    public function deleteDetail()
    {
        $res = [];
        $this->db->transBegin();

        try {
            $id = $this->request->getPost('id');

            if (empty($id)) {
                throw new Exception('ID tidak valid');
            }

            // Check if detail exists
            $detail = $this->mDetail->getOne($id);
            if (empty($detail)) {
                throw new Exception('Detail tidak ditemukan');
            }

            $deleted = $this->mDetail->destroy($id);

            if (!$deleted) {
                throw new Exception('Gagal menghapus detail');
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => 'Detail berhasil dihapus',
                'csrfToken' => csrf_hash()
            ];
        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukses' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }
}