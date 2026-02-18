<style>
    .dropzone {
        border: 2px dashed #0087F7;
        border-radius: 10px;
        background: white;
        min-height: 150px;
        padding: 20px;
        cursor: pointer;
        text-align: center;
        transition: all 0.3s ease;
    }
    
    .dropzone:hover {
        border-color: #0056b3;
        background: #f8f9fa;
    }
    
    .dropzone.dz-clickable {
        cursor: pointer;
    }
    
    .dropzone .dz-message {
        font-size: 1.1em;
        font-weight: 500;
        color: #6c757d;
    }
    
    .upload-progress {
        margin-top: 15px;
        display: none;
    }
    
    .upload-progress .progress {
        height: 20px;
        background-color: #e9ecef;
        border-radius: 5px;
        overflow: hidden;
    }
    
    .upload-progress .progress-bar {
        height: 100%;
        background-color: #007bff;
        transition: width 0.3s ease;
        line-height: 20px;
        color: white;
        font-size: 12px;
    }
    
    .upload-progress .progress-text {
        margin-top: 5px;
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .upload-status {
        margin-top: 15px;
        padding: 10px;
        border-radius: 5px;
        display: none;
    }
    
    .upload-status.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .upload-status.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .upload-status.info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .btn-group-upload {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
    }
    
    .btn-group-upload .btn {
        padding: 0.5rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 500;
    }
</style>

<form id="form-files" method="post">
    <input type="hidden" name="form_type" id="form-type" value="<?= $form_type ?>">
    <input type="hidden" name="id" id="id" value="<?= isset($file['fileid']) ? $file['fileid'] : '' ?>">
    <input type="hidden" name="filename" id="filename" value="<?= isset($file['filename']) ? $file['filename'] : '' ?>">
    <input type="hidden" name="filerealname" id="filerealname" value="<?= isset($file['filerealname']) ? $file['filerealname'] : '' ?>">
    <input type="hidden" name="filedirectory" id="filedirectory" value="<?= isset($file['filedirectory']) ? $file['filedirectory'] : '' ?>">
    
    <div class="mb-3">
        <div class="dropzone" id="file-dropzone">
            <div class="dz-message">
                <i class="bx bx-cloud-upload" style="font-size: 2.5rem; color: #6c757d;"></i>
                <br>
                <span>Drag & drop file di sini atau klik untuk memilih</span>
                <br>
                <small class="text-muted">Max: 100MB (chunk 2MB)</small>
            </div>
        </div>
        
        <div class="upload-progress" id="upload-progress">
            <div class="progress">
                <div class="progress-bar" role="progressbar" style="width: 0%;" id="progress-bar">0%</div>
            </div>
            <div class="progress-text" id="progress-text">Mengupload chunk 0/0...</div>
        </div>
        
        <div class="upload-status" id="upload-status"></div>
    </div>
    

    
    <div class="btn-group-upload">
        <button type="button" class="btn btn-secondary" onclick="$('#modaldetail').modal('hide');">
            Cancel
        </button>
        <button type="button" class="btn btn-primary" id="btn-upload">
            <i class="bx bx-upload"></i> Upload
        </button>
    </div>
</form>

<script>
    var selectedFile = null;
    var isUploading = false;
    var csrfToken = '<?= csrf_token() ?>';
    var CHUNK_SIZE = 2097152; // 2MB
    var MAX_FILE_SIZE = 104857600; // 100MB
    
    $(document).ready(function() {
        initDropzone();
    });
    
    function initDropzone() {
        var dropzone = document.getElementById('file-dropzone');
        var fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.style.display = 'none';
        fileInput.id = 'file-input';
        document.body.appendChild(fileInput);
        
        // Click to open file browser
        dropzone.addEventListener('click', function() {
            fileInput.click();
        });
        
        // Drag and drop events
        dropzone.addEventListener('dragover', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '#0056b3';
            dropzone.style.background = '#f8f9fa';
        });
        
        dropzone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '#0087F7';
            dropzone.style.background = 'white';
        });
        
        dropzone.addEventListener('drop', function(e) {
            e.preventDefault();
            dropzone.style.borderColor = '#0087F7';
            dropzone.style.background = 'white';
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                handleFile(e.dataTransfer.files[0]);
            }
        });
        
        // File selected via click
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                handleFile(this.files[0]);
            }
        });
        
        // Upload button click
        $('#btn-upload').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (selectedFile) {
                uploadWithChunking();
            } else {
                showStatus('Pilih file terlebih dahulu', 'error');
            }
        });
    }
    
    function handleFile(file) {
        if (file.size > MAX_FILE_SIZE) {
            showStatus('File terlalu besar! Maksimal ukuran file adalah 100MB', 'error');
            selectedFile = null;
            document.getElementById('file-input').value = '';
            return;
        }
        
        selectedFile = file;
        
        var dropzone = document.getElementById('file-dropzone');
        dropzone.querySelector('.dz-message').innerHTML = 
            '<i class="bx bx-file" style="font-size: 2rem; color: #28a745;"></i><br>' +
            '<span style="color: #28a745; font-weight: 600;">' + file.name + '</span><br>' +
            '<small class="text-muted">' + formatBytes(file.size) + '</small>';
        
        $('#upload-progress').hide();
        document.getElementById('progress-bar').style.width = '0%';
        document.getElementById('progress-bar').textContent = '0%';
        showStatus('File dipilih. Klik Upload untuk memulai.', 'info');
    }
    
    function formatBytes(bytes, decimals = 2) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var dm = decimals < 0 ? 0 : decimals;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
    }
    
    async function uploadWithChunking() {
        if (!selectedFile || isUploading) return;
        
        isUploading = true;
        var fileSize = selectedFile.size;
        var totalChunks = Math.ceil(fileSize / CHUNK_SIZE);
        var uuid = generateUUID();
        
        $('#upload-status').hide();
        $('#upload-progress').show();
        $('#btn-upload').prop('disabled', true);
        
        try {
            for (var chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                if (!isUploading) {
                    throw new Error('Upload dibatalkan');
                }
                
                var start = chunkIndex * CHUNK_SIZE;
                var end = Math.min(start + CHUNK_SIZE, fileSize);
                var chunk = selectedFile.slice(start, end);
                
                $('#progress-text').text('Mengupload chunk ' + (chunkIndex + 1) + '/' + totalChunks + '...');
                
                await uploadChunk(chunk, chunkIndex, totalChunks, uuid, selectedFile.name);
                
                var progress = Math.round(((chunkIndex + 1) / totalChunks) * 100);
                var progressBar = document.getElementById('progress-bar');
                if (progressBar) {
                    progressBar.style.width = progress + '%';
                    progressBar.textContent = progress + '%';
                }
            }
            
            $('#progress-text').text('Menggabungkan chunk...');
            await mergeChunks(uuid, selectedFile.name, totalChunks);
            
            showStatus('File berhasil diupload!', 'success');
            
            setTimeout(function() {
                saveToDatabase();
            }, 1000);
            
        } catch (error) {
            if (error.message !== 'Upload dibatalkan') {
                console.error('Upload error:', error);
                showStatus('Error: ' + error.message, 'error');
            }
            $('#btn-upload').prop('disabled', false);
        } finally {
            isUploading = false;
        }
    }
    
    function uploadChunk(chunk, chunkIndex, totalChunks, uuid, filename) {
        return new Promise(function(resolve, reject) {
            var formData = new FormData();
            formData.append('file', chunk);
            formData.append('dzchunkindex', chunkIndex);
            formData.append('dztotalchunkcount', totalChunks);
            formData.append('dzuuid', uuid);
            formData.append('dzfilename', filename);
            formData.append(csrfToken, decrypter($("#csrf_token").val()));
            
            $.ajax({
                url: '<?= getURL('files/upload_chunk') ?>',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.sukes === 1) {
                        resolve(response);
                    } else {
                        reject(new Error(response.error || 'Gagal upload chunk'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }
    
    function mergeChunks(uuid, filename, totalChunks) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '<?= getURL('files/merge_chunks') ?>',
                type: 'POST',
                data: {
                    dzuuid: uuid,
                    dzfilename: filename,
                    dztotalchunkcount: totalChunks,
                    [csrfToken]: decrypter($("#csrf_token").val())
                },
                dataType: 'json',
                success: function(response) {
                    if (response.sukes === 1) {
                        document.getElementById('filename').value = response.filename;
                        document.getElementById('filerealname').value = response.filerealname;
                        document.getElementById('filedirectory').value = response.filedirectory;
                        resolve(response);
                    } else {
                        reject(new Error(response.error || 'Gagal menggabungkan chunk'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error(error));
                }
            });
        });
    }
    
    function saveToDatabase() {
        var formType = document.getElementById('form-type').value;
        var id = document.getElementById('id').value;
        var filename = document.getElementById('filename').value;
        var filerealname = document.getElementById('filerealname').value;
        var filedirectory = document.getElementById('filedirectory').value;
        
        var postData = {
            form_type: formType,
            id: id,
            filename: filename,
            filerealname: filerealname,
            filedirectory: filedirectory
        };
        postData[csrfToken] = decrypter($("#csrf_token").val());
        
        $.ajax({
            url: '<?= getURL('files/store') ?>',
            type: 'POST',
            data: postData,
            dataType: 'json',
            success: function(res) {
                if (res.sukes === 1) {
                    showStatus('Data berhasil disimpan!', 'success');
                    
                    setTimeout(function() {
                        $('#modaldetail').modal('hide');
                        $('.table-master').DataTable().ajax.reload();
                    }, 1000);
                } else {
                    showStatus(res.pesan || 'Gagal menyimpan data', 'error');
                    $('#btn-upload').prop('disabled', false);
                }
                
                $("#csrf_token").val(encrypter(res.csrfToken));
            },
            error: function(xhr, status, error) {
                showStatus('Error: ' + error, 'error');
                $('#btn-upload').prop('disabled', false);
            }
        });
    }
    
    function showStatus(message, type) {
        $('#upload-status')
            .show()
            .removeClass('success error info')
            .addClass(type)
            .html('<i class="bx ' + (type === 'success' ? 'bx-check-circle' : type === 'error' ? 'bx-error-circle' : 'bx-info-circle') + '"></i> ' + message);
    }
    
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            var r = Math.random() * 16 | 0,
                v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    $('#modaldetail').on('hidden.bs.modal', function() {
        selectedFile = null;
        isUploading = false;
        var progressBar = document.getElementById('progress-bar');
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
        }
        var uploadProgress = document.getElementById('upload-progress');
        if (uploadProgress) {
            uploadProgress.style.display = 'none';
        }
    });
</script>
