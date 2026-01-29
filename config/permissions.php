<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Permission Definitions
    |--------------------------------------------------------------------------
    |
    | Here we define the available permissions and which roles possess them.
    |
    */

    'roles' => [
        'admin' => 'Administrator',
        'editor' => 'Editor',
        'member' => 'Member',
        'viewer' => 'Viewer',
    ],

    'permissions' => [
        'document.create' => [
            'label' => 'Create Documents',
            'roles' => ['admin', 'editor'],
        ],
        'document.edit' => [
            'label' => 'Edit Documents',
            'roles' => ['admin', 'editor'],
        ],
        'document.delete' => [
            'label' => 'Delete Documents',
            'roles' => ['admin'],
        ],
        'document.view' => [
            'label' => 'View Documents',
            'roles' => ['admin', 'editor', 'member', 'viewer'],
        ],

        'team.manage' => [
            'label' => 'Manage Team Members',
            'roles' => ['admin'],
        ],
        'team.invite' => [
            'label' => 'Invite New Members',
            'roles' => ['admin'],
        ],
        'team.view' => [
            'label' => 'View Team List',
            'roles' => ['admin', 'editor', 'member', 'viewer'],
        ],

        'settings.manage' => [
            'label' => 'Manage Business Settings',
            'roles' => ['admin'],
        ],
        'settings.signature' => [
            'label' => 'Manage Signature',
            'roles' => ['admin', 'editor', 'member', 'viewer'],
        ],
    ],
];
