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
     * Get datatable data - SERVER SIDE
     */
    public function datatable()
    {
        log_message('info', '=== PURCHASE REQUEST DATATABLE CALLED ===');

        $csrfToken = $this->request->getPost(csrf_token());
        log_message('info', 'CSRF Token: ' . ($csrfToken ? 'Received' : 'Missing'));

        try {
            $table = Datatables::method([MPurchaseRequestHd::class, 'datatable'], 'searchable')->make();

            $table->updateRow(function ($db, $no) {
                $btn_edit = "<button type='button' class='btn btn-sm btn-warning' onclick=\"modalForm('Update Purchase Request - " . $db->transcode . "', 'modal-lg', '" . getURL('purchase-request/form/' . encrypting($db->id)) . "', {identifier: this})\"><i class='bx bx-edit-alt'></i></button>";

                $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' onclick=\"modalDelete('Delete Purchase Request - " . $db->transcode . "', {'link':'" . getURL('purchase-request/delete') . "', 'id':'" . encrypting($db->id) . "', 'pagetype':'table'})\"><i class='bx bx-trash'></i></button>";

                return [
                    $no,
                    $db->transcode,
                    date('d-m-Y', strtotime($db->transdate)),
                    $db->suppliername ?? '-',
                    $db->description ?? '-',
                    "<div class='text-center'>$btn_edit&nbsp;$btn_hapus</div>"
                ];
            });

            log_message('info', '=== DATATABLE RESPONSE SENT ===');
            return $table->toJson();

        } catch (\Exception $e) {
            log_message('error', 'Datatable Error: ' . $e->getMessage());
            log_message('error', 'Trace: ' . $e->getTraceAsString());

            return $this->response->setJSON([
                'draw' => (int) ($this->request->getPost('draw') ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
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

            $viewContent = view('master/purchase_request/v_form', [
                'form_type' => $form_type,
                'header' => $header,
                'detail' => $detail
            ]);

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
     * ✅ REFACTORED: Store method (Header) - Gabungan add() dan update()
     * @param string $type - 'add' atau 'update'
     */
    private function storeHeader($type = 'add')
    {
        $res = [];
        $this->db->transBegin();

        try {
            // Get post data
            $id = $type === 'update' ? decrypting($this->request->getPost('id')) : null;
            $transcode = $this->request->getPost('transcode');
            $transdate = $this->request->getPost('transdate');
            $supplierid = $this->request->getPost('supplierid');
            $description = $this->request->getPost('description');
            $items = json_decode($this->request->getPost('items'), true);

            // Validasi
            if ($type === 'update' && empty($id)) {
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

            // Check duplicate transcode
            $checkBuilder = $this->db->table('trpurchaserequesthd')->where('transcode', $transcode);
            if ($type === 'update') {
                $checkBuilder->where('id !=', $id);
            }
            if ($checkBuilder->countAllResults() > 0) {
                throw new Exception('Transcode sudah terdaftar');
            }

            // Prepare data
            $headerData = [
                'transcode' => $transcode,
                'transdate' => $transdate,
                'supplierid' => $supplierid,
                'description' => $description
            ];

            if ($type === 'add') {
                // Insert mode
                $headerData['createdby'] = getSession('userid');
                $headerData['createddate'] = date('Y-m-d H:i:s');
                $headerData['isactive'] = true;
                
                $headerId = $this->mHeader->store($headerData);
                
                if (!$headerId) {
                    throw new Exception('Gagal menyimpan header');
                }

                // Insert detail items
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
                
                $message = 'Purchase Request berhasil disimpan';
            } else {
                // Update mode
                $header = $this->mHeader->getOne($id);
                if (empty($header)) {
                    throw new Exception('Purchase Request tidak ditemukan');
                }

                $headerData['updatedby'] = getSession('userid');
                $headerData['updateddate'] = date('Y-m-d H:i:s');
                
                $this->mHeader->edit($headerData, $id);
                
                $message = 'Purchase Request berhasil diupdate';
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => $message,
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
     * Add new Purchase Request
     */
    public function add()
    {
        return $this->storeHeader('add');
    }

    /**
     * Update existing Purchase Request
     */
    public function update()
    {
        return $this->storeHeader('update');
    }

    /**
     * Store method - Auto detect add or update
     */
    public function store()
    {
        $form_type = !empty($this->request->getPost('id')) ? 'update' : 'add';
        return $this->storeHeader($form_type);
    }

    /**
     * Delete Purchase Request
     */
    public function delete()
    {
        $purchaserequestid = $this->request->getPost('id');
        $res = array();
        $this->db->transBegin();
        
        try {
            if (empty($purchaserequestid))
                throw new Exception("ID Purchase Request tidak ditemukan!");

            $purchaserequestid = decrypting($purchaserequestid);
            $row = $this->mHeader->getOne($purchaserequestid);

            if (empty($row))
                throw new Exception("Purchase Request tidak terdaftar di sistem!");

            // Hapus detail terlebih dahulu
            $this->db->table('trpurchaserequestdt')->delete(['headerid' => $purchaserequestid]);

            // Hapus header
            $this->mHeader->destroy('id', $purchaserequestid);

            $res = [
                'sukses' => '1',
                'pesan' => 'Data berhasil dihapus!',
                'dbError' => db_connect()->error()
            ];
            $this->db->transCommit();
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        $this->db->transComplete();
        echo json_encode($res);
    }

    /**
     *  REFACTORED: Universal Search for Select2
     * @param string $entity - 'supplier', 'product', atau 'uom'
     */
    public function search($entity)
    {
        try {
            $term = $this->request->getGet('term') ?? $this->request->getPost('term') ?? '';

            // Konfigurasi berdasarkan entity
            $config = [
                'supplier' => [
                    'table' => 'mssupplier',
                    'textField' => 'suppliername',
                    'orderBy' => 'suppliername'
                ],
                'product' => [
                    'table' => 'msproduct',
                    'textField' => 'productname',
                    'orderBy' => 'productname'
                ],
                'uom' => [
                    'table' => 'msuom',
                    'textField' => 'uomnm',
                    'orderBy' => 'uomnm'
                ]
            ];

            if (!isset($config[$entity])) {
                throw new Exception('Invalid entity');
            }

            $cfg = $config[$entity];
            $builder = $this->db->table($cfg['table']);
            $builder->select('id, ' . $cfg['textField'] . ' as text');

            if ($this->db->fieldExists('isactive', $cfg['table'])) {
                $builder->where('isactive', true);
            }

            if (!empty($term)) {
                $builder->groupStart()
                    ->like('LOWER(' . $cfg['textField'] . ')', strtolower($term))
                    ->orLike($cfg['textField'], $term)
                    ->groupEnd();
            }

            $builder->orderBy($cfg['orderBy'], 'ASC');
            $builder->limit(50);

            $results = $builder->get()->getResultArray();

            return $this->response->setJSON($results);

        } catch (\Exception $e) {
            log_message('error', 'Search ' . ucfirst($entity) . ' Error: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }

    /**
     * Wrapper methods untuk backward compatibility
     */
    public function searchSupplier()
    {
        return $this->search('supplier');
    }

    public function searchProduct()
    {
        return $this->search('product');
    }

    public function searchUom()
    {
        return $this->search('uom');
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
     * ✅ REFACTORED: Store Detail - Gabungan addDetail() dan updateDetail()
     * @param string $type - 'add' atau 'update'
     */
    private function storeDetail($type = 'add')
    {
        $res = [];
        $this->db->transBegin();

        try {
            $id = $type === 'update' ? decrypting($this->request->getPost('id')) : null;
            $headerId = $type === 'add' ? decrypting($this->request->getPost('headerId')) : null;
            $productId = $this->request->getPost('productId');
            $uomId = $this->request->getPost('uomId');
            $qty = $this->request->getPost('qty');

            // Validasi
            if ($type === 'update' && empty($id)) {
                throw new Exception('ID tidak valid');
            }
            if ($type === 'add' && empty($headerId)) {
                throw new Exception('Header ID wajib diisi');
            }
            if (empty($productId) || empty($qty)) {
                throw new Exception('Product dan Qty wajib diisi');
            }

            if ($type === 'add') {
                // Check if header exists
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
                
                $message = 'Detail berhasil ditambahkan';
            } else {
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
                
                $message = 'Detail berhasil diupdate';
            }

            $this->db->transCommit();
            $res = [
                'sukses' => 1,
                'pesan' => $message,
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
     * Add Detail Item
     */
    public function addDetail()
    {
        return $this->storeDetail('add');
    }

    /**
     * Update Detail Item
     */
    public function updateDetail()
    {
        return $this->storeDetail('update');
    }

    /**
     * Delete Detail Item
     */
    public function deleteDetail()
    {
        $detailid = $this->request->getPost('id');
        $res = array();
        $this->db->transBegin();
        
        try {
            if (empty($detailid)) {
                throw new Exception("ID Detail tidak ditemukan!");
            }

            $detailid = decrypting($detailid);
            $row = $this->mDetail->getOne($detailid);

            if (empty($row)) {
                throw new Exception("Detail tidak terdaftar di sistem!");
            }

            // Hapus detail
            $this->mDetail->destroy($detailid);

            $res = [
                'sukses' => '1',
                'pesan' => 'Detail berhasil dihapus!',
                'csrfToken' => csrf_hash(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transCommit();
            
        } catch (Exception $e) {
            $res = [
                'sukses' => '0',
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash(),
                'traceString' => $e->getTraceAsString(),
                'dbError' => db_connect()->error()
            ];
            $this->db->transRollback();
        }
        
        $this->db->transComplete();
        echo json_encode($res);
    }
}