<?php
// lib_report_view.php
session_start();
ob_start();
date_default_timezone_set('Asia/Colombo');

include("includes/header.php");
require_once __DIR__ . '/database/connection.php';

if (!isset($_SESSION['username'])) {
    echo '<script>window.location.href = "login";</script>';
    exit;
}

require_once 'PermissionChecking.php';

// Fetch categories from lib_printing_jobs (item_category)
$categories = [];
$catResult = $conn->query("SELECT DISTINCT item_category FROM lib_printing_jobs WHERE item_category IS NOT NULL AND item_category != '' ORDER BY item_category");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['item_category'];
    }
}

// Fetch items from lib_printing_jobs - get unique item_name_display with size
$items = [];
$itemResult = $conn->query("SELECT DISTINCT item_name_display, size FROM lib_printing_jobs WHERE item_name_display IS NOT NULL AND item_name_display != '' ORDER BY item_name_display");
if ($itemResult) {
    while ($row = $itemResult->fetch_assoc()) {
        $items[] = $row;
    }
}

$apiBase = 'libPOS/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Report – BMS Library</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
        .card-header { background: #f8f9fa; border-bottom: 2px solid #042d5c; }
        .stat-box { background: white; padding: 12px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
        .stat-number { font-size: 22px; font-weight: 700; color: #042d5c; }
        .stat-number-red { font-size: 22px; font-weight: 700; color: #dc3545; }
        .report-table-container { max-height: 500px; overflow-y: auto; }
        .report-table { font-size: 0.78rem; }
        .report-table th { background: #1e2831; color: white; position: sticky; top: 0; z-index: 10; }
        .report-table td { border-bottom: 1px solid #e9ecef; }
        .report-table tr:last-child td { border-bottom: none; }
        .report-table .grand-total { background: #042d5c !important; color: #ffc107 !important; }
        .report-table .grand-total td {background-color: #723649; color: #e4e4e4 !important; font-size: 1rem; }
        .report-table .date-header { background: #e8f0fe !important; font-weight: bold; }
        .report-table .date-header td { color: #042d5c; }
        .report-table .bill-total { background: #fff3cd !important; font-weight: bold; }
        .report-table .date-total { background: #d4edda !important; font-weight: bold; }
        .report-table .date-total td {background-color: #677069; color: #ffffff !important; }
        .chart-container { position: relative; height: 300px; }
        .filter-section { background: white; padding: 15px; border-radius: 10px; margin-bottom: 15px; }
        .nav-tabs .nav-link { font-size: 0.85rem; padding: 8px 16px; }
        .nav-tabs .nav-link.active { background: #042d5c; color: white; border-color: #042d5c; }
        .btn-primary { background: #042d5c; border: none; }
        .btn-primary:hover { background: #031f40; }
        .btn-success { background: #28a745; border: none; }
        .btn-success:hover { background: #1e7e34; }
        .filter-radio-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .filter-radio-group .btn-check:checked+.btn { background: #042d5c; color: white; border-color: #042d5c; }
        .filter-radio-group .btn { font-size: 0.75rem; padding: 4px 12px; border-radius: 20px; border: 1px solid #ced4da; }
        .search-summary { background: #f8f9fa; padding: 8px 12px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid #042d5c; }
        .search-summary .badge { font-size: 0.7rem; }
        .chart-controls { background: #f8f9fa; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center; }
        .chart-controls select { width: auto; display: inline-block; }
        .chart-controls label { font-size: 0.75rem; font-weight: 600; margin-right: 5px; }
        .category-badge { display: inline-block; padding: 2px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; color: white; }
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; border: 1px solid #ddd; }
            #wrapper, #content-wrapper, #content { margin: 0 !important; padding: 0 !important; }
            #content .p-3 { padding: 10px !important; }
            .report-table-container { max-height: none !important; overflow: visible !important; }
            .report-table th { background: #2c3e50 !important; color: white !important; }
            .report-table .grand-total { background: #042d5c !important; color: #ffc107 !important; }
            .report-table .date-total { background: #d4edda !important; }
            .report-table .date-total td { color: #155724 !important; }
            .stat-box { box-shadow: none !important; border: 1px solid #ddd; }
            #wrapper > nav, #wrapper > #content-wrapper #content > .topnav,
            #wrapper > #content-wrapper #content > .p-3 > .d-sm-flex,
            .filter-section, .no-print, .btn, .search-summary, .summary-stats {
                display: none !important;
            }
            #wrapper > #content-wrapper #content > .p-3 {
                padding-top: 0 !important;
            }
            .card-header .float-end, .card-header .text-muted {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div id="wrapper">
    <?php include("nav.php"); ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            <?php include("includes/topnav.php"); ?>
            <div class="p-3">
                <div class="d-sm-flex align-items-center justify-content-between mb-4 no-print">
                    <h4 class="h4 mb-0 text-gray-800"><i class="bi bi-graph-up-arrow me-2"></i>Finance Report</h4>
                    <div>
                        <button class="btn btn-secondary btn-sm no-print" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                        <button class="btn btn-success btn-sm no-print" onclick="downloadReport('pdf')" style="background: #dc3545; border-color: #dc3545;">
                            <i class="bi bi-file-pdf"></i> PDF
                        </button>
                        <button class="btn btn-success btn-sm no-print" onclick="downloadReport('excel')" style="background: #1a7a3a; border-color: #1a7a3a;">
                            <i class="bi bi-file-excel"></i> Excel
                        </button>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section no-print">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">From</label>
                            <input type="date" class="form-control form-control-sm" id="reportFrom" value="<?= date('Y-m-01') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">To</label>
                            <input type="date" class="form-control form-control-sm" id="reportTo" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Report Type</label>
                            <select class="form-select form-select-sm" id="reportType" onchange="toggleReportFilters(); loadReport();">
                                <option value="all">All (Bill Summary)</option>
                                <option value="bill">Bill (Itemized)</option>
                                <option value="category">Category Wise</option>
                                <option value="item">Item Wise</option>
                                <option value="zreport">Z-Report (Hourly)</option>
                                <option value="xreport">X-Report (Daily)</option>
                                <option value="financial">Financial</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-primary btn-sm w-100" onclick="loadReport()">
                                <i class="bi bi-eye"></i> Show Report
                            </button>
                        </div>
                    </div>
                    
                    <!-- Category Radio Buttons -->
                    <div class="row mt-2" id="categoryFilter" style="display:none;">
                        <div class="col-12">
                            <label class="form-label small fw-bold me-2">Category:</label>
                            <div class="filter-radio-group">
                                <input type="radio" class="btn-check" name="categoryRadio" id="catAll" value="all" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="catAll">All</label>
                                <?php foreach ($categories as $catName): 
                                    $catId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $catName));
                                ?>
                                    <input type="radio" class="btn-check" name="categoryRadio" id="cat_<?= $catId ?>" value="<?= htmlspecialchars($catName) ?>">
                                    <label class="btn btn-outline-secondary btn-sm" for="cat_<?= $catId ?>">
                                        <?= htmlspecialchars($catName) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Item Radio Buttons -->
                    <div class="row mt-2" id="itemFilter" style="display:none;">
                        <div class="col-12">
                            <label class="form-label small fw-bold me-2">Item:</label>
                            <div class="filter-radio-group">
                                <input type="radio" class="btn-check" name="itemRadio" id="itemAll" value="all" checked>
                                <label class="btn btn-outline-secondary btn-sm" for="itemAll">All</label>
                                <?php foreach ($items as $item): 
                                    $displayName = $item['item_name_display'];
                                    if ($item['size']) {
                                        $displayName .= ' (' . $item['size'] . ')';
                                    }
                                    $itemId = strtolower(preg_replace('/[^a-zA-Z0-9]/', '_', $item['item_name_display']));
                                ?>
                                    <input type="radio" class="btn-check" name="itemRadio" id="item_<?= $itemId ?>" value="<?= htmlspecialchars($item['item_name_display']) ?>">
                                    <label class="btn btn-outline-secondary btn-sm" for="item_<?= $itemId ?>">
                                        <?= htmlspecialchars($displayName) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Content -->
                <div id="reportContent">
                    <div class="text-center py-5">
                        <i class="bi bi-graph-up-arrow" style="font-size: 48px; color: #c8d6e5;"></i>
                        <h5 class="mt-3 text-muted">Select report parameters and click Show Report</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var apiBase = '<?= $apiBase ?>';
var chartInstances = {};
var currentReportData = null;
var currentReportType = '';

// Auto reload when category or item radio changes
document.addEventListener('DOMContentLoaded', function() {
    loadReport();
    
    document.querySelectorAll('input[name="categoryRadio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            loadReport();
        });
    });
    
    document.querySelectorAll('input[name="itemRadio"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
            loadReport();
        });
    });
});

function toggleReportFilters() {
    var type = document.getElementById('reportType').value;
    document.getElementById('categoryFilter').style.display = (type === 'category') ? 'block' : 'none';
    document.getElementById('itemFilter').style.display = (type === 'item') ? 'block' : 'none';
}

function loadReport() {
    var from = document.getElementById('reportFrom').value;
    var to = document.getElementById('reportTo').value;
    var reportType = document.getElementById('reportType').value;
    
    var category = 'both';
    if (reportType === 'category') {
        var catRadio = document.querySelector('input[name="categoryRadio"]:checked');
        if (catRadio && catRadio.value !== 'all') {
            category = catRadio.value;
        }
    }
    
    var itemName = 'all';
    if (reportType === 'item') {
        var itemRadio = document.querySelector('input[name="itemRadio"]:checked');
        if (itemRadio && itemRadio.value !== 'all') {
            itemName = itemRadio.value;
        }
    }

    if (!from || !to) {
        alert('Please select date range.');
        return;
    }

    showLoading();

    var url = apiBase + 'lib_report_data.php?from=' + from + '&to=' + to + '&report_type=' + reportType;
    
    if (reportType === 'category') {
        url += '&category=' + encodeURIComponent(category);
    }
    
    if (reportType === 'item') {
        url += '&item_name=' + encodeURIComponent(itemName);
    }

    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success === false) {
                showError(data.error || 'Error loading report');
                return;
            }
            currentReportData = data;
            currentReportType = reportType;
            renderReport(data, reportType);
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Error loading report: ' + error.message);
        });
}

function renderReport(data, reportType) {
    var container = document.getElementById('reportContent');
    var jobs = data.jobs || [];
    var summary = data.summary || {};
    var from = data.from || '';
    var to = data.to || '';

    if (jobs.length === 0) {
        container.innerHTML = '<div class="alert alert-info text-center py-4">No data found for the selected period.</div>';
        return;
    }

    var html = '';
    
    html += `
        <div class="search-summary">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Report:</strong> ${reportType.toUpperCase()} &nbsp;|&nbsp;
            <strong>Period:</strong> ${from} to ${to} &nbsp;|&nbsp;
            <strong>Total Bills:</strong> <span class="badge bg-primary">${summary.total_jobs || 0}</span> &nbsp;|&nbsp;
            <strong>Total Revenue:</strong> <span class="badge bg-success">${formatCurrency(summary.total_revenue)}</span>
        </div>
    `;
    
    html += `
        <div class="row g-2 summary-stats mb-3">
            <div class="col"><div class="stat-box text-center"><div class="text-muted small">Total Bills</div><div class="stat-number">${summary.total_jobs || 0}</div></div></div>
            <div class="col"><div class="stat-box text-center"><div class="text-muted small">Total Revenue</div><div class="stat-number-red">${formatCurrency(summary.total_revenue)}</div></div></div>
            <div class="col"><div class="stat-box text-center"><div class="text-muted small">Print Revenue</div><div class="stat-number">${formatCurrency(summary.print_revenue)}</div></div></div>
            <div class="col"><div class="stat-box text-center"><div class="text-muted small">Binding Revenue</div><div class="stat-number">${formatCurrency(summary.binding_revenue)}</div></div></div>
            <div class="col"><div class="stat-box text-center"><div class="text-muted small">Total Discount</div><div class="stat-number">${formatCurrency(summary.total_discount)}</div></div></div>
        </div>
    `;

    html += `
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-table me-1"></i> ${reportType.toUpperCase()} Report</strong>
                <span class="text-muted small">${from} to ${to} | ${jobs.length} Bills</span>
            </div>
            <div class="card-body p-0">
                <div class="report-table-container">
                    ${buildReportTable(data, reportType)}
                </div>
            </div>
        </div>
    `;

    if (reportType === 'financial') {
        html += renderFinancialCharts(data);
    }

    container.innerHTML = html;
    hideLoading();
    
    if (reportType === 'financial') {
        setTimeout(function() {
            initCharts(data);
        }, 500);
    }
}

function renderFinancialCharts(data) {
    return `
        <div class="card mt-3">
            <div class="card-header">
                <strong><i class="bi bi-graph-up me-1"></i> Charts</strong>
            </div>
            <div class="card-body">
                <div class="chart-controls">
                    <div>
                        <label>Period:</label>
                        <select class="form-select form-select-sm d-inline-block" id="chartPeriod" onchange="updateCharts()">
                            <option value="day">Day</option>
                            <option value="week" selected>Week</option>
                            <option value="month">Month</option>
                            <option value="year">Year</option>
                        </select>
                    </div>
                    <div>
                        <label>Report:</label>
                        <select class="form-select form-select-sm d-inline-block" id="chartReportType" onchange="updateCharts()">
                            <option value="category">Category Wise</option>
                            <option value="item">Item Wise</option>
                        </select>
                    </div>
                    <div>
                        <label>Data:</label>
                        <select class="form-select form-select-sm d-inline-block" id="chartDataType" onchange="updateCharts()">
                            <option value="revenue">Revenue</option>
                            <option value="discount">Discount</option>
                            <option value="qty">Sales Qty</option>
                        </select>
                    </div>
                </div>
                <ul class="nav nav-tabs chart-tabs" id="chartTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#lineChart">Line Graph</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#comboChart">Combo Chart</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#pieChart">Pie Graph</a></li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="lineChart">
                        <div class="chart-container">
                            <canvas id="lineChartCanvas"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="comboChart">
                        <div class="chart-container">
                            <canvas id="comboChartCanvas"></canvas>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pieChart">
                        <div class="chart-container">
                            <canvas id="pieChartCanvas"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildReportTable(data, reportType) {
    var jobs = data.jobs || [];
    
    if (jobs.length === 0) {
        return '<div class="text-center py-4 text-muted">No data available</div>';
    }

    var html = '<table class="table table-bordered table-hover report-table mb-0">';
    html += '<thead><tr>';
    
    var headers = getReportHeaders(reportType);
    headers.forEach(h => {
        html += `<th>${h}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    html += getReportBody(reportType, jobs);
    
    html += '</tbody></table>';
    return html;
}

function getReportHeaders(reportType) {
    switch(reportType) {
        case 'all':
            return ['Date', 'Bill #', 'Customer', 'Batch', 'Type', 'Items', 'Qty', 'Total', 'User', 'Status'];
        case 'bill':
            return ['Date', 'Bill #', 'Time', 'Customer', 'Batch', 'Type', 'Item', 'Qty', 'Price', 'Disc%', 'Discount', 'Net', 'Status'];
        case 'category':
            return ['Date', 'Category', 'Jobs', 'Qty', 'Total Revenue'];
        case 'item':
            return ['Date', 'Bill #', 'Item', 'Category', 'Qty', 'Price', 'Disc%', 'Total'];
        case 'zreport':
            return ['Hour', 'Bills', 'Qty', 'Gross', 'Discount', 'Net', 'Avg per Bill'];
        case 'xreport':
            return ['Date', 'Bills', 'Qty', 'Gross', 'Discount', 'Net', 'Avg per Bill'];
        case 'financial':
            return ['Date', 'Bills', 'Qty', 'Revenue', 'Discount', 'Paid', 'Free', 'Print', 'Binding'];
        default:
            return ['Date', 'Bill #', 'Customer', 'Total', 'Status'];
    }
}

function getCategoryColor(category) {
    var colors = {
        'printing': '#007bff',
        'binding': '#13796b',
        'lamination': '#fd7e14',
        'cutting': '#dc3545',
        'other': '#6c757d'
    };
    var catLower = (category || '').toLowerCase();
    return colors[catLower] || '#6c757d';
}

function getReportBody(reportType, jobs) {
    var html = '';
    var grandTotal = 0;
    var grandQty = 0;
    var grandCount = 0;
    var summary = window.currentReportData ? window.currentReportData.summary : {};
    var totalDiscount = summary.total_discount || 0;
    
    var dateGroups = {};
    jobs.forEach(job => {
        var date = job.job_date || 'Unknown';
        if (!dateGroups[date]) dateGroups[date] = [];
        dateGroups[date].push(job);
    });
    
    var sortedDates = Object.keys(dateGroups).sort();
    
    sortedDates.forEach(date => {
        var bills = dateGroups[date];
        
        switch(reportType) {
            case 'all':
                html += `<tr class="date-header"><td colspan="10"><strong>=== ${date} ===</strong></td></tr>`;
                var dateTotal = 0;
                var dateQty = 0;
                bills.forEach(bill => {
                    var status = bill.is_free ? 'FREE' : 'PAID';
                    var userType = bill.user_type || 'Other';
                    var itemNames = bill.items.map(i => i.item_name).join(', ');
                    var billQty = bill.items.reduce((sum, i) => sum + i.qty, 0);
                    html += `<tr>
                        <td>${date}</td>
                        <td><strong>${bill.bill_id}</strong></td>
                        <td>${bill.name}</td>
                        <td>${bill.batch || '-'}</td>
                        <td>${userType}</td>
                        <td style="font-size:0.7rem;">${itemNames}</td>
                        <td>${billQty}</td>
                        <td><strong>${formatCurrency(bill.total)}</strong></td>
                        <td>${bill.user || 'N/A'}</td>
                        <td><span class="badge ${status === 'FREE' ? 'bg-danger' : 'bg-success'}">${status}</span></td>
                    </tr>`;
                    dateTotal += bill.total;
                    dateQty += billQty;
                    grandTotal += bill.total;
                    grandQty += billQty;
                    grandCount++;
                });
                html += `<tr class="date-total"><td colspan="6"><strong>--- DATE TOTAL ---</strong></td><td><strong>${dateQty}</strong></td><td><strong>${formatCurrency(dateTotal)}</strong></td><td colspan="2"></td></tr>`;
                break;
                
            case 'bill':
                html += `<tr class="date-header"><td colspan="13"><strong>=== ${date} ===</strong></td></tr>`;
                var dateTotal = 0;
                var dateQty = 0;
                bills.forEach(bill => {
                    var status = bill.is_free ? 'FREE' : 'PAID';
                    var userType = bill.user_type || 'Other';
                    var firstItem = true;
                    bill.items.forEach(item => {
                        html += `<tr>
                            <td>${firstItem ? date : ''}</td>
                            <td>${firstItem ? bill.bill_id : ''}</td>
                            <td>${firstItem ? (bill.job_time || '-') : ''}</td>
                            <td>${firstItem ? bill.name : ''}</td>
                            <td>${firstItem ? (bill.batch || '-') : ''}</td>
                            <td>${firstItem ? userType : ''}</td>
                            <td>${item.item_name}</td>
                            <td>${item.qty}</td>
                            <td>${formatCurrency(item.price)}</td>
                            <td>${item.discount_percent ? item.discount_percent + '%' : '-'}</td>
                            <td>${formatCurrency(item.discount)}</td>
                            <td>${formatCurrency(item.net_amount)}</td>
                            <td>${firstItem ? status : ''}</td>
                        </tr>`;
                        firstItem = false;
                        dateQty += item.qty;
                        grandQty += item.qty;
                    });
                    html += `<tr class="bill-total"><td colspan="10"><strong>--- BILL TOTAL ---</strong></td><td>${formatCurrency(bill.discount)}</td><td><strong>${formatCurrency(bill.total)}</strong></td><td></td></tr>`;
                    dateTotal += bill.total;
                    grandTotal += bill.total;
                    grandCount++;
                });
                html += `<tr class="date-total"><td colspan="10"><strong>--- DATE TOTAL ---</strong></td><td>${formatCurrency(dateTotal)}</td><td colspan="2"></td></tr>`;
                break;
                
            case 'category':
                html += `<tr class="date-header"><td colspan="5"><strong>=== ${date} ===</strong></td></tr>`;
                var catMap = {};
                bills.forEach(bill => {
                    bill.items.forEach(item => {
                        var cat = item.category || 'Other';
                        if (!catMap[cat]) catMap[cat] = { jobs: 0, qty: 0, total: 0 };
                        catMap[cat].jobs++;
                        catMap[cat].qty += item.qty;
                        catMap[cat].total += item.net_amount;
                    });
                });
                var dateTotal = 0;
                var dateQty = 0;
                var dateJobs = 0;
                Object.keys(catMap).sort().forEach(cat => {
                    var data = catMap[cat];
                    var catColor = getCategoryColor(cat);
                    html += `<tr>
                        <td>${date}</td>
                        <td><span class="category-badge" style="background:${catColor};">${cat}</span></td>
                        <td>${data.jobs}</td>
                        <td>${data.qty}</td>
                        <td><strong>${formatCurrency(data.total)}</strong></td>
                    </tr>`;
                    dateTotal += data.total;
                    dateQty += data.qty;
                    dateJobs += data.jobs;
                    grandTotal += data.total;
                    grandQty += data.qty;
                });
                html += `<tr class="date-total"><td colspan="2"><strong>--- DATE TOTAL ---</strong></td><td>${dateJobs}</td><td>${dateQty}</td><td><strong>${formatCurrency(dateTotal)}</strong></td></tr>`;
                break;
                
            case 'item':
                var itemMap = {};
                bills.forEach(bill => {
                    bill.items.forEach(item => {
                        var name = item.item_name || 'Unknown';
                        var size = item.size || '';
                        var displayName = name + (size ? ' (' + size + ')' : '');
                        if (!itemMap[displayName]) {
                            itemMap[displayName] = { 
                                qty: 0, 
                                total: 0, 
                                price: item.price, 
                                discount_percent: item.discount_percent || 0, 
                                category: item.category || 'Other',
                                bills: []
                            };
                        }
                        itemMap[displayName].qty += item.qty;
                        itemMap[displayName].total += item.net_amount;
                        if (item.discount_percent > itemMap[displayName].discount_percent) {
                            itemMap[displayName].discount_percent = item.discount_percent;
                        }
                        if (!itemMap[displayName].bills.includes(bill.bill_id)) {
                            itemMap[displayName].bills.push(bill.bill_id);
                        }
                    });
                });
                
                if (Object.keys(itemMap).length > 0) {
                    html += `<tr class="date-header"><td colspan="8"><strong>=== ${date} ===</strong></td></tr>`;
                    var dateTotal = 0;
                    var dateQty = 0;
                    Object.keys(itemMap).sort().forEach(name => {
                        var data = itemMap[name];
                        var catColor = getCategoryColor(data.category);
                        var billNumbers = data.bills.join(', ');
                        html += `<tr>
                            <td>${date}</td>
                            <td><strong>${billNumbers}</strong></td>
                            <td><strong>${name}</strong></td>
                            <td><span class="category-badge" style="background:${catColor};">${data.category}</span></td>
                            <td>${data.qty}</td>
                            <td>${formatCurrency(data.price)}</td>
                            <td>${data.discount_percent ? data.discount_percent + '%' : '-'}</td>
                            <td><strong>${formatCurrency(data.total)}</strong></td>
                        </tr>`;
                        dateTotal += data.total;
                        dateQty += data.qty;
                        grandTotal += data.total;
                        grandQty += data.qty;
                    });
                    html += `<tr class="date-total"><td colspan="4"><strong>--- DATE TOTAL ---</strong></td><td>${dateQty}</td><td></td><td></td><td><strong>${formatCurrency(dateTotal)}</strong></td></tr>`;
                }
                break;
                
            case 'zreport':
                var reportData = [];
                var totals = { bills: 0, qty: 0, revenue: 0, discount: 0 };
                bills.forEach(bill => {
                    var key = bill.job_time ? parseInt(bill.job_time) : 0;
                    var label = String(key).padStart(2, '0') + ':00 - ' + String(key+1).padStart(2, '0') + ':00';
                    if (!reportData[key]) reportData[key] = { label: label, bills: 0, qty: 0, revenue: 0, discount: 0 };
                    var billQty = bill.items.reduce((sum, i) => sum + i.qty, 0);
                    reportData[key].bills++;
                    reportData[key].qty += billQty;
                    reportData[key].revenue += bill.total;
                    reportData[key].discount += bill.discount;
                    totals.bills++;
                    totals.qty += billQty;
                    totals.revenue += bill.total;
                    totals.discount += bill.discount;
                });
                Object.keys(reportData).sort((a, b) => a - b).forEach(key => {
                    var data = reportData[key];
                    var avg = data.bills > 0 ? data.revenue / data.bills : 0;
                    html += `<tr>
                        <td>${data.label}</td>
                        <td>${data.bills}</td>
                        <td>${data.qty}</td>
                        <td>${formatCurrency(data.revenue + data.discount)}</td>
                        <td>${formatCurrency(data.discount)}</td>
                        <td>${formatCurrency(data.revenue)}</td>
                        <td>${formatCurrency(avg)}</td>
                    </tr>`;
                });
                html += `<tr class="grand-total"><td><strong>=== GRAND TOTAL ===</strong></td>
                    <td>${totals.bills}</td>
                    <td>${totals.qty}</td>
                    <td>${formatCurrency(totals.revenue + totals.discount)}</td>
                    <td>${formatCurrency(totals.discount)}</td>
                    <td>${formatCurrency(totals.revenue)}</td>
                    <td>${formatCurrency(totals.bills > 0 ? totals.revenue / totals.bills : 0)}</td>
                </tr>`;
                break;
                
            case 'xreport':
                var dateTotal = 0;
                var dateQty = 0;
                var dateBills = 0;
                var dateDiscount = 0;
                bills.forEach(bill => {
                    var billQty = bill.items.reduce((sum, i) => sum + i.qty, 0);
                    dateBills++;
                    dateQty += billQty;
                    dateTotal += bill.total;
                    dateDiscount += bill.discount;
                    grandTotal += bill.total;
                    grandQty += billQty;
                    grandCount++;
                });
                var avg = dateBills > 0 ? dateTotal / dateBills : 0;
                html += `<tr>
                    <td>${date}</td>
                    <td>${dateBills}</td>
                    <td>${dateQty}</td>
                    <td>${formatCurrency(dateTotal + dateDiscount)}</td>
                    <td>${formatCurrency(dateDiscount)}</td>
                    <td>${formatCurrency(dateTotal)}</td>
                    <td>${formatCurrency(avg)}</td>
                </tr>`;
                break;
                
            case 'financial':
                html += `<tr class="date-header"><td colspan="9"><strong>=== ${date} ===</strong></td></tr>`;
                var dateData = { bills: 0, qty: 0, revenue: 0, discount: 0, paid: 0, free: 0, print: 0, binding: 0 };
                bills.forEach(bill => {
                    var billQty = bill.items.reduce((sum, i) => sum + i.qty, 0);
                    dateData.bills++;
                    dateData.qty += billQty;
                    dateData.revenue += bill.total;
                    dateData.discount += bill.discount;
                    if (bill.is_free) dateData.free++;
                    else dateData.paid++;
                    
                    bill.items.forEach(item => {
                        var itemNet = item.net_amount || 0;
                        if (item.job_type === 'binding') {
                            dateData.binding += itemNet;
                        } else {
                            dateData.print += itemNet;
                        }
                    });
                });
                html += `<tr>
                    <td>${date}</td>
                    <td>${dateData.bills}</td>
                    <td>${dateData.qty}</td>
                    <td>${formatCurrency(dateData.revenue)}</td>
                    <td>${formatCurrency(dateData.discount)}</td>
                    <td>${dateData.paid}</td>
                    <td>${dateData.free}</td>
                    <td>${formatCurrency(dateData.print)}</td>
                    <td>${formatCurrency(dateData.binding)}</td>
                </tr>`;
                grandTotal += dateData.revenue;
                grandQty += dateData.qty;
                grandCount += dateData.bills;
                break;
        }
    });
    
    if (reportType === 'xreport') {
        var avgGrand = grandCount > 0 ? grandTotal / grandCount : 0;
        html += `<tr class="grand-total"><td><strong>=== GRAND TOTAL ===</strong></td>
            <td>${grandCount}</td>
            <td>${grandQty}</td>
            <td>${formatCurrency(grandTotal + totalDiscount)}</td>
            <td>${formatCurrency(totalDiscount)}</td>
            <td>${formatCurrency(grandTotal)}</td>
            <td>${formatCurrency(avgGrand)}</td>
        </tr>`;
    }
    
    if (reportType !== 'zreport' && reportType !== 'xreport' && reportType !== 'item') {
        var colspan = reportType === 'all' ? 6 : (reportType === 'bill' ? 10 : (reportType === 'category' ? 2 : 3));
        html += `<tr class="grand-total">
            <td colspan="${colspan}"><strong>=== GRAND TOTAL ===</strong></td>
            <td><strong>${grandQty}</strong></td>
            <td colspan="3" style="text-align: center; font-size: 1.3rem; "><strong>${formatCurrency(grandTotal)}</strong></td>
        </tr>`;
        html += `<tr><td colspan="${reportType === 'category' ? 5 : 10}"><strong>Total Bills: ${grandCount}</strong></td></tr>`;
    }
    
    if (reportType === 'item') {
        html += `<tr class="grand-total">
            <td colspan="4"><strong>=== GRAND TOTAL ===</strong></td>
            <td><strong>${grandQty}</strong></td>
            <td></td>
            <td></td>
            <td><strong>${formatCurrency(grandTotal)}</strong></td>
        </tr>`;
        html += `<tr><td colspan="8"><strong>Total Bills: ${grandCount}</strong></td></tr>`;
    }
    
    return html;
}

function formatCurrency(value) {
    var num = parseFloat(value) || 0;
    return num.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function initCharts(data) {
    var jobs = data.jobs || [];
    var chartPeriod = document.getElementById('chartPeriod') ? document.getElementById('chartPeriod').value : 'week';
    var chartReportType = document.getElementById('chartReportType') ? document.getElementById('chartReportType').value : 'category';
    var chartDataType = document.getElementById('chartDataType') ? document.getElementById('chartDataType').value : 'revenue';
    
    var filteredData = filterDataByPeriod(jobs, chartPeriod);
    
    var from = document.getElementById('reportFrom').value;
    var to = document.getElementById('reportTo').value;
    var allDates = getDateRange(from, to);
    
    var dateGroups = {};
    var categoryMap = {};
    var itemMap = {};
    
    filteredData.forEach(job => {
        var date = job.job_date || 'Unknown';
        if (!dateGroups[date]) {
            dateGroups[date] = { revenue: 0, discount: 0, qty: 0, categories: {}, items: {} };
        }
        var billQty = job.items.reduce((sum, i) => sum + i.qty, 0);
        dateGroups[date].revenue += job.total;
        dateGroups[date].discount += job.discount;
        dateGroups[date].qty += billQty;
        
        job.items.forEach(item => {
            var cat = item.category || 'Other';
            
            var displayName = (item.item_name_display && item.item_name_display.trim()) ||
                              (item.item_name && item.item_name.trim()) ||
                              'Unknown';
            
            displayName = displayName.toLowerCase().trim();
            displayName = displayName.replace(/\b\w/g, function(c){
                return c.toUpperCase();
            });
            
            if (!dateGroups[date].categories[cat]) {
                dateGroups[date].categories[cat] = { revenue: 0, discount: 0, qty: 0 };
            }
            dateGroups[date].categories[cat].revenue += item.net_amount;
            dateGroups[date].categories[cat].discount += item.discount;
            dateGroups[date].categories[cat].qty += item.qty;
            
            if (!dateGroups[date].items.hasOwnProperty(displayName)) {
                dateGroups[date].items[displayName] = {
                    revenue: 0,
                    discount: 0,
                    qty: 0
                };
            }
            dateGroups[date].items[displayName].revenue += item.net_amount;
            dateGroups[date].items[displayName].discount += item.discount;
            dateGroups[date].items[displayName].qty += item.qty;
            
            if (!categoryMap[cat]) {
                categoryMap[cat] = { revenue: 0, discount: 0, qty: 0 };
            }
            categoryMap[cat].revenue += item.net_amount;
            categoryMap[cat].discount += item.discount;
            categoryMap[cat].qty += item.qty;
            
            if (!itemMap.hasOwnProperty(displayName)) {
                itemMap[displayName] = {
                    revenue: 0,
                    discount: 0,
                    qty: 0
                };
            }
            itemMap[displayName].revenue += Number(item.net_amount || 0);
            itemMap[displayName].discount += Number(item.discount || 0);
            itemMap[displayName].qty += Number(item.qty || 0);
        });
    });
    
    var dates = [];
    var totalRevenue = [];
    var totalDiscount = [];
    var totalQty = [];
    
    allDates.forEach(date => {
        dates.push(date);
        if (dateGroups[date]) {
            totalRevenue.push(dateGroups[date].revenue);
            totalDiscount.push(dateGroups[date].discount);
            totalQty.push(dateGroups[date].qty);
        } else {
            totalRevenue.push(0);
            totalDiscount.push(0);
            totalQty.push(0);
        }
    });
    
    Object.keys(chartInstances).forEach(key => {
        if (chartInstances[key]) {
            chartInstances[key].destroy();
            delete chartInstances[key];
        }
    });
    
    var colors = ['#042d5c', '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6c757d', '#fd7e14', '#6610f2', '#e83e8c', '#20c997', '#00bcd4', '#9c27b0'];
    var isCategory = chartReportType === 'category';
    var dataSource = isCategory ? categoryMap : itemMap;
    var dataLabels = Object.keys(dataSource);
    var dataColors = dataLabels.map((_, i) => colors[i % colors.length]);
    
    var datasets = [];
    var dataType = chartDataType;
    
    if (dataLabels.length > 0) {
        dataLabels.forEach((label, index) => {
            var dataPoints = [];
            allDates.forEach(date => {
                if (dateGroups[date]) {
                    var group = isCategory ? dateGroups[date].categories[label] : dateGroups[date].items[label];
                    if (group) {
                        dataPoints.push(dataType === 'revenue' ? group.revenue : (dataType === 'discount' ? group.discount : group.qty));
                    } else {
                        dataPoints.push(0);
                    }
                } else {
                    dataPoints.push(0);
                }
            });
            datasets.push({
                label: label,
                data: dataPoints,
                borderColor: dataColors[index % dataColors.length],
                backgroundColor: dataColors[index % dataColors.length] + '33',
                fill: true,
                tension: 0.4
            });
        });
    } else {
        var totalData = dataType === 'revenue' ? totalRevenue : (dataType === 'discount' ? totalDiscount : totalQty);
        datasets.push({
            label: dataType === 'revenue' ? 'Total Revenue' : (dataType === 'discount' ? 'Total Discount' : 'Total Qty'),
            data: totalData,
            borderColor: '#042d5c',
            backgroundColor: 'rgba(4, 45, 92, 0.1)',
            fill: true,
            tension: 0.4
        });
    }
    
    var ctx1 = document.getElementById('lineChartCanvas');
    if (ctx1) {
        chartInstances.line = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: dates,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    var ctx2 = document.getElementById('comboChartCanvas');
    if (ctx2) {
        var comboDatasets = [];
        
        if (dataType === 'revenue') {
            var revenueData = [];
            var discountData = [];
            allDates.forEach(date => {
                if (dateGroups[date]) {
                    revenueData.push(dateGroups[date].revenue);
                    discountData.push(dateGroups[date].discount);
                } else {
                    revenueData.push(0);
                    discountData.push(0);
                }
            });
            comboDatasets = [
                {
                    label: 'Revenue (Bar)',
                    data: revenueData,
                    backgroundColor: 'rgba(4, 45, 92, 0.7)',
                    borderColor: '#042d5c',
                    borderWidth: 2,
                    order: 2
                },
                {
                    label: 'Discount (Line)',
                    data: discountData,
                    type: 'line',
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#dc3545',
                    order: 1,
                    yAxisID: 'y1'
                }
            ];
        } else if (dataType === 'qty') {
            var qtyData = [];
            var revData = [];
            allDates.forEach(date => {
                if (dateGroups[date]) {
                    qtyData.push(dateGroups[date].qty);
                    revData.push(dateGroups[date].revenue);
                } else {
                    qtyData.push(0);
                    revData.push(0);
                }
            });
            comboDatasets = [
                {
                    label: 'Qty (Bar)',
                    data: qtyData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: '#28a745',
                    borderWidth: 2,
                    order: 2
                },
                {
                    label: 'Revenue (Line)',
                    data: revData,
                    type: 'line',
                    borderColor: '#042d5c',
                    backgroundColor: 'rgba(4, 45, 92, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#042d5c',
                    order: 1,
                    yAxisID: 'y1'
                }
            ];
        } else {
            var discData = [];
            var revData2 = [];
            allDates.forEach(date => {
                if (dateGroups[date]) {
                    discData.push(dateGroups[date].discount);
                    revData2.push(dateGroups[date].revenue);
                } else {
                    discData.push(0);
                    revData2.push(0);
                }
            });
            comboDatasets = [
                {
                    label: 'Discount (Bar)',
                    data: discData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: '#dc3545',
                    borderWidth: 2,
                    order: 2
                },
                {
                    label: 'Revenue (Line)',
                    data: revData2,
                    type: 'line',
                    borderColor: '#042d5c',
                    backgroundColor: 'rgba(4, 45, 92, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#042d5c',
                    order: 1,
                    yAxisID: 'y1'
                }
            ];
        }
        
        chartInstances.combo = new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: dates,
                datasets: comboDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: {
                            callback: function(value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
    
    var ctx3 = document.getElementById('pieChartCanvas');
    if (ctx3) {
        var pieLabels = [];
        var pieValues = [];
        var pieColors2 = [];
        
        if (dataLabels.length > 0) {
            dataLabels.forEach((label, index) => {
                var total = 0;
                allDates.forEach(date => {
                    if (dateGroups[date]) {
                        var group = isCategory ? dateGroups[date].categories[label] : dateGroups[date].items[label];
                        if (group) {
                            total += dataType === 'revenue' ? group.revenue : (dataType === 'discount' ? group.discount : group.qty);
                        }
                    }
                });
                if (total > 0) {
                    pieLabels.push(label);
                    pieValues.push(total);
                    pieColors2.push(dataColors[index % dataColors.length]);
                }
            });
        } else {
            var totalVal = dataType === 'revenue' ? totalRevenue.reduce((a, b) => a + b, 0) : 
                          (dataType === 'discount' ? totalDiscount.reduce((a, b) => a + b, 0) : totalQty.reduce((a, b) => a + b, 0));
            pieLabels = [dataType === 'revenue' ? 'Total Revenue' : (dataType === 'discount' ? 'Total Discount' : 'Total Qty')];
            pieValues = [totalVal];
            pieColors2 = ['#042d5c'];
        }
        
        if (pieLabels.length > 0) {
            chartInstances.pie = new Chart(ctx3, {
                type: 'pie',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieValues,
                        backgroundColor: pieColors2,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            });
        }
    }
}

function filterDataByPeriod(jobs, period) {
    var now = new Date();
    var from = new Date();
    var to = new Date();
    
    switch(period) {
        case 'day':
            from = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            to = new Date(now.getFullYear(), now.getMonth(), now.getDate());
            break;
        case 'week':
            var day = now.getDay();
            var diff = (day === 0 ? 6 : day - 1);
            from = new Date(now);
            from.setDate(now.getDate() - diff);
            to = new Date(from);
            to.setDate(from.getDate() + 6);
            break;
        case 'month':
            from = new Date(now.getFullYear(), now.getMonth(), 1);
            to = new Date(now.getFullYear(), now.getMonth() + 1, 0);
            break;
        case 'year':
            from = new Date(now.getFullYear(), 0, 1);
            to = new Date(now.getFullYear(), 11, 31);
            break;
        default:
            return jobs;
    }
    
    var fromStr = from.toISOString().split('T')[0];
    var toStr = to.toISOString().split('T')[0];
    
    return jobs.filter(job => {
        return job.job_date >= fromStr && job.job_date <= toStr;
    });
}

function getDateRange(from, to) {
    var dates = [];
    var current = new Date(from);
    var end = new Date(to);
    
    while (current <= end) {
        dates.push(current.toISOString().split('T')[0]);
        current.setDate(current.getDate() + 1);
    }
    return dates;
}

function updateCharts() {
    if (currentReportData && currentReportType === 'financial') {
        initCharts(currentReportData);
    }
}

function showLoading() {
    var container = document.getElementById('reportContent');
    container.innerHTML = `
        <div class="text-center py-5 relative">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading report data...</p>
        </div>
    `;
}

function hideLoading() {}

function showError(message) {
    var container = document.getElementById('reportContent');
    container.innerHTML = `
        <div class="alert alert-danger py-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

function downloadReport(format) {
    var from = document.getElementById('reportFrom').value;
    var to = document.getElementById('reportTo').value;
    var reportType = document.getElementById('reportType').value;
    
    var category = 'both';
    if (reportType === 'category') {
        var catRadio = document.querySelector('input[name="categoryRadio"]:checked');
        if (catRadio && catRadio.value !== 'all') {
            category = catRadio.value;
        }
    }
    
    var itemName = 'all';
    if (reportType === 'item') {
        var itemRadio = document.querySelector('input[name="itemRadio"]:checked');
        if (itemRadio && itemRadio.value !== 'all') {
            itemName = itemRadio.value;
        }
    }
    
    var url = apiBase + 'lib_report.php?format=' + format + '&type=custom&from=' + from + '&to=' + to + '&report_type=' + reportType;
    
    if (reportType === 'category') {
        url += '&category=' + encodeURIComponent(category);
    }
    
    if (reportType === 'item') {
        url += '&item_name=' + encodeURIComponent(itemName);
    }
    
    window.open(url, '_blank');
}
</script>

</body>
</html>