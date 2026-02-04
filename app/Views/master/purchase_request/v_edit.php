<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<style>
    .required:after {
        content: " *";
        color: red;
    }

    .form-actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
        padding-top: 1rem;
        border-top: 1px solid #e9ecef;
    }

    .form-actions .btn {
        min-width: 100px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
    }

    .page-header h4 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 2px solid #e9ecef;
    }

    .section-header h5 {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Styling untuk form add detail */
    .add-detail-form {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 1.5rem;
    }

    .add-detail-form .row {
        align-items: end;
    }

    /* Spacing untuk action buttons di table */
    #detailsTable .btn {
        margin: 0 2px;
    }

    /* Table styling */
    #detailsTable th,
    #detailsTable td {
        padding: 0.75rem;
    }
</style>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">

        <!-- Card Body -->
        <div class="card-body">
            <!-- FORM HEADER -->
            <form id="form-purchaserequest" method="post">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_token_form">
                <input type="hidden" name="id" value="<?= encrypting($header['id']) ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">PR Number</label>
                            <input type="text" class="form-control" id="transcode" name="transcode"
                                value="<?= esc($header['transcode']) ?>" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Request Date</label>
                            <input type="date" class="form-control" id="transdate" name="transdate"
                                value="<?= $header['transdate'] ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Supplier</label>
                    <select class="form-control" id="supplierid" name="supplierid" required style="width:100%">
                        <option value="<?= $header['supplierid'] ?>" selected>
                            <?= esc($header['suppliername']) ?>
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" id="description" name="description"
                        rows="3"><?= esc($header['description']) ?></textarea>
                </div>

                <!-- Form Actions dengan styling yang rapi -->
                <div class="form-actions">
                    <a href="<?= base_url('purchase-request') ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back
                    </a>
                    <button type="reset" class="btn btn-warning">
                        <i class="bx bx-revision"></i> Reset
                    </button>
                    <button type="submit" id="btn-submit" class="btn btn-primary">
                        <i class="bx bx-check"></i> Update
                    </button>
                </div>
            </form>

            <!-- SECTION DETAIL ITEMS -->
            <div class="section-header">
                <h5><i class="bx bx-list-ul"></i> Purchase Request Items</h5>
            </div>

            <!-- Form Add Detail -->
            <div class="add-detail-form">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group mb-2">
                            <label class="required">Product</label>
                            <select id="productid" class="form-control" style="width:100%">
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="required">UOM</label>
                            <select id="uomid" class="form-control" style="width:100%">
                                <option value="">Select UOM</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group mb-2">
                            <label class="required">Quantity</label>
                            <input type="number" id="qty" class="form-control" step="0.001"
                                min="0.001" placeholder="0.000">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group mb-2">
                            <label>&nbsp;</label>
                            <button type="button" id="add-detail-btn" class="btn btn-primary btn-block w-100">
                                <i class="bx bx-plus-circle"></i> Add Item
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Detail -->
            <div class="table-responsive">
                <table id="detailsTable" class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 5%">No</th>
                            <th style="width: 40%">Product</th>
                            <th style="width: 15%">UOM</th>
                            <th style="width: 15%" class="text-right">Quantity</th>
                            <th style="width: 15%" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>

<script>
    var detailsTbl;

    $(document).ready(function () {
        // ==================== INIT SELECT2 ====================
        $('#supplierid').select2({
            placeholder: 'Select Supplier',
            width: '100%',
            ajax: {
                url: '<?= getURL('purchase-request/search-supplier') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { term: params.term || '' }; },
                processResults: function (data) { return { results: data }; }
            }
        });

        $('#productid').select2({
            placeholder: 'Select Product',
            width: '100%',
            ajax: {
                url: '<?= getURL('purchase-request/search-product') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { term: params.term || '' }; },
                processResults: function (data) { return { results: data }; }
            }
        });

        $('#uomid').select2({
            placeholder: 'Select UOM',
            width: '100%',
            ajax: {
                url: '<?= getURL('purchase-request/search-uom') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) { return { term: params.term || '' }; },
                processResults: function (data) { return { results: data }; }
            }
        });

        // ==================== FORM SUBMIT HEADER ====================
        $('#form-purchaserequest').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-submit');

            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Updating...');

            $.ajax({
                url: '<?= getURL('purchase-request/update') ?>',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Update');

                    if (res.sukses == 1) {
                        showNotif('success', res.pesan);
                        if (res.csrfToken) $('#csrf_token_form').val(res.csrfToken);

                        // Redirect
                        setTimeout(() => {
                            window.location.href = '<?= base_url('purchase-request') ?>';
                        }, 300);
                    } else {
                        showNotif('error', res.pesan);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Update');
                    showNotif('error', 'Terjadi kesalahan sistem');
                }
            });
        });

        // ==================== INIT DATATABLE ====================
        detailsTbl = $('#detailsTable').DataTable({
            serverSide: true,
            processing: true,
            ajax: {
                url: "<?= getURL('purchase-request/getdetailsajax') ?>",
                type: "POST",
                data: function (d) {
                    d.headerId = '<?= encrypting($header['id']) ?>';
                    d.<?= csrf_token() ?> = $('#csrf_token_form').val();
                }
            },
            columns: [
                { data: 0, width: '5%', orderable: false, className: 'text-center' },
                { data: 1, width: '40%' },
                { data: 2, width: '15%' },
                { data: 3, width: '15%', className: 'text-right' },
                { data: 4, width: '15%', orderable: false, className: 'text-center' }
            ],
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
                emptyTable: 'Belum ada item',
                zeroRecords: 'Data tidak ditemukan'
            }
        });

        // ==================== ADD DETAIL BUTTON ====================
        $('#add-detail-btn').on('click', function () {
            const productId = $('#productid').val();
            const uomId = $('#uomid').val();
            const qty = $('#qty').val();
            const $btn = $(this);

            // Validasi
            if (!productId) {
                showNotif('warning', 'Product wajib dipilih');
                return;
            }
            if (!qty || parseFloat(qty) <= 0) {
                showNotif('warning', 'Quantity harus lebih besar dari 0');
                return;
            }

            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Adding...');

            $.ajax({
                url: '<?= getURL('purchase-request/adddetail') ?>',
                type: 'POST',
                data: {
                    headerId: '<?= encrypting($header['id']) ?>',
                    productId: productId,
                    uomId: uomId,
                    qty: qty,
                    <?= csrf_token() ?>: $('#csrf_token_form').val()
                },
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="bx bx-plus-circle"></i> Add Item');

                    if (res.sukses == 1) {
                        // Reset form
                        $('#productid, #uomid').val(null).trigger('change');
                        $('#qty').val('');

                        // Reload table
                        detailsTbl.ajax.reload(null, false);
                        showNotif('success', res.pesan);

                        // Update CSRF
                        if (res.csrfToken) $('#csrf_token_form').val(res.csrfToken);
                    } else {
                        showNotif('error', res.pesan);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html('<i class="bx bx-plus-circle"></i> Add Item');
                    showNotif('error', 'Terjadi kesalahan sistem');
                }
            });
        });

        // ==================== EDIT DETAIL (MODAL) ====================
        $(document).on('click', '.btn-edit-detail', function (e) {
            e.preventDefault();
            const detailId = $(this).data('id-encrypted');

            console.log('Edit Detail Clicked');
            console.log('Encrypted ID:', detailId);

            modalForm(
                'Edit Detail Item',
                'modal-md',
                '<?= getURL('purchase-request/form_edit_detail/') ?>' + detailId
            );
        });

        // ==================== CALLBACK DELETE ====================
        window.afterDeleteDetail = function () {
            detailsTbl.ajax.reload(null, false);
        };
    });
</script>