<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>


<style>
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
</style>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <!-- Card Header -->
        <div class="card-header dflex align-center justify-between">
            <div>
                <h4 class="mb-0">Purchase Request</h4>
                <p class="text-muted fs-7 mb-0">Manage your purchase requests</p>
            </div>
            <div class="dflex align-center " style="gap : 0.75rem; ">

                <!-- Redirect ke halaman add -->
                <a href="<?= getURL('purchase-request/add-page') ?>" class="btn btn-primary dflex align-center">
                    <i class="bx bx-plus-circle margin-r-2"></i>
                    <span class="fw-normal fs-7">Add New</span>
                </a>
                <!-- Tombol Export Excel -->
                <button id="btn-export" class="btn btn-success dflex align-center">
                    <i class="bx bx-download margin-r-2"></i>
                    <span class="fw-normal fs-7">Export</span>
                </button>
            </div>
        </div>


        <!-- Card Body -->
        <div class="card mt-4 shadow-sm w-100 gap">
            <div class="card-body">
                <div class="table-responsive margin-t-14p">
                    <table class="table table-bordered table-master fs-7 w-100">
                        <thead>
                            <tr>
                                <td class="tableheader">No</td>
                                <td class="tableheader">PR Number</td>
                                <td class="tableheader">Request Date</td>
                                <td class="tableheader">Supplier</td>
                                <td class="tableheader">Description</td>
                                <td class="tableheader">Actions</td>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>

<!-- Modal Export Progress -->
<div class="modal fade" id="exportModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-download"></i> Export Excel
                </h5>
            </div>
            <div class="modal-body">
                <div style="text-align: center; padding: 20px 0;">
                    <i class="bx bx-loader-alt bx-spin" style="font-size: 48px; color: #28a745;" id="export-icon"></i>
                    <h6 id="export-status" style="margin-top: 20px; font-weight: 500;">Memulai export...</h6>
                    <p id="export-detail" style="color: #6c757d; font-size: 14px; margin-top: 5px;">0 records</p>

                    <div style="margin: 20px 0;">
                        <div style="background: #e9ecef; border-radius: 10px; overflow: hidden; height: 10px;">
                            <div id="export-progress"
                                style="background: #28a745; height: 100%; width: 0%; transition: width 0.3s;"></div>
                        </div>
                        <small id="export-percentage"
                            style="color: #6c757d; font-size: 12px; margin-top: 5px; display: block;">0%</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" id="btn-cancel-export">
                    <i class="bx bx-x"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>
<!--  AKHIR TAMBAHAN Modal -->

<script>
    var tbl;

    $(document).ready(function () {
        console.log('PR - delete-detail binding ready');
        $(document).on('click', '.btn-delete-detail', function (e) {
            console.log('PR delete-detail clicked', $(this).data('id'));
        });
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
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response:', xhr.responseText);
                    alert('Terjadi kesalahan saat memuat data');
                }
            },
            columns: [
                { data: 0, orderable: false, searchable: false },
                { data: 1, orderable: true },
                { data: 2, orderable: true },
                { data: 3, orderable: true },
                { data: 4, orderable: true },
                { data: 5, orderable: false, searchable: false }
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
    });

    //  Export Excel dengan Modal Progress + Cancel (Chunk 500)
    let isExporting = false;
    let currentXHR = null;

    $('#btn-export').on('click', function () {
        if (isExporting) {
            alert('Export sedang berjalan, mohon tunggu...');
            return;
        }

        isExporting = true;

        // Tampilkan modal
        const modal = new bootstrap.Modal(document.getElementById('exportModal'));
        modal.show();

        //  Set chunk size ke 500 (atau ambil dari dropdown kalau ada)
        let limit = parseInt($('#chunk-size').val()) || 500;
        let allData = [];
        let offset = 0;
        let totalFetched = 0;
        let isCancelled = false;
        let downloadLink = null;

        function resetExport() {
            isExporting = false;
            isCancelled = false;
            modal.hide();
            allData = [];
            currentXHR = null;
            downloadLink = null;

            // Reset UI
            $('#export-icon').removeClass('bx-check-circle bx-x-circle').addClass('bx-loader-alt bx-spin').css('color', '#28a745');
            $('#export-status').text('Memulai export...');
            $('#export-detail').text('0 records');
            $('#export-progress').css('background', '#28a745').css('width', '0%');
            $('#export-percentage').text('0%');
            $('#btn-cancel-export').prop('disabled', false).html('<i class="bx bx-x"></i> Cancel');
        }

        function cancelExport() {
            isCancelled = true;

            if (currentXHR) {
                currentXHR.abort();
                currentXHR = null;
            }

            if (downloadLink) {
                document.body.removeChild(downloadLink);
                downloadLink = null;
            }

            $('#export-icon').removeClass('bx-loader-alt bx-spin').addClass('bx-x-circle').css('color', '#dc3545');
            $('#export-status').text('Export dibatalkan');
            $('#export-detail').text('Proses dibatalkan oleh user');
            $('#export-progress').css('background', '#dc3545').css('width', '100%');
            $('#btn-cancel-export').prop('disabled', true);

            setTimeout(() => {
                resetExport();
            }, 1500);
        }

        // Event click untuk cancel
        $('#btn-cancel-export').off('click').on('click', function () {
                cancelExport();
        });

        function updateProgress(current, total, status, detail) {
            if (isCancelled) return;

            const percentage = total > 0 ? Math.round((current / total) * 100) : 0;
            $('#export-status').text(status);
            $('#export-detail').text(detail);
            $('#export-progress').css('width', percentage + '%');
            $('#export-percentage').text(percentage + '%');
        }

        function getChunk() {
            if (isCancelled) {
                resetExport();
                return;
            }

            if (currentXHR) {
                currentXHR.abort();
            }

            currentXHR = $.ajax({
                url: '<?= site_url('purchase-request/get_chunk') ?>',
                type: 'GET',
                data: { limit: limit, offset: offset },
                dataType: 'json',
                timeout: 30000,
                success: function (res) {
                    if (isCancelled) {
                        resetExport();
                        return;
                    }

                    currentXHR = null;

                    if (res.error) {
                        throw new Error(res.error);
                    }

                    if (res.data && res.data.length > 0) {
                        allData = allData.concat(res.data);
                        totalFetched += res.data.length;
                        offset += limit;

                        const fetchProgress = Math.min((totalFetched / (totalFetched + limit)) * 30, 30);
                        updateProgress(
                            fetchProgress,
                            100,
                            'Mengambil data dari database...',
                            totalFetched + ' records terkumpul'
                        );

                        setTimeout(getChunk, 100);
                    } else {
                        updateProgress(30, 100, 'Data terkumpul!', totalFetched + ' records. Membuat file Excel...');
                        setTimeout(exportToExcel, 500);
                    }
                },
                error: function (xhr, status, error) {
                    currentXHR = null;

                    if (status === 'abort' || isCancelled) {
                        return;
                    }

                    let errorMsg = 'Gagal mengambil data';
                    if (status === 'timeout') {
                        errorMsg = 'Request timeout';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error';
                    }

                    $('#export-icon').removeClass('bx-loader-alt bx-spin').addClass('bx-x-circle').css('color', '#dc3545');
                    $('#export-status').text('Error!');
                    $('#export-detail').text(errorMsg);
                    $('#export-progress').css('background', '#dc3545').css('width', '100%');
                    $('#btn-cancel-export').prop('disabled', true);

                    setTimeout(() => {
                        alert(errorMsg);
                        resetExport();
                    }, 2000);
                }
            });
        }

        function exportToExcel() {
            if (isCancelled) {
                resetExport();
                return;
            }

            if (allData.length === 0) {
                alert('Tidak ada data untuk di-export');
                resetExport();
                return;
            }

            updateProgress(40, 100, 'Membuat file Excel...', allData.length + ' records');

            if (currentXHR) {
                currentXHR.abort();
            }

            currentXHR = $.ajax({
                url: '<?= site_url('purchase-request/export-excel-all') ?>',
                type: 'POST',
                data: JSON.stringify({ data: allData }),
                contentType: 'application/json',
                timeout: 120000,
                xhrFields: {
                    responseType: 'blob'
                },
                xhr: function () {
                    const xhr = new window.XMLHttpRequest();
                    xhr.addEventListener("progress", function (evt) {
                        if (evt.lengthComputable && !isCancelled) {
                            const percentComplete = evt.loaded / evt.total;
                            const progress = 40 + (percentComplete * 50);
                            updateProgress(progress, 100, 'Membuat file Excel...',
                                Math.round(percentComplete * 100) + '% selesai');
                        }
                    }, false);
                    return xhr;
                },
                success: function (blob, status, xhr) {
                    if (isCancelled) {
                        resetExport();
                        return;
                    }

                    currentXHR = null;

                    const disposition = xhr.getResponseHeader('Content-Disposition');
                    let filename = 'All_PR_<?= date("Ymd_His") ?>.xlsx';

                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        const filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        const matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }

                    updateProgress(95, 100, 'Mempersiapkan download...', filename);

                    if (isCancelled) {
                        resetExport();
                        return;
                    }

                    const url = window.URL.createObjectURL(blob);
                    downloadLink = document.createElement('a');
                    downloadLink.href = url;
                    downloadLink.download = filename;
                    document.body.appendChild(downloadLink);

                    updateProgress(98, 100, 'Mengunduh file...', filename);

                    setTimeout(() => {
                        if (isCancelled) {
                            document.body.removeChild(downloadLink);
                            window.URL.revokeObjectURL(url);
                            resetExport();
                            return;
                        }

                        downloadLink.click();

                        updateProgress(100, 100, 'Download dimulai!', filename);

                        setTimeout(() => {
                            if (downloadLink && document.body.contains(downloadLink)) {
                                document.body.removeChild(downloadLink);
                            }
                            window.URL.revokeObjectURL(url);
                            downloadLink = null;

                            if (!isCancelled) {
                                $('#export-icon').removeClass('bx-loader-alt bx-spin').addClass('bx-check-circle').css('color', '#28a745');
                                $('#export-status').text('Berhasil!');
                                $('#export-detail').text(allData.length + ' records berhasil di-export');
                                $('#btn-cancel-export').prop('disabled', true);

                                setTimeout(() => {
                                    resetExport();
                                }, 2000);
                            }
                        }, 1000);
                    }, 500);
                },
                error: function (xhr, status, error) {
                    currentXHR = null;

                    if (status === 'abort' || isCancelled) {
                        return;
                    }

                    let errorMsg = 'Gagal export Excel';
                    if (status === 'timeout') {
                        errorMsg = 'Export timeout (' + allData.length + ' records)';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error saat membuat Excel';
                    }

                    $('#export-icon').removeClass('bx-loader-alt bx-spin').addClass('bx-x-circle').css('color', '#dc3545');
                    $('#export-status').text('Error!');
                    $('#export-detail').text(errorMsg);
                    $('#export-progress').css('background', '#dc3545').css('width', '100%');
                    $('#btn-cancel-export').prop('disabled', true);

                    setTimeout(() => {
                        alert(errorMsg);
                        resetExport();
                    }, 2000);
                }
            });
        }

        // Mulai proses
        getChunk();
    });

    // Cancel export ketika user leave page
    $(window).on('beforeunload', function () {
        if (isExporting && currentXHR) {
            currentXHR.abort();
            return 'Export sedang berjalan. Yakin ingin meninggalkan halaman?';
        }
    });



</script>