<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
        }
        .header, .footer {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 16px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .details {
            margin-bottom: 20px;
            font-size: 12px;
        }
        .details span {
            display: block;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        table.no-border td {
            border: none;
        }
        
    </style>
</head>
<body>

    <div class="header">
        <h1>Manifest</h1>
        <p>Ref. No: <?php echo date("YmdHis");?></p>
        <p>Printed Time: <?php echo date("D, d M Y, H:i:s");?> +0100</p>
    </div>

    <div class="details">
        <span><strong>Seller Name:</strong> FORMULATWOIT</span>
        <span><strong>Seller ID:</strong> B86840451</span>
        <span><strong>Shipping Provider:</strong> <?php echo $shipping_method;?> Spain</span><br>
        <span><strong>Warehouse Name:</strong> MANUFACTURAS TORRERO</span>
        <span><strong>Warehouse Address:</strong> AVENIDA DE LOS PENASCALES 14 - MADRID (28250), MADRID, España</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>N°</th>
                <th>Tracking Number</th>
                <th>Order Number</th>
                <th>QTY</th>
                <th>Ship to Country</th>
                <th>Comment</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($profofDelivery as $k=>$pf)
            <tr>
                <td>{{$k+1}}</td>
                <td>{{is_array($pf['tracking']) ? $pf['tracking'][0] : $pf['tracking'];}}</td>
                <td>{{$pf['orderid']}}</td>
                <td>{{$pf['quantity']}}</td>
                <td>{{$pf['country']}}</td>
                <td></td>
            </tr>
            @endforeach
            <!-- Repeat rows as per your data -->
        </tbody>
    </table>
    <br>
    <br>
    <table class="no-border">
        <tr>
            <td>Total of Package in the List: <strong><?php echo count($profofDelivery) ?></strong></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Total of Package Received</td>
            <td style="width: 150px;"><hr/></td>
            <td>Total of Pallets Received</td>
            <td style="width: 150px;"><hr/></td>
        </tr>
    </table>
    <br>
    <br>
    <table class="no-border" style="border: 1px solid #ccc">
        <tr>
            <td>License Plate Number</td>
            <td>Driver Signature/Stamp</td>
            <td>Collection Date and Time</td>
        </tr>
        <tr>
            <td colspan="3"><hr/></td>
        </tr>
    </table>
</body>
</html>
