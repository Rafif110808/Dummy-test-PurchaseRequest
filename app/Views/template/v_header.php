<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $title ?></title>
    <?= $this->include('template/v_import') ?>
    <style>
        .cropper-crop-box,
        .cropper-view-box {
            border-radius: 100%;
        }

        .cropper-view-box {
            box-shadow: 0 0 0 1px #39f;
            outline: 0;
        }

        #debug-bar {
            z-index: 1898123789 !important;
        }
    </style>
</head>

<body>
    <div class="global-containers">
        <?= $this->include('template/v_sidebar') ?>
        <div class="content">
            <div class="container-fluid p-x-y">
                <?= $this->include('template/v_navbar') ?>