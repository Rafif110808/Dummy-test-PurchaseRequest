<?php foreach ($rowsub as $t) { ?>
    <?php $checkSub = getSidebarSubMenu($t['menuid']); ?>
    <div class="sub-item submenu-item <?= (!empty($checkSub) ? 'haveSub' : '') ?>" link="<?= getURL($t['menulink']) ?>">
        <div class="dflex justify-between">
            <span class="fw-normal fs-7set"><?= ucwords($t['menuname']) ?></span>
            <?php if (!empty($checkSub)) : ?>
                <div class="navicon">
                    <i class='bx bx-chevron-right'></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="link-new">
            <div class="link-item">
                <div class="fw-normal fs-7set text-primary link-items btn-newtab" data-link="<?= getURL($t['menulink']) ?>">Open link in new tab</div>
                <div class="fw-normal fs-7set text-primary link-items btn-copylink">
                    <input type="hidden" id="copys" value="<?= getURL($t['menulink']) ?>">
                    Copy link address
                </div>
            </div>
        </div>
        <?php if (!empty($checkSub)) : ?>
            <div class="childSub">
                <?= getSidebarView($t['menuid']); ?>
            </div>
        <?php else : ?>
            <?= getSidebarView($t['menuid']); ?>
        <?php endif; ?>
    </div>
<?php } ?>