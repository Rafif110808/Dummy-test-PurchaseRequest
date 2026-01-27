<style>
    .modal-body {
        max-height: 75vh;
        overflow-y: auto;
    }

    .form-section {
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .form-section h5 {
        margin-bottom: 1rem;
        color: #495057;
        font-weight: 600;
    }

    .required:after {
        content: " *";
        color: red;
    }

    .btn-group {
        gap: 0.5rem;
    }

    #detailsTable_wrapper {
        margin-top: 1rem;
    }

    .select2-container {
        z-index: 9999 !important;
    }

    .select2-dropdown {
        z-index: 9999 !important;
    }
</style>

<!-- HEADER FORM SECTION -->
<div class="form-section">
    <h5><i class="bx bx-file"></i> Purchase Request Information</h5>
    <form id="form-purchaserequest" method="post">
        <?php if ($form_type == 'edit'): ?>
            <input type="hidden" name="id" value="<?= encrypting($header['id']) ?>">
        <?php endif ?>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="required">PR Number</label>
                    <input type="text" class="form-control form-control-sm" id="transcode" name="transcode"
                        value="<?= ($form_type == 'edit') ? esc($header['transcode']) : '' ?>"
                        placeholder="Enter PR Number" required>
                    <small class="form-text text-muted">Format: PR-YYYY-NNNN</small>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="required">Request Date</label>
                    <input type="date" class="form-control form-control-sm" id="transdate" name="transdate"
                        value="<?= ($form_type == 'edit') ? $header['transdate'] : date('Y-m-d') ?>" required>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="required">Supplier</label>
            <select class="form-control form-control-sm" id="supplierid" name="supplierid" required style="width:100%">
                <option value="">Select Supplier</option>
                <?php if ($form_type == 'edit' && !empty($header['supplierid'])): ?>
                    <option value="<?= $header['supplierid'] ?>" selected>
                        <?= esc($header['suppliername']) ?>
                    </option>
                <?php endif ?>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea class="form-control form-control-sm" id="description" name="description"
                placeholder="Enter description (optional)"
                rows="3"><?= ($form_type == 'edit') ? esc($header['description']) : '' ?></textarea>
        </div>

        <div class="modal-footer px-0 pb-0">
            <button type="button" class="btn btn-secondary btn-sm" onclick="$('#modalAdd').modal('hide')">
                <i class="bx bx-x"></i> Cancel
            </button>
            <button type="button" class="btn btn-warning btn-sm" onclick="resetForm('form-purchaserequest')">
                <i class="bx bx-revision"></i> Reset
            </button>
            <button type="submit" id="btn-submit" class="btn btn-primary btn-sm">
                <i class="bx bx-check"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>
            </button>
        </div>
    </form>
</div>

<!-- DETAIL ITEMS SECTION (Only show in edit mode) -->
<?php if ($form_type == 'edit'): ?>
    <div class="form-section">
        <h5><i class="bx bx-list-ul"></i> Purchase Request Items</h5>

        <!-- Add/Edit Detail Form -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="required">Product</label>
                            <select id="productid" class="form-control form-control-sm" style="width:100%">
                                <option value="">Select Product</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required">UOM</label>
                            <select id="uomid" class="form-control form-control-sm" style="width:100%">
                                <option value="">Select UOM</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="required">Quantity</label>
                            <input type="number" id="qty" class="form-control form-control-sm" step="0.001" min="0.001"
                                placeholder="0.000">
                        </div>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <div class="btn-group w-100" role="group">
                            <button type="button" id="add-detail-btn" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus-circle"></i> Add
                            </button>
                            <button type="button" id="reset-detail-btn" class="btn btn-warning btn-sm"
                                style="display: none;">
                                <i class="bx bx-revision"></i>
                            </button>
                            <button type="button" id="cancel-edit-btn" class="btn btn-secondary btn-sm"
                                style="display: none;">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Table -->
        <div class="card-body">
            <div class="table-responsive margin-t-14p">
                <table id="detailsTable" class="table table-sm table-bordered table-hover">
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
<?php endif ?>

<script>
    $(document).ready(function () {
        // Initialize Select2 for all dropdowns
        initializeSelect2();

        // ==================== HANDLE FORM SUBMIT FOR HEADER ====================
        $('#form-purchaserequest').off('submit').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btn-submit');

            // Validasi form
            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }

            // Disable submit button
            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

            const url = '<?= ($form_type == 'edit' ? getURL('purchase-request/update') : getURL('purchase-request/add')) ?>';

            $.ajax({
                url: url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (res) {
                    // Re-enable button
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>');

                    if (res.sukses == 1) {
                        // Update CSRF token
                        if (res.csrfToken) {
                            $('input[name="<?= csrf_token() ?>"]').val(res.csrfToken);
                            if ($('#csrf_token_form').length) {
                                $('#csrf_token_form').val(res.csrfToken);
                            }
                        }

                        // Tutup modal
                        $('#modalAdd').modal('hide');

                        // Show notification (Notif hijau seperti semula)
                        showNotif('success', res.pesan || 'Data berhasil disimpan');

                        // REDIRECT CEPAT (300ms - cukup buat notif muncul)
                        setTimeout(function () {
                            window.location.href = '<?= base_url('purchase-request') ?>';
                        }, 300);

                    } else {
                        // Show error notification (pakai showNotif)
                        showNotif('error', res.pesan || 'Gagal menyimpan data');
                    }
                },
                error: function (xhr, status, error) {
                    // Re-enable button
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>');

                    // Parse error message
                    let errorMsg = 'Terjadi kesalahan sistem';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.error || response.pesan || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.statusText || errorMsg;
                    }

                    // Show error notification (pakai showNotif)
                    showNotif('error', errorMsg);
                }
            });
        });

        <?php if ($form_type == 'edit'): ?>
            // ==================== INITIALIZE DATATABLE FOR DETAILS ====================
            initializeDetailsTable();

            // ==================== EVENT DELEGATION UNTUK TOMBOL EDIT & DELETE ====================
            $(document).on('click', '.btn-edit-detail', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const id = $btn.data('id');
                const productId = $btn.data('productid');
                const uomId = $btn.data('uomid');
                const qty = $btn.data('qty');
                const productName = $btn.data('productname');
                const uomName = $btn.data('uomname');

                console.log('Edit detail clicked:', { id, productId, productName });

                if (typeof editDetail === 'function') {
                    editDetail(id, productId, uomId, qty, productName, uomName);
                } else {
                    console.error('editDetail function not found');
                    Swal.fire('Error', 'Edit function not available', 'error');
                }
            });


            
            // ==================== GANTI DENGAN KODE CALLBACK INI ====================
            // Callback setelah delete detail berhasil (dipanggil oleh modalDelete)
            window.afterDeleteDetail = function () {
                console.log('After delete detail callback');

                // Reload DataTable detail
                if (typeof detailsTbl !== 'undefined') {
                    detailsTbl.ajax.reload(null, false);
                }
            };
            // ==================== EDIT DETAIL FUNCTION ====================
            window.editDetail = function (id, productId, uomId, qty, productName = '', uomName = '') {
                ensureSelectOption($('#productid'), productId, productName);
                ensureSelectOption($('#uomid'), uomId, uomName);
                $('#qty').val(qty);

                $('#add-detail-btn')
                    .html('<i class="bx bx-check"></i> Update')
                    .removeClass('btn-primary')
                    .addClass('btn-warning')
                    .data('detail-id', id);

                $('#reset-detail-btn, #cancel-edit-btn').show();
            };

            // ==================== ENSURE SELECT OPTION EXISTS ====================
            function ensureSelectOption($select, id, text) {
                if (!id) return;
                if ($select.find("option[value='" + id + "']").length === 0) {
                    const opt = new Option(text || id, id, true, true);
                    $select.append(opt);
                }
                $select.val(id).trigger('change');
            }

            // ==================== RESET DETAIL FORM ====================
            function resetDetailForm() {
                $('#productid, #uomid').val(null).trigger('change');
                $('#qty').val('');

                $('#add-detail-btn')
                    .html('<i class="bx bx-plus-circle"></i> Add')
                    .removeClass('btn-warning')
                    .addClass('btn-primary')
                    .removeData('detail-id');

                $('#reset-detail-btn, #cancel-edit-btn').hide();
            }

            // Reset button click
            $('#reset-detail-btn').on('click', resetDetailForm);

            // Cancel edit button click
            $('#cancel-edit-btn').on('click', resetDetailForm);

            // ==================== ADD/UPDATE DETAIL BUTTON CLICK ====================
            $('#add-detail-btn').off('click').on('click', function () {
                const detailId = $(this).data('detail-id');
                const $btn = $(this);

                const productId = $('#productid').val();
                const uomId = $('#uomid').val();
                const qty = $('#qty').val();

                // Validation
                if (!productId) {
                    showNotif('warning', 'Product wajib dipilih');
                    $('#productid').focus();
                    return;
                }

                if (!qty || parseFloat(qty) <= 0) {
                    showNotif('warning', 'Quantity harus lebih besar dari 0');
                    $('#qty').focus();
                    return;
                }

                // Disable button
                const originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

                const url = detailId
                    ? '<?= getURL('purchase-request/updatedetail') ?>'
                    : '<?= getURL('purchase-request/adddetail') ?>';

                const payload = {
                    headerId: '<?= encrypting($header['id']) ?>',
                    productId: productId,
                    uomId: uomId,
                    qty: qty,
                    <?= csrf_token() ?>: $('#csrf_token_form').val()
                };

                if (detailId) {
                    payload.id = detailId;
                }

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: function (res) {
                        $btn.prop('disabled', false).html(originalHtml);

                        if (res.sukses == 1) {
                            // Update CSRF token
                            if (res.csrfToken) {
                                $('input[name="<?= csrf_token() ?>"]').val(res.csrfToken);
                                if ($('#csrf_token_form').length) {
                                    $('#csrf_token_form').val(res.csrfToken);
                                }
                            }

                            // Reset form
                            resetDetailForm();

                            // Reload DataTable
                            if (typeof detailsTbl !== 'undefined') {
                                detailsTbl.ajax.reload(null, false);
                            }

                            // Show success notification (pakai showNotif)
                            showNotif('success', res.pesan || (detailId ? 'Detail berhasil diupdate' : 'Detail berhasil ditambahkan'));
                        } else {
                            showNotif('error', res.pesan || 'Terjadi kesalahan');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).html(originalHtml);

                        let errorMsg = 'Terjadi kesalahan sistem';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMsg = response.error || response.pesan || errorMsg;
                        } catch (e) {
                            errorMsg = xhr.statusText || errorMsg;
                        }

                        showNotif('error', errorMsg);
                    }
                });
            });
        <?php endif ?>
    });

    // ==================== INITIALIZE SELECT2 FUNCTION ====================
    function initializeSelect2() {
        setTimeout(function () {
            // Destroy existing select2 first
            if ($('#supplierid').hasClass('select2-hidden-accessible')) {
                $('#supplierid').select2('destroy');
            }
            if ($('#productid').hasClass('select2-hidden-accessible')) {
                $('#productid').select2('destroy');
            }
            if ($('#uomid').hasClass('select2-hidden-accessible')) {
                $('#uomid').select2('destroy');
            }

            // Get the modal parent element
            var $modalParent = $('#supplierid').closest('.modal');
            if ($modalParent.length === 0) {
                $modalParent = $('body');
            }

            // Supplier Select2 with AJAX
            $('#supplierid').select2({
                dropdownParent: $modalParent,
                placeholder: 'Select Supplier',
                allowClear: true,
                width: '100%',
                minimumInputLength: 0,
                ajax: {
                    url: '<?= getURL('purchase-request/search-supplier') ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            term: params.term || ''
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            });

            <?php if ($form_type == 'edit'): ?>
                // Product Select2 with AJAX
                $('#productid').select2({
                    dropdownParent: $modalParent,
                    placeholder: 'Select Product',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: '<?= getURL('purchase-request/search-product') ?>',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    }
                });

                // UOM Select2 with AJAX
                $('#uomid').select2({
                    dropdownParent: $modalParent,
                    placeholder: 'Select UOM',
                    allowClear: true,
                    width: '100%',
                    minimumInputLength: 0,
                    ajax: {
                        url: '<?= getURL('purchase-request/search-uom') ?>',
                        dataType: 'json',
                        delay: 250,
                        data: function (params) {
                            return {
                                term: params.term || ''
                            };
                        },
                        processResults: function (data) {
                            return {
                                results: data
                            };
                        },
                        cache: true
                    }
                });
            <?php endif ?>

            // Enable the dropdowns
            $('#supplierid, #productid, #uomid').prop('disabled', false);
        }, 300);
    }

   <?php if ($form_type == 'edit'): ?>
    // ==================== INITIALIZE DATATABLE FOR DETAILS ====================
    function initializeDetailsTable() {
        if ($.fn.DataTable.isDataTable('#detailsTable')) {
            $('#detailsTable').DataTable().destroy();
        }

        window.detailsTbl = $('#detailsTable').DataTable({
            serverSide: true,
            processing: true,
            autoWidth: false,
            scrollX: true,
            language: {
                processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
                emptyTable: 'Belum ada data detail',
                zeroRecords: 'Data tidak ditemukan'
            },
            ajax: {
                url: "<?= getURL('purchase-request/getdetailsajax') ?>",
                type: "POST",
                data: function (d) {
                    d.headerId = '<?= encrypting($header['id']) ?>';
                    d.<?= csrf_token() ?> = $('#csrf_token_form').val();
                },
                error: function(xhr, error, code) {
                    console.error('DataTable error:', error, code);
                    showNotif('error', 'Gagal memuat data detail');
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
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
        });
    }

    // ==================== CALLBACK UNTUK RELOAD DETAIL TABLE SETELAH DELETE ====================
    window.afterDeleteDetail = function() {
        console.log('Reloading detail table after delete');
        
        // Reload DataTable detail dengan server-side processing
        if (typeof detailsTbl !== 'undefined' && detailsTbl) {
            detailsTbl.ajax.reload(null, false); // false = stay on current page
        }
    };
<?php endif ?>

    // Tambahkan di bagian atas script jika belum ada
    if (typeof showNotif !== 'function') {
        window.showNotif = function (type, message) {
            // Gunakan Swal sebagai fallback
            const icon = type === 'success' ? 'success' : 'error';
            Swal.fire({
                icon: icon,
                title: type === 'success' ? 'Berhasil' : 'Gagal',
                text: message,
                timer: 3000,
                showConfirmButton: false
            });
        };
    }
</script>
