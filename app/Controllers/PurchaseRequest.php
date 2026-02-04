<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Helpers\Datatables\Datatables;
use App\Models\MPurchaseRequestHd;
use App\Models\MPurchaseRequestDt;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
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
                // Tombol Edit
                $btn_edit = "<a href='" . getURL('purchase-request/edit-page/' . encrypting($db->id)) . "' 
                   class='btn btn-sm btn-warning' title='Edit'>
                   <i class='bx bx-edit-alt'></i>
                </a>";

                // Tombol Delete
                $btn_hapus = "<button type='button' class='btn btn-sm btn-danger' title='Delete'
                    onclick=\"modalDelete('Delete Purchase Request - " . $db->transcode . "', {
                        'link':'" . getURL('purchase-request/delete') . "', 
                        'id':'" . encrypting($db->id) . "', 
                        'pagetype':'table'
                    })\">
                    <i class='bx bx-trash'></i>
                  </button>";

                // Tombol Print PDF (BARU)
                $btn_print = "<a href='" . getURL('purchase-request/print-pdf/' . encrypting($db->id)) . "' 
                    class='btn btn-sm btn-info' title='Print PDF' target='_blank'>
                    <i class='bx bx-printer'></i>
                 </a>";


                // Format tanggal
                $bulanIndo = [
                    1 => 'Januari',
                    2 => 'Februari',
                    3 => 'Maret',
                    4 => 'April',
                    5 => 'Mei',
                    6 => 'Juni',
                    7 => 'Juli',
                    8 => 'Agustus',
                    9 => 'September',
                    10 => 'Oktober',
                    11 => 'November',
                    12 => 'Desember'
                ];
                $timestamp = strtotime($db->transdate);
                $tanggalFormat = date('d', $timestamp) . ' ' . $bulanIndo[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

                return [
                    $no,
                    $db->transcode,
                    $tanggalFormat,
                    $db->suppliername ?? '-',
                    $db->description ?? '-',
                    "<div class='action-buttons'>{$btn_edit} {$btn_hapus} {$btn_print}</div>"
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
     *  REFACTORED Store method - updatedby dan updateddate selalu terisi
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
                //  Insert mode - isi createdby, createddate, updatedby, updateddate
                $headerData['createdby'] = getSession('userid');
                $headerData['createddate'] = date('Y-m-d H:i:s');
                $headerData['updatedby'] = getSession('userid');  //  TAMBAHAN
                $headerData['updateddate'] = date('Y-m-d H:i:s'); //  TAMBAHAN
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
                            // : Tambahkan updatedby dan updateddate di add detail
                            'updatedby' => getSession('userid'),
                            'updateddate' => date('Y-m-d H:i:s'),
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

                // Update tetap isi updatedby dan updateddate
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
     * Universal Search for Select2
     */
    public function search($entity)
    {
        try {
            $term = $this->request->getGet('term') ?? $this->request->getPost('term') ?? '';

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
     *  Store Detail - updatedby dan updateddate selalu terisi
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
                $header = $this->mHeader->getOne($headerId);
                if (empty($header)) {
                    throw new Exception('Purchase Request tidak ditemukan');
                }

                //  Add detail dengan updatedby dan updateddate
                $data = [
                    'headerid' => $headerId,
                    'productid' => $productId,
                    'uomid' => $uomId,
                    'qty' => (float) $qty,
                    'createdby' => getSession('userid'),
                    'createddate' => date('Y-m-d H:i:s'),
                    'updatedby' => getSession('userid'),   // TAMBAHAN
                    'updateddate' => date('Y-m-d H:i:s'),  //  TAMBAHAN
                    'isactive' => true
                ];

                $insertId = $this->mDetail->store($data);
                if (!$insertId) {
                    throw new Exception('Gagal menambahkan detail');
                }

                $message = 'Detail berhasil ditambahkan';
            } else {
                $detail = $this->mDetail->getOne($id);
                if (empty($detail)) {
                    throw new Exception('Detail tidak ditemukan');
                }

                //  Update detail dengan updatedby dan updateddate
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

    public function addDetail()
    {
        return $this->storeDetail('add');
    }

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

    /**
     * Halaman Add Purchase Request (New Page)
     */
    public function add_page()
    {
        return view('master/purchase_request/v_add', [
            'title' => 'Add Purchase Request',
            'breadcrumb' => array_merge($this->bc, [['Add Purchase Request']]),
            'section' => 'Transaction',
            'form_type' => 'add'
        ]);
    }

    /**
     * Halaman Edit Purchase Request (New Page)
     */
    public function edit_page($id = '')
    {
        try {
            if (empty($id)) {
                return redirect()->to('purchase-request')->with('error', 'ID tidak valid');
            }

            $id = decrypting($id);
            $header = $this->mHeader->getOne($id);

            if (empty($header)) {
                return redirect()->to('purchase-request')->with('error', 'Purchase Request tidak ditemukan');
            }

            return view('master/purchase_request/v_edit', [
                'title' => 'Edit Purchase Request - ' . $header['transcode'],
                'breadcrumb' => array_merge($this->bc, [['Edit Purchase Request']]),
                'section' => 'Transaction',
                'form_type' => 'edit',
                'header' => $header
            ]);

        } catch (Exception $e) {
            return redirect()->to('purchase-request')->with('error', $e->getMessage());
        }
    }

    /**
     * Modal Form untuk Edit Detail
     */
    public function form_edit_detail($id = '')
    {
        try {
            if (empty($id)) {
                throw new Exception('ID tidak valid');
            }

            $decryptedId = decrypting($id);

            if (!$decryptedId || !is_numeric($decryptedId)) {
                log_message('error', 'Failed to decrypt ID: ' . $id);
                throw new Exception('Invalid ID format');
            }

            $detail = $this->mDetail->getOne($decryptedId);

            if (empty($detail)) {
                throw new Exception('Detail tidak ditemukan');
            }

            $viewContent = view('master/purchase_request/v_modal_edit_detail', [
                'detail' => $detail
            ]);

            echo json_encode([
                'view' => $viewContent,
                'csrfToken' => csrf_hash()
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error in form_edit_detail: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'error' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ]);
        }
    }



    /**
     * Print Purchase Request to PDF
     */
    public function print_pdf($id = '')
    {
        try {
            if (empty($id)) {
                return redirect()->to('purchase-request')->with('error', 'ID tidak valid');
            }

            $id = decrypting($id);

            // Get header data with supplier info
            $header = $this->mHeader->getOne($id);

            if (empty($header)) {
                return redirect()->to('purchase-request')->with('error', 'Purchase Request tidak ditemukan');
            }

            // Get detail data
            $details = $this->mDetail->getByHeader($id);

            // Load FPDF Library
            $pdf = new \App\Libraries\PdfPurchaseRequest();

            // Set header data
            $pdf->setHeaderData([
                'transcode' => $header['transcode'],
                'transdate' => $header['transdate']
            ]);

            $pdf->AliasNbPages();
            $pdf->AddPage();
            $pdf->SetFont('Arial', '', 10);



            $pdf->SetFont('Arial', '', 9);
            // No Purchase Request
            $pdf->Cell(45, 6, 'No. Purchase Request', 0, 0, 'L');
            $pdf->Cell(5, 6, ':', 0, 0, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(45, 6, $header['transcode'], 0, 0, 'L');

            // Tanggal
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(45, 6, 'Tanggal Request', 0, 0, 'R');
            $pdf->Cell(5, 6, ':', 0, 0, 'C');
            $pdf->Cell(25, 6, $this->formatDateIndo($header['transdate']), 0, 1, 'R');


            // Supplier
            $pdf->Cell(45, 6, 'Supplier', 0, 0, 'L');
            $pdf->Cell(5, 6, ':', 0, 0, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(45, 6, $header['suppliername'] ?? '-', 0, 1, 'L');




            $pdf->Ln(5);

            // SECTION: Detail Items
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 8, 'Detail Item Purchase Request', 0, 1, 'L');
            $pdf->Ln(2);

            // Table headers
            $headerTable = ['No', 'Nama Product', 'UOM', 'Quantity'];

            // Table data
            $pdf->ImprovedTable($headerTable, $details);

            // Output PDF
            $filename = 'PR_' . $header['transcode'] . '_' . date('Ymd') . '.pdf';
            $pdf->Output('D', $filename); // D = Download
            exit;
        } catch (\Exception $e) {
            log_message('error', 'Error generating PDF: ' . $e->getMessage());
            return redirect()->to('purchase-request')->with('error', 'Gagal generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Helper: Format date to Indonesian
     */
    private function formatDateIndo($date)
    {
        $bulanIndo = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember'
        ];

        $timestamp = strtotime($date);
        return date('d', $timestamp) . ' ' . $bulanIndo[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    /**
     *  Export Excel dengan Chunk
     * Method ini menerima data dari frontend yang sudah di-chunk
     */
    public function exportExcelAll()
    {
        // Close session agar tidak blocking request lain
        session_write_close();

        // Set time limit dan memory limit
        set_time_limit(300); // 5 menit
        ini_set('memory_limit', '512M'); // Adjust sesuai kebutuhan

        try {
            // Ambil raw input karena dikirim sebagai JSON
            $rawInput = $this->request->getBody();
            $jsonData = json_decode($rawInput, true);

            // Ekstrak data dari JSON
            $data = $jsonData['data'] ?? [];

            if (empty($data)) {
                return $this->response->setJSON([
                    'sukses' => 0,
                    'pesan' => 'Data kosong untuk export'
                ]);
            }

            //  Log jumlah data
            log_message('info', 'Export Excel - Total data: ' . count($data));


            // Buat Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Judul
            $sheet->setCellValue('A1', 'Daftar Purchase Request');
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // Header kolom
            $sheet->setCellValue('A3', 'No');
            $sheet->setCellValue('B3', 'Transcode');
            $sheet->setCellValue('C3', 'Tanggal');
            $sheet->setCellValue('D3', 'Supplier');
            $sheet->setCellValue('E3', 'Description');
            $sheet->getStyle('A3:E3')->getFont()->setBold(true);
            $sheet->getStyle('A3:E3')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFD9D9D9');

            // Isi data dari frontend
            $rowExcel = 4;
            $no = 1;
            foreach ($data as $header) {
                $sheet->setCellValue('A' . $rowExcel, $no++);
                $sheet->setCellValue('B' . $rowExcel, $header['transcode']);
                $sheet->setCellValue('C' . $rowExcel, $this->formatDateIndo($header['transdate']));
                $sheet->setCellValue('D' . $rowExcel, $header['suppliername'] ?? '-');
                $sheet->setCellValue('E' . $rowExcel, $header['description'] ?? '-');
                $rowExcel++;
            }

            // Border + styling
            $lastRow = $rowExcel - 1;
            $sheet->getStyle("A3:E{$lastRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ]);

            // Auto-size columns
            foreach (range('A', 'E') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Clear output buffer sebelum download
            if (ob_get_level()) {
                ob_end_clean();
            }

            // Generate filename
            $filename = 'All_PR_' . date('Ymd_His') . '.xlsx';

            //  Set headers yang benar untuk download    Excel
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            header('Cache-Control: max-age=1');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Cache-Control: cache, must-revalidate');
            header('Pragma: public');

            // Output Excel
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export Excel Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'sukses' => 0,
                'pesan' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get data dengan chunk untuk export
     */
    public function get_chunk()
    {
        // Close session agar tidak blocking request lain
        session_write_close();

        try {
            $limit = $this->request->getGet('limit') ?? 1000;
            $offset = $this->request->getGet('offset') ?? 0;

            $results = $this->mHeader->getChunk($limit, $offset);

            return $this->response->setJSON([
                'data' => $results,
                'count' => count($results)
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get Chunk Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'data' => [],
                'count' => 0,
                'error' => $e->getMessage()
            ]);
        }
    }
}