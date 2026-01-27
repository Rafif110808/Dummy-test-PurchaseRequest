<?= $this->include('template/v_header') ?>
    <?= $this->include('template/v_appbar') ?>

    <div class="main-content content margin-t-4">
        <div class="card p-x shadow-sm w-100">
            <!-- Card Header -->
            <div class="card-header dflex align-center justify-between">
                <div>
                    <h4 class="mb-0">Purchase Request</h4>
                    <p class="text-muted fs-7 mb-0">Manage your purchase requests</p>
                </div>
                <button class="btn btn-primary dflex align-center"
                    onclick="return modalForm('Add Purchase Request', 'modal-lg', '<?= getURL('purchase-request/form') ?>')">
                    <i class="bx bx-plus-circle margin-r-2"></i>
                    <span class="fw-normal fs-7">Add New</span>
                </button>
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

   <script>
    var tbl;

    $(document).ready(function () {
        // Hapus tabel lama jika ada
        if ($.fn.DataTable.isDataTable('.table-master')) {
            $('.table-master').DataTable().destroy();
        }

        // Initialize DataTable
        tbl = $('.table-master').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= site_url('purchase-request/datatable') ?>',
                type: 'POST',
                data: function(d) {
                    // Tambahkan CSRF token
                    d.<?= csrf_token() ?> = $('meta[name="csrf-token"]').attr('content') || $('input[name="<?= csrf_token() ?>"]').val();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error Details:');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.error('Response:', xhr.responseText);
                    
                    // Tampilkan pesan error yang lebih user-friendly
                    if (xhr.status === 0) {
                        alert('Tidak dapat terhubung ke server. Periksa koneksi internet Anda.');
                    } else if (xhr.status === 404) {
                        alert('Endpoint tidak ditemukan. Periksa konfigurasi route.');
                    } else if (xhr.status === 500) {
                        alert('Server error. Silakan cek log server.');
                    } else {
                        alert('Terjadi kesalahan: ' + error);
                    }
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
            order: [[1, 'desc']], // Order by PR Number descending
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Memuat data...',
                emptyTable: 'Tidak ada data Purchase Request',
                zeroRecords: 'Data tidak ditemukan',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                search: 'Cari:',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya'
                }
            }
        });
    });
</script>