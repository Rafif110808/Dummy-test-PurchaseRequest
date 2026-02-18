<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\MFiles;
use Exception;

class Files extends BaseController
{
    protected $mFiles;
    protected $db;
    protected $bc;

    protected $uploadPath;
    protected $chunksPath;
    protected $chunkSize;
    protected $maxFileSize;

    public function __construct()
    {
        $this->mFiles = new MFiles();
        $this->db = db_connect();
        $this->bc = [['Master', 'Files']];

        $this->uploadPath = FCPATH . 'uploads/files/';
        $this->chunksPath = FCPATH . 'uploads/files/chunks/';
        $this->chunkSize = 2097152; // 2MB
        $this->maxFileSize = 104857600; // 100MB
    }

    /**
     * Index page - List all Files
     */
    public function index()
    {
        return view('master/file/v_files', [
            'title' => 'Files',
            'breadcrumb' => $this->bc,
            'section' => 'Master'
        ]);
    }

    /**
     * Get datatable data - SERVER SIDE
     */
    public function datatable()
    {
        try {
            $draw = $this->request->getPost('draw') ?? 1;
            $start = $this->request->getPost('start') ?? 0;
            $length = $this->request->getPost('length') ?? 10;
            $searchValue = $this->request->getPost('search')['value'] ?? '';

            $builderCount = $this->db->table('msfiles as f')
                ->select('f.fileid')
                ->where('f.isactive', true);

            $totalRecords = $builderCount->countAllResults();

            $builder = $this->db->table('msfiles as f')
                ->select('f.*, u.fullname as created_by_name')
                ->join('msuser as u', 'f.created_by = u.id', 'left')
                ->where('f.isactive', true);

            if (!empty($searchValue)) {
                $builder->groupStart()
                    ->like('f.filename', $searchValue)
                    ->orLike('f.filerealname', $searchValue)
                    ->orLike('f.filedirectory', $searchValue)
                    ->groupEnd();
            }

            $data = $builder->orderBy('f.created_date', 'DESC')
                ->limit($length, $start)
                ->get()
                ->getResultArray();

            $formattedData = [];
            $no = $start + 1;

            foreach ($data as $row) {
                $extension = strtolower(pathinfo($row['filename'], PATHINFO_EXTENSION));
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                $isImage = in_array($extension, $imageExtensions);

                if ($isImage) {
                    $btn_view = "<a href='" . base_url($row['filedirectory']) . "' 
                        class='btn btn-sm btn-info' title='Preview' target='_blank'>
                        <i class='bx bx-images'></i>
                    </a>";
                } else {
                    $btn_view = "<button type='button' class='btn btn-sm btn-secondary' title='Preview (Only for images)' disabled>
                        <i class='bx bx-images'></i>
                    </button>";
                }

                $btn_download = "<a href='" . getURL('files/download/' . $row['fileid']) . "' 
                    class='btn btn-sm btn-success' title='Download' target='_blank'>
                    <i class='bx bx-download'></i>
                </a>";

                $btn_edit = "<button type='button' class='btn btn-sm btn-warning' title='Edit'
                    onclick=\"modalForm('Edit File', 'modal-lg', '" . getURL('files/form/' . $row['fileid']) . "', {identifier: this})\">
                    <i class='bx bx-edit-alt'></i>
                </button>";

                $btn_delete = "<button type='button' class='btn btn-sm btn-danger' title='Delete'
                    onclick=\"modalDelete('Delete File - " . htmlspecialchars($row['filerealname'] ?? $row['filename']) . "', {
                        'link':'" . getURL('files/delete') . "', 
                        'id':'" . $row['fileid'] . "', 
                        'pagetype':'table'
                    })\">
                    <i class='bx bx-trash'></i>
                </button>";

                $createdDate = !empty($row['created_date']) ? date('d-m-Y H:i', strtotime($row['created_date'])) : '-';

                $formattedData[] = [
                    $no++,
                    htmlspecialchars($row['filerealname'] ?? $row['filename']),
                    htmlspecialchars($row['filedirectory']),
                    $createdDate,
                    htmlspecialchars($row['created_by_name'] ?? '-'),
                    "<div class='action-buttons'>{$btn_view} {$btn_download} {$btn_edit} {$btn_delete}</div>"
                ];
            }

            $response = [
                'draw' => intval($draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $totalRecords,
                'data' => $formattedData
            ];

            return $this->response->setJSON($response);

        } catch (\Exception $e) {
            log_message('error', 'Files Datatable Error: ' . $e->getMessage());

            return $this->response->setJSON([
                'draw' => intval($this->request->getPost('draw') ?? 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show form for add/edit File
     */
    public function form($id = '')
    {
        try {
            $file = [];
            $form_type = 'add';

            if (!empty($id)) {
                $file = $this->mFiles->getById($id);
                $form_type = 'edit';

                if (empty($file)) {
                    http_response_code(404);
                    echo json_encode([
                        'error' => 'File tidak ditemukan',
                        'csrfToken' => csrf_hash()
                    ]);
                    return;
                }
            }

            $viewContent = view('master/file/v_form', [
                'form_type' => $form_type,
                'file' => $file
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
     * Upload single file (bukan chunking)
     */
    public function upload()
    {
        try {
            $file = $this->request->getFile('file');
            
            if (!$file->isValid()) {
                throw new Exception('File tidak valid: ' . $file->getErrorString());
            }

            if ($file->getSize() > $this->maxFileSize) {
                throw new Exception('File terlalu besar! Maksimal ukuran file adalah 100MB');
            }

            $originalName = $file->getClientName();
            $extension = $file->getClientExtension();
            $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

            // Generate unique filename dengan suffix jika ada yang sama
            $uniqueFilename = $this->mFiles->generateUniqueFilename($originalName);
            
            // Upload ke folder
            if (!$file->move($this->uploadPath, $uniqueFilename)) {
                throw new Exception('Gagal upload file');
            }

            log_message('info', "File uploaded successfully: {$uniqueFilename}");

            return $this->response->setJSON([
                'sukes' => 1,
                'filename' => $uniqueFilename,
                'filerealname' => $originalName,
                'filedirectory' => 'uploads/files/' . $uniqueFilename,
                'csrfToken' => csrf_hash()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Upload Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'sukes' => 0,
                'error' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ], 500);
        }
    }

    /**
     * Upload single chunk (2MB)
     */
    public function uploadChunk()
    {
        try {
            $chunkIndex = $this->request->getPost('dzchunkindex');
            $totalChunks = $this->request->getPost('dztotalchunkcount');
            $uuid = $this->request->getPost('dzuuid');
            $filename = $this->request->getPost('dzfilename');

            if (empty($uuid)) {
                throw new Exception('UUID tidak valid');
            }

            $chunkDir = $this->chunksPath . $uuid . '/';
            if (!is_dir($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }

            $chunkFile = $chunkDir . 'chunk_' . $chunkIndex;

            $file = $this->request->getFile('file');
            if (!$file->isValid()) {
                throw new Exception('File upload error: ' . $file->getErrorString());
            }

            $file->move($chunkDir, 'chunk_' . $chunkIndex);

            log_message('info', "Chunk uploaded: {$uuid}/chunk_{$chunkIndex} (" . ($chunkIndex + 1) . "/{$totalChunks})");

                return $this->response->setJSON([
                'sukes' => 1,
                'message' => 'Chunk uploaded successfully',
                'chunkIndex' => $chunkIndex,
                'totalChunks' => $totalChunks
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Upload Chunk Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'sukes' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Merge all chunks into one file
     */
    public function mergeChunks()
    {
        try {
            $uuid = $this->request->getPost('dzuuid');
            $filename = $this->request->getPost('dzfilename');
            $totalChunks = $this->request->getPost('dztotalchunkcount');

            if (empty($uuid)) {
                throw new Exception('UUID tidak valid');
            }

            $chunkDir = $this->chunksPath . $uuid . '/';

            if (!is_dir($chunkDir)) {
                throw new Exception('Chunk directory not found');
            }

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

            $uniqueFilename = $this->mFiles->generateUniqueFilename($nameWithoutExt . '.' . $extension);
            $finalPath = $this->uploadPath . $uniqueFilename;

            $finalFile = fopen($finalPath, 'wb');

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFile = $chunkDir . 'chunk_' . $i;
                
                if (!file_exists($chunkFile)) {
                    throw new Exception('Chunk ' . $i . ' tidak ditemukan');
                }

                $chunkContent = file_get_contents($chunkFile);
                fwrite($finalFile, $chunkContent);
            }

            fclose($finalFile);

            $finalSize = filesize($finalPath);
            if ($finalSize > $this->maxFileSize) {
                unlink($finalPath);
                throw new Exception('File terlalu besar! Maksimal ukuran file adalah 100MB');
            }

            $this->deleteChunkDir($chunkDir);

            log_message('info', "File merged successfully: {$uniqueFilename}");

            return $this->response->setJSON([
                'sukes' => 1,
                'filename' => $uniqueFilename,
                'filerealname' => $filename,
                'filedirectory' => 'uploads/files/' . $uniqueFilename
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Merge Chunks Error: ' . $e->getMessage());
            return $this->response->setJSON([
                'sukes' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store file data to database
     */
    public function store()
    {
        $res = [];
        $this->db->transBegin();

        try {
            $formType = $this->request->getPost('form_type');
            $fileId = $this->request->getPost('id');
            $filename = $this->request->getPost('filename');
            $filerealname = $this->request->getPost('filerealname');
            $filedirectory = $this->request->getPost('filedirectory');

            if ($formType === 'edit') {
                if (empty($fileId)) {
                    throw new Exception('ID tidak valid');
                }

                $existingFile = $this->mFiles->getById($fileId);
                if (empty($existingFile)) {
                    throw new Exception('File tidak ditemukan');
                }

                $updateData = [
                    'update_by' => getSession('userid'),
                    'update_date' => date('Y-m-d H:i:s')
                ];

                // Jika ada file baru diupload
                if (!empty($filename) && !empty($filedirectory)) {
                    // Hapus file lama jika ada
                    if (!empty($existingFile['filedirectory'])) {
                        $oldFilePath = FCPATH . $existingFile['filedirectory'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }

                    $updateData['filename'] = $filename;
                    $updateData['filerealname'] = $filerealname ?? $filename;
                    $updateData['filedirectory'] = $filedirectory;
                }

                $this->mFiles->edit($updateData, $fileId);

                $message = 'File berhasil diupdate';
            } else {
                if (empty($filename) || empty($filedirectory)) {
                    throw new Exception('File dan direktori wajib diisi');
                }

                $storeData = [
                    'filename' => $filename,
                    'filerealname' => $filerealname ?? $filename,
                    'filedirectory' => $filedirectory,
                    'created_by' => getSession('userid'),
                    'created_date' => date('Y-m-d H:i:s'),
                    'update_by' => getSession('userid'),
                    'update_date' => date('Y-m-d H:i:s'),
                    'isactive' => true
                ];

                $fileId = $this->mFiles->store($storeData);

                if (!$fileId) {
                    throw new Exception('Gagal menyimpan file');
                }

                $message = 'File berhasil disimpan';
            }

            $this->db->transCommit();
            $res = [
                'sukes' => 1,
                'pesan' => $message,
                'csrfToken' => csrf_hash()
            ];

        } catch (Exception $e) {
            $this->db->transRollback();
            $res = [
                'sukes' => 0,
                'pesan' => $e->getMessage(),
                'csrfToken' => csrf_hash()
            ];
        }

        echo json_encode($res);
    }

    /**
     * Delete File
     */
    public function delete()
    {
        $fileId = $this->request->getPost('id');
        $res = [];
        $this->db->transBegin();

        try {
            if (empty($fileId)) {
                throw new Exception("ID File tidak ditemukan!");
            }

            $file = $this->mFiles->getById($fileId);

            if (empty($file)) {
                throw new Exception("File tidak terdaftar di sistem!");
            }

            $filePath = FCPATH . $file['filedirectory'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->mFiles->destroy($fileId);

            $res = [
                'sukes' => '1',
                'pesan' => 'File berhasil dihapus!',
                'dbError' => db_connect()->error()
            ];
            $this->db->transCommit();

        } catch (Exception $e) {
            $res = [
                'sukes' => '0',
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
     * Download File
     */
    public function download($id)
    {
        try {
            $file = $this->mFiles->getById($id);

            if (empty($file)) {
                return redirect()->to('files')->with('error', 'File tidak ditemukan');
            }

            $filePath = FCPATH . $file['filedirectory'];

            if (!file_exists($filePath)) {
                return redirect()->to('files')->with('error', 'File tidak ditemukan di server');
            }

            $filename = $file['filerealname'] ?? $file['filename'];

            return $this->response->download($filePath, null)
                ->setFileName($filename);

        } catch (\Exception $e) {
            log_message('error', 'Download Error: ' . $e->getMessage());
            return redirect()->to('files')->with('error', 'Gagal mendownload file');
        }
    }

    /**
     * View File (for images - show in modal)
     */
    public function view($id)
    {
        try {
            $file = $this->mFiles->getById($id);

            if (empty($file)) {
                http_response_code(404);
                echo json_encode([
                    'error' => 'File tidak ditemukan',
                    'csrfToken' => csrf_hash()
                ]);
                return;
            }

            // Get created_by_name
            $user = $this->db->table('msuser')
                ->select('fullname')
                ->where('id', $file['created_by'])
                ->get()
                ->getRowArray();
            $file['created_by_name'] = $user['fullname'] ?? '-';

            $filePath = FCPATH . $file['filedirectory'];
            $fileUrl = base_url($file['filedirectory']);

            $extension = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION));
            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            $isImage = in_array($extension, $imageExtensions);

            $viewContent = view('master/file/v_view', [
                'file' => $file,
                'fileUrl' => $fileUrl,
                'isImage' => $isImage
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
     * Clean up chunks directory
     */
    private function deleteChunkDir($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteChunkDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Clean old chunks (for cleanup on failure)
     */
    public function cleanupChunks()
    {
        $uuid = $this->request->getPost('uuid');
        
        if (!empty($uuid)) {
            $chunkDir = $this->chunksPath . $uuid . '/';
            $this->deleteChunkDir($chunkDir);
        }

        return $this->response->setJSON(['sukes' => 1]);
    }
}
