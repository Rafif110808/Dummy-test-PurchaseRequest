<?php if ($isImage): ?>
    <div class="text-center">
        <img src="<?= $fileUrl ?>" alt="<?= $file['filename'] ?>" class="img-fluid" style="max-width: 100%; max-height: 500px;">
    </div>
<?php else: ?>
    <div class="text-center">
        <i class="bx bx-file" style="font-size: 5rem; color: #6c757d;"></i>
        <p class="mt-3">File: <?= $file['filerealname'] ?? $file['filename'] ?></p>
        <a href="<?= base_url('files/download/' . $file['fileid']) ?>" class="btn btn-primary">
            <i class="bx bx-download"></i> Download File
        </a>
    </div>
<?php endif; ?>

<div class="mt-3">
    <table class="table table-sm table-bordered">
        <tr>
            <th width="30%">File Name</th>
            <td><?= htmlspecialchars($file['filerealname'] ?? $file['filename']) ?></td>
        </tr>
        <tr>
            <th>Directory</th>
            <td><?= htmlspecialchars($file['filedirectory']) ?></td>
        </tr>
        <tr>
            <th>Created Date</th>
            <td><?= !empty($file['created_date']) ? date('d-m-Y H:i', strtotime($file['created_date'])) : '-' ?></td>
        </tr>
        <tr>
            <th>Created By</th>
            <td><?= htmlspecialchars($file['created_by_name'] ?? '-') ?></td>
        </tr>
    </table>
</div>
