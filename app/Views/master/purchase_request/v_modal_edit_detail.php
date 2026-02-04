<style>
.required:after { content: " *"; color: red; }
.select2-container { z-index: 10000 !important; }
.select2-dropdown { z-index: 10001 !important; }
</style>

<form id="form-edit-detail" method="post">
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>" id="csrf_modal">
    <input type="hidden" name="id" value="<?= encrypting($detail['id']) ?>">

    <div class="form-group">
        <label class="required">Product</label>
        <select id="modal_productid" name="productId" class="form-control form-control-sm select2-modal" required style="width:100%">
            <option value="<?= $detail['productid'] ?>" selected>
                <?= esc($detail['productname']) ?>
            </option>
        </select>
    </div>

    <div class="form-group">
        <label class="required">UOM</label>
        <select id="modal_uomid" name="uomId" class="form-control form-control-sm select2-modal" required style="width:100%">
            <?php if (!empty($detail['uomid'])): ?>
                <option value="<?= $detail['uomid'] ?>" selected>
                    <?= esc($detail['uomnm']) ?>
                </option>
            <?php else: ?>
                <option value="">Select UOM</option>
            <?php endif; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="required">Quantity</label>
        <input type="number" id="modal_qty" name="qty" class="form-control form-control-sm" 
            value="<?= $detail['qty'] ?>" step="0.001" min="0.001" required>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">
            <i class="bx bx-x"></i> Cancel
        </button>
        <button type="submit" class="btn btn-sm btn-primary" id="btn-update-detail">
            <i class="bx bx-check"></i> Update
        </button>
    </div>
</form>

<script>
(function() {
    console.log('Modal Edit Detail Loaded');

    //  TUNGGU DOM READY
    $(document).ready(function() {
        console.log('Initializing Select2 in modal...');

        //  DESTROY OLD INSTANCES
        $('.select2-modal').each(function() {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
        });

        //  GET MODAL PARENT
        var $modalParent = $('#modal_productid').closest('.modal');
        if ($modalParent.length === 0) {
            $modalParent = $('body');
            console.warn('Modal parent not found, using body');
        } else {
            console.log('Modal parent found:', $modalParent.attr('id'));
        }

        //  INIT SELECT2 PRODUCT
        try {
            $('#modal_productid').select2({
                dropdownParent: $modalParent,
                placeholder: 'Select Product',
                allowClear: false,
                width: '100%',
                ajax: {
                    url: '<?= getURL('purchase-request/search-product') ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { 
                        return { term: params.term || '' }; 
                    },
                    processResults: function(data) { 
                        return { results: data }; 
                    },
                    cache: true
                }
            });
            console.log('Product Select2 initialized');
        } catch (e) {
            console.error('Error initializing product select2:', e);
        }

        //  INIT SELECT2 UOM
        try {
            $('#modal_uomid').select2({
                dropdownParent: $modalParent,
                placeholder: 'Select UOM',
                allowClear: true,
                width: '100%',
                ajax: {
                    url: '<?= getURL('purchase-request/search-uom') ?>',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) { 
                        return { term: params.term || '' }; 
                    },
                    processResults: function(data) { 
                        return { results: data }; 
                    },
                    cache: true
                }
            });
            console.log('UOM Select2 initialized');
        } catch (e) {
            console.error('Error initializing uom select2:', e);
        }

        //  SUBMIT FORM
        $('#form-edit-detail').off('submit').on('submit', function(e) {
            e.preventDefault();
            const $btn = $('#btn-update-detail');
            const $form = $(this);

            //  VALIDASI HTML5
            if (!$form[0].checkValidity()) {
                $form[0].reportValidity();
                return;
            }

            //  VALIDASI MANUAL
            const productId = $('#modal_productid').val();
            const qty = $('#modal_qty').val();

            if (!productId) {
                if (typeof showNotif === 'function') {
                    showNotif('warning', 'Product wajib dipilih');
                } else {
                    alert('Product wajib dipilih');
                }
                return;
            }

            if (!qty || parseFloat(qty) <= 0) {
                if (typeof showNotif === 'function') {
                    showNotif('warning', 'Quantity harus lebih besar dari 0');
                } else {
                    alert('Quantity harus lebih besar dari 0');
                }
                return;
            }

            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Updating...');

            $.ajax({
                url: '<?= getURL('purchase-request/updatedetail') ?>',
                type: 'POST',
                data: $form.serialize(),
                dataType: 'json',
                success: function(res) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Update');

                    if (res.sukses == 1) {
                        // Close modal
                        $modalParent.modal('hide');
                        
                        // Show notification
                        if (typeof showNotif === 'function') {
                            showNotif('success', res.pesan);
                        } else {
                            alert(res.pesan);
                        }
                        
                        //  RELOAD TABLE
                        if (typeof detailsTbl !== 'undefined' && detailsTbl) {
                            detailsTbl.ajax.reload(null, false);
                        }
                        
                        // Update CSRF
                        if (res.csrfToken) {
                            $('#csrf_token_form').val(res.csrfToken);
                        }
                    } else {
                        if (typeof showNotif === 'function') {
                            showNotif('error', res.pesan);
                        } else {
                            alert(res.pesan);
                        }
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Update');
                    
                    let errorMsg = 'Terjadi kesalahan sistem';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMsg = response.error || response.pesan || errorMsg;
                    } catch (e) {
                        errorMsg = xhr.statusText || errorMsg;
                    }
                    
                    if (typeof showNotif === 'function') {
                        showNotif('error', errorMsg);
                    } else {
                        alert(errorMsg);
                    }
                }
            });
        });
    });
})();
</script>