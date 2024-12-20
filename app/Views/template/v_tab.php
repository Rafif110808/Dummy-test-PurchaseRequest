<?php if (isset($tabs)) : ?>
    <div class="tabs sc-sm">
        <div class="tabs-wrapper">
            <button class="btn btn-primary arr-left" onclick="return swipeTab('left')">
                <i class="bx bx-chevron-left"></i>
            </button>
            <div class="tabs-content" id="tabs-content">
                <?php for ($i = 0; $i < count($tabs); $i++) : ?>
                    <?php $exp = explode(';', $tabs[$i]); ?>
                    <div class="tabs-item margin-r-3" onclick="return tabChange(this, '<?= getURL($search . '/tab') ?>', '<?= $exp[1] ?>', '<?= $exp[1] ?>', '<?= $parentTab ?>')">
                        <span class="fw-semibold fs-7"><?= $exp[0] ?></span>
                    </div>
                <?php endfor; ?>
            </div>
            <button class="btn btn-primary arr-right" onclick="return swipeTab('right')">
                <i class="bx bx-chevron-right"></i>
            </button>
        </div>
    </div>
<?php endif; ?>