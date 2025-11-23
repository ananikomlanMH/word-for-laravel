<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | Disk where generated Word documents will be saved by default.
    |
    */
    'default_disk' => env('WORD_STORAGE_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | Directory for temporary file storage during document generation.
    |
    */
    'temp_dir' => env('WORD_TEMP_DIR', sys_get_temp_dir()),

    /*
    |--------------------------------------------------------------------------
    | Default Page Orientation
    |--------------------------------------------------------------------------
    |
    | Default page orientation for documents.
    | Options: 'portrait', 'landscape'
    |
    */
    'default_orientation' => env('WORD_DEFAULT_ORIENTATION', 'portrait'),

    /*
    |--------------------------------------------------------------------------
    | Default Section Style
    |--------------------------------------------------------------------------
    |
    | Default margins and section styling in twips (1/20 of a point).
    | 1000 twips = approximately 1.76 cm
    |
    */
    'section_style' => [
        'marginTop' => 1000,    // ~1.76 cm
        'marginBottom' => 1000, // ~1.76 cm
        'marginLeft' => 1000,   // ~1.76 cm
        'marginRight' => 1000,  // ~1.76 cm
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Font Settings
    |--------------------------------------------------------------------------
    |
    | Default font family and size for document text.
    |
    */
    'default_font' => [
        'name' => 'Arial',
        'size' => 12, // points
    ],
];
