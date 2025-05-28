<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PustakaKu - <?php echo isset($page_title) ? $page_title : 'Library Management System'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #2e59d9;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --danger-color: #e74a3b;
            --warning-color: #f6c23e;
            --info-color: #36b9cc;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
            --white: #ffffff;
            --border-color: #e3e6f0;
            --shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            --shadow-lg: 0 1rem 3rem rgba(0, 0, 0, 0.175);
            --border-radius: 0.75rem;
            --border-radius-sm: 0.5rem;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--light-color);
            color: var(--dark-color);
            line-height: 1.6;
            padding-bottom: 80px;
            font-size: 14px;
        }

        /* Container and Layout */
        .container {
            max-width: 100%;
            padding-left: 1rem;
            padding-right: 1rem;
        }

        /* Typography */
        .h1, .h2, .h3, .h4, .h5, .h6,
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1rem;
        }

        .h3, h3 {
            font-size: 1.5rem;
        }

        /* Cards */
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            margin-bottom: 1.5rem;
            background: var(--white);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 600;
            font-size: 1rem;
        }

        .card-body {
            padding: 1.25rem;
        }

        /* Buttons */
        .btn {
            border-radius: var(--border-radius-sm);
            font-weight: 500;
            padding: 0.5rem 1rem;
            transition: var(--transition);
            border: none;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            box-shadow: 0 0.125rem 0.25rem rgba(78, 115, 223, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 0.25rem 0.5rem rgba(78, 115, 223, 0.4);
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success-color) 0%, #17a673 100%);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #c0392b 100%);
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #f39c12 100%);
            color: var(--white);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #6c757d 100%);
        }

        /* Book Cards */
        .book-card {
            height: 100%;
            transition: var(--transition);
            border-radius: var(--border-radius);
            overflow: hidden;
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .book-image {
            height: 180px;
            width: 100%;
            object-fit: cover;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .book-card .card-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            height: calc(100% - 180px);
        }

        .book-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 0.5rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-card .card-text {
            font-size: 0.8rem;
            color: var(--secondary-color);
            flex-grow: 1;
        }

        /* Mobile Navigation */
        .mobile-navbar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--white);
            border-top: 1px solid var(--border-color);
            box-shadow: 0 -2px 20px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 0.5rem 0;
            backdrop-filter: blur(10px);
        }

        .mobile-navbar a {
            color: var(--secondary-color);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 0.25rem;
            transition: var(--transition);
            border-radius: var(--border-radius-sm);
            margin: 0 0.25rem;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .mobile-navbar a:hover,
        .mobile-navbar a.active {
            color: var(--primary-color);
            background: rgba(78, 115, 223, 0.1);
        }

        .mobile-navbar i {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        /* Badges */
        .badge {
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: var(--border-radius-sm);
            font-size: 0.75rem;
        }

        .badge.bg-success {
            background: var(--success-color) !important;
        }

        .badge.bg-danger {
            background: var(--danger-color) !important;
        }

        .badge.bg-warning {
            background: var(--warning-color) !important;
            color: var(--white) !important;
        }

        .badge.bg-info {
            background: var(--info-color) !important;
        }

        /* Forms */
        .form-control,
        .form-select {
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius-sm);
            padding: 0.75rem;
            font-size: 0.875rem;
            transition: var(--transition);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        /* Alerts */
        .alert {
            border: none;
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(28, 200, 138, 0.1) 0%, rgba(23, 166, 115, 0.1) 100%);
            color: var(--success-color);
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(231, 74, 59, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        /* Tables */
        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: var(--dark-color);
            padding: 1rem 0.75rem;
        }

        .table td {
            padding: 1rem 0.75rem;
            border-top: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Loading States */
        .loading {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }

            .h3, h3 {
                font-size: 1.25rem;
            }

            .book-image {
                height: 140px;
            }

            .book-card .card-body {
                padding: 0.75rem;
                height: calc(100% - 140px);
            }

            .book-card .card-title {
                font-size: 0.85rem;
            }

            .book-card .card-text {
                font-size: 0.75rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.5rem 0.75rem;
            }

            .btn-sm {
                font-size: 0.75rem;
                padding: 0.375rem 0.5rem;
            }

            .table-responsive {
                font-size: 0.8rem;
            }

            .table th,
            .table td {
                padding: 0.75rem 0.5rem;
            }

            .mobile-navbar a {
                font-size: 0.7rem;
                padding: 0.375rem 0.125rem;
            }

            .mobile-navbar i {
                font-size: 1.1rem;
            }
        }

        @media (max-width: 576px) {
            body {
                font-size: 13px;
            }

            .container {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .card-body {
                padding: 0.75rem;
            }

            .h3, h3 {
                font-size: 1.1rem;
            }

            .book-image {
                height: 120px;
            }

            .book-card .card-body {
                padding: 0.5rem;
                height: calc(100% - 120px);
            }

            .book-card .card-title {
                font-size: 0.8rem;
                margin-bottom: 0.25rem;
            }

            .book-card .card-text {
                font-size: 0.7rem;
            }

            .btn {
                font-size: 0.75rem;
                padding: 0.375rem 0.5rem;
            }

            .btn-sm {
                font-size: 0.7rem;
                padding: 0.25rem 0.375rem;
            }

            .badge {
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
            }

            .form-control,
            .form-select {
                font-size: 0.8rem;
                padding: 0.5rem;
            }

            .mobile-navbar {
                padding: 0.25rem 0;
            }

            .mobile-navbar a {
                font-size: 0.65rem;
                padding: 0.25rem 0.125rem;
                margin: 0 0.125rem;
            }

            .mobile-navbar i {
                font-size: 1rem;
                margin-bottom: 0.125rem;
            }
        }

        @media (max-width: 400px) {
            .container {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }

            .book-image {
                height: 100px;
            }

            .book-card .card-body {
                height: calc(100% - 100px);
            }

            .mobile-navbar a span {
                display: none;
            }

            .mobile-navbar i {
                font-size: 1.1rem;
                margin-bottom: 0;
            }

            .mobile-navbar a {
                padding: 0.5rem 0.25rem;
            }
        }

        /* Utility Classes */
        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .text-truncate-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }

        .shadow {
            box-shadow: var(--shadow) !important;
        }

        .shadow-lg {
            box-shadow: var(--shadow-lg) !important;
        }

        .rounded-lg {
            border-radius: var(--border-radius) !important;
        }

        .rounded-sm {
            border-radius: var(--border-radius-sm) !important;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--light-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Animations */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-up {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="container mt-3">
