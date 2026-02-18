<form id="importexcel" style="padding-inline: 0px;">
    <div class="row">
        <div>
            <div class="form-group">
                <label class="required">Excel File</label>
                <input type="file" name="excelfile" id="excelfile" accept=".xlsx, .xls" class="form-input" style="padding: 8px;pointer-events: unset !important;">
            </div>
        </div>
    </div>
    
    <!-- Progress Area -->
    <div id="loading-alltrans" class="hiding">
        <h4>
            <i class='bx bx-loader-circle bx-spin text-info'></i> Processing <span class="text-primary" id="totalsent">0</span> / <span id="alltotals" class="text-primary">0</span>
        </h4>
        <p class="text-muted" style="font-size: 12px; margin-top: 8px;">
            <span id="batch-info">Preparing...</span>
        </p>
    </div>
    
    <div class="modal-footer dflex" style="justify-content: space-between !important;">
        <button style="margin: 0 !important;" class="btn btn-info dflex align-center justify-center" type="button" onclick="downloadTemplate()" id="btn-template">
            <i class="bx bx-download margin-r-2"></i>
            <span class="fw-normal fs-7">Template</span>
        </button>
        <div style="margin-left: 0 !important; margin-right: 0 !important;" class="dflex">
            <button class="btn btn-warning dflex button-import align-center margin-r-2" type="button" id="btn-cancel">
                <i class="bx bx-x margin-r-2"></i>
                <span class="fw-normal fs-7">Cancel</span>
            </button>
            <button class="btn btn-primary dflex button-import align-center" type="submit" id="btn-process">
                <i class="bx bx-check margin-r-2"></i>
                <span class="fw-normal fs-7">Process</span>
            </button>
        </div>
    </div>
</form>

<script>
    // ===== GLOBAL STATE MANAGEMENT =====
    let isImporting = false;
    let isCancelled = false;
    let currentBatch = 0;
    let totalBatches = 0;
    let undfhpr = 0;
    let activeAjaxRequest = null; // Track active AJAX

    function downloadTemplate() {
        var url = '<?= base_url('public/downloadable/Template Purchase Request.xlsx') ?>';
        window.location.href = url;
    }

    /**
     * Cancel import process - IMPROVED
     */
    function cancelImport() {
        if (!isImporting) {
            close_modal('modaldetail');
            return;
        }

        console.log('🚫 Cancel requested');
        
        // Set cancel flag IMMEDIATELY
        isCancelled = true;
        
        // Abort active AJAX request if any
        if (activeAjaxRequest) {
            console.log('⚠️ Aborting active AJAX request');
            activeAjaxRequest.abort();
            activeAjaxRequest = null;
        }
        
        // Update UI immediately
        $("#batch-info").text('Cancelling...');
        $("#btn-cancel").attr('disabled', 'disabled').html('<i class="bx bx-loader bx-spin"></i> Cancelling...');
        
        // Show notification
        showNotif("error", "Import dibatalkan. Menunggu batch saat ini selesai...");
        
        // Force reset after 2 seconds (safety timeout)
        setTimeout(() => {
            if (isCancelled) {
                console.log('⏰ Force reset after timeout');
                forceReset();
            }
        }, 2000);
    }

    /**
     * Force reset import state
     */
    function forceReset() {
        isImporting = false;
        isCancelled = true;
        
        // Abort any pending request
        if (activeAjaxRequest) {
            activeAjaxRequest.abort();
            activeAjaxRequest = null;
        }

        // Reset UI
        $("#loading-alltrans").addClass('hiding');
        $("#batch-info").text('Cancelled');
        
        // Enable buttons
        $(".button-import").removeAttr('disabled');
        $("#btn-template").removeAttr('disabled');
        $("#btn-cancel").html('<i class="bx bx-x margin-r-2"></i><span class="fw-normal fs-7">Close</span>');
        $("#btn-process").removeAttr('disabled');
        $('#excelfile').removeAttr('disabled').val('');
        
        console.log('🔄 Force reset completed');
        
        // Auto close modal after 1 second
        setTimeout(() => {
            close_modal('modaldetail');
        }, 1000);
    }

    /**
     * Reset import state - NORMAL
     */
    function resetImportState() {
        isImporting = false;
        isCancelled = false;
        currentBatch = 0;
        totalBatches = 0;
        undfhpr = 0;
        activeAjaxRequest = null;

        // Reset UI
        $("#loading-alltrans").addClass('hiding');
        $("#totalsent").text('0');
        $("#alltotals").text('0');
        $("#batch-info").text('Preparing...');
        
        // Enable buttons
        $(".button-import").removeAttr('disabled');
        $("#btn-template").removeAttr('disabled');
        $("#btn-cancel").html('<i class="bx bx-x margin-r-2"></i><span class="fw-normal fs-7">Cancel</span>');
        $("#btn-process").removeAttr('disabled');
        $('#excelfile').removeAttr('disabled').val('');
        
        console.log('🔄 Import state reset');
    }

    /**
     * Convert Date to Y-m-d format
     */
    function convertToYMD(val) {
        if (!val) return '';

        if (val instanceof Date) {
            var y = val.getFullYear();
            var m = String(val.getMonth() + 1).padStart(2, '0');
            var d = String(val.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) {
            return val;
        }

        if (typeof val === 'string' && /^\d{1,2}\/\d{1,2}\/\d{4}$/.test(val)) {
            var parts = val.split('/');
            return parts[2] + '-' + parts[1].padStart(2, '0') + '-' + parts[0].padStart(2, '0');
        }

        if (typeof val === 'number') {
            var excelEpoch = new Date(1899, 11, 30);
            var date = new Date(excelEpoch.getTime() + val * 86400000);
            var y = date.getFullYear();
            var m = String(date.getMonth() + 1).padStart(2, '0');
            var d = String(date.getDate()).padStart(2, '0');
            return y + '-' + m + '-' + d;
        }

        return String(val);
    }

    /**
     * Parse Excel file
     */
    async function getFiles(e) {
        e = e || window.event;
        let file = e.target.files[0];

        if (!file) {
            console.log('No file selected');
            return;
        }

        // Reset state
        isImporting = true;
        isCancelled = false;
        activeAjaxRequest = null;

        let data = await file.arrayBuffer();

        // ✅ CHECK CANCEL SETELAH FILE READ
        if (isCancelled) {
            console.log('🚫 Cancelled during file reading');
            forceReset();
            return;
        }

        let wb = XLSX.read(data, {
            cellDates: true,
            dateNF: 'yyyy-mm-dd'
        });

        let ws = wb.Sheets[wb.SheetNames[0]];
        let last_key = Object.keys(ws);
        last_key.shift();
        last_key.pop();
        last_key = last_key.filter(key => key !== '!margins');

        let getlen = last_key[last_key.length - 1];
        getlen = getlen.replace(/[^0-9\.]/g, '');

        // AUTO-DETECT HEADER ROW
        let headerRow = 1;
        let colTranscode, colDate, colSupplier, colDesc;

        for (let r = 1; r <= 5; r++) {
            let rowVals = [];
            ['A','B','C','D','E'].forEach(col => {
                let cell = ws[col + r];
                if (cell && cell.v !== undefined) {
                    rowVals.push(String(cell.v).trim().toLowerCase());
                }
            });
            let hasTranscode = rowVals.some(v => v.includes('transcode') || v.includes('pr number') || v.includes('no'));
            if (hasTranscode) {
                headerRow = r;
                break;
            }
        }

        let headerMap = {};
        ['A','B','C','D','E','F'].forEach(col => {
            let cell = ws[col + headerRow];
            if (cell && cell.v !== undefined) {
                headerMap[String(cell.v).trim().toLowerCase()] = col;
            }
        });

        console.log('Header row detected at:', headerRow);
        console.log('Header map:', headerMap);

        colTranscode = headerMap['transcode'] || headerMap['pr number'] || headerMap['pr_number'] || 'B';
        colDate      = headerMap['tanggal'] || headerMap['date'] || headerMap['transdate'] || 'C';
        colSupplier  = headerMap['supplier'] || headerMap['supplier name'] || headerMap['suppliername'] || 'D';
        colDesc      = headerMap['description'] || headerMap['deskripsi'] || headerMap['desc'] || 'E';

        console.log('Columns → Transcode:', colTranscode, '| Date:', colDate, '| Supplier:', colSupplier, '| Desc:', colDesc);

        let dataStartRow = headerRow + 1;
        let arr = [];
        let offset = 500; // Batch size
        let keys = 0;
        let batchNumber = 0;

        // Hitung total valid rows
        let totalValidRows = 0;
        for (let o = dataStartRow; o <= getlen * 1; o++) {
            if (!ws[colTranscode + o] || ws[colTranscode + o].v === undefined) continue;
            let transcodeVal = String(ws[colTranscode + o].v).trim();
            if (transcodeVal === '') continue;
            totalValidRows++;
        }

        $("#alltotals").text(formatRupiah(totalValidRows));
        totalBatches = Math.ceil(totalValidRows / offset);
        
        console.log('Total valid rows:', totalValidRows);
        console.log('Batch size:', offset);
        console.log('Expected batches:', totalBatches);

        $("#batch-info").text(`Batch 0 of ${totalBatches}`);

        // ✅ CHECK CANCEL BEFORE LOOP
        if (isCancelled) {
            console.log('🚫 Cancelled before processing');
            forceReset();
            return;
        }

        // Process rows
        for (let o = dataStartRow; o <= getlen * 1; o++) {
            // ✅ CHECK CANCEL SETIAP ITERASI
            if (isCancelled) {
                console.log('🚫 Loop cancelled at row:', o);
                forceReset();
                return;
            }

            if (!ws[colTranscode + o] || ws[colTranscode + o].v === undefined) continue;

            let transcodeVal = String(ws[colTranscode + o].v).trim();
            if (transcodeVal === '') continue;

            keys++;

            let rawDate       = (ws[colDate + o] && ws[colDate + o].v !== undefined) ? ws[colDate + o].v : '';
            let supplier      = (ws[colSupplier + o] && ws[colSupplier + o].v !== undefined) ? String(ws[colSupplier + o].v).trim() : '';
            let desc          = (ws[colDesc + o] && ws[colDesc + o].v !== undefined) ? String(ws[colDesc + o].v).trim() : '';
            let convertedDate = convertToYMD(rawDate);

            arr.push([transcodeVal, convertedDate, supplier, desc]);

            if (keys == offset) {
                keys = 0;
                batchNumber++;
                console.log(`🔄 Sending batch ${batchNumber} with ${arr.length} records...`);
                
                // ✅ SEND DATA WITH AWAIT
                await sendData(arr, 'f', batchNumber);
                
                // ✅ CHECK CANCEL AFTER SEND
                if (isCancelled) {
                    console.log('🚫 Cancelled after batch', batchNumber);
                    forceReset();
                    return;
                }
                
                arr = [];
            }
        }

        // ✅ CHECK CANCEL BEFORE FINAL BATCH
        if (isCancelled) {
            console.log('🚫 Cancelled before final batch');
            forceReset();
            return;
        }

        // Send final batch
        if (arr.length > 0) {
            batchNumber++;
            console.log(`✅ Sending final batch ${batchNumber} with ${arr.length} records...`);
            await sendData(arr, 't', batchNumber);
        } else if (batchNumber > 0) {
            console.log(`✅ All batches sent. Total batches: ${batchNumber}`);
            await sendData([], 't', batchNumber);
        }

        // Normal completion
        if (!isCancelled) {
            isImporting = false;
        }
    }

    $(document).ready(function() {
        $("#importexcel").on('submit', function(e) {
            e.preventDefault();
            
            // Validate file
            let fileInput = $('#excelfile')[0];
            if (!fileInput.files || fileInput.files.length === 0) {
                showNotif('error', 'Silakan pilih file Excel terlebih dahulu');
                return false;
            }
            
            // Disable buttons
            $("#btn-process").attr('disabled', 'disabled');
            $("#btn-template").attr('disabled', 'disabled');
            $("#btn-cancel").html('<i class="bx bx-x margin-r-2"></i><span class="fw-normal fs-7">Cancel Import</span>');
            
            $("#loading-alltrans").removeClass('hiding');
            $('#excelfile').attr('disabled', 'disabled');
            
            // Trigger file read
            getFiles({ target: fileInput });
            
            return false;
        });
        
        // Cancel button handler
        $('#btn-cancel').on('click', function() {
            cancelImport();
        });
    });

    /**
     * Send data to server - IMPROVED with abort support
     */
    async function sendData(arr, isfinish = 'f', batchNum = 0) {
        // ✅ CHECK CANCEL BEFORE SEND
        if (isCancelled) {
            console.log('🚫 sendData skipped - cancelled');
            return;
        }

        if (arr.length === 0 && isfinish !== 't') {
            return;
        }

        currentBatch = batchNum;
        $("#batch-info").text(`Batch ${currentBatch} of ${totalBatches}`);

        await sleep(500); // Reduced delay untuk responsive cancel

        // ✅ CHECK CANCEL AFTER DELAY
        if (isCancelled) {
            console.log('🚫 sendData cancelled after delay');
            return;
        }

        let textproses = $("#totalsent").text();
        $("#totalsent").text(formatRupiah(exp_number(textproses) + arr.length));

        console.log(`📤 AJAX Request - Batch ${batchNum} | Records: ${arr.length} | IsFinish: ${isfinish}`);

        return new Promise((resolve, reject) => {
            // ✅ STORE AJAX REQUEST OBJECT
            activeAjaxRequest = $.ajax({
                url: '<?= base_url('purchase-request/importExcel') ?>',
                type: 'post',
                dataType: 'json',
                data: {
                    datas: JSON.stringify(arr),
                    <?= csrf_token() ?>: decrypter($("#csrf_token").val())
                },
                success: function(res) {
                    activeAjaxRequest = null; // Clear reference
                    
                    // ✅ CHECK CANCEL IN SUCCESS
                    if (isCancelled) {
                        console.log('🚫 Response ignored - cancelled');
                        resolve();
                        return;
                    }

                    console.log(`✅ Response - Batch ${batchNum} | Success: ${res.sukses} | Failed: ${res.undfhpr || 0}`);
                    
                    // Update CSRF token
                    if (res.csrfToken) {
                        $("#csrf_token").val(encrypter(res.csrfToken));
                    }
                    
                    undfhpr += (res.undfhpr || 0);

                    if (isfinish == 't') {
                        console.log('🎉 Import completed!');
                        isImporting = false;
                        
                        showNotif("success", res.pesan || "Data berhasil diimport");

                        if (undfhpr >= 1) {
                            showNotif("error", `${undfhpr} Purchase Request dilewatkan`);

                            if (res.undfhprarr && res.undfhprarr.length > 0) {
                                console.log('PR yang gagal:', res.undfhprarr);
                            }
                        }

                        setTimeout(() => {
                            resetImportState();
                            close_modal('modaldetail');
                            if (typeof tbl !== 'undefined') {
                                tbl.ajax.reload();
                            }
                        }, 1000);
                    }
                    
                    resolve();
                },
                error: function(xhr, status, error) {
                    activeAjaxRequest = null; // Clear reference
                    
                    // ✅ JANGAN ERROR KALAU ABORT
                    if (status === 'abort') {
                        console.log('🚫 AJAX aborted');
                        resolve(); // Resolve, bukan reject
                        return;
                    }
                    
                    console.error(`❌ Error - Batch ${batchNum}:`, error);
                    
                    isImporting = false;
                    
                    showNotif("error", "Gagal import data: " + error);
                    forceReset();
                    
                    reject(error);
                }
            });
        });
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
</script>