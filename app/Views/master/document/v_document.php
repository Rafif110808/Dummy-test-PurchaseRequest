<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">
        <div class="card-header dflex align-center justify-end">
            <button class="btn btn-primary dflex align-center" onclick="return modalForm('Add Document', 'modal-lg', '<?= getURL('document/form') ?>')">
                <i class="bx bx-plus-circle margin-r-2"></i>
                <span class="fw-normal fs-7">Add New</span>
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table class="table table-bordered table-master fs-7 w-100" id="documentTable">
                    <thead>
                        <tr>
                            <th class="tableheader">No</th>
                            <th class="tableheader">Document Name</th>
                            <th class="tableheader">Filepath</th>
                            <th class="tableheader">Description</th>
                            <th class="tableheader">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data akan dimuat dengan AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modaldetail" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title">Form</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-body">
                <!-- Konten modal akan dimuat di sini -->
            </div>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>

<script>
    let tbl;
    $(document).ready(function () {
        // Inisialisasi DataTable
        tbl = $('#documentTable').DataTable({
            ajax: "<?= getURL('document/list') ?>",
            columns: [
                { data: 'no' },
                { data: 'documentname' },
                { data: 'filepath' },
                { data: 'description' },
                {
                    data: null,
                    render: function (data) {
                        return `
                            <button class="btn btn-sm btn-primary" onclick="editDocument(${data.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteDocument(${data.id})">Delete</button>
                        `;
                    }
                }
            ]
        });
    });

    function editDocument(id) {
        let url = "<?= getURL('document/form') ?>/" + id;
        modalForm('Edit Document', 'modal-lg', url);
    }

    function deleteDocument(id) {
        if (confirm("Are you sure you want to delete this document?")) {
            $.ajax({
                url: "<?= getURL('document/delete') ?>/" + id,
                type: 'DELETE',
                success: function (response) {
                    showNotif('success', response.pesan || 'Document deleted successfully');
                    tbl.ajax.reload();
                },
                error: function (xhr) {
                    showError(xhr.responseJSON?.pesan || 'Error occurred. Please try again.');
                }
            });
        }
    }

    function modalForm(title, size, url) {
        $('#modal-title').text(title);
        $('#modal-body').load(url, function () {
            $('#modaldetail').modal('show');
        });
    }
</script>
