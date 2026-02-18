<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<style>
    table.dataTable>thead>tr>td {
        border-bottom: none !important;
    }

    .table-bordered thead td,
    .table-bordered thead th {
        border-bottom-width: 1px !important;
    }

    .action-buttons {
        display: inline-flex;
        gap: 0.25rem;
        justify-content: center;
    }

    .action-buttons .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

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

    .btn-success {
        background-color: #28a745;
        border-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
        color: white;
    }

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

    .card-header {
        padding: 1.25rem;
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
</style>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header">
            <div class="header-actions">
                <button class="btn btn-primary" onclick="return modalForm('Add File', 'modal-lg', '<?= getURL('files/form') ?>')">
                    <i class="bx bx-plus-circle"></i>
                    <span>Add New</span>
                </button>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-master fs-7 w-100">
                    <thead>
                        <tr>
                            <td class="tableheader" style="width: 5%">No</td>
                            <td class="tableheader" style="width: 25%">File Name</td>
                            <td class="tableheader" style="width: 25%">Directory</td>
                            <td class="tableheader" style="width: 15%">Created Date</td>
                            <td class="tableheader" style="width: 15%">Created By</td>
                            <td class="tableheader" style="width: 15%">Actions</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>

<script>
    var tbl;

    $(document).ready(function () {
        initDataTable();
    });

    function initDataTable() {
        if ($.fn.DataTable.isDataTable('.table-master')) {
            $('.table-master').DataTable().destroy();
        }

        tbl = $('.table-master').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= site_url('files/table') ?>',
                type: 'POST',
                dataSrc: function(json) {
                    if (json && json.data) {
                        return json.data;
                    }
                    return [];
                },
                data: function (d) {
                    d.<?= csrf_token() ?> = $('meta[name="csrf-token"]').attr('content') || $('input[name="<?= csrf_token() ?>"]').val();
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
            order: [[3, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Memuat data...',
                emptyTable: 'Tidak ada data Files',
                zeroRecords: 'Data tidak ditemukan'
            }
        });
    }
</script>
