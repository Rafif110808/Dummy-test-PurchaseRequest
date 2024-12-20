<?php if (count($rowsub) > 0) : ?>
    <div class="submenu-child-div subChild">
        <?php foreach ($rowsub as $dm) { ?>
            <?php
            $nextSub = getSidebarSubMenu($dm['menuid']);
            $nextShrink = sidebarShrink($dm['menuid']);
            ?>
            <div class="sub-item submenu-item-side fs-7set <?= (!empty($nextSub) ? 'haveChild' : '') ?>" link="<?= getURL($dm['menulink']) ?>" style="padding-left: 14px !important;">
                <div class="dflex align-center">
                    <div class="dflex align-center">
                        <?php if (!empty($nextSub)) : ?>
                            <i class="bx bxs-right-arrow margin-r-3" style="font-size: 6px;"></i>
                        <?php endif; ?>
                        <?= ucwords($dm['menuname']) ?>
                    </div>
                </div>
            </div>
            <div id="listSub">
                <?= $nextShrink ?>
            </div>
        <?php } ?>
    </div>
<?php else :
    return null; ?>
<?php endif; ?>