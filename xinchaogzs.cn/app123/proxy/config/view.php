<?php
/**
 * view.php
 * @author houguang<houguang@sina.cn>
 * @date 2020-03-02 12:48
 * @description
 */
return [
    // 模板引擎类型使用ThinkTemplate
    'type' => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule' => 1,
    // 模板目录名
    'view_dir_name' => 'view',
    // 模板后缀
    'view_suffix' => 'html',
    'tpl_replace_string' => [
        '__CSS__' => '/static/admin/css',
        '__JS__' => '/static/admin/js',
        "__IMAGES__" => "/static/admin/img",
        "__PLUGINS__" => "/static/admin/plugins"
    ],
    "plugins" => [
        "upload" => [
            "/static/admin/plugins/webuploader/webuploader.html5only.min.js",
            "/static/admin/plugins/webuploader/webuploader.css",
        ],
        "filepond" => [
            "/static/node_modules/filepond/dist/filepond.css",
            "/static/node_modules/filepond/dist/filepond.js",
            "/static/node_modules/jquery-filepond/filepond.jquery.js",
            "/static/node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.css",
            "/static/node_modules/filepond-plugin-image-preview/dist/filepond-plugin-image-preview.js",
            "/static/node_modules/filepond-plugin-image-edit/dist/filepond-plugin-image-edit.css",
            "/static/node_modules/filepond-plugin-image-edit/dist/filepond-plugin-image-edit.js",
            "/static/node_modules/filepond-plugin-image-crop/dist/filepond-plugin-image-crop.js"
        ],
        "ueditor" => [
            ""
        ],
        "select2" => [
            "/static/admin/plugins/select2/css/select2.min.css",
            "/static/admin/plugins/select2/js/select2.full.min.js",
            "/static/admin/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css"
        ],
        "switch" => [
            "/static/admin/plugins/bootstrap-switch/js/bootstrap-switch.min.js",
            "/static/admin/plugins/bootstrap-switch/css/bootstrap3/bootstrap-switch.min.css"
        ],
        "tags_input" => [
            "/static/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.css",
            "/static/node_modules/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js"
        ],
        "chart_js" => [
            "/static/node_modules/chart.js/dist/Chart.css",
            "/static/node_modules/chart.js/dist/Chart.js"
        ]
    ]
];