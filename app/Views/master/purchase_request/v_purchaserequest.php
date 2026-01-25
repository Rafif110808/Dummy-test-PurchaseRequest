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
                    <table class="table table-bordered table-hover fs-7 w-100" id="table-purchase-request">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">No</th>
                                <th style="width: 15%">PR Number</th>
                                <th style="width: 12%">Request Date</th>
                                <th style="width: 20%">Supplier</th>
                                <th style="width: 33%">Description</th>
                                <th style="width: 15%" class="text-center">Actions</th>
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
    var purchaseRequestTable;

    $(document).ready(function () {
        // Destroy existing DataTable if it exists
        if ($.fn.DataTable.isDataTable('#table-purchase-request')) {
            $('#table-purchase-request').DataTable().destroy();
        }

        // Initialize DataTable
        purchaseRequestTable = $('#table-purchase-request').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= getURL('purchase-request/table') ?>',
                type: 'POST',
                error: function (xhr, status, error) {
                    console.error('Error loading data:', error);
                    console.error('Response:', xhr.responseText);
                    showNotif('error', 'Failed to load data');
                }
            },
            columns: [
                { data: 0, width: '5%', orderable: false },
                { data: 1, width: '15%' },
                { data: 2, width: '12%' },
                { data: 3, width: '20%' },
                { data: 4, width: '33%' },
                { data: 5, width: '15%', orderable: false, searchable: false, className: 'text-center' }
            ],
            order: [[1, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
                search: 'Search:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                infoEmpty: 'Showing 0 to 0 of 0 entries',
                infoFiltered: '(filtered from _MAX_ total entries)',
                zeroRecords: 'No matching records found',
                emptyTable: 'No data available in table',
                paginate: {
                    first: '<i class="bx bx-chevrons-left"></i>',
                    previous: '<i class="bx bx-chevron-left"></i>',
                    next: '<i class="bx bx-chevron-right"></i>',
                    last: '<i class="bx bx-chevrons-right"></i>'
                }
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                '<"row"<"col-sm-12"tr>>' +
                '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            drawCallback: function () {
                // Add custom styling or actions after table draw
                $('[data-toggle="tooltip"]').tooltip();
            }
        });

        // Reload table on modal close
        $('#modalAdd').on('hidden.bs.modal', function () {
            if (typeof purchaseRequestTable !== 'undefined') {
                purchaseRequestTable.ajax.reload(null, false);
            }
        });
    });

    // Refresh table function (optional - for manual refresh)
    function refreshTable() {
        if (typeof purchaseRequestTable !== 'undefined') {
            purchaseRequestTable.ajax.reload(null, false);
        }
    }

    // Header delete - use modal confirmation (server-side)
    $(document).on('click', '.btn-delete-pr', function () {
        const id = $(this).data('id');
        const transcode = $(this).data('transcode');
        // Use modalDelete to confirm deletion
        modalDelete('Delete Purchase Request - ' + transcode, {
            id: id,
            custom_handler: 'confirmDeletePR'
        });
    });

    // Global handler invoked by modalDelete for header deletion
    window.confirmDeletePR = function(data) {
        $.ajax({
            url: '<?= getURL('purchase-request/delete') ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                id: data.id,
                <?= csrf_token() ?>: $('#csrf_token_form').val()
            },
            success: function(res) {
                $('#modaldel').modal('hide');
                if (res.sukses == 1) {
                    if (typeof purchaseRequestTable !== 'undefined') {
                        purchaseRequestTable.ajax.reload(null, false);
                    }
                    showNotif('success', res.pesan);
                } else {
                    showNotif('error', res.pesan);
                }
            },
            error: function(xhr) {
                $('#modaldel').modal('hide');
                showNotif('error', 'Delete failed: ' + xhr.responseText);
            }
        });
    };

</script>
