<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<style>
    /* Fix border menumpuk di DataTables header */
    table.dataTable>thead>tr>td {
        border-bottom: none !important;
    }

    .table-bordered thead td,
    .table-bordered thead th {
        border-bottom-width: 1px !important;
    }

    /* Styling untuk tombol print */
    .btn-info {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-info:hover {
        background-color: #138496;
        border-color: #117a8b;
        color: white;
    }

    /* Action buttons spacing */
    .action-buttons {
        display: inline-flex;
        gap: 0.25rem;
        justify-content: center;
    }

    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    /* Print button icon */
    .bx-printer {
        font-size: 1rem;
    }

    /* Filter Section - IMPROVED */
    .filter-section {
        background: #f8f9fa;
        padding: 1.25rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border: 1px solid #e9ecef;
    }

    .filter-row {
        display: flex;
        gap: 1rem;
        align-items: end;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 180px;
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        margin-bottom: 0.375rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: #495057;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
    }

    .filter-actions {
        display: flex;
        gap: 0.5rem;
        align-items: end;
    }

    .filter-actions .btn {
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Header Actions - IMPROVED */
    .header-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        align-items: center;
    }

    .header-actions .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        white-space: nowrap;
    }

    .header-actions .btn i {
        font-size: 1.125rem;
    }

    /* Button Consistent Styling */
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
        .filter-row {
            flex-direction: column;
        }

        .filter-group {
            min-width: 100%;
        }

        .filter-actions {
            width: 100%;
        }

        .filter-actions .btn {
            flex: 1;
        }

        .header-actions {
            flex-wrap: wrap;
        }
    }

    @media (max-width: 576px) {
        .header-actions {
            flex-direction: column;
            width: 100%;
        }

        .header-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Toast Export Style */
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }

        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .export-toast {
        position: fixed;
        top: 80px;
        right: 20px;
        width: 320px;
        background: white;
        border-left: 4px solid #17a2b8;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 16px;
        z-index: 9999;
        animation: slideInRight 0.3s ease-out;
    }

    .export-toast.closing {
        animation: slideOutRight 0.3s ease-in;
    }

    .toast-progress {
        background: #e9ecef;
        height: 6px;
        border-radius: 3px;
        overflow: hidden;
        margin-top: 10px;
    }

    .toast-progress-bar {
        background: #17a2b8;
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
    }

    /* Card header spacing */
    .card-header {
        padding: 1.25rem;
    }
</style>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header">
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Start Date</label>
                        <input type="date" id="filter_start_date" class="form-control form-control-sm">
                    </div>

                    <div class="filter-group">
                        <label>End Date</label>
                        <input type="date" id="filter_end_date" class="form-control form-control-sm">
                    </div>

                    <div class="filter-group">
                        <label>Supplier</label>
                        <select id="filter_supplier" class="form-control form-control-sm" style="width: 100%;">
                            <option value="">All Suppliers</option>
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button id="btn-filter" class="btn btn-primary btn-sm">
                            <i class="bx bx-filter-alt"></i>
                            <span>Filter</span>
                        </button>
                        <button id="btn-reset-filter" class="btn btn-secondary btn-sm">
                            <i class="bx bx-revision"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="header-actions">
                <a href="<?= getURL('purchase-request/add-page') ?>" class="btn btn-primary">
                    <i class="bx bx-plus-circle"></i>
                    <span>Add New</span>
                </a>
                <button class="btn btn-primary"
                    onclick="return modalForm('Import Purchase Request', 'modal-lg', '<?= getURL('purchase-request/formImport') ?>')">
                    <i class="bx bx-download"></i>
                    <span>Import</span>
                </button>
                <button id="btn-export" class="btn btn-success">
                    <i class="bx bx-upload"></i>
                    <span>Export</span>
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-master fs-7 w-100">
                    <thead>
                        <tr>
                            <td class="tableheader" style="width: 5%">No</td>
                            <td class="tableheader" style="width: 15%">PR Number</td>
                            <td class="tableheader" style="width: 15%">Request Date</td>
                            <td class="tableheader" style="width: 20%">Supplier</td>
                            <td class="tableheader" style="width: 30%">Description</td>
                            <td class="tableheader" style="width: 15%">Actions</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="export-toast" style="display: none;"></div>

<?= $this->include('template/v_footer') ?>

<script>
    var tbl;

    $(document).ready(function () {
        // Initialize Supplier Filter Select2
        $('#filter_supplier').select2({
            placeholder: 'All Suppliers',
            allowClear: true,
            width: '100%',
            ajax: {
                url: '<?= getURL('purchase-request/search-supplier') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            }
        });

        // Initialize DataTable
        initDataTable();

        // Filter Button Click
        $('#btn-filter').on('click', function () {
            console.log('Filter clicked');
            tbl.ajax.reload();
        });

        // Reset Filter Button Click
        $('#btn-reset-filter').on('click', function () {
            console.log('Reset filter clicked');
            $('#filter_start_date').val('');
            $('#filter_end_date').val('');
            $('#filter_supplier').val(null).trigger('change');
            tbl.ajax.reload();
        });
    });

    function initDataTable() {
        if ($.fn.DataTable.isDataTable('.table-master')) {
            $('.table-master').DataTable().destroy();
        }

        tbl = $('.table-master').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= site_url('purchase-request/datatable') ?>',
                type: 'POST',
                data: function (d) {
                    d.<?= csrf_token() ?> = $('meta[name="csrf-token"]').attr('content') || $('input[name="<?= csrf_token() ?>"]').val();

                    // Tambahkan filter parameters
                    d.filter_start_date = $('#filter_start_date').val();
                    d.filter_end_date = $('#filter_end_date').val();
                    d.filter_supplier = $('#filter_supplier').val();

                    console.log('Filter params:', {
                        start: d.filter_start_date,
                        end: d.filter_end_date,
                        supplier: d.filter_supplier
                    });
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    alert('Terjadi kesalahan saat memuat data');
                }
            },
            columns: [
                { data: 0, orderable: false, searchable: false, className: 'text-center' },
                { data: 1, orderable: true },
                { data: 2, orderable: true },
                { data: 3, orderable: true },
                { data: 4, orderable: true },
                { data: 5, orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Memuat data...',
                emptyTable: 'Tidak ada data Purchase Request',
                zeroRecords: 'Data tidak ditemukan'
            }
        });
    }

    // ===== EXPORT WITH CANCEL & ACTIVITY TIMEOUT =====
    let isExporting = false;
    let exportCancelled = false;
    let currentExportXHR = null;
    let exportTimeouts = [];
    let activityTimeout = null; // ← Timeout untuk detect stuck

    $('#btn-export').on('click', function () {
        if (isExporting) {
            console.log('⚠️ Export already running');
            return;
        }

        isExporting = true;
        exportCancelled = false;
        const $btn = $(this);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> <span>Exporting...</span>');

        let limit = 500;
        let allData = [];
        let offset = 0;
        let totalFetched = 0;

        const filterStartDate = $('#filter_start_date').val();
        const filterEndDate = $('#filter_end_date').val();
        const filterSupplier = $('#filter_supplier').val();

        console.log('Export with filters:', { filterStartDate, filterEndDate, filterSupplier });

        // ===== TOAST UI =====
        const toastHtml = `
    <div class="export-toast">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i id="toast-icon" class="bx bx-download" style="font-size: 20px; color: #17a2b8;"></i>
                <div>
                    <div id="toast-title" style="font-weight: 600; font-size: 13px; color: #212529;">Export Excel</div>
                    <div id="toast-status" style="font-size: 12px; color: #6c757d; margin-top: 2px;">Preparing...</div>
                </div>
            </div>
            <button id="toast-close" type="button" style="background: none; border: none; color: #6c757d; cursor: pointer; padding: 0; font-size: 18px; line-height: 1;">
                <i class="bx bx-x"></i>
            </button>
        </div>
        <div class="toast-progress">
            <div id="toast-bar" class="toast-progress-bar"></div>
        </div>
        <div style="display: flex; justify-content: space-between; margin-top: 6px;">
            <small id="toast-info" style="font-size: 11px; color: #6c757d;">Starting export...</small>
            <small id="toast-percent" style="font-size: 11px; color: #17a2b8; font-weight: 600;">0%</small>
        </div>
    </div>
`;

        $('#export-toast').html(toastHtml).show();

        // ===== START ACTIVITY TIMEOUT =====
        resetActivityTimeout();

        function resetActivityTimeout() {
            // Clear timeout lama
            if (activityTimeout) {
                clearTimeout(activityTimeout);
            }

            // Set timeout baru 10 detik
            // Kalau 10 detik tidak ada update, anggap stuck
            activityTimeout = setTimeout(() => {
                console.log('⚠️ No activity for 10 seconds - Force closing');
                forceCloseStuck();
            }, 10000);

            console.log('🔄 Activity timeout reset (10s)');
        }

        function clearActivityTimeout() {
            if (activityTimeout) {
                clearTimeout(activityTimeout);
                activityTimeout = null;
                console.log('✅ Activity timeout cleared');
            }
        }

        function update(percent, status, info) {
            if (exportCancelled) return;

            $('#toast-bar').css('width', percent + '%');
            $('#toast-percent').text(percent + '%');
            $('#toast-status').text(status);
            $('#toast-info').text(info);

            // ===== RESET TIMEOUT SETIAP ADA UPDATE =====
            resetActivityTimeout();
        }

        function reset() {
            // Abort current XHR if any
            if (currentExportXHR) {
                currentExportXHR.abort();
                currentExportXHR = null;
            }

            // Clear all timeouts
            exportTimeouts.forEach(t => clearTimeout(t));
            exportTimeouts = [];

            // Clear activity timeout
            clearActivityTimeout();

            // Remove event listeners
            $(document).off('click', '#toast-close');

            // Reset state
            isExporting = false;
            exportCancelled = true;

            // Hide toast
            $('.export-toast').addClass('closing');
            setTimeout(() => {
                $('#export-toast').hide().html('');
            }, 300);

            // Re-enable button
            $btn.prop('disabled', false).html(originalHtml);
            allData = [];

            console.log('🔄 Export state reset');
        }

        function forceCloseStuck() {
            console.log('⏰ Export stuck - Force closing toast');

            // Abort request
            if (currentExportXHR) {
                currentExportXHR.abort();
                currentExportXHR = null;
            }

            // Update UI
            $('#toast-icon').removeClass('bx-download').addClass('bx-error-circle').css('color', '#ffc107');
            $('#toast-title').text('Timeout');
            $('#toast-status').text('Export terlalu lama');
            $('#toast-bar').css('background', '#ffc107').css('width', '100%');

            // Tutup setelah 2 detik
            setTimeout(reset, 2000);

            // Notif
            showNotif('error', 'Export timeout - Proses terlalu lama');
        }

        function cancel() {
            if (!isExporting) return;

            exportCancelled = true;

            console.log('🚫 Export cancelled by user');

            if (currentExportXHR) {
                currentExportXHR.abort();
                currentExportXHR = null;
            }

            exportTimeouts.forEach(t => clearTimeout(t));
            exportTimeouts = [];

            // Clear activity timeout
            clearActivityTimeout();

            $('#toast-icon').removeClass('bx-download').addClass('bx-x-circle').css('color', '#dc3545');
            $('#toast-title').text('Cancelled');
            $('#toast-status').text('Export cancelled');
            $('#toast-bar').css('background', '#dc3545').css('width', '100%');

            setTimeout(reset, 1500);
        }

        // Cancel button handler
        $(document).off('click', '#toast-close').on('click', '#toast-close', function () {
            if (!isExporting) {
                reset();
                return;
            }

            if (!exportCancelled) {
                cancel();
            } else {
                reset();
            }
        });

        function fetch() {
            if (exportCancelled) {
                console.log('🚫 Fetch cancelled');
                return;
            }

            if (currentExportXHR) currentExportXHR.abort();

            currentExportXHR = $.ajax({
                url: '<?= site_url('purchase-request/get_chunk') ?>',
                type: 'GET',
                data: {
                    limit: limit,
                    offset: offset,
                    filter_start_date: filterStartDate,
                    filter_end_date: filterEndDate,
                    filter_supplier: filterSupplier
                },
                dataType: 'json',
                timeout: 30000,
                success: function (res) {
                    if (exportCancelled) {
                        console.log('🚫 Fetch response ignored - cancelled');
                        return;
                    }

                    currentExportXHR = null;

                    if (res.error) {
                        clearActivityTimeout();
                        showNotif('error', 'Error: ' + res.error);
                        reset();
                        return;
                    }

                    if (res.data && res.data.length > 0) {
                        allData = allData.concat(res.data);
                        totalFetched += res.data.length;
                        offset += limit;

                        const p = Math.min(Math.floor((totalFetched / (totalFetched + limit)) * 40), 40);

                        // ===== UPDATE (otomatis reset timeout) =====
                        update(p, 'Fetching data...', totalFetched + ' records collected');

                        const t = setTimeout(fetch, 100);
                        exportTimeouts.push(t);
                    } else {
                        // ===== UPDATE (otomatis reset timeout) =====
                        update(40, 'Processing...', totalFetched + ' records ready');

                        const t = setTimeout(generate, 200);
                        exportTimeouts.push(t);
                    }
                },
                error: function (xhr, status) {
                    currentExportXHR = null;
                    if (status === 'abort' || exportCancelled) {
                        console.log('🚫 Fetch aborted');
                        return;
                    }

                    clearActivityTimeout();
                    showNotif('error', 'Failed to fetch data');
                    reset();
                }
            });
        }

        function generate() {
            if (exportCancelled) {
                console.log('🚫 Generate cancelled');
                return;
            }

            if (allData.length === 0) {
                clearActivityTimeout();
                showNotif('error', 'No data to export');
                reset();
                return;
            }

            // ===== UPDATE (otomatis reset timeout) =====
            update(50, 'Creating file...', 'Generating Excel...');

            if (currentExportXHR) currentExportXHR.abort();

            currentExportXHR = $.ajax({
                url: '<?= site_url('purchase-request/export-excel-all') ?>',
                type: 'POST',
                data: JSON.stringify({ data: allData }),
                contentType: 'application/json',
                dataType: 'json',
                timeout: 120000,
                success: function (res) {
                    if (exportCancelled) {
                        console.log('🚫 Generate response ignored - cancelled');
                        return;
                    }

                    currentExportXHR = null;

                    // Check if response indicates error
                    if (!res || res.sukses === 0) {
                        clearActivityTimeout();
                        $('#toast-icon').removeClass('bx-download').addClass('bx-error-circle').css('color', '#dc3545');
                        $('#toast-title').text('Failed');
                        $('#toast-status').text(res?.pesan || 'Export error');
                        $('#toast-bar').css('background', '#dc3545');

                        setTimeout(function () {
                            showNotif('error', res?.pesan || 'Export failed');
                            reset();
                        }, 1000);
                        return;
                    }

                    // ===== UPDATE (otomatis reset timeout) =====
                    update(90, 'Downloading...', 'Almost done...');

                    // Decode base64 blob from JSON response
                    try {
                        const binaryString = atob(res.blob);
                        const bytes = new Uint8Array(binaryString.length);
                        for (let i = 0; i < binaryString.length; i++) {
                            bytes[i] = binaryString.charCodeAt(i);
                        }
                        const blob = new Blob([bytes], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });

                        // Download file
                        const url = window.URL.createObjectURL(blob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = res.filename || 'All_PR_<?= date("Ymd_His") ?>.xlsx';
                        link.style.display = 'none';
                        document.body.appendChild(link);
                        link.click();

                        // Cleanup
                        setTimeout(() => {
                            if (document.body.contains(link)) {
                                document.body.removeChild(link);
                            }
                            window.URL.revokeObjectURL(url);
                        }, 100);
                    } catch (decodeError) {
                        console.error('Blob decode error:', decodeError);
                        clearActivityTimeout();
                        showNotif('error', 'Gagal memproses file Excel');
                        reset();
                        return;
                    }

                    // ===== CLEAR ACTIVITY TIMEOUT (SELESAI!) =====
                    clearActivityTimeout();

                    // Update UI complete
                    update(100, 'Complete!', allData.length + ' records exported');
                    $('#toast-icon').removeClass('bx-download').addClass('bx-check-circle').css('color', '#28a745');
                    $('#toast-bar').css('background', '#28a745');

                    // Langsung reset setelah 1 detik
                    setTimeout(reset, 1000);
                },
                error: function (xhr, status) {
                    currentExportXHR = null;
                    if (status === 'abort' || exportCancelled) {
                        console.log('🚫 Generate aborted');
                        return;
                    }

                    clearActivityTimeout();

                    $('#toast-icon').removeClass('bx-download').addClass('bx-error-circle').css('color', '#dc3545');
                    $('#toast-title').text('Failed');
                    $('#toast-status').text('Export error');
                    $('#toast-bar').css('background', '#dc3545');

                    setTimeout(function () {
                        showNotif('error', 'Export failed');
                        reset();
                    }, 1000);
                }
            });
        }

        // Start fetching
        fetch();
    });

    // Cleanup on page unload
    $(window).on('beforeunload', function () {
        if (isExporting && !exportCancelled) {
            if (currentExportXHR) {
                currentExportXHR.abort();
            }
            exportTimeouts.forEach(t => clearTimeout(t));

            if (activityTimeout) {
                clearTimeout(activityTimeout);
            }

            return 'Export running';
        }
    })

</script>