<?= $this->include('template/v_header') ?>
<?= $this->include('template/v_appbar') ?>

<style>
    /* Custom styling untuk form */
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
</style>

<div class="main-content content margin-t-4">
    <div class="card p-x shadow-sm w-100">

        <!-- Card Body -->
        <div class="card-body">
            <form id="form-purchaserequest" method="post">
                <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">PR Number</label>
                            <input type="text" class="form-control" id="transcode" name="transcode"
                                placeholder="Enter PR Number" required>
                            <small class="form-text text-muted">Format: PR-YYYY-NNNN</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="required">Request Date</label>
                            <input type="date" class="form-control" id="transdate" name="transdate"
                                value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="required">Supplier</label>
                    <select class="form-control" id="supplierid" name="supplierid" required style="width:100%">
                        <option value="">Select Supplier</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" id="description" name="description"
                        placeholder="Enter description (optional)" rows="3"></textarea>
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
                        <i class="bx bx-check"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->include('template/v_footer') ?>

<script>
    $(document).ready(function () {
        // Initialize Select2
        $('#supplierid').select2({
            placeholder: 'Select Supplier',
            allowClear: true,
            ajax: {
                url: '<?= getURL('purchase-request/search-supplier') ?>',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { term: params.term || '' };
                },
                processResults: function (data) {
                    return { results: data };
                }
            }
        });

        // Form Submit
        $('#form-purchaserequest').on('submit', function (e) {
            e.preventDefault();
            const $btn = $('#btn-submit');

            if (!this.checkValidity()) {
                this.reportValidity();
                return;
            }

            $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

            $.ajax({
                url: '<?= getURL('purchase-request/add') ?>',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function (res) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Save');

                    if (res.sukses == 1) {
                        showNotif('success', res.pesan);
                        setTimeout(() => {
                            window.location.href = '<?= base_url('purchase-request') ?>';
                        }, 300);
                    } else {
                        showNotif('error', res.pesan);
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="bx bx-check"></i> Save');
                    showNotif('error', 'Terjadi kesalahan sistem');
                }
            });
        });
    });
</script>