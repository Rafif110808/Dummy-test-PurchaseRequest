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
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
                <button id="btn-export" class="btn btn-success">
                    <i class="bx bx-download"></i>
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
        $('#btn-filter').on('click', function() {
            console.log('Filter clicked');
            tbl.ajax.reload();
        });

        // Reset Filter Button Click
        $('#btn-reset-filter').on('click', function() {
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

    // EXPORT DENGAN FILTER
    let isExporting = false;
    let currentXHR = null;
    let safetyTimeout = null;
    let pendingTimeouts = [];

    $('#btn-export').on('click', function () {
        if (isExporting) return;

        isExporting = true;
        const $btn = $(this);
        const originalHtml = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> <span>Exporting...</span>');

        let limit = 500;
        let allData = [];
        let offset = 0;
        let totalFetched = 0;
        let isCancelled = false;

        // Ambil nilai filter
        const filterStartDate = $('#filter_start_date').val();
        const filterEndDate = $('#filter_end_date').val();
        const filterSupplier = $('#filter_supplier').val();

        console.log('Export with filters:', { filterStartDate, filterEndDate, filterSupplier });

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

        safetyTimeout = setTimeout(function() {
            console.log(' SAFETY TIMEOUT TRIGGERED');
            isCancelled = true;
            
            if (currentXHR) {
                currentXHR.abort();
                currentXHR = null;
            }
            
            $('#toast-icon').removeClass('bx-download').addClass('bx-info-circle').css('color', '#ffc107');
            $('#toast-title').text('Auto Closed');
            $('#toast-status').text('Export completed (forced)');
            $('#toast-bar').css('background', '#ffc107').css('width', '100%');
            
            const t = setTimeout(reset, 1500);
            pendingTimeouts.push(t);
        }, 10000);

        function update(percent, status, info) {
            if (isCancelled) return;
            $('#toast-bar').css('width', percent + '%');
            $('#toast-percent').text(percent + '%');
            $('#toast-status').text(status);
            $('#toast-info').text(info);
        }

        function reset() {
            if (safetyTimeout) {
                clearTimeout(safetyTimeout);
                safetyTimeout = null;
            }

            pendingTimeouts.forEach(t => clearTimeout(t));
            pendingTimeouts = [];

            if (currentXHR) {
                currentXHR.abort();
                currentXHR = null;
            }

            $(document).off('click', '#toast-close');

            isExporting = false;
            isCancelled = true;
            
            $('.export-toast').addClass('closing');
            setTimeout(() => {
                $('#export-toast').hide().html('');
            }, 300);
            
            $btn.prop('disabled', false).html(originalHtml);
            allData = [];
        }

        function cancel() {
            isCancelled = true;
            
            if (safetyTimeout) {
                clearTimeout(safetyTimeout);
                safetyTimeout = null;
            }

            pendingTimeouts.forEach(t => clearTimeout(t));
            pendingTimeouts = [];
            
            if (currentXHR) {
                currentXHR.abort();
                currentXHR = null;
            }
            
            $('#toast-icon').removeClass('bx-download').addClass('bx-x-circle').css('color', '#dc3545');
            $('#toast-title').text('Cancelled');
            $('#toast-status').text('Export cancelled');
            $('#toast-bar').css('background', '#dc3545').css('width', '100%');
            
            const t = setTimeout(reset, 1500);
            pendingTimeouts.push(t);
        }

        $(document).off('click', '#toast-close').on('click', '#toast-close', function() {
            if (!isCancelled) {
                cancel();
            }
        });

        function fetch() {
            if (isCancelled) return;

            if (currentXHR) currentXHR.abort();

            currentXHR = $.ajax({
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
                    if (isCancelled) return;

                    currentXHR = null;

                    if (res.error) {
                        alert('Error: ' + res.error);
                        reset();
                        return;
                    }

                    if (res.data && res.data.length > 0) {
                        allData = allData.concat(res.data);
                        totalFetched += res.data.length;
                        offset += limit;

                        const p = Math.min(Math.floor((totalFetched / (totalFetched + limit)) * 40), 40);
                        update(p, 'Fetching data...', totalFetched + ' records collected');

                        const t = setTimeout(fetch, 100);
                        pendingTimeouts.push(t);
                    } else {
                        update(40, 'Processing...', totalFetched + ' records ready');
                        const t = setTimeout(generate, 200);
                        pendingTimeouts.push(t);
                    }
                },
                error: function (xhr, status) {
                    currentXHR = null;
                    if (status === 'abort' || isCancelled) return;
                    alert('Failed to fetch data');
                    reset();
                }
            });
        }

        function generate() {
            if (isCancelled) return;

            if (allData.length === 0) {
                alert('No data to export');
                reset();
                return;
            }

            update(50, 'Creating file...', 'Generating Excel...');

            if (currentXHR) currentXHR.abort();

            currentXHR = $.ajax({
                url: '<?= site_url('purchase-request/export-excel-all') ?>',
                type: 'POST',
                data: JSON.stringify({ data: allData }),
                contentType: 'application/json',
                timeout: 120000,
                xhrFields: { responseType: 'blob' },
                success: function (blob, status, xhr) {
                    if (isCancelled) return;

                    currentXHR = null;
                    update(90, 'Almost done...', 'Preparing download');

                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'All_PR_<?= date("Ymd_His") ?>.xlsx';

                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        const matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }

                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = filename;
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();

                    const t = setTimeout(function() {
                        if (isCancelled) return;

                        update(100, 'Complete!', allData.length + ' records exported');
                        $('#toast-icon').removeClass('bx-download').addClass('bx-check-circle').css('color', '#28a745');
                        $('#toast-bar').css('background', '#28a745');
                        
                        $(document).off('click', '#toast-close').on('click', '#toast-close', function() {
                            if (document.body.contains(link)) {
                                document.body.removeChild(link);
                            }
                            window.URL.revokeObjectURL(url);
                            reset();
                        });

                        const autoClose = setTimeout(function() {
                            if (isExporting && !isCancelled) {
                                if (document.body.contains(link)) {
                                    document.body.removeChild(link);
                                }
                                window.URL.revokeObjectURL(url);
                                reset();
                            }
                        }, 3000);
                        pendingTimeouts.push(autoClose);
                    }, 150);
                    pendingTimeouts.push(t);
                },
                error: function (xhr, status) {
                    currentXHR = null;
                    if (status === 'abort' || isCancelled) return;

                    $('#toast-icon').removeClass('bx-download').addClass('bx-error-circle').css('color', '#dc3545');
                    $('#toast-title').text('Failed');
                    $('#toast-status').text('Export error');
                    $('#toast-bar').css('background', '#dc3545');
                    
                    const t = setTimeout(function() {
                        alert('Export failed');
                        reset();
                    }, 1000);
                    pendingTimeouts.push(t);
                }
            });
        }

        fetch();
    });

    $(window).on('beforeunload', function () {
        if (isExporting && currentXHR) {
            currentXHR.abort();
            
            if (safetyTimeout) {
                clearTimeout(safetyTimeout);
                safetyTimeout = null;
            }

            pendingTimeouts.forEach(t => clearTimeout(t));
            pendingTimeouts = [];
            
            return 'Export running';
        }
    });
</script>