<?php

namespace App\Services;

class MenuService
{
    public static function getMenuForUser($user)
    {
        $isRabotnik = $user->hasRole('Rabotnik');
        $isAdmin = $user->hasRole('Admin');

        $menu = [
            [
                'href' => '/dashboard',
                'title' => 'Dashboard',
                'icon' => 'fa fa-user',
                'class' => '', // Add any custom classes needed
                'disabled' => false,
                'hiddenOnCollapse' => false,
            ],
            [
                'href' => '',
                'title' => 'Order',
                'icon' => 'fas fa-receipt',
                'child' => [
                    [
                        'href' => '/orders',
                        'title' => 'View all orders',
                        'class' => '',
                        'disabled' => false,
                    ],
                    [
                        'href' => '/orders/create',
                        'title' => 'Create Order',
                        'class' => '',
                        'disabled' => false,
                    ],
                ],
            ],
            // ... rest of your menu items with all necessary properties
        ];

        if ($isRabotnik) {
            $menu = array_merge($menu, [
                [                
                    'href' => '',
                    'title' => 'Materials',
                    'icon' => 'fa-solid fa-layer-group',
                    'child' => [
                        [
                            'href' => '/materials/small',
                            'title' => 'Small Materials',
                        ],
                        [
                            'href' => '/materials/large',
                            'title' => 'Large Materials',
                        ],
                    ],
                
                ],
                [
                    'href' => '',
                    'title' => 'Clients',
                    'icon' => 'fa-solid fa-users',
                    'child' => [
                        [
                            'href' => '/clients',
                            'title' => 'View all clients',
                        ],
                        [
                            'href' => '/clients/create',
                            'title' => 'Add new client',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Production',
                    'icon' => 'fa-solid fa-chart-pie',
                    'child' => [
                        [
                            'href' => '/production',
                            'title' => 'Dashboard',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Machines',
                    'icon' => 'fa-solid fa-print',
                    'child' => [
                        [
                            'href' => '/machines',
                            'title' => 'Dashboard',
                        ],
                    ],
                ],
                [
                    'href' => '/refinements',
                    'title' => 'Refinements',
                    'icon' => 'fa-solid fa-screwdriver-wrench',
                ],
                [
                    'href' => '',
                    'title' => 'Articles',
                    'icon' => 'fa-regular fa-newspaper',
                    'child' => [
                        [
                            'href' => '/articles',
                            'title' => 'View all articles',
                        ],
                        [
                            'href' => '/articles/create',
                            'title' => 'Add new article',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Catalog',
                    'icon' => 'fa-solid fa-book-open',
                    'child' => [
                        [
                            'href' => '/catalog',
                            'title' => 'View all catalog items',
                        ],
                        [
                            'href' => '/catalog/create',
                            'title' => 'Add new catalog item',
                        ]
                    ],
                ],
            ]);
        }

        if ($isAdmin) {
            $menu = array_merge($menu, [
                
                [                
                    'href' => '',
                    'title' => 'Materials',
                    'icon' => 'fa-solid fa-layer-group',
                    'child' => [
                        [
                            'href' => '/materials/small',
                            'title' => 'Small Materials',
                        ],
                        [
                            'href' => '/materials/large',
                            'title' => 'Large Materials',
                        ],
                    ],
                
                ],
                [
                    'href' => '/warehouse',
                    'title' => 'Warehouse',
                    'icon' => 'fa-solid fa-warehouse',
                ],
                [
                    'href' => '',
                    'title' => 'Clients',
                    'icon' => 'fa-solid fa-users',
                    'child' => [
                        [
                            'href' => '/clients',
                            'title' => 'View all clients',
                        ],
                        [
                            'href' => '/clients/create',
                            'title' => 'Add new client',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Production',
                    'icon' => 'fa-solid fa-chart-pie',
                    'child' => [
                        [
                            'href' => '/production',
                            'title' => 'Dashboard',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Machines',
                    'icon' => 'fa-solid fa-print',
                    'child' => [
                        [
                            'href' => '/machines',
                            'title' => 'Dashboard',
                        ],
                    ],
                ],
                [
                    'href' => '/refinements',
                    'title' => 'Refinements',
                    'icon' => 'fa-solid fa-screwdriver-wrench',
                ],
                [
                    'href' => '',
                    'title' => 'Articles',
                    'icon' => 'fa-regular fa-newspaper',
                    'child' => [
                        [
                            'href' => '/articles',
                            'title' => 'View all articles',
                        ],
                        [
                            'href' => '/articles/create',
                            'title' => 'Add new article',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Receipt',
                    'icon' => 'fa-solid fa-file-invoice',
                    'child' => [
                        [
                            'href' => '/receipt',
                            'title' => 'View all receipts',
                            ],
                        [
                            'href' => '/receipt/create',
                            'title' => 'Add new receipt',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Finances',
                    'icon' => 'fa-solid fa-file-invoice-dollar',
                    'child' => [
                        [
                            'href' => '/allInvoices',
                            'title' => 'Invoiced Orders',
                        ],
                        [
                            'href' => '/notInvoiced',
                            'title' => 'Uninvoiced Orders',
                        ],
                        [
                            'href' => '/incomingInvoice',
                            'title' => 'Incoming Invoice',
                        ],
                        [
                            'href' => '/statements',
                            'title' => 'Bank Statements',
                        ],
                        [
                            'href' => '/cardStatements',
                            'title' => 'Client Card Statements',
                        ],
                    ],
                ],  
                [
                    'href' => '',
                    'title' => 'Catalog',
                    'icon' => 'fa-solid fa-book-open',
                    'child' => [
                        [
                            'href' => '/catalog',
                            'title' => 'View all catalog items',
                        ],
                        [
                            'href' => '/catalog/create',
                            'title' => 'Add new catalog item',
                        ],
                        [
                            'href' => '/quantity-prices',
                            'title' => 'Prices per quantity',
                        ],
                        [
                            'href' => '/client-prices',
                            'title' => 'Prices per client',
                        ],
                    ],
                ],
                [
                    'href' => '',       
                    'title' => 'Offers',
                    'icon' => 'fa-solid fa-file-invoice',
                    'child' => [
                        [
                            'href' => '/offers',
                            'title' => 'View all offers'
                        ],
                        [
                            'href' => '/offer/create',
                            'title' => 'Add new offer'
                        ],
                    ],
                ],
                [
                    'href' => '/analytics',
                    'title' => 'Analytics',
                    'icon' => 'fa-solid fa-chart-pie',
                    'child' => [
                        [
                            'href' => '/analytics-orders',
                            'title' => 'Order - User',
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'User Management',
                'icon' => 'fa-solid fa-user-gear',
                'child' => [
                    [
                        'href' => '/user-management',
                        'title' => 'User Management',
                        'class' => '',
                        'disabled' => false,
                    ],
                    [
                        'href' => '/user-roles',
                        'title' => 'User Roles',
                        'class' => '',
                        'disabled' => false,
                    ],
                    [
                        'href' => '/account-creation',
                        'title' => 'Account Creation',
                        'class' => '',
                        'disabled' => false,
                        ],
                    ],
                ],
                [
                    'href' => '',
                    'title' => 'Questions',
                    'icon' => 'fa-solid fa-question',
                    'child' => [
                        [
                            'href' => '/admin/questions',
                            'title' => 'Production Questions',
                        ],
                    ],
                ],
            ]);
        }

        return $menu;
    }
} 