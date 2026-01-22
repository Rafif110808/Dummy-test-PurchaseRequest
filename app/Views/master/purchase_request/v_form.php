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

        // Handle form submit for header
        $('#form-purchaserequest').off('submit').on('submit', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $btn = $('#btn-submit');

            // Disable submit button
            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

            const url = '<?= ($form_type == 'edit' ? getURL('purchase-request/update') : getURL('purchase-request/add')) ?>';

            $.ajax({
                url: url,
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>');

                    if (res.sukses == 1) {
                        $('#modalAdd').modal('hide');

                        // Reload main table
                        if (typeof purchaseRequestTable !== 'undefined') {
                            purchaseRequestTable.ajax.reload(null, false);
                        } else {
                            window.location.reload();
                        }

                        // Update CSRF token
                        if (res.csrfToken) {
                            $('#csrf_token_form').val(res.csrfToken);
                        }

                        showNotif('success', res.pesan || 'Data saved successfully');
                    } else {
                        showNotif('error', res.pesan || 'Failed to save data');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> <?= ($form_type == 'edit' ? 'Update' : 'Save') ?>');
                    showNotif('error', 'Error: ' + (xhr.responseJSON?.error || xhr.responseText || xhr.statusText));
                }
            });
        });

        <?php if ($form_type == 'edit'): ?>
            // Initialize DataTable for details (edit mode only)
            initializeDetailsTable();

            // EVENT DELEGATION untuk tombol Edit dan Delete
            // FIXED: Prevent event bubbling dan tambahkan debugging
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

                console.log('Edit detail clicked:', { id, productId, productName }); // Debug

                if (typeof editDetail === 'function') {
                    editDetail(id, productId, uomId, qty, productName, uomName);
                } else {
                    console.error('editDetail function not found');
                    showNotif('error', 'Edit function not available');
                }
            });

            $(document).on('click', '.btn-delete-detail', function (e) {
                e.preventDefault();
                e.stopPropagation();

                const $btn = $(this);
                const id = $btn.data('id');
                const productName = $btn.data('productname');

                console.log('Delete detail clicked:', { id, productName }); // Debug

                if (typeof deleteDetail === 'function') {
                    deleteDetail(id, productName);
                } else {
                    console.error('deleteDetail function not found');
                    showNotif('error', 'Delete function not available');
                }
            });


            // Function to ensure select option exists
            function ensureSelectOption($select, id, text) {
                if (!id) return;
                if ($select.find("option[value='" + id + "']").length === 0) {
                    const opt = new Option(text || id, id, true, true);
                    $select.append(opt);
                }
                $select.val(id).trigger('change');
            }

            // Edit detail function (global scope)
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

            // DELETE DETAIL FUNCTION - ENHANCED VERSION WITH BETTER ERROR HANDLING
            window.deleteDetail = function (id, productName) {
                console.log('deleteDetail called with:', { id, productName }); // Debug

                if (!id || !productName) {
                    console.error('Invalid parameters:', { id, productName });
                    showNotif('error', 'Invalid detail data');
                    return;
                }

                Swal.fire({
                    title: 'Delete Detail',
                    html: 'Are you sure you want to delete <strong>' + productName + '</strong>?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log('Delete confirmed, sending AJAX request...'); // Debug

                        $.ajax({
                            url: '<?= getURL('purchase-request/deletedetail') ?>',
                            type: 'POST',
                            data: {
                                id: id,
                                'csrf_token_name': $('#csrf_token_form').val() // Gunakan nama token yang benar
                            },
                            dataType: 'json',
                            timeout: 30000, // 30 second timeout
                            beforeSend: function () {
                                console.log('Sending delete request for ID:', id);
                                Swal.fire({
                                    title: 'Deleting...',
                                    html: 'Please wait',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function (res) {
                                console.log('Delete response:', res); // Debug

                                if (res.sukses == 1) {
                                    // Update CSRF token
                                    if (res.csrfToken) {
                                        $('#csrf_token_form').val(res.csrfToken);
                                    }

                                    // RELOAD DATATABLE - SERVER SIDE dengan delay untuk memastikan
                                    setTimeout(function () {
                                        if (typeof detailsTbl !== 'undefined' && detailsTbl) {
                                            console.log('Reloading DataTable...');
                                            detailsTbl.ajax.reload(null, false);
                                        } else {
                                            console.error('DataTable not found, reloading page...');
                                            location.reload();
                                        }
                                    }, 500);

                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: res.pesan || 'Detail has been deleted.',
                                        timer: 1500,
                                        showConfirmButton: false
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Failed!',
                                        text: res.pesan || 'Failed to delete detail'
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Delete AJAX error:', { xhr, status, error });

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'Error: ' + (xhr.responseJSON?.error || xhr.responseText || error || 'Unknown error')
                                });
                            }
                        });
                    }
                });
            };

            // Reset detail form
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

            // Add/Update detail button click
            $('#add-detail-btn').off('click').on('click', function () {
                const detailId = $(this).data('detail-id');
                const $btn = $(this);

                const productId = $('#productid').val();
                const uomId = $('#uomid').val();
                const qty = $('#qty').val();

                // Validation
                if (!productId || !qty) {
                    showNotif('error', 'Product and Quantity are required');
                    return;
                }

                if (parseFloat(qty) <= 0) {
                    showNotif('error', 'Quantity must be greater than 0');
                    return;
                }

                // Disable button
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
                        $btn.prop('disabled', false);

                        if (res.sukses == 1) {
                            resetDetailForm();

                            // RELOAD DATATABLE - SERVER SIDE
                            if (typeof detailsTbl !== 'undefined') {
                                detailsTbl.ajax.reload(null, false);
                            }

                            if (res.csrfToken) {
                                $('#csrf_token_form').val(res.csrfToken);
                            }

                            showNotif('success', res.pesan || (detailId ? 'Detail updated' : 'Detail added'));
                        } else {
                            $btn.html(detailId ? '<i class="bx bx-check"></i> Update' : '<i class="bx bx-plus-circle"></i> Add');
                            showNotif('error', res.pesan || 'An error occurred');
                        }
                    },
                    error: function (xhr) {
                        $btn.prop('disabled', false).html(detailId ? '<i class="bx bx-check"></i> Update' : '<i class="bx bx-plus-circle"></i> Add');
                        showNotif('error', 'Error: ' + (xhr.responseJSON?.error || xhr.responseText || xhr.statusText));
                    }
                });
            });
        <?php endif ?>
    });

    // Initialize Select2 for all dropdowns
    function initializeSelect2() {
        // Tunggu sampai modal benar-benar terbuka
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
                $modalParent = $('body'); // fallback to body if not in modal
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
                // Product Select2 with AJAX (only in edit mode)
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

                // UOM Select2 with AJAX (only in edit mode)
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
        }, 300); // Wait 300ms for modal to fully render
    }

    <?php if ($form_type == 'edit'): ?>
        // Initialize DataTable for details
        function initializeDetailsTable() {
            if ($.fn.DataTable.isDataTable('#detailsTable')) {
                $('#detailsTable').DataTable().destroy();
            }

            window.detailsTbl = $('#detailsTable').DataTable({
                serverSide: true,
                processing: true,
                ajax: {
                    url: "<?= getURL('purchase-request/getdetailsajax') ?>",
                    type: "POST",
                    data: function (d) {
                        d.headerId = '<?= encrypting($header['id']) ?>';
                        d.<?= csrf_token() ?> = $('#csrf_token_form').val();
                        return d;
                    },
                    error: function (xhr) {
                        console.error('Error loading details:', xhr.responseText);
                        showNotif('error', 'Failed to load details');
                    }
                },
                columns: [
                    { data: 0, width: '5%', orderable: false, className: 'text-center' },
                    { data: 1, width: '40%', orderable: true },
                    { data: 2, width: '15%', orderable: true },
                    { data: 3, width: '15%', orderable: true, className: 'text-right' },
                    { data: 4, width: '15%', orderable: false, className: 'text-center' }
                ],
                searching: true,
                paging: true,
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                info: true,
                order: [[1, 'asc']],
                language: {
                    processing: '<i class="bx bx-loader-alt bx-spin"></i> Loading...',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ items',
                    info: 'Showing _START_ to _END_ of _TOTAL_ items',
                    infoEmpty: 'No items to show',
                    zeroRecords: 'No matching items found',
                    emptyTable: 'No items added yet'
                }
            });
        }
    <?php endif ?>

</script>