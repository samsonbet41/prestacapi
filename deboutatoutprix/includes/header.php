<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Admin PrestaCapi</title>
    <link rel="stylesheet" href="/deboutatoutprix/assets/css/admin.css">
    <link rel="stylesheet" href="/deboutatoutprix/assets/css/dashboard.css">
    <link rel="stylesheet" href="/deboutatoutprix/assets/css/userlist.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../images/favicon/favicon.ico">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
</head>
<body class="admin-layout">
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="header-logo">
                    <img src="../images/logo.png" alt="PrestaCapi Admin" onerror="this.style.display='none'">
                    <span class="logo-text">PrestaCapi <small>Admin</small></span>
                </div>
            </div>
            
            <div class="header-center">
                <div class="header-search">
                    <input type="text" placeholder="Rechercher..." id="globalSearch">
                    <button type="button" class="search-btn">🔍</button>
                </div>
            </div>
        </header>