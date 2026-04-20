<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Text editor — allowed file extensions (case-insensitive)
    |--------------------------------------------------------------------------
    */
    'editable_extensions' => [
        'txt', 'text', 'html', 'htm', 'css', 'js', 'mjs', 'json', 'md', 'markdown',
        'xml', 'svg', 'env', 'gitignore', 'php', 'log', 'yml', 'yaml', 'ini', 'sh', 'sql',
    ],

    /*
    | Maximum file size (bytes) that can be loaded/saved in the text editor.
    */
    'max_edit_bytes' => 2 * 1024 * 1024,

];
