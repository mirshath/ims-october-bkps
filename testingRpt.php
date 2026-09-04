<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f8f9fa;
            position: relative;
        }

        .receipt-container {
            max-width: 800px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin: auto;
            position: relative;
            overflow: hidden;
        }

        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.5;
            /* Adjust opacity for visibility */
            z-index: -1;
            width: 100%;
            text-align: center;
        }

        .watermark img {
            width: 100%;
            max-width: 600px;
            /* Adjust the size of the watermark */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-info p {
            margin: 0;
            font-size: 14px;
        }

        .table th,
        .table td {
            border: 1px solid #000 !important;
            text-align: left;
        }

        .total-section {
            font-size: 18px;
            font-weight: bold;
            text-align: right;
        }

        .thank-you-message {
            text-align: center;
            font-size: 16px;
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="container receipt-container">
        <!-- Watermark -->
        <div class="watermark">
            <img src="img/images.png" alt="Watermark">
        </div>

        <div class="header">
            <div class="company-info">
                <h3>Business Management School</h3>
                <p>123 Main Street, Hamilton, OH 44116</p>
                <p>(321) 456-7890 | Email: info@company.com</p>
            </div>
            <div>
                <h4>Official E-Receipt</h4>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <p><strong>Date:</strong> 08/30/17</p>
                <p><strong>Receipt No.:</strong> A246</p>
                <p><strong>Customer No.:</strong> 114H</p>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Salesperson:</strong> John Smith</p>
                <p><strong>Payment Method:</strong> Credit Card</p>
            </div>
        </div>

        <table  class="table table-striped  table-hover mt-3">
            <thead class="table-light">
                <tr>
                    <th>Item No.</th>
                    <th>Description</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>A111</td>
                    <td>Women's Tall - M</td>
                    <td>$100.00</td>
                </tr>
                <tr>
                    <td>B222</td>
                    <td>Men's Tall - M</td>
                    <td>$100.00</td>
                </tr>
                <tr>
                    <td>C333</td>
                    <td>Children's - S</td>
                    <td>$50.00</td>
                </tr>
                <tr>
                    <td>D444</td>
                    <td>Men's - XL</td>
                    <td>$50.00</td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <p>Total: $356.40</p>
        </div>

        <div class="thank-you-message">
            <p>Thank you for your payment! We wish you all the best.</p>
        </div>
    </div>
</body>

</html>